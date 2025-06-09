<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Helpers\BncHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01', // Este viene en bolívares
            'nationality' => 'required|string|in:V,E,J',
            'id_number' => 'required|string|max:20',
            'bank' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'payment_date' => 'required|date',
        ]);

        // Concatenar nacionalidad con número de cédula
        $fullIdNumber = $validated['nationality'] . '-' . $validated['id_number'];

        // Obtener la tasa BCV actual
        $bcvData = BncHelper::getBcvRatesCached();
        //dd($bcvData);
        $bcvRate = $bcvData['Rate'] ?? null;

        if (!$bcvRate) {
            return redirect()->route('dashboard')->with('error', 'No se pudo obtener la tasa BCV. Intente nuevamente.');
        }

                // Convertir el monto de bolívares a dólares
        $amountInUSD = $validated['amount'] / $bcvRate;

        // PASO 1: Registrar SIEMPRE el pago original (independientemente de facturas)
        $originalPayment = Payment::create([
            'reference' => $validated['reference'],
            'user_id' => $user->id,
            'invoice_id' => null, // Sin factura asociada inicialmente
            'amount' => $amountInUSD, // En USD
            'id_number' => $fullIdNumber,
            'bank' => $validated['bank'],
            'phone' => $validated['phone'],
            'payment_date' => $validated['payment_date'],
        ]);

        $remainingPayment = $amountInUSD; // Monto disponible para aplicar
        $creditAvailable = $user->credit_balance ?? 0; // Ya está en USD
        $appliedInvoices = [];

        // PASO 2: Obtener facturas pendientes y aplicar pagos
        $invoices = $user->invoices()
            ->where('status', '!=', 'paid')
            ->orderBy('period')
            ->get();

        // Si hay facturas pendientes, aplicar el pago
        if ($invoices->count() > 0) {
            foreach ($invoices as $invoice) {
                $remaining = $invoice->amount_due - $invoice->amount_paid; // En USD

                if ($remaining <= 0) continue; // Saltar facturas ya pagadas

                // Aplicar el pago a esta factura
                if ($remainingPayment > 0) {
                    $paymentToApply = min($remaining, $remainingPayment);

                    // Asociar parte del pago original a esta factura
                    if ($paymentToApply > 0) {
                    // Crear un nuevo registro de pago asociado a la factura
                         Payment::create([
                             'reference' => $validated['reference'] . ' (Aplicado a Factura)',
                             'user_id' => $user->id,
                             'invoice_id' => $invoice->id,
                             'amount' => $paymentToApply, // En USD
                             'id_number' => $fullIdNumber,
                             'bank' => $validated['bank'],
                             'phone' => $validated['phone'],
                             'payment_date' => $validated['payment_date'],
                         ]);

                        $invoice->amount_paid += $paymentToApply;
                        $remainingPayment -= $paymentToApply;

                        // Actualizar estado de la factura
                        if ($invoice->amount_paid >= $invoice->amount_due) {
                            $invoice->amount_paid = $invoice->amount_due;
                            $invoice->status = 'paid';
                        } elseif ($invoice->amount_paid > 0) {
                            $invoice->status = 'partial';
                        }

                        $invoice->save();

                        $appliedInvoices[] = [
                            'id' => $invoice->id,
                            'period' => $invoice->period->format('Y-m'),
                            'applied_amount_usd' => $paymentToApply,
                            'applied_amount_bs' => $paymentToApply * $bcvRate,
                            'status' => $invoice->status,
                        ];
                    }
                }

                // Si ya no queda dinero para aplicar, salir
                if ($remainingPayment <= 0) break;
            }
        }

        // PASO 3: Actualizar el crédito del usuario con lo que sobró
        $finalCreditBalance = $creditAvailable + $remainingPayment;
        \App\Models\User::where('id', $user->id)->update(['credit_balance' => $finalCreditBalance]);

        // Preparar mensaje informativo
        $message = 'Pago registrado exitosamente. ';
        if (count($appliedInvoices) > 0) {
            $message .= 'Aplicado a ' . count($appliedInvoices) . ' factura(s). ';
        }
        if ($remainingPayment > 0) {
            $message .= 'Crédito disponible: $' . number_format($remainingPayment, 2);
        }

        return redirect()->route('dashboard')->with('success', $message);

        /* return response()->json([
            'success' => true,
            'message' => '✅ Pago registrado exitosamente.',
            'data' => [
                'original_payment_bs' => $validated['amount'],
                'original_payment_usd' => $amountInUSD,
                'bcv_rate_used' => $bcvRate,
                'current_credit_balance_usd' => $finalCreditBalance,
                'current_credit_balance_bs' => $finalCreditBalance * $bcvRate,
                'applied_invoices' => $appliedInvoices,
            ],
        ]); */
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}

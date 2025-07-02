<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Helpers\BncHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'invoice'])->orderBy('id', 'desc');

        // Filtro de búsqueda por referencia o código de abonado
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('code', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $payments = $query->paginate(15)->appends($request->query());

        // Formatear los datos para la vista
        $formattedPayments = $payments->getCollection()->map(function ($payment) {
            return [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'amount_bs' => $payment->amount * (\App\Helpers\BncHelper::getBcvRatesCached()['Rate'] ?? 1),
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('d/m/Y') : null,
                'bank' => $payment->bank,
                'phone' => $payment->phone,
                'id_number' => $payment->id_number,
                'user_name' => $payment->user ? $payment->user->name : 'N/A',
                'user_code' => $payment->user ? $payment->user->code : 'N/A',
                'invoice_period' => $payment->invoice && $payment->invoice->period ?
                    $payment->invoice->period->format('Y-m') : 'Sin factura',
                'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : null,
                'image_path' => $payment->image_path,
            ];
        });

        return \Inertia\Inertia::render('Payments/Index', [
            'data' => $formattedPayments,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
            ],
        ]);
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
        try {
            // Si viene user_id, usar ese usuario; si no, usar el autenticado (si existe)
            if ($request->has('user_id')) {
                $user = \App\Models\User::findOrFail($request->user_id);
            } elseif (Auth::check()) {
                $user = Auth::user();
            } else {
                Log::error('PAYMENT STORE: No hay usuario especificado ni autenticado');

                if ($request->is('quick-payment')) {
                    return back()->with('error', 'Debe especificar un usuario para el pago');
                }

                return redirect()->back()->with('error', 'Debe especificar un usuario para el pago');
            }

            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'reference' => 'nullable|string|max:255',
                'amount' => 'required|numeric|min:0.01', // Este viene en bolívares
                'nationality' => 'required|string|in:V,E,J',
                'id_number' => 'required|string|max:20',
                'bank' => 'required|string|max:100',
                'phone' => 'required|string|max:20',
                'payment_date' => 'required|date',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048', // 4MB max
            ]);

        // Manejar la subida de imagen
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('payment-receipts', 'public');
        }

        // Concatenar nacionalidad con número de cédula
        $fullIdNumber = $validated['nationality'] . '-' . $validated['id_number'];

        // Obtener la tasa BCV actual
        $bcvData = BncHelper::getBcvRatesCached();
        $bcvRate = $bcvData['Rate'] ?? null;

        if (!$bcvRate) {
            if ($request->is('quick-payment')) {
                return back()->with('error', 'No se pudo obtener la tasa BCV. Intente nuevamente.');
            }
            return redirect()->back()->with('error', 'No se pudo obtener la tasa BCV. Intente nuevamente.');
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
            'image_path' => $imagePath, // Guardar la ruta de la imagen
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
                             'image_path' => $imagePath,
                         ]);

                        $invoice->amount_paid += $paymentToApply;
                        $remainingPayment -= $paymentToApply;

                        // Actualizar estado de la factura con comparación robusta para decimales
                        $amountDiff = abs($invoice->amount_paid - $invoice->amount_due);

                        if ($amountDiff < 0.01 || $invoice->amount_paid >= $invoice->amount_due) {
                            // Si la diferencia es menor a 1 centavo o está pagado completamente
                            $invoice->amount_paid = $invoice->amount_due; // Asegurar que sea exacto
                            $invoice->status = 'paid';
                            Log::info('PAYMENT STORE: Factura marcada como PAID', [
                                'invoice_id' => $invoice->id,
                                'amount_due' => $invoice->amount_due,
                                'amount_paid' => $invoice->amount_paid,
                                'diff' => $amountDiff
                            ]);
                        } elseif ($invoice->amount_paid > 0) {
                            $invoice->status = 'partial';
                            Log::info('PAYMENT STORE: Factura marcada como PARTIAL', [
                                'invoice_id' => $invoice->id,
                                'amount_due' => $invoice->amount_due,
                                'amount_paid' => $invoice->amount_paid,
                                'diff' => $amountDiff
                            ]);
                        }

                        $invoice->save();

                        $appliedInvoices[] = [
                            'id' => $invoice->id,
                            'period' => $invoice->period ? $invoice->period->format('Y-m') : null,
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

        // Determinar la redirección apropiada
        if ($request->is('quick-payment')) {
            // Para pago rápido desde login, usar respuesta JSON compatible con Inertia
            return back()->with('success', $message);
        }

        return redirect()->route('dashboard')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('PAYMENT STORE: Error procesando pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->is('quick-payment')) {
                return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
            }

            return redirect()->back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
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

    /**
     * Valida una referencia de pago con el banco
     */
    public function validateReference(Request $request, string $reference)
    {
        try {
            // Validar que la fecha de movimiento y monto sean proporcionados
            $request->validate([
                'payment_date' => 'required|date_format:Y-m-d',
                'expected_amount' => 'required|numeric|min:0.01',
            ]);

            $result = BncHelper::validateOperationReference(
                $reference,
                $request->payment_date,
                $request->expected_amount
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'showReportLink' => true,
                    'message' => 'No se pudo validar la referencia con el banco. ¿Desea reportar su pago manualmente?'
                ]);
            }

            // Validar si el movimiento existe
            if (!$result['MovementExists']) {
                return response()->json([
                    'success' => false,
                    'showReportLink' => true,
                    'message' => 'No se encontró ningún pago con esta referencia. ¿Desea reportar su pago manualmente?'
                ]);
            }

            // Si el movimiento existe, validar que el monto sea correcto (con un margen de error de 0.01)
            $amountDiff = abs($result['Amount'] - $request->expected_amount);
            if ($amountDiff > 0.01) {
                return response()->json([
                    'success' => false,
                    'showReportLink' => true,
                    'message' => 'El monto del pago no coincide con el esperado. ¿Desea reportar su pago manualmente?'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Pago validado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('LOG:: Error validando referencia BNC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al validar la referencia'
            ], 500);
        }
    }

    /**
     * Obtiene la lista de bancos desde el BNC
     */
    public function getBanks()
    {
        try {
            $banks = BncHelper::getBanks();

            if (!$banks) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener la lista de bancos'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $banks
            ]);
        } catch (\Exception $e) {
            Log::error('LOG:: Error obteniendo bancos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener la lista de bancos'
            ], 500);
        }
    }
}

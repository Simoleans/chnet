<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Payment;

class GenerateMonthlyInvoices extends Command
{
    protected $signature   = 'generate:monthly-invoices';
    protected $description = 'Genera facturas aplicando deuda previa y credit_balance; el prepago original queda intacto.';

    public function handle(): void
    {
        $period = Carbon::now()->startOfMonth();     // ej. 2025-06-01
        $total  = 0;

        foreach (User::active()->with('plan')->get() as $user) {

            /* 1. Filtros básicos */
            if (!$user->plan)                                   continue;
            if ($user->invoices()->where('period', $period)->exists()) continue;

            /* 2. Deuda anterior + precio del plan */
            $oldDebt = $user->invoices()
                ->where('status', '!=', 'paid')
                ->where('period', '<', $period)
                ->sum(DB::raw('amount_due - amount_paid'));

            $amountDue = $user->plan->price + $oldDebt;

            /* 3. Aplicar credit_balance (única fuente que toca la factura) */
            $creditUsed           = min($user->credit_balance, $amountDue);
            $user->credit_balance = max(0, $user->credit_balance - $creditUsed);
            $user->save();

            /* 4. Crear factura */
            $invoice = $user->invoices()->create([
                'plan_id'     => $user->plan_id,
                'period'      => $period,
                'amount_due'  => $amountDue,
                'amount_paid' => $creditUsed,
                'status'      => 'pending',
            ]);



            /* 5. Registrar el pago automático por crédito */
            if ($creditUsed > 0) {
                Payment::create([
                    'user_id'      => $user->id,
                    'invoice_id'   => $invoice->id,
                    'reference'    => 'credit',
                    'id_number'    => $user->id_number,
                    'bank'         => 'credit',
                    'phone'        => $user->phone,
                    'payment_date' => now(),
                    'amount'       => $creditUsed,
                ]);
            }

            /* 6. ¡¡NO tocar prepagos!!  – Dejarlos intactos */
            // (Si quisieras usarlos en un futuro, los tomarías cuando credit_balance=0)

            /* 7. Actualizar estado de la factura */
            if ($invoice->amount_paid >= $invoice->amount_due) {
                $invoice->amount_paid = $invoice->amount_due;
                $invoice->status      = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = 'partial';
            }
            $invoice->save();

            Log::info("Factura {$invoice->id} | due {$invoice->amount_due} | paid {$invoice->amount_paid} | user {$user->id}");
            $total++;
        }

        $this->info("✅ Facturas generadas: {$total}");
    }
}

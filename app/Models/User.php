<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'zone_id',
        'code',
        'id_number',
        'plan_id',
        'status',
        'role',
        'credit_balance',
    ];

    protected $casts = [
        'status' => 'boolean',
        'role' => 'integer',
    ];

    //append due
    protected $appends = ['due'];


    public function getDueAttribute()
    {
        // Obtener todas las facturas no pagadas completamente
        $invoices = $this->invoices()
            ->where('status', '!=', 'paid')
            ->sum(DB::raw('amount_due - amount_paid'));

        return $invoices;
    }

    //scope active
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    //scope not admin
    public function scopeNotAdmin($query)
    {
        return $query->where('role', '!=', 1);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getCurrentInvoiceData()
    {
        $currentPeriod = Carbon::now()->startOfMonth();

        $invoice = $this->invoices()
            ->with(['plan', 'payments'])
            ->where('period', $currentPeriod)
            ->first();

        if (!$this->plan) {
            return [
                'invoice_exists' => false,
                'error' => 'El usuario no tiene plan asignado.'
            ];
        }

        if (!$invoice) {
            $previous_unpaid = $this->invoices()
                ->where('status', '!=', 'paid')
                ->where('period', '<', $currentPeriod)
                ->sum(DB::raw('amount_due - amount_paid'));

            $total_due = $this->plan->price + $previous_unpaid;

            return [
                'invoice_exists' => false,
                'plan_name' => $this->plan->name,
                'plan_price' => $this->plan->price,
                'debt' => $previous_unpaid,
                'total_due' => $total_due,
            ];
        }

        return [
            'invoice_exists' => true,
            'invoice_id' => $invoice->id,
            'period' => $invoice->period ? $invoice->period->format('Y-m') : null,
            'status' => $invoice->status,
            'amount_due' => $invoice->amount_due,
            'amount_paid' => $invoice->amount_paid,
            'pending_amount' => $invoice->amount_due - $invoice->amount_paid,
            'plan' => $this->plan ? $this->plan->only(['id', 'name', 'price', 'mbps', 'type']) : null,
            'payments' => $invoice->payments->map(function ($p) {
                return [
                    'amount' => $p->amount,
                    'bank' => $p->bank,
                    'phone' => $p->phone,
                    'payment_date' => $p->payment_date ? $p->payment_date->format('Y-m-d') : null,
                ];
            }),
            'is_paid' => $invoice->status === 'paid',
            'is_partial' => $invoice->status === 'partial',
            'is_pending' => $invoice->status === 'pending',
        ];
    }

    /**
     * Busca un usuario por código y retorna información de deuda
     */
    public static function searchByCode(string $code): ?array
    {
        $user = self::with(['plan', 'zone'])
            ->where('code', $code)
            ->where('status', true)
            ->first();

        if (!$user) {
            return null;
        }

        $invoiceData = $user->getCurrentInvoiceData();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'code' => $user->code,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'zone' => $user->zone?->name,
            'plan' => $user->plan ? [
                'name' => $user->plan->name,
                'price' => $user->plan->price,
                'mbps' => $user->plan->mbps,
                'type' => $user->plan->type,
            ] : null,
            'credit_balance' => $user->credit_balance ?? 0,
            'total_debt' => $user->due,
            'invoice_data' => $invoiceData,
        ];
    }
}

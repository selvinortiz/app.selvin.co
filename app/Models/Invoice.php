<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUser;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'client_id' => 'integer',
        'date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // When paid_at is set, automatically set sent_at if it's null
        static::updating(function ($invoice) {
            if ($invoice->isDirty('paid_at') && $invoice->paid_at !== null && $invoice->sent_at === null) {
                $invoice->sent_at = $invoice->date->setTime(13, 0, 0);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(Hour::class);
    }

    /**
     * Check if invoice is in draft status (not sent).
     */
    public function isDraft(): bool
    {
        return $this->sent_at === null;
    }

    /**
     * Check if invoice is sent but not paid.
     */
    public function isSent(): bool
    {
        return $this->sent_at !== null && $this->paid_at === null;
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Check if invoice is overdue (sent but not paid and past due date).
     */
    public function isOverdue(): bool
    {
        return $this->isSent() && $this->due_date < now();
    }

    /**
     * Get status label based on timestamps.
     */
    public function getStatusLabel(): string
    {
        if ($this->isPaid()) {
            return 'Paid';
        }
        if ($this->isSent()) {
            return 'Sent';
        }
        return 'Draft';
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColor(): string
    {
        if ($this->isPaid()) {
            return 'success';
        }
        if ($this->isSent()) {
            return 'warning';
        }
        return 'gray';
    }
}

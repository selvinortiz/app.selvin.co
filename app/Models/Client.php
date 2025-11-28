<?php

namespace App\Models;

use App\Models\Concerns\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUser;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'business_name',
        'short_name',
        'address',
        'business_phone',
        'business_email',
        'tax_id',
        'website',
        'default_rate',
        'contact_name',
        'contact_title',
        'contact_email',
        'contact_phone',
        'send_invoices_to_contact',
        'payment_terms_days',
        'invoice_notes',
        'internal_notes',
        'code',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'default_rate' => 'decimal:2',
        'send_invoices_to_contact' => 'boolean',
    ];

    /**
     * Get the display name (short name if available, otherwise business name).
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?? $this->business_name;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(Hour::class);
    }
}

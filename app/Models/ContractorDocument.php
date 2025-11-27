<?php

namespace App\Models;

use App\Models\Concerns\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractorDocument extends Model
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
        'contractor_id' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($document) {
            // Set file metadata if file_path is set and metadata is missing
            if ($document->file_path && Storage::disk('s3')->exists($document->file_path)) {
                if (empty($document->file_name)) {
                    $document->file_name = basename($document->file_path);
                }
                if (empty($document->file_size)) {
                    $document->file_size = Storage::disk('s3')->size($document->file_path);
                }
                if (empty($document->mime_type)) {
                    $document->mime_type = Storage::disk('s3')->mimeType($document->file_path) ?? 'application/pdf';
                }
            }
        });
    }

    /**
     * Get the contractor that owns the document.
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * Get the tenant that owns the document.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get human-readable document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'contractor_agreement' => 'Contractor Agreement',
            'w8ben' => 'Form W-8BEN',
            'w9' => 'Form W-9',
            '1099' => 'Form 1099',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->document_type)),
        };
    }
}

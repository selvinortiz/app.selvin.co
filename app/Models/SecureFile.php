<?php

namespace App\Models;

use App\Models\Concerns\HasUser;
use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SecureFile extends Model
{
    use HasFactory, HasUser, HasTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'client_id',
        'name',
        'filename',
        'file_path',
        'mime_type',
        'file_size',
        'access_token',
        'password',
        'download_limit',
        'download_count',
        'expires_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'client_id' => 'integer',
        'file_size' => 'integer',
        'download_limit' => 'integer',
        'download_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($secureFile) {
            if (empty($secureFile->access_token)) {
                $secureFile->access_token = Str::random(32);
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

    public function canBeDownloaded(): bool
    {
        // Check if expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check download limit
        if ($this->download_count >= $this->download_limit) {
            return false;
        }

        return true;
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function getDownloadUrl(): string
    {
        return route('secure-file.download', $this->access_token);
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

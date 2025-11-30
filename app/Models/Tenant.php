<?php

namespace App\Models;

use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    use HasFactory;

    public const BRAND_COLORS = [
        'Purple' => Color::Purple,
        'Blue' => Color::Blue,
        'Green' => Color::Green,
        'Red' => Color::Red,
        'Indigo' => Color::Indigo,
        'Pink' => Color::Pink,
        'Orange' => Color::Orange,
        'Amber' => Color::Amber,
        'Emerald' => Color::Emerald,
        'Teal' => Color::Teal,
        'Cyan' => Color::Cyan,
        'Sky' => Color::Sky,
        'Violet' => Color::Violet,
        'Fuchsia' => Color::Fuchsia,
        'Rose' => Color::Rose,
        'Slate' => Color::Slate,
        'Gray' => Color::Gray,
        'Zinc' => Color::Zinc,
        'Neutral' => Color::Neutral,
        'Stone' => Color::Stone,
    ];

    protected ?string $logoUrlCache = null;
    protected bool $logoUrlResolved = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'website',
        'phone',
        'email',
        'address',
        'tax_id',
        'contact_name',
        'contact_title',
        'contact_email',
        'contact_phone',
        'logo_path',
        'brand_color',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(Hour::class);
    }

    public function contractors(): HasMany
    {
        return $this->hasMany(Contractor::class);
    }

    public function contractorInvoices(): HasMany
    {
        return $this->hasMany(ContractorInvoice::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the logo URL (temporary S3 URL).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logoUrlResolved) {
            return $this->logoUrlCache;
        }

        $this->logoUrlResolved = true;

        if (!$this->logo_path) {
            return null;
        }

        try {
            $cacheKey = "tenant_logo_url_{$this->id}_" . md5($this->logo_path);

            $url = Cache::remember(
                $cacheKey,
                now()->addMinutes(55),
                fn () => Storage::disk('s3')->temporaryUrl(
                    $this->logo_path,
                    now()->addMinutes(60)
                ),
            );

            return $this->logoUrlCache = $url;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if tenant has a logo.
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    /**
     * Get Filament Color enum from brand_color string.
     */
    public function getBrandColorEnum(): array
    {
        if (!$this->brand_color) {
            return Color::Purple; // Default
        }

        $colorName = ucfirst(strtolower($this->brand_color));

        return self::BRAND_COLORS[$colorName] ?? Color::Purple;
    }

    /**
     * Get brand color hex value for CSS.
     */
    public function getBrandColorHex(): string
    {
        $colorEnum = $this->getBrandColorEnum();
        $rgb = $colorEnum[500] ?? '139, 92, 246'; // Purple 500 default

        // Color enum values are strings like '139, 92, 246'
        $rgbArray = array_map('trim', explode(',', $rgb));

        return sprintf('#%02x%02x%02x', (int)$rgbArray[0], (int)$rgbArray[1], (int)$rgbArray[2]);
    }

    /**
     * Options for brand color form inputs.
     */
    public static function brandColorOptions(): array
    {
        $names = array_keys(self::BRAND_COLORS);

        return array_combine($names, $names);
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Facades\Filament;

trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::creating(function (Model $model)
        {
            if (!isset($model->tenant_id) && Filament::getTenant())
            {
                $model->tenant_id = Filament::getTenant()->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;

class SetTenantFilamentColors
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if ($tenant?->brand_color) {
            FilamentColor::register([
                'primary' => $tenant->getBrandColorEnum(),
            ]);
        }

        return $next($request);
    }
}

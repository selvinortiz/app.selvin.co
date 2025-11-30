@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $logoUrl = $tenant?->logo_url;
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $tenant?->name ?? 'Tenant' }} logo" class="h-8">
@else
    <span class="font-semibold">{{ config('app.name') }}</span>
@endif

<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __invoke(string $token): View
    {
        $client = Client::where('portal_token', $token)->firstOrFail();

        $month = request('month')
            ? Carbon::createFromFormat('Y-m', request('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $activeTag = request('tag');

        $hoursQuery = $client->hours()
            ->where('is_billable', true)
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        $tags = (clone $hoursQuery)
            ->whereNotNull('tag')
            ->distinct()
            ->orderBy('tag')
            ->pluck('tag');

        if ($activeTag) {
            $hoursQuery->where('tag', $activeTag);
        }

        $hours = $hoursQuery->orderBy('date', 'desc')->get();

        $totalHours = $hours->sum('hours');
        $entriesCount = $hours->count();

        $prevMonth = $month->copy()->subMonth();
        $nextMonth = $month->copy()->addMonth();

        $tenant = $client->tenant;

        $routeParams = array_filter([
            'token' => $token,
            'month' => $prevMonth->format('Y-m'),
            'tag' => $activeTag,
        ]);

        $routeParamsNext = array_filter([
            'token' => $token,
            'month' => $nextMonth->format('Y-m'),
            'tag' => $activeTag,
        ]);

        return view('hours', [
            'client' => $client,
            'tenant' => $tenant,
            'hours' => $hours,
            'month' => $month,
            'totalHours' => $totalHours,
            'entriesCount' => $entriesCount,
            'prevMonthUrl' => route('hours', $routeParams),
            'nextMonthUrl' => route('hours', $routeParamsNext),
            'tags' => $tags,
            'activeTag' => $activeTag,
            'token' => $token,
        ]);
    }
}

<!DOCTYPE html>
<html class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $client->display_name }} — Hours Log</title>
    @vite(['resources/css/app.css'])

    {{-- Print Styles --}}
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white !important;
            }

            .no-print {
                display: none !important;
            }

            .print-header {
                display: flex !important;
            }

            .content-card {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .summary-card {
                background: white !important;
                border: 1px solid #e5e7eb !important;
            }

            .footer-print {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
            }

            table {
                page-break-inside: auto;
                border-collapse: separate;
                border-spacing: 0;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            @page {
                margin: 2rem;
            }
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    {{-- Top Bar --}}
    <div class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center no-print z-10">
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold">
                {{ $client->display_name }} — Billable Hours
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Print
            </button>
        </div>
    </div>

    {{-- Print Header (hidden on screen) --}}
    <div class="hidden print-header justify-between items-end mb-6 max-w-4xl mx-auto px-8 pt-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $client->display_name }}</h1>
            <p class="text-gray-500">Billable Hours</p>
        </div>
        <div class="text-right text-xl font-bold">
            {{ $month->format('F Y') }}
        </div>
    </div>

    {{-- Content --}}
    <div class="content-card max-w-4xl mx-auto bg-white mt-20 mb-8 shadow-sm border border-gray-200 rounded-lg">
        <div class="p-8">
            {{-- Month Navigation --}}
            <div class="flex items-center justify-between mb-8 no-print">
                <a href="{{ $prevMonthUrl }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    &larr; {{ \Carbon\Carbon::parse($month)->subMonth()->format('M Y') }}
                </a>
                <h2 class="text-2xl font-bold">{{ $month->format('F Y') }}</h2>
                <a href="{{ $nextMonthUrl }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    {{ \Carbon\Carbon::parse($month)->addMonth()->format('M Y') }} &rarr;
                </a>
            </div>

            {{-- Tag Filter --}}
            @if($tags->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 mb-8 no-print">
                    <span class="text-sm text-gray-500 mr-1">Filter:</span>
                    <a href="{{ route('hours', array_filter(['token' => $token, 'month' => $month->format('Y-m')])) }}"
                       @class([
                           'px-3 py-1 rounded-full text-sm font-medium border transition-colors',
                           'bg-indigo-600 text-white border-indigo-600' => !$activeTag,
                           'bg-white text-gray-700 border-gray-300 hover:border-indigo-400' => $activeTag,
                       ])>
                        All
                    </a>
                    @foreach($tags as $tag)
                        <a href="{{ route('hours', array_filter(['token' => $token, 'month' => $month->format('Y-m'), 'tag' => $tag])) }}"
                           @class([
                               'px-3 py-1 rounded-full text-sm font-medium border transition-colors',
                               'bg-indigo-600 text-white border-indigo-600' => $activeTag === $tag,
                               'bg-white text-gray-700 border-gray-300 hover:border-indigo-400' => $activeTag !== $tag,
                           ])>
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Summary --}}
            <div class="flex gap-8 mb-8">
                <div class="summary-card bg-gray-50 rounded-lg px-6 py-4">
                    <div class="text-sm text-gray-500">Total Hours</div>
                    <div class="text-2xl font-bold">{{ number_format($totalHours, 1) }}</div>
                </div>
                <div class="summary-card bg-gray-50 rounded-lg px-6 py-4">
                    <div class="text-sm text-gray-500">Entries</div>
                    <div class="text-2xl font-bold">{{ $entriesCount }}</div>
                </div>
            </div>

            {{-- Hours Table --}}
            @if($hours->isNotEmpty())
                <div class="hours-table-container">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="pb-2 text-left font-bold">Date</th>
                                <th class="pb-2 text-right font-bold">Hours</th>
                                <th class="pb-2 text-left font-bold pl-8">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $previousDate = null; @endphp
                            @foreach($hours as $hour)
                                @php
                                    $currentDate = $hour->date->format('m/d');
                                    $showDate = $previousDate !== $currentDate;
                                    $previousDate = $currentDate;
                                @endphp
                                <tr class="border-b border-gray-200">
                                    <td class="pt-4 pb-4">{{ $showDate ? $currentDate : '' }}</td>
                                    <td class="pt-4 pb-4 text-right pr-4">{{ number_format($hour->hours, 1) }}</td>
                                    <td class="pt-4 pb-4 pl-8 text-xs font-mono">{{ $hour->description }}</td>
                                </tr>
                            @endforeach
                            {{-- Totals Row --}}
                            <tr class="border-t-2 border-gray-400 font-bold">
                                <td class="pt-4 pb-4">Total</td>
                                <td class="pt-4 pb-4"></td>
                                <td class="pt-4 pb-4 pl-8 text-right">{{ number_format($totalHours, 1) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    No hours logged for {{ $month->format('F Y') }}.
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="max-w-4xl mx-auto text-center text-sm text-gray-400 mb-8 no-print">
        {{ $tenant->name }}
    </div>
</body>
</html>

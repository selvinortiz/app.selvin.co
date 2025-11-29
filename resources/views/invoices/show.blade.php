<!DOCTYPE html>
<html class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->number }}</title>
    @vite(['resources/css/app.css'])

    {{-- Print Styles --}}
    <style>
        @media print {
            body {
                margin: 0;
                padding: 2rem;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    @if($print)
    <script>
        // Automatically open print dialog when print=true
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
    @endif
</head>
<body class="h-full bg-gray-50">
    {{-- Print Controls --}}
    @unless($print)
        <div class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center no-print">
            <div class="flex items-center gap-4">
                <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700">
                    &larr; Back
                </a>
                <h1 class="text-lg font-semibold">
                    Invoice {{ $invoice->number }}
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('invoice.view', ['invoice' => $invoice, 'print' => true]) }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                   target="_blank">
                    Print / Download PDF
                </a>
            </div>
        </div>
    @endunless

    {{-- Invoice Content --}}
    <div class="max-w-4xl mx-auto bg-white {{ $print ? '' : 'mt-20 mb-8 shadow-sm border border-gray-200 rounded-lg' }}">
        <div class="p-8">
            {{-- Header --}}
            <div class="flex justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-purple-800">{{ config('company.name') }}</h1>
                    <h2 class="text-2xl mt-1">{{ config('company.contact.name') }}</h2>
                    <div class="mt-4 space-y-1">
                        <p>{{ config('company.address.street') }},</p>
                        <p>{{ config('company.address.city') }}, {{ config('company.address.state') }} {{ config('company.address.zip') }}</p>
                        <p>{{ config('company.contact.phone') }}</p>
                        <p>{{ config('company.contact.email') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-4xl font-bold">INVOICE</h1>
                    <div class="mt-4">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <div class="text-right font-bold">Invoice Number:</div>
                            <div class="text-left">{{ $invoice->number }}</div>
                            <div class="text-right font-bold">Invoice Date:</div>
                            <div class="text-left">{{ $invoice->date->format('m/d/Y') }}</div>
                            <div class="text-right font-bold">Reference:</div>
                            <div class="text-left">{{ $invoice->reference }}</div>
                            <div class="text-right font-bold">Due Date:</div>
                            <div class="text-left">{{ $invoice->due_date->format('m/d/Y') }}</div>
                            <div class="text-right font-bold">Status:</div>
                            <div class="text-left">
                                <span @class([
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    'bg-gray-100 text-gray-800' => $invoice->isDraft(),
                                    'bg-yellow-100 text-yellow-800' => $invoice->isSent(),
                                    'bg-green-100 text-green-800' => $invoice->isPaid(),
                                ])>
                                    {{ $invoice->getStatusLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="mt-8 border border-gray-200 rounded-lg p-4">
                        <div class="text-3xl font-bold text-center">
                            ${{ number_format($invoice->amount, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bill To --}}
            <div class="mt-8">
                <h3 class="font-bold text-3xl">BILL TO</h3>
                <div class="mt-4">
                    <h4 class="text-2xl font-bold">{{ $invoice->client->business_name }}</h4>
                    <div class="space-y-1">
                        <p class="text-2xl">{{ $invoice->client->contact_name }}</p>
                        <p>{!! nl2br(e($invoice->client->address)) !!}</p>
                        <p class="opacity-75">
                            @if($invoice->client->billing_phone)
                                {{ $invoice->client->billing_phone }} •
                            @endif
                            {{ $invoice->client->billing_email }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <div class="mt-8">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="pb-2 text-left font-bold">Description</th>
                            <th class="pb-2 text-right font-bold pl-8">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pt-4 whitespace-pre-line">{{ $invoice->description }}</td>
                            <td class="pt-4 text-right">${{ number_format($invoice->amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="mt-8">
                <div class="ml-auto w-48">
                    <div class="flex justify-between py-1">
                        <div class="text-right">Subtotal</div>
                        <div>${{ number_format($invoice->amount, 2) }}</div>
                    </div>
                    <div class="flex justify-between py-1 font-bold">
                        <div>Total</div>
                        <div>${{ number_format($invoice->amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

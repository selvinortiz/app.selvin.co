<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ContractorInvoice;
use App\Models\Hour;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Scramble extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scramble
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scramble financial data by dividing monetary values by 10 (for demo purposes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Only allow in non-production environments
        if (app()->environment('production')) {
            $this->error('This command can only be run in non-production environments.');
            return Command::FAILURE;
        }

        $this->info('This command will scramble financial data by dividing monetary values by 10.');
        $this->warn('This operation will modify:');
        $this->line('  - Client default rates');
        $this->line('  - Hour rates');
        $this->line('  - Invoice amounts');
        $this->line('  - Contractor invoice amounts');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Starting scramble...');
        $this->newLine();

        $stats = [
            'clients' => 0,
            'hours' => 0,
            'invoices' => 0,
            'contractor_invoices' => 0,
        ];

        // Scramble Client default rates
        $this->info('Processing clients...');
        $stats['clients'] = Client::whereNotNull('default_rate')
            ->where('default_rate', '>', 0)
            ->update([
                'default_rate' => DB::raw('default_rate / 10')
            ]);
        $this->line("  ✓ Updated {$stats['clients']} client default rates");

        // Scramble Hour rates
        $this->info('Processing hours...');
        $stats['hours'] = Hour::whereNotNull('rate')
            ->where('rate', '>', 0)
            ->update([
                'rate' => DB::raw('rate / 10')
            ]);
        $this->line("  ✓ Updated {$stats['hours']} hour rates");

        // Scramble Invoice amounts
        $this->info('Processing invoices...');
        $stats['invoices'] = Invoice::whereNotNull('amount')
            ->where('amount', '>', 0)
            ->update([
                'amount' => DB::raw('amount / 10')
            ]);
        $this->line("  ✓ Updated {$stats['invoices']} invoice amounts");

        // Scramble ContractorInvoice amounts
        $this->info('Processing contractor invoices...');
        $stats['contractor_invoices'] = ContractorInvoice::whereNotNull('amount')
            ->where('amount', '>', 0)
            ->update([
                'amount' => DB::raw('amount / 10')
            ]);
        $this->line("  ✓ Updated {$stats['contractor_invoices']} contractor invoice amounts");

        $this->newLine();
        $this->info('Scramble complete!');
        $this->table(
            ['Model', 'Records Updated'],
            [
                ['Clients', $stats['clients']],
                ['Hours', $stats['hours']],
                ['Invoices', $stats['invoices']],
                ['Contractor Invoices', $stats['contractor_invoices']],
            ]
        );

        return Command::SUCCESS;
    }
}

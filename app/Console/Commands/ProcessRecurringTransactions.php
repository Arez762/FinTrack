<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create transactions for every active recurring template that is due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $processed = 0;
        $created = 0;

        RecurringTransaction::query()
            ->due($today)
            ->orderBy('id')
            ->chunkById(100, function (Collection $templates) use ($today, &$processed, &$created): void {
                foreach ($templates as $template) {
                    $processed++;
                    $created += $this->runTemplate($template, $today);
                }
            });

        $this->info(sprintf(
            'Processed %d recurring transaction(s), created %d transaction(s).',
            $processed,
            $created,
        ));

        return self::SUCCESS;
    }

    /**
     * Create every missed transaction for a template and advance its schedule.
     */
    private function runTemplate(RecurringTransaction $template, Carbon $today): int
    {
        $created = 0;

        DB::transaction(function () use ($template, $today, &$created): void {
            while ($template->next_run_date->lessThanOrEqualTo($today)) {
                Transaction::create([
                    'user_id' => $template->user_id,
                    'account_id' => $template->account_id,
                    'category_id' => $template->category_id,
                    'type' => $template->type,
                    'amount' => $template->amount,
                    'description' => $template->description,
                    'transaction_date' => $template->next_run_date->toDateString(),
                ]);

                $template->advanceNextRunDate();
                $created++;
            }
        });

        return $created;
    }
}

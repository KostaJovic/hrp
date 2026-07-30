<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;

class MaterializeRecurringExpenses extends Command
{
    protected $signature = 'expenses:materialize';

    protected $description = 'Book due recurring expenses and advance their schedules';

    public function handle(): int
    {
        $templates = Expense::query()
            ->whereNotNull('recurrence_interval')
            ->whereNotNull('recurrence_unit')
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', today())
            ->get();

        $booked = 0;

        foreach ($templates as $template) {
            // Catch up every missed period, not just the latest one.
            while ($template->next_due_on->lte(today())) {
                Expense::query()->create([
                    'description' => $template->description,
                    'amount_cents' => $template->amount_cents,
                    'category_id' => $template->category_id,
                    'project_id' => $template->project_id,
                    'item_id' => $template->item_id,
                    'spent_on' => $template->next_due_on->toDateString(),
                ]);

                $template->next_due_on = $template->recurrence_unit->addTo(
                    $template->next_due_on->copy(),
                    $template->recurrence_interval,
                );
                $booked++;
            }

            $template->save();
        }

        $this->info("Booked {$booked} recurring expense(s).");

        return self::SUCCESS;
    }
}

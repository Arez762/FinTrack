<?php

namespace App\Models;

use Database\Factories\RecurringTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id',
    'account_id',
    'category_id',
    'type',
    'amount',
    'description',
    'frequency',
    'start_date',
    'next_run_date',
    'is_active',
])]
class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'next_run_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Limit the query to active templates that are due on or before the given date.
     */
    public function scopeDue(Builder $query, ?Carbon $date = null): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $date ?? Carbon::today());
    }

    /**
     * Move the schedule forward by exactly one frequency interval.
     */
    public function advanceNextRunDate(): void
    {
        $this->next_run_date = $this->followingRunDate($this->next_run_date);
        $this->save();
    }

    /**
     * The date that follows the given run date according to the frequency.
     */
    public function followingRunDate(Carbon $from): Carbon
    {
        return match ($this->frequency) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonthNoOverflow(),
            'yearly' => $from->copy()->addYearNoOverflow(),
        };
    }
}

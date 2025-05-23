<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'total_hours',
        'location_id',
        'is_recurring',
        'recurrence_pattern',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_hours' => 'float',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the user that owns the schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location associated with the schedule.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Calculate the total hours for the schedule.
     */
    public function calculateTotalHours(): float
    {
        $startTime = \Carbon\Carbon::parse($this->start_time);
        $endTime = \Carbon\Carbon::parse($this->end_time);

        return round($endTime->diffInMinutes($startTime) / 60, 2);
    }

    /**
     * Save the model to the database.
     */
    public function save(array $options = []): bool
    {
        // Calculate total hours
        $this->total_hours = $this->calculateTotalHours();

        return parent::save($options);
    }
}

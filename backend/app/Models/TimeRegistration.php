<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeRegistration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'location_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'notes',
        'status',
        // Schedule-related fields
        'scheduled_start',
        'scheduled_end',
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
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'total_hours' => 'float',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the user that owns the time registration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the location associated with the time registration.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Calculate the total hours worked.
     */
    public function calculateTotalHours(): float|null
    {
        if (!$this->clock_out) {
            return null;
        }

        $clockIn = \Carbon\Carbon::parse($this->clock_in);
        $clockOut = \Carbon\Carbon::parse($this->clock_out);

        // Calculate the difference in minutes and ensure it's positive
        // The first parameter should be the earlier time (clock_in)
        // The second parameter (absolute) should be true to always get positive values
        return round($clockOut->diffInMinutes($clockIn, true) / 60, 2);
    }

    /**
     * Save the model to the database.
     */
    public function save(array $options = []): bool
    {
        // Calculate total hours if clock-out is set
        if ($this->clock_out) {
            $this->total_hours = $this->calculateTotalHours();
        }

        return parent::save($options);
    }
}

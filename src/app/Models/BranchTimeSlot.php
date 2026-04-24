<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTimeSlot extends Model
{
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function getDayOptions(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    public static function getDayName(int $day): string
    {
        return self::getDayOptions()[$day] ?? 'Unknown';
    }
}

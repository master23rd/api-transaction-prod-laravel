<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\CafeTimeSlot;
use Illuminate\Database\Seeder;

class CafeTimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $daysOfWeek = [0, 1, 2, 3, 4, 5, 6];

        $cafes = Cafe::all();

        foreach ($cafes as $cafe) {
            foreach ($daysOfWeek as $day) {
                // Weekend has different hours (0 = Sunday, 6 = Saturday)
                $isWeekend = in_array($day, [0, 6]);

                CafeTimeSlot::firstOrCreate(
                    [
                        'cafe_id' => $cafe->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'start_time' => $isWeekend ? '09:00' : '07:00',
                        'end_time' => $isWeekend ? '23:00' : '22:00',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

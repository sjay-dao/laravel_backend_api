<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\Schedule;

use Illuminate\Support\Facades\DB;
use App\Domains\Employee\Models\ScheduleDay;
use App\Domains\Employee\Services\ScheduleCalculator;

class ScheduleRepository
{
    public function __construct(
        protected ScheduleCalculator $calculator
    ) {
    }

    public function index(array $filters = [])
    {
        $query = Schedule::with('days');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query
            ->latest()
            ->paginate(20);
    }

    public function find(int $id): Schedule
    {
        return Schedule::with('days')
            ->findOrFail($id);
    }


    public function store(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {

            $days = $data['days'];

            unset($data['days']);

            $schedule = Schedule::create($data);
            $totalMinutes = 0;
            foreach ($days as $day) {

                $day = $this->calculator->calculate($day);
                $totalMinutes += $day['required_minutes'];
                $day['schedule_id'] = $schedule->id;

                ScheduleDay::create($day);

            }
            $schedule->update([
                'weekly_required_minutes' => $totalMinutes,
            ]);
            
            return $schedule
                ->load('days');

        });
    }

    public function update(int $id, array $data): Schedule
    {
        return DB::transaction(function () use ($id, $data) {

            $schedule = $this->find($id);

            $days = $data['days'];

            unset($data['days']);

            $schedule->update($data);

            $schedule->days()->delete();

            $totalMinutes = 0;

            foreach ($days as $day) {

                $day = $this->calculator->calculate($day);

                $totalMinutes += $day['required_minutes'];

                $day['schedule_id'] = $schedule->id;

                ScheduleDay::create($day);

            }

            $schedule->update([
                'weekly_required_minutes' => $totalMinutes,
            ]);

            return $schedule->fresh()->load('days');

        });
    }

    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }
}
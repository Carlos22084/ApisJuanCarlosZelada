<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionTask extends Model
{
    private const WORKDAY_START_HOUR = 8;
    private const WORKDAY_END_HOUR = 18;

    protected $guarded = [];
    protected function casts(): array { return ['started_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function order() { return $this->belongsTo(Order::class); }
    public function worker() { return $this->belongsTo(User::class, 'assigned_to'); }

    public function estimatedMinutesFor(int $furnitureQuantity): int
    {
        $workdayMinutes = (self::WORKDAY_END_HOUR - self::WORKDAY_START_HOUR) * 60;

        return max(1, $furnitureQuantity) * $workdayMinutes;
    }

    public function estimatedDurationFor(int $furnitureQuantity): string
    {
        $days = max(1, $furnitureQuantity);

        return $days === 1 ? '1 día laboral' : $days.' días laborales';
    }

    public function elapsedDuration(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $finishedAt = $this->completed_at ?? now();
        $minutes = $this->workingMinutesBetween($this->started_at, $finishedAt);

        return self::formatMinutes($minutes);
    }

    private function workingMinutesBetween($startedAt, $finishedAt): int
    {
        if ($finishedAt->lessThanOrEqualTo($startedAt)) {
            return 0;
        }

        $minutes = 0;
        $day = $startedAt->copy()->startOfDay();
        $lastDay = $finishedAt->copy()->startOfDay();

        while ($day->lessThanOrEqualTo($lastDay)) {
            $workStartsAt = $day->copy()->setTime(self::WORKDAY_START_HOUR, 0);
            $workEndsAt = $day->copy()->setTime(self::WORKDAY_END_HOUR, 0);
            $periodStartsAt = $startedAt->greaterThan($workStartsAt) ? $startedAt : $workStartsAt;
            $periodEndsAt = $finishedAt->lessThan($workEndsAt) ? $finishedAt : $workEndsAt;

            if ($periodEndsAt->greaterThan($periodStartsAt)) {
                $minutes += (int) round($periodStartsAt->diffInSeconds($periodEndsAt) / 60);
            }

            $day->addDay();
        }

        return $minutes;
    }

    private static function formatMinutes(int $minutes): string
    {
        if ($minutes < 1) {
            return 'Menos de 1 min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' h';
        }
        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes.' min';
        }

        return implode(' ', $parts);
    }
}

<?php

namespace App\Services;

use App\Models\ApplicationCodeSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ApplicationCodeService
{
    private const PAD_LENGTH = 5;

    public function generateForDate(CarbonImmutable $date): string
    {
        return DB::transaction(function () use ($date): string {
            $sequence = ApplicationCodeSequence::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['sequence_date' => $date->toDateString()],
                    ['last_sequence' => 0],
                );

            $next = $sequence->last_sequence + 1;

            $sequence->update(['last_sequence' => $next]);

            return sprintf('HS-%s-%0'.self::PAD_LENGTH.'d', $date->format('Ymd'), $next);
        });
    }
}

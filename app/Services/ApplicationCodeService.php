<?php

namespace App\Services;

use App\Models\ApplicationCodeSequence;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ApplicationCodeService
{
    private const PAD_LENGTH = 5;

    public function generateForDate(CarbonImmutable $date): string
    {
        $dateString = $date->toDateString();

        try {
            return $this->generateCode($date, $dateString);
        } catch (UniqueConstraintViolationException) {
            // Hai request nộp đồng thời cho cùng ngày: request thua chờ transaction
            // của request kia commit rồi mới nhận unique violation, nên row chắc chắn
            // đã tồn tại; thử lại transaction mới sẽ đọc đúng last_sequence.
            return $this->generateCode($date, $dateString);
        }
    }

    private function generateCode(CarbonImmutable $date, string $dateString): string
    {
        return DB::transaction(function () use ($date, $dateString): string {
            $sequence = ApplicationCodeSequence::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['sequence_date' => $dateString],
                    ['last_sequence' => 0],
                );

            $next = $sequence->last_sequence + 1;

            $sequence->update(['last_sequence' => $next]);

            return sprintf('HS-%s-%0'.self::PAD_LENGTH.'d', $date->format('Ymd'), $next);
        });
    }
}

<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    public const COMPLETED_FILTER = 'completed';

    case Received = 'received';
    case Processing = 'processing';
    case SupplementRequired = 'supplement_required';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Mới tiếp nhận',
            self::Processing => 'Đang xử lý',
            self::SupplementRequired => 'Chờ bổ sung',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Đã từ chối',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::SupplementRequired => 'warning',
            self::Processing => 'info',
            self::Received => 'neutral',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }

    /** @return list<string> */
    public static function completedValues(): array
    {
        return [self::Approved->value, self::Rejected->value];
    }

    /** @return list<string> */
    public static function filterValues(): array
    {
        return [...array_column(self::cases(), 'value'), self::COMPLETED_FILTER];
    }

    /** @return list<string> */
    public static function sortValues(): array
    {
        return array_keys(self::sortOptions());
    }

    /** @return array<string, string> */
    public static function sortOptions(): array
    {
        return [
            'newest' => 'Nộp mới nhất',
            'oldest' => 'Nộp cũ nhất',
            'code_asc' => 'Mã hồ sơ A-Z',
            'code_desc' => 'Mã hồ sơ Z-A',
            'status_asc' => 'Trạng thái A-Z',
            'status_desc' => 'Trạng thái Z-A',
        ];
    }

    public static function defaultSort(): string
    {
        return 'newest';
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

final class StaleDepartmentVersion extends RuntimeException
{
    public function __construct(
        public readonly int $departmentId,
        public readonly int $expectedVersion,
        public readonly int $actualVersion,
    ) {
        parent::__construct(
            'Dữ liệu phòng ban vừa được thay đổi bởi người khác. Vui lòng tải lại trang và thử lại.',
        );
    }
}

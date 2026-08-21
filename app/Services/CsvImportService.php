<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CsvImportService
{
    /**
     * Import Citizen accounts from CSV file.
     *
     * @param  string  $filePath  Absolute path to uploaded CSV file.
     * @return array<string, mixed>
     */
    public function importCitizens(string $filePath): array
    {
        return $this->processCsv(
            filePath: $filePath,
            type: 'citizen',
            requiredHeaders: ['name', 'email', 'citizen_id'],
            rulesCallback: fn () => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'citizen_id' => ['required', 'digits:12', 'unique:users,citizen_id'],
                'phone' => ['nullable', 'regex:/^[0-9]{10,11}$/'],
                'address' => ['nullable', 'string', 'max:500'],
                'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            ],
            saveCallback: function (array $validRows): int {
                $inserted = 0;
                foreach ($validRows as $row) {
                    User::query()->create([
                        'name' => trim($row['name']),
                        'email' => strtolower(trim($row['email'])),
                        'citizen_id' => trim($row['citizen_id']),
                        'phone' => isset($row['phone']) && trim($row['phone']) !== '' ? trim($row['phone']) : null,
                        'address' => isset($row['address']) && trim($row['address']) !== '' ? trim($row['address']) : null,
                        'date_of_birth' => isset($row['date_of_birth']) && trim($row['date_of_birth']) !== '' ? trim($row['date_of_birth']) : null,
                        'role' => UserRole::Citizen,
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]);
                    $inserted++;
                }

                return $inserted;
            }
        );
    }

    /**
     * Import Staff accounts from CSV file.
     *
     * @param  string  $filePath  Absolute path to uploaded CSV file.
     * @return array<string, mixed>
     */
    public function importStaff(string $filePath): array
    {
        return $this->processCsv(
            filePath: $filePath,
            type: 'staff',
            requiredHeaders: ['name', 'email', 'department_id', 'role'],
            rulesCallback: fn () => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'role' => ['required', Rule::in(['staff', 'manager', UserRole::Staff->value, UserRole::Manager->value])],
                'phone' => ['nullable', 'regex:/^[0-9]{10,11}$/'],
            ],
            saveCallback: function (array $validRows): int {
                $inserted = 0;
                foreach ($validRows as $row) {
                    $roleInput = strtolower(trim($row['role']));
                    $roleEnum = match ($roleInput) {
                        'manager' => UserRole::Manager,
                        default => UserRole::Staff,
                    };

                    $user = User::query()->create([
                        'name' => trim($row['name']),
                        'email' => strtolower(trim($row['email'])),
                        'phone' => isset($row['phone']) && trim($row['phone']) !== '' ? trim($row['phone']) : null,
                        'role' => $roleEnum,
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]);

                    $departmentId = (int) trim($row['department_id']);
                    $user->departments()->syncWithoutDetaching([$departmentId]);

                    $inserted++;
                }

                return $inserted;
            }
        );
    }

    /**
     * Process CSV parsing, validation, database insertion, and activity logging.
     *
     * @param  array<int, string>  $requiredHeaders
     * @return array<string, mixed>
     */
    private function processCsv(
        string $filePath,
        string $type,
        array $requiredHeaders,
        callable $rulesCallback,
        callable $saveCallback
    ): array {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'success' => false,
                'message' => 'Không thể đọc tệp CSV đã tải lên.',
                'data' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'errors' => [
                        [
                            'line_number' => 0,
                            'field' => 'csv_file',
                            'message' => 'Tệp dữ liệu không thể truy cập hoặc không hợp lệ.',
                        ],
                    ],
                ],
            ];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Không thể mở tệp CSV.',
                'data' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'errors' => [],
                ],
            ];
        }

        // Parse header and strip UTF-8 BOM if present
        $rawHeaders = fgetcsv($handle);
        if (! is_array($rawHeaders) || empty(filter_var_array($rawHeaders))) {
            fclose($handle);

            return [
                'success' => false,
                'message' => 'Tệp CSV rỗng hoặc cấu trúc tiêu đề không hợp lệ.',
                'data' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'errors' => [
                        [
                            'line_number' => 1,
                            'field' => 'headers',
                            'message' => 'Cấu trúc tiêu đề tệp CSV rỗng hoặc không đúng định dạng.',
                        ],
                    ],
                ],
            ];
        }

        $headers = array_map(function (string $header, int $index): string {
            if ($index === 0) {
                // Strip UTF-8 BOM (\xEF\xBB\xBF)
                $header = (string) preg_replace('/^\xEF\xBB\xBF/', '', $header);
            }

            return strtolower(trim($header, " \t\n\r\0\x0B\"'"));
        }, $rawHeaders, array_keys($rawHeaders));

        // Validate required headers
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (! empty($missingHeaders)) {
            fclose($handle);

            return [
                'success' => false,
                'message' => 'Cấu trúc cột trong tệp CSV không đúng mẫu quy định.',
                'data' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'errors' => [
                        [
                            'line_number' => 1,
                            'field' => 'headers',
                            'message' => 'Thiếu các cột bắt buộc: '.implode(', ', $missingHeaders),
                        ],
                    ],
                ],
            ];
        }

        $lineNumber = 1;
        $totalRows = 0;
        $validRows = [];
        $errors = [];
        $seenEmails = [];
        $seenCitizenIds = [];

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // Skip completely empty rows
            if (empty(array_filter($row, fn ($val) => $val !== null && trim((string) $val) !== ''))) {
                continue;
            }

            $totalRows++;
            $rowData = [];
            foreach ($headers as $index => $key) {
                $rowData[$key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $email = isset($rowData['email']) ? strtolower(trim($rowData['email'])) : '';
            $citizenId = isset($rowData['citizen_id']) ? trim($rowData['citizen_id']) : '';

            $rowError = null;

            // In-file duplicate checking
            if ($email !== '' && in_array($email, $seenEmails, true)) {
                $rowError = [
                    'line_number' => $lineNumber,
                    'field' => 'email',
                    'message' => "Email '{$email}' bị trùng lặp ngay trong tệp CSV.",
                    'raw_data' => $rowData,
                ];
            } elseif ($citizenId !== '' && in_array($citizenId, $seenCitizenIds, true)) {
                $rowError = [
                    'line_number' => $lineNumber,
                    'field' => 'citizen_id',
                    'message' => "Số CCCD '{$citizenId}' bị trùng lặp ngay trong tệp CSV.",
                    'raw_data' => $rowData,
                ];
            } else {
                // Validate row using Laravel Validator
                $rules = call_user_func($rulesCallback);
                $validator = Validator::make($rowData, $rules);

                if ($validator->fails()) {
                    $failedField = array_key_first($validator->errors()->toArray());
                    $errorMessage = $validator->errors()->first($failedField);

                    $rowError = [
                        'line_number' => $lineNumber,
                        'field' => $failedField,
                        'message' => $errorMessage,
                        'raw_data' => $rowData,
                    ];
                }
            }

            if ($rowError !== null) {
                $errors[] = $rowError;
            } else {
                if ($email !== '') {
                    $seenEmails[] = $email;
                }
                if ($citizenId !== '') {
                    $seenCitizenIds[] = $citizenId;
                }
                $validRows[] = $rowData;
            }
        }

        fclose($handle);

        // Perform database inserts in a transaction
        $successCount = 0;
        if (! empty($validRows)) {
            $successCount = DB::transaction(fn () => call_user_func($saveCallback, $validRows));
        }

        $failureCount = count($errors);

        // Log activity
        ActivityLog::query()->create([
            'actor_id' => Auth::id(),
            'action' => 'user.import.'.$type,
            'subject_type' => User::class,
            'description' => "Đã import {$successCount} tài khoản {$type} từ CSV với {$failureCount} lỗi.",
            'metadata' => [
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'message' => "Đã nhập thành công {$successCount}/{$totalRows} tài khoản.",
            'data' => [
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'errors' => $errors,
            ],
        ];
    }
}

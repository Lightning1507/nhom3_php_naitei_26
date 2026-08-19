<?php

namespace App\Support;

use Illuminate\Support\Str;

class ServiceSchema
{
    /**
     * Normalize a service's document_requirements array into the canonical shape:
     * [{ code, label, required, type }].
     *
     * Accepts legacy shapes: [{ name, is_required }] and [{ code, label, required }].
     * A provided non-empty code is kept; otherwise one is derived from the label.
     * Codes are guaranteed to be unique within the service.
     *
     * @return array<int, array{code: string, label: string, required: bool, type: string}>
     */
    public static function normalizeDocumentRequirements(mixed $requirements): array
    {
        $requirements = is_array($requirements) ? $requirements : [];

        $normalized = [];
        $usedCodes = [];

        foreach ($requirements as $item) {
            if (is_string($item)) {
                $item = ['name' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? $item['name'] ?? ''));
            if ($label === '') {
                continue;
            }

            $required = (bool) ($item['required'] ?? $item['is_required'] ?? false);
            $type = $item['type'] ?? 'mixed';
            if (! in_array($type, ['pdf', 'image', 'mixed'], true)) {
                $type = 'mixed';
            }

            $baseCode = Str::slug($label);
            $providedCode = $item['code'] ?? null;
            $code = is_string($providedCode) && trim($providedCode) !== ''
                ? trim($providedCode)
                : $baseCode;
            if ($code === '') {
                $code = 'giay-to';
            }

            $candidate = $code;
            $counter = 2;
            while (isset($usedCodes[$candidate])) {
                $candidate = $code.'-'.$counter;
                $counter++;
            }

            $usedCodes[$candidate] = true;

            $normalized[] = [
                'code' => $candidate,
                'label' => $label,
                'required' => $required,
                'type' => $type,
            ];
        }

        return $normalized;
    }

    /**
     * Normalize a service's form_schema array into the canonical shape:
     * [{ name, label, type, required }].
     *
     * Accepts both [{ name, type, is_required }] and [{ name, label, type, required }] shapes.
     * Fields typed "file" are dropped; file uploads are handled through document_requirements.
     *
     * @return array<int, array{name: string, label: string, type: string, required: bool}>
     */
    public static function normalizeFormSchema(mixed $schema): array
    {
        $schema = is_array($schema) ? $schema : [];

        $normalized = [];

        foreach ($schema as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $type = (string) ($item['type'] ?? 'text');
            if ($type === 'file') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'label' => trim((string) ($item['label'] ?? $name)),
                'type' => $type,
                'required' => (bool) ($item['required'] ?? $item['is_required'] ?? false),
            ];
        }

        return $normalized;
    }
}

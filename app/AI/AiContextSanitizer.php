<?php

namespace App\AI;

class AiContextSanitizer
{
    private const FORBIDDEN = ['first_name', 'last_name', 'full_name', 'phone', 'email', 'address', 'passport_number', 'license_number', 'national_code', 'notes'];

    public function sanitize(array $context): array
    {
        foreach ($context as $key => $value) {
            if (in_array((string) $key, self::FORBIDDEN, true)) {
                unset($context[$key]);
                continue;
            }
            if (is_array($value)) {
                $context[$key] = $this->sanitize($value);
            }
        }
        return $context;
    }
}

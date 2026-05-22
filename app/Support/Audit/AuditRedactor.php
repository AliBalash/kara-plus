<?php

namespace App\Support\Audit;

class AuditRedactor
{
    private array $blockedFields;
    private string $mask;

    public function __construct()
    {
        $this->blockedFields = array_map('strtolower', config('audit.redaction.fields', []));
        $this->mask = (string) config('audit.redaction.mask', '[REDACTED]');
    }

    public function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $item) {
                if ($this->isBlockedKey($key)) {
                    $redacted[$key] = $this->mask;
                    continue;
                }

                $redacted[$key] = $this->redact($item);
            }

            return $redacted;
        }

        if ($value instanceof \JsonSerializable) {
            return $this->redact($value->jsonSerialize());
        }

        return $value;
    }

    private function isBlockedKey(mixed $key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        return in_array(strtolower($key), $this->blockedFields, true);
    }
}

<?php

namespace App\Support\Audit;

class AuditPayloadNormalizer
{
    public function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->normalize($item);
            }

            return $result;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                return $this->normalize($value->jsonSerialize());
            }

            if (method_exists($value, 'toArray')) {
                return $this->normalize($value->toArray());
            }

            return (string) $value::class;
        }

        if (is_string($value) && strlen($value) > 4000) {
            return substr($value, 0, 4000) . '...[truncated]';
        }

        return $value;
    }
}

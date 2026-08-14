<?php

namespace App\Models\Concerns;

trait EncryptsRouteKey
{
    public function getRouteKey(): mixed
    {
        return enc($this->getKey());
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where($field ?? $this->getRouteKeyName(), dec((string) $value));
    }
}

<?php

namespace App\Domain\OperationalStatus;

use App\Events\OperationalStatusTransitioned;
use App\Exceptions\InvalidOperationalStatusTransition;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

class OperationalStatusTransitionService
{
    public function __construct(private readonly OperationalStatusTransitionMap $map) {}

    /** @param array<string, mixed> $legacyWrites */
    public function transition(
        Model $model,
        string $attribute,
        BackedEnum $to,
        array $legacyWrites = [],
        ?string $reason = null,
        string|int|null $actorId = null,
        bool $force = false,
    ): Model {
        $current = $model->getAttribute($attribute);
        $from = $current instanceof BackedEnum ? $current : ($current === null ? null : $to::tryFrom((string) $current));

        if (! $force && $from !== null && $from->value !== $to->value) {
            $allowed = $this->map->allowedTargets($from);

            if (! in_array($to->value, $allowed, true)) {
                throw InvalidOperationalStatusTransition::between($attribute, $from->value, $to->value);
            }
        }

        $model->setAttribute($attribute, $to);
        $model->fill($legacyWrites);
        $model->save();

        OperationalStatusTransitioned::dispatch(
            $model::class,
            $model->getKey(),
            $attribute,
            $from?->value,
            $to->value,
            $reason,
            $actorId,
            $force,
        );

        return $model;
    }
}

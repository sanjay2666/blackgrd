<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'old_values' => 'array', 'new_values' => 'array', 'changed_fields' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Audit history is append-only.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('Audit history cannot be deleted.');
    }
}

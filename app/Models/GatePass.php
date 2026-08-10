<?php

namespace App\Models;

use App\Enums\GatePassStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'gate_passes';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'gate_pass_status' => GatePassStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (GatePass $gatePass): void {
            $gatePass->gate_pass_status ??= GatePassStatus::Issued;
        });
    }

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setModifiedAttribute($value): void
    {
        $this->attributes['modified_at'] = $value;
    }

    public function WorkInspection()
    {
        return $this->hasOne(WorkInspection::class, 'id', 'inspection_id')->where('status', '!=', 'Deleted')->where('is_deleted', '=', '0');
    }
}

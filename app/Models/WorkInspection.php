<?php

namespace App\Models;

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkInspection extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'work_inspections';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'inspection_status' => InspectionStatus::class,
        'inspection_result' => InspectionResult::class,
    ];

    public function getCreatedAttribute()
    {
        return $this->attributes['created'] ?? $this->attributes['created_at'] ?? null;
    }

    public function setInsItemIdAttribute($value): void
    {
        $this->attributes['item_id'] = $value;
    }

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setInspWorkStatusAttribute($value): void
    {
        $this->attributes['insp_work_status'] = $value;
        if (! array_key_exists('inspection_result', $this->attributes)) {
            $this->attributes['inspection_result'] = app(LegacyOperationalStatusMapper::class)
                ->inspectionResult($value)?->value;
        }
    }

    public function setInspStatusAttribute($value): void
    {
        $this->attributes['insp_status'] = $value;
        if (! array_key_exists('inspection_status', $this->attributes)) {
            $this->attributes['inspection_status'] = $value === 'Complete'
                ? InspectionStatus::Completed->value
                : InspectionStatus::Pending->value;
        }
    }

    public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
    }
}

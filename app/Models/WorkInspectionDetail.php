<?php

namespace App\Models;

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\InspectionResult;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkInspectionDetail extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'work_inspection_details';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'inspection_result' => InspectionResult::class,
    ];

    public function setInsItemIdAttribute($value): void
    {
        $this->attributes['item_id'] = $value;
    }

    public function setInsWorkOrderIdAttribute($value): void
    {
        $this->attributes['work_order_id'] = $value;
    }

    public function setOutputQuanSizeAttribute($value): void
    {
        $this->attributes['output_quantity'] = $value;
    }

    public function setShrinkageQuanSizeAttribute($value): void
    {
        $this->attributes['shrinkage_quantity'] = $value;
    }

    public function setInspecCommentAttribute($value): void
    {
        $this->attributes['inspection_comment'] = $value;
    }

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setWorkStatusAttribute($value): void
    {
        $this->attributes['work_status'] = $value;
        if (! array_key_exists('inspection_result', $this->attributes)) {
            $this->attributes['inspection_result'] = app(LegacyOperationalStatusMapper::class)
                ->inspectionResult($value)?->value;
        }
    }

    public function FabricFaultReason()
    {
        return $this->hasOne(FabricFaultReason::class, 'id', 'fabric_fault_reason_id');
    }
}

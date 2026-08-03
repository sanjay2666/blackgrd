<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseStockReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Vendor',
            'Warehouse',
            'Compartment',
            'Item',
            'Type',
            'Invoice No.',
            'Taka No.',
            'Lot No.',
            'Dyeing',
            'Quantity',
            'Allot Qty',
            'Bal Qty',
            'Unit',
            'Receive Date',
            'Alloted Date',
        ];
    }

    public function collection(): Enumerable
    {
        return $this->data->map(function ($row) {
            return [
                $row->id,
                $row->Vendor->name ?? '',
                $row->Warehouse->warehouse_name ?? '',
                $row->WarehouseCompartment->compartment_name ?? '',
                $row->Item->item_name ?? '',
                $row->ItemType->item_type_name ?? '',
                $row->invoice_number ?? '',
                $row->insp_taka_number ?? '',
                $row->dyeing_lot_number ?? '',
                $row->dyeing_color ?? '',
                $row->insp_quan_size ?? '',
                $row->insp_allot_quan_size ?? '',
                $row->insp_bal_quan_size ?? '',
                $row->UnitType->unit_type_name ?? ($row->quan_size_unit ?? ''),
                !empty($row->receive_date) ? date('d-m-Y', strtotime($row->receive_date)) : '',
                !empty($row->WarehouseOutItem?->created_at) ? date('d-m-Y', strtotime($row->WarehouseOutItem->created_at)) : '',
            ];
        });
    }
}

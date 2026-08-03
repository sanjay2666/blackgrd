<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class WarehouseBalanceStockListing implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($item): array
    {
        $unitTypeId = $item->unit_type_id;
        $unitType = ($unitTypeId == '4') ? 'Kg' : 'Meter';
        
        // Access related models directly
        $itemName = $item->item->item_name ?? 'N/A';
        $itemInternalName = $item->item->internal_item_name ?? 'N/A';
        $receiverName = $item->receiverIndividual->name ?? 'N/A';
        $warehouse = $item->warehouse->warehouse_name ?? 'N/A';
        $warehouseCompartment = $item->warehouseCompartment->compartment_name ?? 'N/A';
        $itemType = $item->itemType->item_type_name ?? 'N/A';
        $receiveDate = $item->receive_date ?? $item->created_at;

        return [
            ($item->warehouseItem->invoice_number ?? '') . ' ' . $item->id,
            $itemName,
            $itemInternalName,
            $warehouse,
            $warehouseCompartment,
            $receiverName,
            !empty($receiveDate) ? Carbon::parse($receiveDate)->format('M jS, Y') : '',
            $itemType,
            $item->dyeing_color,
            $item->item_qty . ' ' . $unitType,
            // Optionally include a URL or identifier
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice/JW No.',
            'Item Name',
            'Internal Name',
            'Warehouse Name',
            'Warehouse Compartment',
            'Receiver Name',
            'Receiving Date',
            'Item Type',
            'Dyeing Color',
            'Quantity',
            'Action',
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseStockDetailsListing implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            $warehouse = $item['Warehouse']->warehouse_name ?? $item['WarehouseItem']['Warehouse']->warehouse_name ?? 'N/A';
            $warehouseComp = $item['WarehouseCompartment']->compartment_name ?? $item['WarehouseItem']['WarehouseCompartment']->compartment_name ?? 'N/A';
            $receiveDate = $item->receive_date ?? $item->created_at;

            return [
                'invoice_number' => $item->invoice_number ?? $item['WarehouseItem']->invoice_number ?? '',
                'vendor' => $item['WarehouseItem']['Vendor']->name ?? 'N/A',
                'item_name' => $item['Item']->item_name ?? '',
                'internal_item_name' => $item['Item']->internal_item_name ?? '',
                'taka_number' => $item->insp_taka_number,
                'warehouse_name' => $warehouse,
                'warehouse_compartment' => $warehouseComp,
                'receiver_name' => $item['ReceiverIndividual']->name ?? 'N/A',
                'receive_date' => !empty($receiveDate) ? \Carbon\Carbon::parse($receiveDate)->format('M jS, Y') : '',
                'item_type' => $item['ItemType']->item_type_name ?? 'N/A',
                'quantity' => round($item->insp_quan_size - $item->insp_allot_quan_size, 2),
                'allotted_qty' => round($item->insp_allot_quan_size, 2),
                'status' => $item->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Invoice/JW No.',
            'Vendor',
            'Item Name',
            'Internal Name',
            'Taka No.',
            'Warehouse Name',
            'Warehouse Compartment',
            'Receiver Name',
            'Receiving Date',
            'Item Type',
            'Quantity',
            'Allotted QTY',
            'Status',
        ];
    }
}

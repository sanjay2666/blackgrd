<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SaleOrderItemExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'Sale Order ID',
            'Sale Order Date',
            'No Of Days',
            'Sale Order Number',
            'Item Name',
            'Dyeing Color',
            'Coating',
            'Extra Job',
            'Print Job',
            'Meter',
            'Delivered Item Meter',
            'Pending Item Meter',
        ];
    }

    public function collection(): Enumerable
    {
        $rows = collect();

        $grouped = $this->data->groupBy(function ($item) {
            $customer = $item->saleOrder->customer ?? null;

            if (!empty($customer)) {
                return !empty($customer->id) ? 'id_'.$customer->id : 'name_'.trim(strtolower($customer->name ?? $customer->company_name));
            }

            return 'name_ajy(self)';
        });

        foreach ($grouped as $items) {
            $first = $items->first();
            $customerName = strtoupper($this->getCustomerName($first));
            $headingCount = count($this->headings());

            $rows->push(array_merge([$customerName], array_fill(0, $headingCount - 1, '')));

            foreach ($items as $item) {
                $saleOrder = $item->saleOrder;
                $saleOrderDate = $saleOrder->sale_order_date ?? null;
                $meter = (float) ($item->meter ?? 0);
                $delivered = (float) ($item->delivered_item_mtr ?? 0);
                $pending = (float) ($item->pending_item_mtr ?? 0);

                if ($pending == 0 && $meter > 0) {
                    $pending = $meter - $delivered;
                }

                $rows->push([
                    $item->sale_order_id ?? '',
                    $saleOrderDate ? date('M jS, Y', strtotime($saleOrderDate)) : '',
                    $saleOrderDate ? $this->daysFromNow($saleOrderDate) : '',
                    $saleOrder->sale_order_number ?? '',
                    $item->item_name ?? '',
                    $item->dyeing_color ?? '',
                    $item->coating_type ?? '',
                    $item->extra_job ?? '',
                    $item->print_job ?? '',
                    $meter,
                    $delivered,
                    max(0, $pending),
                ]);
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $headingCount = count($this->headings());
                $lastColumnLetter = $this->columnLetterFromNumber($headingCount);

                $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellA = (string) $sheet->getCell("A{$row}")->getValue();

                    if (trim($cellA) === '') {
                        continue;
                    }

                    $otherEmpty = true;

                    for ($col = 2; $col <= $headingCount; $col++) {
                        $columnLetter = $this->columnLetterFromNumber($col);
                        $value = (string) $sheet->getCell("{$columnLetter}{$row}")->getValue();

                        if (trim($value) !== '') {
                            $otherEmpty = false;
                            break;
                        }
                    }

                    if ($otherEmpty) {
                        $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '4F81BD'],
                            ],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }
                }
            },
        ];
    }

    protected function getCustomerName($item): string
    {
        $customer = $item->saleOrder->customer ?? null;

        if (!empty($customer)) {
            return $customer->name ?: ($customer->company_name ?: 'Loomexa(Self)');
        }

        return 'Loomexa(Self)';
    }

    protected function daysFromNow($lastDate): string
    {
        $currentDate = now();
        $lastDate = Carbon::parse($lastDate);
        $days = $currentDate->diffInDays($lastDate);

        return $days.' '.($days == 1 ? 'day' : 'days');
    }

    protected function columnLetterFromNumber($number): string
    {
        $letters = '';

        while ($number > 0) {
            $mod = ($number - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $number = intval(($number - $mod) / 26);
        }

        return $letters;
    }
}

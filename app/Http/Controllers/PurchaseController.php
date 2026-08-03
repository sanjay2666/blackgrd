<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $vendorName = trim((string) $request->get('vendorName', ''));
        $invoiceNumber = trim((string) $request->get('invoice_number', ''));
        $fromDate = trim((string) $request->get('from_date', ''));
        $toDate = trim((string) $request->get('to_date', ''));

        $query = Purchase::with(['purchaseOrder', 'vendor', 'items'])
            ->where('status', 'Active');

        if ($vendorName !== '') {
            $query->where(function ($q) use ($vendorName) {
                $q->where('vendor_name', 'like', '%'.$vendorName.'%')
                    ->orWhereHas('vendor', function ($vendorQuery) use ($vendorName) {
                        $vendorQuery->where('name', 'like', '%'.$vendorName.'%')
                            ->orWhere('company_name', 'like', '%'.$vendorName.'%')
                            ->orWhere('phone', 'like', '%'.$vendorName.'%');
                    });
            });
        }

        if ($invoiceNumber !== '') {
            $query->where('invoice_number', 'like', '%'.$invoiceNumber.'%');
        }

        if ($fromDate !== '') {
            $query->whereDate('receiving_date', '>=', date('Y-m-d', strtotime($fromDate)));
        }

        if ($toDate !== '') {
            $query->whereDate('receiving_date', '<=', date('Y-m-d', strtotime($toDate)));
        }

        $dataP = $query->orderByDesc('id')
            ->paginate(config('app.pagination_limit', 15))
            ->withQueryString();

        return view('frontend.purchases.index', compact('dataP', 'vendorName', 'invoiceNumber', 'fromDate', 'toDate'));
    }

    public function show($id)
    {
        $purchaseId = dec($id);

        $purchase = Purchase::with([
                'purchaseOrder',
                'vendor',
                'items.item',
                'items.purchaseOrderItem.ItemType',
                'items.warehouseItemStock.Warehouse',
                'items.warehouseItemStock.WarehouseCompartment',
            ])
            ->where('status', 'Active')
            ->findOrFail($purchaseId);

        return view('frontend.purchases.show', compact('purchase'));
    }
}

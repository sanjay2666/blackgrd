<?php

namespace App\Http\Middleware;

use App\Services\CustomerMasterService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerSaleOrder
{
    public function __construct(private readonly CustomerMasterService $customers) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('customer_id')) {
            $customerId = (int) $request->customer_id;
            $this->customers->assertActiveCustomer($customerId);
            $this->customers->assertAddressBelongs($request->filled('billing_id') ? (int) $request->billing_id : null, $customerId, 'billing_id');
            $this->customers->assertAddressBelongs($request->filled('shipping_id') ? (int) $request->shipping_id : null, $customerId, 'shipping_id');
        }

        return $next($request);
    }
}

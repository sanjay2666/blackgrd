<?php

namespace App\Http\Middleware;

use App\Services\CurrentOrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwitchOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('company_id') || $request->has('factory_id')) {
            $data = $request->validate([
                'company_id' => ['required', 'integer', 'min:1'],
                'factory_id' => ['nullable', 'integer', 'min:1'],
            ]);

            app(CurrentOrganizationContext::class)->switch(
                $request,
                (int) $data['company_id'],
                isset($data['factory_id']) ? (int) $data['factory_id'] : null,
            );
        }

        return $next($request);
    }
}

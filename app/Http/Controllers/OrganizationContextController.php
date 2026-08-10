<?php

namespace App\Http\Controllers;

use App\Services\CurrentOrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationContextController extends Controller
{
    public function switch(Request $request, CurrentOrganizationContext $context): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'factory_id' => ['nullable', 'integer', 'min:1'],
        ]);
        try {
            $context->switch($request, (int) $data['company_id'], isset($data['factory_id']) ? (int) $data['factory_id'] : null);
        } catch (\RuntimeException $exception) {
            abort(403, 'The requested organization is not available.');
        }

        return back()->with('status', 'Organization context updated.');
    }
}

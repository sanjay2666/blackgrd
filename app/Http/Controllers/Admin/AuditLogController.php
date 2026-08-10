<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $this->query($request)->latest('created_at')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog): View
    {
        return view('admin.audit-logs.show', compact('auditLog'));
    }

    private function query(Request $request)
    {
        return AuditLog::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('actor_type'), fn ($q) => $q->where('actor_type', $request->actor_type))
            ->when($request->filled('actor_id'), fn ($q) => $q->where('actor_id', $request->actor_id))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('entity'), fn ($q) => $q->where('auditable_type', 'like', '%'.$request->entity.'%'))
            ->when($request->filled('record_id'), fn ($q) => $q->where('auditable_id', $request->record_id));
    }
}

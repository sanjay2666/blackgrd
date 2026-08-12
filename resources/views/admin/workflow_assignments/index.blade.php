<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.common.head')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="header-icon"><i class="fa fa-link"></i></div>
            <div class="header-title">
                <h1>Sale Order Item Workflows</h1>
                <small>Assign an immutable published Workflow Version before Work Order creation</small>
            </div>
        </section>
        <section class="content">
            {!! display_message('message') !!}
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading">
                    <a href="{{ route('admin.workflow-definitions.index') }}" class="btn btn-default">
                        <i class="fa fa-random"></i> Workflow Definitions
                    </a>
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.workflow-assignments.index') }}" class="row" style="margin-bottom:10px">
                        <div class="col-sm-5">
                            <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search Sale Order, Item, or Quality">
                        </div>
                        <div class="col-sm-2"><button class="btn btn-add"><i class="fa fa-search"></i> Search</button></div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                            <tr class="info">
                                <th>Sale Order</th>
                                <th>Item / Quality</th>
                                <th>Printing / Coating</th>
                                <th>Selected Snapshot</th>
                                <th style="width:360px">Assign Published Version</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($saleOrderItems as $saleOrderItem)
                                <tr>
                                    <td>{{ $saleOrderItem->saleOrder->sale_order_number ?? '-' }}</td>
                                    <td>
                                        <strong>{{ $saleOrderItem->item_name ?: '-' }}</strong><br>
                                        <span class="text-muted">{{ $saleOrderItem->grey_quality ?: '-' }}</span>
                                    </td>
                                    <td>
                                        Print: {{ $saleOrderItem->print_job ?: '-' }}<br>
                                        Coat: {{ $saleOrderItem->coating_type ?: '-' }}
                                    </td>
                                    <td>
                                        @if ($saleOrderItem->workflowVersion)
                                            {{ $saleOrderItem->workflowVersion->definition->workflow_name ?? 'Workflow' }}
                                            (v{{ $saleOrderItem->workflowVersion->version_number }})
                                        @else
                                            <span class="text-muted">Not selected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.workflow-assignments.update', $saleOrderItem) }}" class="form-inline">
                                            @csrf
                                            @method('PATCH')
                                            <select name="workflow_version_id" class="form-control" style="max-width:270px">
                                                <option value="">No Workflow Selected</option>
                                                @if ($saleOrderItem->workflowVersion && ! $workflowVersions->contains('id', $saleOrderItem->workflow_version_id))
                                                    <option value="{{ $saleOrderItem->workflow_version_id }}" selected>
                                                        {{ $saleOrderItem->workflowVersion->definition->workflow_name ?? 'Historical Workflow' }}
                                                        (v{{ $saleOrderItem->workflowVersion->version_number }}, Historical)
                                                    </option>
                                                @endif
                                                @foreach ($workflowVersions as $workflowVersion)
                                                    <option value="{{ $workflowVersion->id }}" @selected((int) $saleOrderItem->workflow_version_id === (int) $workflowVersion->id)>
                                                        {{ $workflowVersion->definition->workflow_name }}
                                                        (v{{ $workflowVersion->version_number }}{{ $workflowVersion->is_current ? ', Current' : '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-primary"><i class="fa fa-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No active Sale Order Items found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination">{{ $saleOrderItems->links() }}</div>
                    <p class="text-muted">Assignment is blocked after downstream Work Order history exists. This page stores the snapshot reference only; it does not execute transitions.</p>
                </div>
            </div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.common.head')
</head>
<body class="hold-transition sidebar-mini">
    <div id="preloader"><div id="status"></div></div>
    <div class="wrapper">
        @include('admin.common.header')
        @include('admin.common.sidebar')
        <div class="content-wrapper">
            <section class="content-header">
                <div class="header-icon"><i class="fa fa-cogs"></i></div>
                <div class="header-title"><h1>Process Configuration</h1><small>{{ $processItem->process_name }}</small></div>
            </section>
            <section class="content">
                {!! display_message('message') !!}
                @if ($errors->any())<div class="alert alert-danger"><strong>Please fix the errors below.</strong></div>@endif
                <div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                    <div class="panel-heading"><a href="{{ route('admin.process-items.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Process List</a> <a href="{{ route('admin.process-items.edit', enc($processItem->id)) }}" class="btn btn-default">Edit Metadata</a></div>
                    <div class="panel-body">
                        <div class="row"><div class="col-sm-3"><strong>Code:</strong> {{ $processItem->short_code }}</div><div class="col-sm-3"><strong>Department:</strong> {{ $processItem->department?->department_name ?? 'Reusable / unassigned' }}</div><div class="col-sm-3"><strong>Legacy Input:</strong> {{ $processItem->entry_name ?: '-' }}</div><div class="col-sm-3"><strong>Legacy Output:</strong> {{ $processItem->output_name }}</div></div>
                        <hr style="margin:10px 0">
                        <form method="POST" action="{{ route('admin.process-items.configuration.update', enc($processItem->id)) }}">
                            @csrf
                            @method('PUT')
                            @php($selectedInputs = old('input_item_type_ids', $processItem->materialConfigurations->where('direction', 'Input')->pluck('item_type_id')->map(fn ($id) => (string) $id)->all()))
                            @php($selectedOutputs = old('output_item_type_ids', $processItem->materialConfigurations->where('direction', 'Output')->pluck('item_type_id')->map(fn ($id) => (string) $id)->all()))
                            @php($selectedNext = old('allowed_next_process_ids', $processItem->allowedNextProcesses->pluck('next_process_item_id')->map(fn ($id) => (string) $id)->all()))
                            <div class="row">
                                <div class="col-sm-4"><div class="form-group"><label>Execution Mode <span class="required">*</span></label><select name="execution_mode" class="form-control" required>@foreach(['Internal', 'External', 'Both'] as $mode)<option value="{{ $mode }}" @selected(old('execution_mode', $processItem->configuration?->execution_mode ?? 'Both') === $mode)>{{ $mode }}</option>@endforeach</select><span class="help-block">Configuration only; no job-work runtime behavior changes here.</span></div></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6"><div class="form-group"><label>Input Item Types</label><div class="well well-sm" style="max-height:220px;overflow:auto">@forelse($itemTypes as $itemType)<label class="checkbox-inline" style="min-width:48%;margin-left:0"><input type="checkbox" name="input_item_type_ids[]" value="{{ $itemType->item_type_id }}" @checked(in_array((string) $itemType->item_type_id, $selectedInputs, true))> {{ $itemType->item_type_name }}</label>@empty<span class="text-muted">No active Item Types are available.</span>@endforelse</div>@error('input_item_type_ids')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                                <div class="col-sm-6"><div class="form-group"><label>Output Item Types</label><div class="well well-sm" style="max-height:220px;overflow:auto">@forelse($itemTypes as $itemType)<label class="checkbox-inline" style="min-width:48%;margin-left:0"><input type="checkbox" name="output_item_type_ids[]" value="{{ $itemType->item_type_id }}" @checked(in_array((string) $itemType->item_type_id, $selectedOutputs, true))> {{ $itemType->item_type_name }}</label>@empty<span class="text-muted">No active Item Types are available.</span>@endforelse</div>@error('output_item_type_ids')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                            </div>
                            <div class="form-group"><label>Allowed Next Processes</label><div class="well well-sm" style="max-height:220px;overflow:auto">@forelse($nextProcesses as $nextProcess)<label class="checkbox-inline" style="min-width:31%;margin-left:0"><input type="checkbox" name="allowed_next_process_ids[]" value="{{ $nextProcess->id }}" @checked(in_array((string) $nextProcess->id, $selectedNext, true))> {{ $nextProcess->process_name }} ({{ $nextProcess->short_code }})</label>@empty<span class="text-muted">No other active processes are available.</span>@endforelse</div><span class="help-block">This defines possible transitions only. A Workflow Version remains the actual Sale Order Item route snapshot.</span>@error('allowed_next_process_ids')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                            @can('processes.update')<div class="reset-button"><a href="{{ route('admin.process-items.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save Configuration</button></div>@endcan
                        </form>
                    </div>
                </div></div></div>
            </section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>

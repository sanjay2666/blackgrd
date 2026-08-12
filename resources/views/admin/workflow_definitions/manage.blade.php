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
            <div class="header-icon"><i class="fa fa-random"></i></div>
            <div class="header-title">
                <h1>{{ $definition->workflow_name }}</h1>
                <small>{{ $definition->workflow_code }} &middot; Version {{ $version->version_number }} &middot; {{ $version->status }}</small>
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
                        <i class="fa fa-list"></i> Workflow List
                    </a>
                    @foreach ($definition->versions as $availableVersion)
                        <a href="{{ route('admin.workflow-definitions.show', [$definition, $availableVersion]) }}"
                           class="btn {{ $availableVersion->is($version) ? 'btn-primary' : 'btn-default' }}">
                            v{{ $availableVersion->version_number }} {{ $availableVersion->status }}
                            @if ($availableVersion->is_current) (Current) @endif
                        </a>
                    @endforeach
                </div>
                <div class="panel-body">
                    <h4>Workflow Information</h4>
                    <form method="POST" action="{{ route('admin.workflow-definitions.update', $definition) }}">
                        @csrf
                        @method('PUT')
                        @include('admin.workflow_definitions.form')
                        <button class="btn btn-primary"><i class="fa fa-save"></i> Save Workflow</button>
                    </form>

                    <hr>
                    <div class="row">
                        <div class="col-sm-7">
                            <h4>
                                Version {{ $version->version_number }}
                                @if ($version->is_current)
                                    <span class="label label-success">Current Published Version</span>
                                @elseif ($version->status === 'Published')
                                    <span class="label label-default">Published Snapshot</span>
                                @else
                                    <span class="label label-warning">Draft</span>
                                @endif
                            </h4>
                            @if ($version->status === 'Published')
                                <p>
                                    Published {{ optional($version->published_at)->format('d-m-Y H:i') ?: '-' }}
                                    &middot; Effective {{ optional($version->effective_from)->format('d-m-Y') ?: '-' }}
                                </p>
                                @if ($version->remarks)<p>{{ $version->remarks }}</p>@endif
                                <p class="text-muted">Published steps are locked. Create a new version to change the sequence.</p>
                            @endif
                        </div>
                        <div class="col-sm-5 text-right">
                            @if ($definition->status === 'Active' && ! $definition->versions->contains('status', 'Draft'))
                                <form method="POST" action="{{ route('admin.workflow-definitions.versions.store', $definition) }}" class="form-inline">
                                    @csrf
                                    <input type="date" name="effective_from" class="form-control" title="Effective From">
                                    <input type="text" name="remarks" class="form-control" maxlength="5000" placeholder="Version remarks">
                                    <button class="btn btn-add"><i class="fa fa-copy"></i> Create New Version</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($version->status === 'Draft')
                        <div class="well well-sm">
                            <form method="POST" action="{{ route('admin.workflow-definitions.versions.publish', [$definition, $version]) }}" class="form-inline">
                                @csrf
                                @method('PATCH')
                                <label>Effective From</label>
                                <input type="date" name="effective_from" value="{{ old('effective_from', optional($version->effective_from)->format('Y-m-d')) }}" class="form-control">
                                <input type="text" name="remarks" value="{{ old('remarks', $version->remarks) }}" class="form-control" maxlength="5000" placeholder="Version remarks">
                                <button class="btn btn-success"><i class="fa fa-check"></i> Publish and Lock</button>
                            </form>
                        </div>
                    @endif

                    <h4>Ordered Process Steps</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr class="info">
                                <th style="width:100px">Step</th>
                                <th>Process</th>
                                <th>Label</th>
                                <th>Description</th>
                                <th style="width:150px">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($version->steps as $step)
                                <tr>
                                    @if ($version->status === 'Draft')
                                        <td><input form="update-step-{{ $step->id }}" type="number" min="1" name="sequence" value="{{ $step->sequence }}" class="form-control" required></td>
                                        <td>
                                            <select form="update-step-{{ $step->id }}" name="process_id" class="form-control" required>
                                                @foreach ($processes as $process)
                                                    <option value="{{ $process->id }}" @selected((int) $step->process_id === (int) $process->id)>{{ $process->process_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input form="update-step-{{ $step->id }}" name="step_label" value="{{ $step->step_label }}" class="form-control" maxlength="255"></td>
                                        <td><input form="update-step-{{ $step->id }}" name="description" value="{{ $step->description }}" class="form-control" maxlength="5000"></td>
                                        <td>
                                            <form id="update-step-{{ $step->id }}" method="POST" action="{{ route('admin.workflow-definitions.steps.update', [$definition, $version, $step]) }}" style="display:inline">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-primary btn-xs" title="Save"><i class="fa fa-save"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.workflow-definitions.steps.destroy', [$definition, $version, $step]) }}" style="display:inline" onsubmit="return confirm('Remove this Draft step?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-xs" title="Remove"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    @else
                                        <td>{{ $step->sequence }}</td>
                                        <td>{{ $step->process->process_name ?? $step->process_id }}</td>
                                        <td>{{ $step->step_label ?: '-' }}</td>
                                        <td>{{ $step->description ?: '-' }}</td>
                                        <td><span class="label label-default">Locked</span></td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No steps yet. Add consecutive steps before publishing.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($version->status === 'Draft')
                        <h4>Add Process Step</h4>
                        <form method="POST" action="{{ route('admin.workflow-definitions.steps.store', [$definition, $version]) }}" class="row">
                            @csrf
                            <div class="col-sm-2"><input type="number" min="1" name="sequence" value="{{ old('sequence', $version->steps->count() + 1) }}" class="form-control" placeholder="Step" required></div>
                            <div class="col-sm-3">
                                <select name="process_id" class="form-control" required>
                                    <option value="">Select Process</option>
                                    @foreach ($processes as $process)
                                        <option value="{{ $process->id }}" @selected((int) old('process_id') === (int) $process->id)>{{ $process->process_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3"><input name="step_label" value="{{ old('step_label') }}" class="form-control" maxlength="255" placeholder="Step label (optional)"></div>
                            <div class="col-sm-3"><input name="description" value="{{ old('description') }}" class="form-control" maxlength="5000" placeholder="Description (optional)"></div>
                            <div class="col-sm-1"><button class="btn btn-success"><i class="fa fa-plus"></i></button></div>
                        </form>

                        @if ($version->version_number > 1)
                            <hr>
                            <form method="POST" action="{{ route('admin.workflow-definitions.versions.destroy', [$definition, $version]) }}" onsubmit="return confirm('Delete this unreferenced Draft version?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger"><i class="fa fa-trash"></i> Delete Draft Version</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>

@php
	$statusOptions = $statusOptions ?? \App\Enums\RecordStatus::formOptions();
	$selectedStatus = $selectedStatus ?? 'Active';
	$selectedStatus = $selectedStatus instanceof \App\Enums\RecordStatus
		? $selectedStatus->value
		: $selectedStatus;
@endphp
@foreach ($statusOptions as $statusValue => $statusLabel)
	<option value="{{ $statusValue }}" @selected(old('status', $selectedStatus) === $statusValue)>{{ $statusLabel }}</option>
@endforeach

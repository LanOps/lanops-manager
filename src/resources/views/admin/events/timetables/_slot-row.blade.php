@php
	$rowId = $row['id'] ?? null;
	$removed = !empty($row['remove']);
	$rowDay = $row['day'] ?? null;
	$field = fn ($name) => 'slots[' . $index . '][' . $name . ']';
	$hasError = fn ($name) => is_int($index) && $errors->has('slots.' . $index . '.' . $name);
@endphp
<tr data-slot-row @class(['slot-removed' => $removed]) @if ($rowId) data-slot-id="{{ $rowId }}" @endif>
	<td data-label="Day">
		@if ($rowId)
			<input type="hidden" name="{{ $field('id') }}" value="{{ $rowId }}">
		@endif
		<select name="{{ $field('day') }}" @class(['form-select form-select-sm', 'is-invalid' => $hasError('day')]) aria-label="Day">
			@foreach ($days as $value => $label)
				<option value="{{ $value }}" @selected($value === $rowDay)>{{ $label }}</option>
			@endforeach
			@if ($rowDay && !isset($days[$rowDay]))
				<option value="{{ $rowDay }}" selected>{{ $rowDay }}</option>
			@endif
		</select>
	</td>
	<td data-label="Time">
		<input type="time" name="{{ $field('time') }}" value="{{ $row['time'] ?? '' }}" @class(['form-control form-control-sm', 'is-invalid' => $hasError('time')]) aria-label="Time">
	</td>
	<td data-label="Name">
		<input type="text" name="{{ $field('name') }}" value="{{ $row['name'] ?? '' }}" maxlength="255" placeholder="Name" @class(['form-control form-control-sm', 'is-invalid' => $hasError('name')]) aria-label="Name">
	</td>
	<td data-label="Description">
		<input type="text" name="{{ $field('desc') }}" value="{{ $row['desc'] ?? '' }}" maxlength="255" placeholder="Description" @class(['form-control form-control-sm', 'is-invalid' => $hasError('desc')]) aria-label="Description">
	</td>
	<td class="text-end">
		<input type="hidden" name="{{ $field('remove') }}" value="{{ $removed ? 1 : 0 }}" data-slot-remove-input>
		<button type="button" class="btn btn-sm btn-outline-danger" data-slot-remove title="{{ $removed ? 'Keep this slot' : 'Remove this slot' }}" aria-label="{{ $removed ? 'Keep this slot' : 'Remove this slot' }}">
			<i class="fa {{ $removed ? 'fa-rotate-left' : 'fa-trash' }} fa-fw"></i>
		</button>
	</td>
</tr>

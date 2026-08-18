@php
    $editing = isset($department);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
    @csrf
    @if ($editing)
        @method('PATCH')
        <input type="hidden" name="version" value="{{ old('version', $department->lock_version) }}">
    @endif

    <div>
        <label class="admin-label" for="department-name">Tên phòng ban <span class="text-danger">*</span></label>
        <input
            id="department-name"
            class="admin-input"
            type="text"
            name="name"
            value="{{ old('name', $department->name ?? '') }}"
            maxlength="255"
            required
            autocomplete="organization"
            aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
            @if ($errors->has('name')) aria-describedby="department-name-error" @endif
        >
        @error('name')
            <p id="department-name-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="department-code">Mã phòng ban <span class="text-danger">*</span></label>
        <input
            id="department-code"
            class="admin-input uppercase"
            type="text"
            name="code"
            value="{{ old('code', $department->code ?? '') }}"
            minlength="2"
            maxlength="50"
            required
            spellcheck="false"
            aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
            @if ($errors->has('code')) aria-describedby="department-code-help department-code-error" @else aria-describedby="department-code-help" @endif
        >
        <p id="department-code-help" class="mt-1.5 text-xs text-gray-500">
            Dùng chữ cái, số, dấu gạch nối hoặc gạch dưới. Mã sẽ được lưu bằng chữ hoa.
        </p>
        @error('code')
            <p id="department-code-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="department-address">Địa chỉ</label>
        <textarea
            id="department-address"
            class="admin-input min-h-28 resize-y"
            name="address"
            maxlength="1000"
            rows="4"
            aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}"
            @if ($errors->has('address')) aria-describedby="department-address-error" @endif
        >{{ old('address', $department->address ?? '') }}</textarea>
        @error('address')
            <p id="department-address-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-5">
        <x-admin.button variant="secondary" :href="$cancelUrl">Hủy</x-admin.button>
        <x-admin.button type="submit">{{ $submitLabel }}</x-admin.button>
    </div>
</form>

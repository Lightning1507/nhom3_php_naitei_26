@php
    $editing = isset($category);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
    @csrf
    @if ($editing)
        @method($method ?? 'PUT')
    @endif

    <div>
        <label class="admin-label" for="category-name">Tên danh mục <span class="text-danger">*</span></label>
        <input
            id="category-name"
            class="admin-input"
            type="text"
            name="name"
            value="{{ old('name', $category->name ?? '') }}"
            maxlength="255"
            required
            aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
            @if ($errors->has('name')) aria-describedby="category-name-error" @endif
        >
        @error('name')
            <p id="category-name-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="category-code">Mã danh mục <span class="text-danger">*</span></label>
        <input
            id="category-code"
            class="admin-input uppercase"
            type="text"
            name="code"
            value="{{ old('code', $category->code ?? '') }}"
            minlength="2"
            maxlength="255"
            required
            spellcheck="false"
            aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
            @if ($errors->has('code')) aria-describedby="category-code-help category-code-error" @else aria-describedby="category-code-help" @endif
        >
        <p id="category-code-help" class="mt-1.5 text-xs text-gray-500">
            Dùng chữ cái, số, dấu gạch nối hoặc gạch dưới. Mã sẽ được lưu bằng chữ hoa và là duy nhất.
        </p>
        @error('code')
            <p id="category-code-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="category-description">Mô tả</label>
        <textarea
            id="category-description"
            class="admin-input min-h-28 resize-y"
            name="description"
            rows="4"
            aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}"
            @if ($errors->has('description')) aria-describedby="category-description-error" @endif
        >{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')
            <p id="category-description-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-5">
        <x-admin.button variant="secondary" :href="$cancelUrl">Hủy</x-admin.button>
        <x-admin.button type="submit">{{ $submitLabel }}</x-admin.button>
    </div>
</form>

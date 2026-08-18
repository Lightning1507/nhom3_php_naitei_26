@props([
    'name',
    'id' => null,
    'options' => [], // array of ['value' => '...', 'label' => '...']
    'value' => null,
    'placeholder' => 'Chọn...',
    'disabled' => false,
])

@php
    $id = $id ?? $name;
@endphp

<div
    x-data="{
        open: false,
        search: '',
        value: '{{ old($name, $value) }}',
        options: {{ json_encode($options) }},
        get filteredOptions() {
            if (this.search === '') {
                return this.options;
            }
            return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
        },
        get selectedLabel() {
            const selected = this.options.find(i => i.value == this.value);
            return selected ? selected.label : '{{ $placeholder }}';
        },
        select(val) {
            this.value = val;
            this.open = false;
            this.search = '';
        }
    }"
    @click.away="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-model="value" />

    <button
        type="button"
        @click="open = !open"
        class="admin-input flex items-center justify-between text-left"
        :class="{ 'border-primary ring-1 ring-primary': open }"
        {{ $disabled ? 'disabled' : '' }}
    >
        <span class="block truncate" x-text="selectedLabel" :class="{ 'text-gray-400': !value, 'text-gray-900': value }"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        style="display: none;"
    >
        <div class="sticky top-0 bg-white px-2 py-1.5 shadow-sm">
            <input
                type="text"
                x-model="search"
                class="admin-input !min-h-8 w-full border-gray-200 px-3 py-1.5 text-sm"
                placeholder="Tìm kiếm..."
                @click.stop
            />
        </div>
        <template x-for="option in filteredOptions" :key="option.value">
            <div
                @click="select(option.value)"
                class="relative cursor-pointer select-none py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-50"
                :class="{ 'bg-primary/5 text-primary': value == option.value }"
            >
                <span class="block truncate" :class="{ 'font-semibold': value == option.value, 'font-normal': value != option.value }" x-text="option.label"></span>
                <span x-show="value == option.value" class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
        </template>
        <div x-show="filteredOptions.length === 0" class="py-2 pl-3 pr-9 text-gray-500">
            Không tìm thấy kết quả.
        </div>
    </div>
</div>

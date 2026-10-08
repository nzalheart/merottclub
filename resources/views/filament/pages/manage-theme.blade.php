<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit">
                Save theme colors
            </x-filament::button>

            <x-filament::button color="gray" type="button" wire:click="resetToNepalDefaults">
                Reset to Nepal flag defaults
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-4">Live preview (public site)</p>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background-color: {{ $this->data['primary_color'] ?? '#DC143C' }}">
                Primary button
            </span>
            <span class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background-color: {{ $this->data['secondary_color'] ?? '#003893' }}">
                Secondary pill
            </span>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border" style="color: {{ $this->data['primary_color'] ?? '#DC143C' }}; border-color: {{ $this->data['primary_color'] ?? '#DC143C' }}; background-color: color-mix(in srgb, {{ $this->data['primary_color'] ?? '#DC143C' }} 12%, white);">
                Soft primary
            </span>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border" style="color: {{ $this->data['secondary_color'] ?? '#003893' }}; border-color: {{ $this->data['secondary_color'] ?? '#003893' }}; background-color: color-mix(in srgb, {{ $this->data['secondary_color'] ?? '#003893' }} 12%, white);">
                Soft secondary
            </span>
        </div>
        <p class="mt-4 text-xs text-gray-500">
            Background stays white. Defaults: crimson <code>#DC143C</code> and blue <code>#003893</code> from the Nepal national flag.
        </p>
    </div>
</x-filament-panels::page>

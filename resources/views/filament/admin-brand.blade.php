@php
    $name = \App\Support\Brand::name();
    $logoUrl = \App\Support\Brand::logoUrl();
@endphp

<span class="inline-flex items-center gap-2 min-w-0">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="" class="h-8 w-8 shrink-0 rounded-lg object-cover">
    @endif
    <span class="text-sm font-bold leading-tight text-gray-950 dark:text-white">{{ $name }}</span>
</span>

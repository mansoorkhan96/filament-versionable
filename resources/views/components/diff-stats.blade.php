@props(['diffStats'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-x-2']) }}>
    <div class="flex items-center gap-x-1">
        <x-filament::icon-button
            icon="heroicon-m-plus-circle"
            tag="a"
            label="Insertions"
            color="success"
        />

        <span class="text-sm font-semibold">
            {{ Arr::get($diffStats, 'inserted') }}
        </span>
    </div>

    <div class="flex items-center gap-x-1">
        <x-filament::icon-button
            icon="heroicon-m-minus-circle"
            tag="a"
            color="danger"
            label="Deletions"
        />

        <span class="text-sm font-semibold">
            {{ Arr::get($diffStats, 'deleted') }}
        </span>
    </div>
</div>

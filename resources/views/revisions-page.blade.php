<x-filament-panels::page>
    <div x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-versionable', 'filament-versionable'))]">
        <div class="mb-4 grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="col-span-3 flex justify-between">
                {{ $this->previousVersionAction }}

                {{ $this->nextVersionAction }}
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="col-span-3">
                <x-filament::section compact>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-3">
                                @if ($this->version->user)
                                    <x-filament-panels::avatar.user
                                        :user="$this->version->user"
                                        size="lg"
                                    />
                                @endif

                                <div class="flex items-center gap-x-3">
                                    <div class="flex flex-col">
                                        <span>
                                            {{ __('filament-versionable::page.revision_by', [
                                                'name' => $this->version->user?->name ?? __('filament-versionable::page.anonymous_user'),
                                            ]) }}
                                        </span>

                                        <small class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $this->version->created_at->diffForHumans() }}
                                            ({{ $this->version->created_at->format('d M') }} @
                                            {{ $this->version->created_at->format('H:i') }})
                                        </small>
                                    </div>

                                    @php
                                        $diffStats = $this->version->diff()->getStatistics();
                                    @endphp

                                    <x-filament-versionable::diff-stats :$diffStats />
                                </div>
                            </div>

                            {{ $this->restoreVersionAction }}
                        </div>
                    </x-slot>

                    <div class="flex flex-col gap-2 divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($this->diff as $fieldName => $diff)
                            <div class="py-5">
                                <p class="mb-2 px-1 text-lg font-medium capitalize">
                                    {{ $fieldName }}
                                </p>

                                {!! $diff !!}
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <div class="col-span-1">
                <x-filament::section compact>
                    <x-slot name="heading">
                        {{ __('filament-versionable::page.revisions_list') }}
                    </x-slot>

                    <ol
                        role="list"
                        class="divide-y divide-gray-200 dark:divide-white/10"
                    >
                        @foreach ($this->revisionsList as $version)
                            <li
                                wire:click="showVersion({{ $version->id }})"
                                @class([
                                    'pb-4' => $loop->first && !$loop->last,
                                    'pt-4' => $loop->last && !$loop->first,
                                    'py-4' => !$loop->first && !$loop->last,
                                    'group cursor-pointer',
                                ])
                            >
                                <div class="flex items-center gap-x-2">
                                    @if ($version->user)
                                        <x-filament-panels::avatar.user
                                            :user="$version->user"
                                            size="sm"
                                        />
                                    @endif

                                    <span
                                        style="flex: 1 1 auto;"
                                        @class([
                                            'text-primary-600' => $version->id === $this->version->id,
                                            'flex-auto truncate text-sm font-medium leading-6 group-hover:text-primary-600',
                                        ])
                                    >
                                        <span
                                            @class([
                                                'text-primary-600' => $version->id === $this->version->id,
                                                'font-normal text-gray-500 group-hover:text-primary-600 dark:text-gray-400',
                                            ])
                                            title="{{ __('filament-versionable::page.revision_by', [
                                                'name' => $version->user?->name ?? __('filament-versionable::page.anonymous_user'),
                                            ]) }}"
                                        >
                                            {{ __('filament-versionable::page.revision_by', ['name' => '']) }}
                                        </span>

                                        {{ $version->user?->name ?? __('filament-versionable::page.anonymous_user') }}
                                    </span>

                                    <span class="flex-none text-xs text-gray-500 dark:text-gray-400">
                                        {{ $version->created_at->diffForHumans(short: true, syntax: Carbon\CarbonInterface::DIFF_ABSOLUTE) }}
                                    </span>
                                </div>

                                @php
                                    $diffStats = $version->diff()->getStatistics();
                                @endphp

                                <x-filament-versionable::diff-stats
                                    :$diffStats
                                    class="mt-2"
                                />
                            </li>
                        @endforeach
                    </ol>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>

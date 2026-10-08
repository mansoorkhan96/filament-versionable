<x-filament-panels::page>
    @if ($this->hasRevisions())
    <div>
        <div class="mb-4 grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="col-span-3 flex justify-between">
                {{ $this->previousVersionAction }}

                {{ $this->nextVersionAction }}
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <div class="lg:col-span-3">
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

                    <div class="space-y-6 divide-y-1 divide-gray-200 dark:divide-white/10">
                        @foreach ($this->diff as $fieldName => $diff)
                            <div class="pb-6">
                                <p class="mb-2 px-1 text-lg font-medium capitalize">
                                    {{ $fieldName }}
                                </p>

                                {!! $diff !!}
                            </div>
                        @endforeach

                        @foreach ($this->relationDiffs as $relationChangeSet)
                            <div class="pb-6">
                                <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 px-1">
                                    <p class="text-lg font-medium capitalize">
                                        {{ $relationChangeSet->label() }}
                                    </p>

                                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $relationChangeSet->typeLabel() }}
                                    </span>

                                    <span class="flex items-center gap-x-1.5">
                                        @if (count($relationChangeSet->added) > 0)
                                            <x-filament::badge color="success" size="xs">
                                                + {{ count($relationChangeSet->added) }}
                                            </x-filament::badge>
                                        @endif

                                        @if (count($relationChangeSet->updated) > 0)
                                            <x-filament::badge color="warning" size="xs">
                                                ~ {{ count($relationChangeSet->updated) }}
                                            </x-filament::badge>
                                        @endif

                                        @if (count($relationChangeSet->removed) > 0)
                                            <x-filament::badge color="danger" size="xs">
                                                − {{ count($relationChangeSet->removed) }}
                                            </x-filament::badge>
                                        @endif

                                        @if ($relationChangeSet->unchanged > 0)
                                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ __('filament-versionable::page.relations.unchanged', ['count' => $relationChangeSet->unchanged]) }}
                                            </span>
                                        @endif
                                    </span>
                                </div>

                                @if ($relationChangeSet->displayOnly)
                                    <p class="mb-2 px-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('filament-versionable::page.relations.display_only_hint') }}
                                    </p>
                                @endif

                                @foreach ([...$relationChangeSet->added, ...$relationChangeSet->removed, ...$relationChangeSet->updated] as $recordChange)
                                    <div class="mb-3 rounded-xl border border-gray-200 p-3 dark:border-white/10">
                                        <div class="mb-2 flex items-center gap-x-2">
                                            @if (in_array($recordChange, $relationChangeSet->added, true))
                                                <x-filament::badge color="success" size="sm">
                                                    {{ __('filament-versionable::page.relations.added') }}
                                                </x-filament::badge>
                                            @elseif (in_array($recordChange, $relationChangeSet->removed, true))
                                                <x-filament::badge color="danger" size="sm">
                                                    {{ __('filament-versionable::page.relations.removed') }}
                                                </x-filament::badge>
                                            @else
                                                <x-filament::badge color="warning" size="sm">
                                                    {{ __('filament-versionable::page.relations.updated') }}
                                                </x-filament::badge>
                                            @endif

                                            <span class="text-sm font-medium">
                                                {{ $recordChange->label() }}
                                            </span>
                                        </div>

                                        @foreach ($recordChange->fields as $field => $payload)
                                            <div class="mb-1">
                                                <p class="px-1 text-sm font-medium capitalize text-gray-500 dark:text-gray-400">
                                                    {{ str_replace('_', ' ', $field) }}
                                                </p>

                                                {!! $payload['html'] !!}
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <div class="lg:col-span-1">
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
                                wire:click="showVersion(@js($version->getKey()))"
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
    @else
        <x-filament::empty-state
            :heading="__('filament-versionable::page.no_revisions')"
            icon="heroicon-o-clock"
            icon-color="gray"
        />
    @endif
</x-filament-panels::page>

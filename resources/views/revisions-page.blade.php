<x-filament-panels::page>
    <div
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-versionable', 'filament-versionable'))]"
        class="grid grid-cols-1 gap-6 lg:grid-cols-4"
    >
        <div class="col-span-3">
            <div class="mb-4 flex justify-between">
                <x-filament::button
                    :disabled="$this->version->previousVersion()->is($this->record->firstVersion)"
                    wire:click="showPreviousVersion"
                >
                    Previous
                </x-filament::button>

                <x-filament::button
                    :disabled="$this->version->is($this->record->lastVersion)"
                    wire:click="showNextVersion"
                >
                    Next
                </x-filament::button>
            </div>

            <x-filament::section compact>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament-panels::avatar.user
                                :user="$this->version->user"
                                size="lg"
                            />

                            <div class="flex flex-col">
                                <span>Revision by {{ $this->version->user->name }}</span>

                                <small class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $this->version->created_at->diffForHumans() }}
                                    ({{ $this->version->created_at->format('d M') }} @
                                    {{ $this->version->created_at->format('H:i') }})
                                </small>
                            </div>
                        </div>

                        @if ($this->version->isNot($this->record->lastVersion))
                            <x-filament::button wire:click="restoreSelectedVersion">
                                Restore This Revision
                            </x-filament::button>
                        @else
                            <x-filament::badge
                                size="xl"
                                color="info"
                                icon="heroicon-m-information-circle"
                            >
                                This is the latest Revision
                            </x-filament::badge>
                        @endif
                    </div>
                </x-slot>

                <div class="flex flex-col gap-2">
                    @foreach ($this->diff as $fieldName => $diff)
                        <div>
                            <p class="mb-2 px-1 text-lg font-medium capitalize">
                                {{ $fieldName }}
                            </p>

                            {!! $diff !!}
                        </div>

                        @if (!$loop->last)
                            <div class="my-4 border-t border-gray-200 dark:border-white/20">
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <x-filament::section
            class="col-span-1"
            compact
        >
            <x-slot name="heading">
                Revisions List
            </x-slot>

            <ol
                role="list"
                class="flex flex-col gap-4"
            >
                @foreach ($this->revisionsList as $version)
                    <li class="flex items-center gap-2">
                        <x-filament-panels::avatar.user :user="$version->user" />

                        <div class="flex flex-1 justify-between space-x-4">
                            <div
                                wire:click="showVersion({{ $version->id }})"
                                class="group cursor-pointer text-sm"
                            >
                                <span @class([
                                    'text-primary-600' => $version->id === $this->version->id,
                                    'group-hover:text-primary-600',
                                ])>
                                    Revision by
                                </span>

                                <span @class([
                                    'text-primary-600' => $version->id === $this->version->id,
                                    'font-medium group-hover:text-primary-600',
                                ])>
                                    {{ $version->user->name }}
                                </span>
                            </div>

                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                {{ $version->created_at->diffForHumans(short: true) }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <x-filament::pagination
                class="mt-6"
                :paginator="$this->revisionsList"
            />
        </x-filament::section>
    </div>
</x-filament-panels::page>

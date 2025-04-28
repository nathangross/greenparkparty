<div class="flex rounded-lg bg-white p-4 shadow">
    <div class="flex h-full w-full items-center justify-between">
        <span class="font-medium text-gray-700">Include Organizers in Stats</span>
        <button
            wire:click="toggle"
            type="button"
            @class([
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                'bg-primary-600' => $isEnabled,
                'bg-gray-200' => !$isEnabled,
            ])
            role="switch"
            aria-checked="{{ $isEnabled }}">
            <span
                @class([
                    'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    'translate-x-5' => $isEnabled,
                    'translate-x-0' => !$isEnabled,
                ])>
                <span
                    @class([
                        'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity',
                        'opacity-0 duration-100 ease-out' => $isEnabled,
                        'opacity-100 duration-200 ease-in' => !$isEnabled,
                    ])
                    aria-hidden="true">
                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span
                    @class([
                        'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity',
                        'opacity-100 duration-200 ease-in' => $isEnabled,
                        'opacity-0 duration-100 ease-out' => !$isEnabled,
                    ])
                    aria-hidden="true">
                    <svg class="text-primary-600 h-3 w-3" fill="currentColor" viewBox="0 0 12 12">
                        <path
                            d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                    </svg>
                </span>
            </span>
        </button>
    </div>
</div>

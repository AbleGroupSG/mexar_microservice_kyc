<div>
    <div class="mb-6">
        <h1 class="text-3xl font-bold">API Request Logs</h1>
        <p class="text-base-content/70 mt-1">View all API requests and responses (Admin Only)</p>
    </div>

    <!-- Filters -->
    <div class="card bg-base-100 shadow mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Search</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Request UUID or provider..."
                        class="input input-bordered"
                    />
                </div>

                <!-- Provider Filter -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Provider</span>
                    </label>
                    <select wire:model.live="providerFilter" class="select select-bordered">
                        <option value="">All Providers</option>
                        <option value="regtank">RegTank</option>
                        <option value="glair">Glair AI</option>
                        <option value="test">Test</option>
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">&nbsp;</span>
                    </label>
                    <button wire:click="clearFilters" class="btn btn-outline">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs List -->
    <div class="space-y-4">
        @forelse ($logs as $log)
            <div class="card bg-base-100 shadow" x-data="{ showPayload: false, showResponse: false }">
                <div class="card-body">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-lg">{{ $log->provider }}</h3>
                                <span class="badge badge-primary">{{ $log->request_uuid }}</span>
                            </div>
                            <p class="text-sm text-base-content/70 mt-1">
                                {{ $log->created_at->format('Y-m-d H:i:s') }} ({{ $log->created_at->diffForHumans() }})
                            </p>
                            @if ($log->kycProfile)
                                <p class="text-sm text-base-content/70 mt-1">
                                    <span class="font-semibold">Profile Status:</span>
                                    <span class="badge badge-sm badge-{{ $log->kycProfile->status === 'APPROVED' ? 'success' : ($log->kycProfile->status === 'REJECTED' ? 'error' : 'warning') }}">
                                        {{ $log->kycProfile->status }}
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Payload Section -->
                    <div class="mt-4">
                        <button
                            @click="showPayload = !showPayload"
                            class="btn btn-sm btn-outline"
                        >
                            <span x-text="showPayload ? 'Hide Payload' : 'Show Payload'"></span>
                        </button>
                        <div x-show="showPayload" class="mt-2" style="display: none;">
                            <div class="mockup-code">
                                <pre class="px-4 overflow-x-auto"><code>{{ json_encode(json_decode($log->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Response Section -->
                    <div class="mt-4">
                        <button
                            @click="showResponse = !showResponse"
                            class="btn btn-sm btn-outline"
                        >
                            <span x-text="showResponse ? 'Hide Response' : 'Show Response'"></span>
                        </button>
                        <div x-show="showResponse" class="mt-2" style="display: none;">
                            @if ($log->response)
                                <div class="mockup-code">
                                    <pre class="px-4 overflow-x-auto"><code>{{ json_encode(json_decode($log->response), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <span>No response data available</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold mt-4">No API Request Logs Found</h3>
                    <p class="text-base-content/70">There are no API request logs matching your filters</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($logs->hasPages())
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
</div>

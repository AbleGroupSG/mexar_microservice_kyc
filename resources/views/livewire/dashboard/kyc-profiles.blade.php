<div>
    <div class="mb-6">
        <h1 class="text-3xl font-bold">KYC Profiles</h1>
        <p class="text-base-content/70 mt-1">View and manage all KYC verification profiles</p>
    </div>

    <!-- Filters -->
    <div class="card bg-base-100 shadow mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="form-control md:col-span-2">
                    <label class="label">
                        <span class="label-text">Search</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search by ID, reference, user name or email..."
                        class="input input-bordered w-full"
                    />
                </div>

                <!-- Status Filter -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <select wire:model.live="statusFilter" class="select select-bordered w-full">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="error">Error</option>
                        <option value="unresolved">Unresolved</option>
                        <option value="provider_approved">Awaiting Review (Approved)</option>
                        <option value="provider_rejected">Awaiting Review (Rejected)</option>
                        <option value="provider_error">Awaiting Review (Error)</option>
                    </select>
                </div>

                <!-- Provider Filter -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Provider</span>
                    </label>
                    <select wire:model.live="providerFilter" class="select select-bordered w-full">
                        <option value="">All Providers</option>
                        <option value="regtank">RegTank</option>
                        <option value="glair_ai">Glair AI</option>
                        <option value="test">Test</option>
                    </select>
                </div>
            </div>

            <!-- Awaiting Review Filter -->
            @if ($isAdmin)
                <div class="form-control mt-4">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input type="checkbox" wire:model.live="awaitingReviewFilter" class="checkbox checkbox-primary" />
                        <span class="label-text">
                            Awaiting Review Only
                            @if ($awaitingReviewCount > 0)
                                <span class="badge badge-primary badge-sm ml-2">{{ $awaitingReviewCount }}</span>
                            @endif
                        </span>
                    </label>
                </div>
            @endif

            @if ($search || $statusFilter || $providerFilter || $awaitingReviewFilter)
                <div class="mt-4">
                    <button wire:click="clearFilters" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Filters
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- KYC Profiles Table -->
    <div class="card bg-base-100 shadow overflow-x-auto">
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kycProfiles as $profile)
                    <tr>
                        <td>
                            <div class="font-mono text-xs">
                                {{ Str::limit($profile->id, 12) }}
                            </div>
                            @if ($profile->provider_reference_id)
                                <div class="text-xs text-base-content/70">
                                    Ref: {{ Str::limit($profile->provider_reference_id, 12) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="font-semibold">{{ $profile->user->name }}</div>
                            <div class="text-sm text-base-content/70">{{ $profile->user->email }}</div>
                            @if ($profile->apiKey)
                                <div class="badge badge-sm mt-1">{{ $profile->apiKey->name }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="badge badge-outline">
                                {{ ucfirst(str_replace('_', ' ', $profile->provider)) }}
                            </div>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'badge-warning',
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-error',
                                    'error' => 'badge-error',
                                    'unresolved' => 'badge-warning',
                                    'provider_approved' => 'badge-info',
                                    'provider_rejected' => 'badge-info',
                                    'provider_error' => 'badge-info',
                                ];
                                $statusValue = $profile->status?->value ?? '';
                                $statusColor = $statusColors[$statusValue] ?? 'badge-neutral';
                                $statusLabels = [
                                    'provider_approved' => 'Awaiting Review (Approved)',
                                    'provider_rejected' => 'Awaiting Review (Rejected)',
                                    'provider_error' => 'Awaiting Review (Error)',
                                ];
                                $statusLabel = $statusLabels[$statusValue] ?? ucfirst($statusValue);
                            @endphp
                            <div class="badge {{ $statusColor }}">
                                {{ $statusLabel ?: 'Unknown' }}
                            </div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $profile->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-base-content/70">{{ $profile->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <button
                                    @click="$dispatch('open-modal', { id: '{{ $profile->id }}' })"
                                    class="btn btn-ghost btn-xs"
                                >
                                    View
                                </button>
                                @if ($isAdmin && $profile->isAwaitingReview())
                                    <button
                                        wire:click="openReviewModal('{{ $profile->id }}', 'approve')"
                                        class="btn btn-success btn-xs"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        wire:click="openReviewModal('{{ $profile->id }}', 'reject')"
                                        class="btn btn-error btn-xs"
                                    >
                                        Reject
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-semibold mt-4">No KYC Profiles Found</h3>
                            <p class="text-base-content/70">
                                @if ($search || $statusFilter || $providerFilter)
                                    Try adjusting your filters
                                @else
                                    No profiles have been created yet
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($kycProfiles->hasPages())
        <div class="mt-6">
            {{ $kycProfiles->links() }}
        </div>
    @endif

    <!-- Profile Details Modal -->
    <div x-data="{ profileId: null, profileData: null }" @open-modal.window="profileId = $event.detail.id; fetchProfile(profileId)">
        <input type="checkbox" :id="'modal-' + profileId" class="modal-toggle" x-model="profileId" />
        <div class="modal" :class="{ 'modal-open': profileId }" role="dialog">
            <div class="modal-box max-w-3xl">
                <h3 class="font-bold text-lg mb-4">Profile Details</h3>
                <div x-show="profileData" class="space-y-4">
                    <div>
                        <label class="label font-semibold">Profile ID</label>
                        <code class="bg-base-200 p-2 rounded block text-xs" x-text="profileId"></code>
                    </div>
                    <div>
                        <label class="label font-semibold">Profile Data</label>
                        <pre class="bg-base-200 p-4 rounded text-xs overflow-auto max-h-96" x-text="JSON.stringify(profileData, null, 2)"></pre>
                    </div>
                </div>
                <div class="modal-action">
                    <button @click="profileId = null; profileData = null" class="btn">Close</button>
                </div>
            </div>
            <div class="modal-backdrop" @click="profileId = null; profileData = null"></div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="toast toast-top toast-end">
            <div class="alert alert-success">
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="toast toast-top toast-end">
            <div class="alert alert-error">
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Manual Review Modal -->
    @if ($reviewProfileId)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">
                    {{ $reviewAction === 'approve' ? 'Approve' : 'Reject' }} KYC Profile
                </h3>
                <p class="py-2 text-sm text-base-content/70">
                    Profile ID: {{ Str::limit($reviewProfileId, 24) }}
                </p>

                <div class="form-control w-full mt-4">
                    <label class="label">
                        <span class="label-text">Review Notes <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="reviewNotes"
                        class="textarea textarea-bordered h-24 @error('reviewNotes') textarea-error @enderror"
                        placeholder="Enter your review notes (minimum 10 characters)..."
                    ></textarea>
                    @error('reviewNotes')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="modal-action">
                    <button wire:click="closeReviewModal" class="btn btn-ghost">Cancel</button>
                    <button
                        wire:click="submitReview"
                        wire:loading.attr="disabled"
                        class="btn {{ $reviewAction === 'approve' ? 'btn-success' : 'btn-error' }}"
                    >
                        <span wire:loading wire:target="submitReview" class="loading loading-spinner loading-xs"></span>
                        Confirm {{ $reviewAction === 'approve' ? 'Approval' : 'Rejection' }}
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeReviewModal"></div>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('profileViewer', () => ({
        async fetchProfile(id) {
            if (!id) return;

            try {
                const response = await fetch(`/api/kyc-profiles/${id}`);
                if (response.ok) {
                    this.profileData = await response.json();
                }
            } catch (error) {
                console.error('Failed to fetch profile:', error);
            }
        }
    }));
</script>
@endscript

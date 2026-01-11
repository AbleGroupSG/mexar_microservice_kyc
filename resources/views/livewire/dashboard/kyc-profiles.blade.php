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
                                    wire:click="openViewModal('{{ $profile->id }}')"
                                    class="btn btn-ghost btn-xs"
                                >
                                    View
                                </button>
                                @if ($profile->isAwaitingReview() && ($isAdmin || $profile->user_id === auth()->id()))
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
                                @if (in_array($profile->status?->value, ['approved', 'rejected']) && ($isAdmin || $profile->user_id === auth()->id()))
                                    <button
                                        wire:click="resendWebhook('{{ $profile->id }}')"
                                        wire:loading.attr="disabled"
                                        class="btn btn-outline btn-xs"
                                        title="Resend webhook notification"
                                    >
                                        Resend
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
    @if ($viewProfileId && $viewProfileData)
        <div class="modal modal-open">
            <div class="modal-box max-w-4xl max-h-[90vh] overflow-y-auto">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    KYC Profile Details
                </h3>

                <!-- Basic Info -->
                <div class="bg-base-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-base-content/60">Profile ID</span>
                            <code class="block text-xs bg-base-300 p-2 rounded mt-1 break-all">{{ $viewProfileData['id'] }}</code>
                        </div>
                        @if ($viewProfileData['provider_reference_id'])
                            <div>
                                <span class="text-xs text-base-content/60">Provider Reference ID</span>
                                <code class="block text-xs bg-base-300 p-2 rounded mt-1 break-all">{{ $viewProfileData['provider_reference_id'] }}</code>
                            </div>
                        @endif
                        <div>
                            <span class="text-xs text-base-content/60">Provider</span>
                            <div class="mt-1">
                                <span class="badge badge-outline">{{ ucfirst(str_replace('_', ' ', $viewProfileData['provider'])) }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60">Status</span>
                            <div class="mt-1">
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
                                    $statusColor = $statusColors[$viewProfileData['status']] ?? 'badge-neutral';
                                @endphp
                                <span class="badge {{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $viewProfileData['status'] ?? 'Unknown')) }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60">Created At</span>
                            <p class="text-sm mt-1">{{ $viewProfileData['created_at'] }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60">Updated At</span>
                            <p class="text-sm mt-1">{{ $viewProfileData['updated_at'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- User Info -->
                <div class="bg-base-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">User Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-base-content/60">Name</span>
                            <p class="text-sm mt-1 font-medium">{{ $viewProfileData['user']['name'] }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60">Email</span>
                            <p class="text-sm mt-1">{{ $viewProfileData['user']['email'] }}</p>
                        </div>
                        @if ($viewProfileData['api_key'])
                            <div>
                                <span class="text-xs text-base-content/60">API Key</span>
                                <div class="mt-1">
                                    <span class="badge badge-sm">{{ $viewProfileData['api_key'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($viewProfileData['profile_data'])
                    <!-- Personal Info -->
                    @if (isset($viewProfileData['profile_data']['personal_info']))
                        <div class="bg-base-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Personal Information</h4>
                            @php $personalInfo = $viewProfileData['profile_data']['personal_info']; @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @if (isset($personalInfo['first_name']))
                                    <div>
                                        <span class="text-xs text-base-content/60">First Name</span>
                                        <p class="text-sm mt-1">{{ $personalInfo['first_name'] }}</p>
                                    </div>
                                @endif
                                @if (isset($personalInfo['last_name']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Last Name</span>
                                        <p class="text-sm mt-1">{{ $personalInfo['last_name'] }}</p>
                                    </div>
                                @endif
                                @if (isset($personalInfo['gender']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Gender</span>
                                        <p class="text-sm mt-1">{{ ucfirst($personalInfo['gender']) }}</p>
                                    </div>
                                @endif
                                @if (isset($personalInfo['date_of_birth']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Date of Birth</span>
                                        <p class="text-sm mt-1">{{ $personalInfo['date_of_birth'] }}</p>
                                    </div>
                                @endif
                                @if (isset($personalInfo['nationality']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Nationality</span>
                                        <p class="text-sm mt-1">{{ $personalInfo['nationality'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Identification -->
                    @if (isset($viewProfileData['profile_data']['identification']))
                        <div class="bg-base-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Identification</h4>
                            @php $identification = $viewProfileData['profile_data']['identification']; @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @if (isset($identification['id_type']))
                                    <div>
                                        <span class="text-xs text-base-content/60">ID Type</span>
                                        <p class="text-sm mt-1">{{ strtoupper($identification['id_type']) }}</p>
                                    </div>
                                @endif
                                @if (isset($identification['id_number']))
                                    <div>
                                        <span class="text-xs text-base-content/60">ID Number</span>
                                        <p class="text-sm mt-1 font-mono">{{ $identification['id_number'] }}</p>
                                    </div>
                                @endif
                                @if (isset($identification['issuing_country']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Issuing Country</span>
                                        <p class="text-sm mt-1">{{ $identification['issuing_country'] }}</p>
                                    </div>
                                @endif
                                @if (isset($identification['issue_date']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Issue Date</span>
                                        <p class="text-sm mt-1">{{ $identification['issue_date'] }}</p>
                                    </div>
                                @endif
                                @if (isset($identification['expiry_date']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Expiry Date</span>
                                        <p class="text-sm mt-1">{{ $identification['expiry_date'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Address -->
                    @if (isset($viewProfileData['profile_data']['address']))
                        <div class="bg-base-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Address</h4>
                            @php $address = $viewProfileData['profile_data']['address']; @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @if (isset($address['address_line']))
                                    <div class="md:col-span-2">
                                        <span class="text-xs text-base-content/60">Address Line</span>
                                        <p class="text-sm mt-1">{{ $address['address_line'] }}</p>
                                    </div>
                                @endif
                                @if (isset($address['street']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Street</span>
                                        <p class="text-sm mt-1">{{ $address['street'] }}</p>
                                    </div>
                                @endif
                                @if (isset($address['city']))
                                    <div>
                                        <span class="text-xs text-base-content/60">City</span>
                                        <p class="text-sm mt-1">{{ $address['city'] }}</p>
                                    </div>
                                @endif
                                @if (isset($address['state']))
                                    <div>
                                        <span class="text-xs text-base-content/60">State</span>
                                        <p class="text-sm mt-1">{{ $address['state'] }}</p>
                                    </div>
                                @endif
                                @if (isset($address['postal_code']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Postal Code</span>
                                        <p class="text-sm mt-1">{{ $address['postal_code'] }}</p>
                                    </div>
                                @endif
                                @if (isset($address['country']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Country</span>
                                        <p class="text-sm mt-1">{{ $address['country'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Contact -->
                    @if (isset($viewProfileData['profile_data']['contact']))
                        <div class="bg-base-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Contact Information</h4>
                            @php $contact = $viewProfileData['profile_data']['contact']; @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @if (isset($contact['email']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Email</span>
                                        <p class="text-sm mt-1">{{ $contact['email'] }}</p>
                                    </div>
                                @endif
                                @if (isset($contact['phone']))
                                    <div>
                                        <span class="text-xs text-base-content/60">Phone</span>
                                        <p class="text-sm mt-1">{{ $contact['phone'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Review Info (if reviewed) -->
                @if ($viewProfileData['reviewer'] || $viewProfileData['review_notes'])
                    <div class="bg-base-200 rounded-lg p-4 mb-4">
                        <h4 class="font-semibold mb-3 text-sm uppercase tracking-wide text-base-content/70">Review Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @if ($viewProfileData['reviewer'])
                                <div>
                                    <span class="text-xs text-base-content/60">Reviewed By</span>
                                    <p class="text-sm mt-1">{{ $viewProfileData['reviewer'] }}</p>
                                </div>
                            @endif
                            @if ($viewProfileData['reviewed_at'])
                                <div>
                                    <span class="text-xs text-base-content/60">Reviewed At</span>
                                    <p class="text-sm mt-1">{{ $viewProfileData['reviewed_at'] }}</p>
                                </div>
                            @endif
                            @if ($viewProfileData['review_notes'])
                                <div class="md:col-span-2">
                                    <span class="text-xs text-base-content/60">Review Notes</span>
                                    <p class="text-sm mt-1 bg-base-300 p-3 rounded">{{ $viewProfileData['review_notes'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Raw Data (Collapsible) -->
                <div class="collapse collapse-arrow bg-base-200 mb-4">
                    <input type="checkbox" />
                    <div class="collapse-title font-semibold text-sm uppercase tracking-wide text-base-content/70">
                        Raw Profile Data (JSON)
                    </div>
                    <div class="collapse-content">
                        <pre class="bg-base-300 p-4 rounded text-xs overflow-auto max-h-64 mt-2">{{ json_encode($viewProfileData['profile_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>

                @if ($viewProfileData['provider_response_data'])
                    <div class="collapse collapse-arrow bg-base-200 mb-4">
                        <input type="checkbox" />
                        <div class="collapse-title font-semibold text-sm uppercase tracking-wide text-base-content/70">
                            Provider Response Data (JSON)
                        </div>
                        <div class="collapse-content">
                            <pre class="bg-base-300 p-4 rounded text-xs overflow-auto max-h-64 mt-2">{{ json_encode($viewProfileData['provider_response_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif

                <div class="modal-action">
                    <button wire:click="closeViewModal" class="btn">Close</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeViewModal"></div>
        </div>
    @endif

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

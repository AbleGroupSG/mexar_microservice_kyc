<?php

namespace App\Livewire\Dashboard;

use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Services\KYC\KycWorkflowService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('KYC Profiles')]
class KycProfiles extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'provider')]
    public string $providerFilter = '';

    #[Url(as: 'awaiting_review')]
    public bool $awaitingReviewFilter = false;

    // Review modal properties
    public ?string $reviewProfileId = null;
    public string $reviewNotes = '';
    public ?string $reviewAction = null;

    public function getIsAdminProperty(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Open the review modal for a specific profile.
     */
    public function openReviewModal(string $profileId, string $action): void
    {
        $this->reviewProfileId = $profileId;
        $this->reviewAction = $action;
        $this->reviewNotes = '';
        $this->resetValidation();
    }

    /**
     * Close the review modal.
     */
    public function closeReviewModal(): void
    {
        $this->reset(['reviewProfileId', 'reviewAction', 'reviewNotes']);
        $this->resetValidation();
    }

    /**
     * Submit the manual review.
     */
    public function submitReview(): void
    {
        if (!$this->isAdmin) {
            session()->flash('error', 'Only admins can perform reviews.');
            $this->closeReviewModal();
            return;
        }

        $this->validate([
            'reviewNotes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'reviewNotes.required' => 'Review notes are required.',
            'reviewNotes.min' => 'Review notes must be at least 10 characters.',
            'reviewNotes.max' => 'Review notes cannot exceed 1000 characters.',
        ]);

        $profile = KYCProfile::with(['apiKey', 'user'])->find($this->reviewProfileId);

        if (!$profile) {
            session()->flash('error', 'Profile not found.');
            $this->closeReviewModal();
            return;
        }

        // Verify profile is awaiting review
        if (!$profile->isAwaitingReview()) {
            session()->flash('error', 'This profile is not awaiting review.');
            $this->closeReviewModal();
            return;
        }

        $finalStatus = $this->reviewAction === 'approve'
            ? KycStatuseEnum::APPROVED
            : KycStatuseEnum::REJECTED;

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            $finalStatus,
            $this->reviewNotes,
            auth()->user()
        );

        $this->closeReviewModal();

        $statusLabel = $this->reviewAction === 'approve' ? 'approved' : 'rejected';
        session()->flash('success', "Profile {$statusLabel} successfully. Webhook dispatched.");
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProviderFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAwaitingReviewFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'providerFilter', 'awaitingReviewFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = KYCProfile::query()
            ->with(['user', 'apiKey', 'reviewer'])
            ->latest();

        // Filter by user for standard users
        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('id', 'like', "%{$this->search}%")
                    ->orWhere('provider_reference_id', 'like', "%{$this->search}%");

                // Only search user info if admin
                if ($this->isAdmin) {
                    $q->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
                }
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->providerFilter) {
            $query->where('provider', $this->providerFilter);
        }

        // Filter for profiles awaiting manual review
        if ($this->awaitingReviewFilter) {
            $query->whereIn('status', [
                KycStatuseEnum::PROVIDER_APPROVED->value,
                KycStatuseEnum::PROVIDER_REJECTED->value,
                KycStatuseEnum::PROVIDER_ERROR->value,
            ]);
        }

        // Count profiles awaiting review for badge
        $awaitingReviewCount = KYCProfile::query()
            ->when(!$this->isAdmin, fn($q) => $q->where('user_id', auth()->id()))
            ->whereIn('status', [
                KycStatuseEnum::PROVIDER_APPROVED->value,
                KycStatuseEnum::PROVIDER_REJECTED->value,
                KycStatuseEnum::PROVIDER_ERROR->value,
            ])
            ->count();

        return view('livewire.dashboard.kyc-profiles', [
            'kycProfiles' => $query->paginate(15),
            'isAdmin' => $this->isAdmin,
            'awaitingReviewCount' => $awaitingReviewCount,
        ]);
    }
}

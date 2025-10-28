<?php

namespace App\Livewire\Dashboard;

use App\Models\KYCProfile;
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'providerFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = KYCProfile::query()
            ->with(['user', 'apiKey'])
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('id', 'like', "%{$this->search}%")
                    ->orWhere('provider_reference_id', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->providerFilter) {
            $query->where('provider', $this->providerFilter);
        }

        return view('livewire.dashboard.kyc-profiles', [
            'kycProfiles' => $query->paginate(15),
        ]);
    }
}

<?php

namespace App\Livewire\Dashboard;

use App\Models\ApiRequestLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('API Request Logs')]
class ApiRequestLogs extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'provider')]
    public string $providerFilter = '';

    public function mount(): void
    {
        // Only admins can access this page
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProviderFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'providerFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = ApiRequestLog::query()
            ->with('kycProfile')
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('request_uuid', 'like', "%{$this->search}%")
                    ->orWhere('provider', 'like', "%{$this->search}%");
            });
        }

        if ($this->providerFilter) {
            $query->where('provider', $this->providerFilter);
        }

        return view('livewire.dashboard.api-request-logs', [
            'logs' => $query->paginate(20),
        ]);
    }
}

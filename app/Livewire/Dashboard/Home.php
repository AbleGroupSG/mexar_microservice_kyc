<?php

namespace App\Livewire\Dashboard;

use App\Models\KYCProfile;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Home extends Component
{
    public function render()
    {
        $totalKycProfiles = KYCProfile::count();
        $pendingKycProfiles = KYCProfile::where('status', 'PENDING')->count();
        $approvedKycProfiles = KYCProfile::where('status', 'APPROVED')->count();
        $rejectedKycProfiles = KYCProfile::where('status', 'REJECTED')->count();
        $totalUsers = User::count();

        return view('livewire.dashboard.home', [
            'totalKycProfiles' => $totalKycProfiles,
            'pendingKycProfiles' => $pendingKycProfiles,
            'approvedKycProfiles' => $approvedKycProfiles,
            'rejectedKycProfiles' => $rejectedKycProfiles,
            'totalUsers' => $totalUsers,
        ]);
    }
}

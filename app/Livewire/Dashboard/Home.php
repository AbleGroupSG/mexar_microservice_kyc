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
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Build query based on user type
        $kycQuery = KYCProfile::query();
        if (!$isAdmin) {
            $kycQuery->where('user_id', $user->id);
        }

        $totalKycProfiles = (clone $kycQuery)->count();
        $pendingKycProfiles = (clone $kycQuery)->where('status', 'PENDING')->count();
        $approvedKycProfiles = (clone $kycQuery)->where('status', 'APPROVED')->count();
        $rejectedKycProfiles = (clone $kycQuery)->where('status', 'REJECTED')->count();

        // Total users only visible to admins
        $totalUsers = $isAdmin ? User::count() : null;

        return view('livewire.dashboard.home', [
            'totalKycProfiles' => $totalKycProfiles,
            'pendingKycProfiles' => $pendingKycProfiles,
            'approvedKycProfiles' => $approvedKycProfiles,
            'rejectedKycProfiles' => $rejectedKycProfiles,
            'totalUsers' => $totalUsers,
            'isAdmin' => $isAdmin,
        ]);
    }
}

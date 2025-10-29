<div>
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <p class="text-base-content/70 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total KYC Profiles -->
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="stat-title">Total KYC Profiles</div>
                <div class="stat-value text-primary">{{ number_format($totalKycProfiles) }}</div>
                <div class="stat-desc">All time profiles</div>
            </div>
        </div>

        <!-- Pending Profiles -->
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-title">Pending</div>
                <div class="stat-value text-warning">{{ number_format($pendingKycProfiles) }}</div>
                <div class="stat-desc">Awaiting verification</div>
            </div>
        </div>

        <!-- Approved Profiles -->
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-title">Approved</div>
                <div class="stat-value text-success">{{ number_format($approvedKycProfiles) }}</div>
                <div class="stat-desc">Successfully verified</div>
            </div>
        </div>

        <!-- Rejected Profiles -->
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-title">Rejected</div>
                <div class="stat-value text-error">{{ number_format($rejectedKycProfiles) }}</div>
                <div class="stat-desc">Failed verification</div>
            </div>
        </div>

        <!-- Total Users (Admin Only) -->
        @if ($isAdmin)
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value text-secondary">{{ number_format($totalUsers) }}</div>
                    <div class="stat-desc">System users</div>
                </div>
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('dashboard.api-keys') }}" wire:navigate class="card bg-base-100 shadow hover:shadow-lg transition-shadow">
                <div class="card-body">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Manage API Keys
                    </h3>
                    <p>View and manage your API keys for accessing the service</p>
                </div>
            </a>

            <a href="{{ route('dashboard.kyc-profiles') }}" wire:navigate class="card bg-base-100 shadow hover:shadow-lg transition-shadow">
                <div class="card-body">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        View KYC Profiles
                    </h3>
                    <p>Browse and search through all KYC profiles</p>
                </div>
            </a>
        </div>
    </div>
</div>

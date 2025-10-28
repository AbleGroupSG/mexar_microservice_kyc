<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">API Keys</h1>
            <p class="text-base-content/70 mt-1">Manage your API keys for accessing the KYC service</p>
        </div>
        <button wire:click="toggleCreateForm" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Key
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Create Form -->
    @if ($showCreateForm)
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">Create New API Key</h2>
                <form wire:submit="createApiKey">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Key Name</span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="e.g., Production API Key"
                            class="input input-bordered w-full @error('name') input-error @enderror"
                            required
                        />
                        @error('name')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="form-control w-full mt-4">
                        <label class="label">
                            <span class="label-text">Webhook URL (Optional)</span>
                        </label>
                        <input
                            type="url"
                            wire:model="webhook_url"
                            placeholder="https://your-app.com/webhook"
                            class="input input-bordered w-full @error('webhook_url') input-error @enderror"
                        />
                        @error('webhook_url')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                        <label class="label">
                            <span class="label-text-alt">URL to receive KYC result webhooks</span>
                        </label>
                    </div>

                    <div class="form-control w-full mt-4">
                        <label class="label">
                            <span class="label-text">Signature Key (Optional)</span>
                        </label>
                        <input
                            type="text"
                            wire:model="signature_key"
                            placeholder="Leave blank to auto-generate"
                            class="input input-bordered w-full @error('signature_key') input-error @enderror"
                        />
                        @error('signature_key')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                        <label class="label">
                            <span class="label-text-alt">Provide signature key from another system, or leave blank to auto-generate (min. 16 characters)</span>
                        </label>
                    </div>

                    <div class="card-actions justify-end mt-4">
                        <button type="button" wire:click="toggleCreateForm" class="btn btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create API Key</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- API Keys List -->
    <div class="space-y-4">
        @forelse ($apiKeys as $apiKey)
            <div class="card bg-base-100 shadow" x-data="{ showKey: false, showSignature: false }">
                <div class="card-body">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-bold text-lg">{{ $apiKey->name }}</h3>
                            <p class="text-sm text-base-content/70">
                                Created {{ $apiKey->created_at->diffForHumans() }}
                            </p>

                            <!-- API Key -->
                            <div class="mt-4">
                                <label class="label">
                                    <span class="label-text font-semibold">API Key</span>
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        :type="showKey ? 'text' : 'password'"
                                        value="{{ $apiKey->api_key }}"
                                        class="input input-bordered flex-1 font-mono text-sm"
                                        readonly
                                    />
                                    <button
                                        @click="showKey = !showKey"
                                        class="btn btn-square btn-outline"
                                        type="button"
                                    >
                                        <svg x-show="!showKey" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showKey" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $apiKey->api_key }}')"
                                        class="btn btn-square btn-outline"
                                        type="button"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Signature Key -->
                            <div class="mt-4">
                                <label class="label">
                                    <span class="label-text font-semibold">Signature Key</span>
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        :type="showSignature ? 'text' : 'password'"
                                        value="{{ $apiKey->signature_key }}"
                                        class="input input-bordered flex-1 font-mono text-sm"
                                        readonly
                                    />
                                    <button
                                        @click="showSignature = !showSignature"
                                        class="btn btn-square btn-outline"
                                        type="button"
                                    >
                                        <svg x-show="!showSignature" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showSignature" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $apiKey->signature_key }}')"
                                        class="btn btn-square btn-outline"
                                        type="button"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if ($apiKey->webhook_url)
                                <div class="mt-4">
                                    <label class="label">
                                        <span class="label-text font-semibold">Webhook URL</span>
                                    </label>
                                    <input
                                        type="text"
                                        value="{{ $apiKey->webhook_url }}"
                                        class="input input-bordered w-full font-mono text-sm"
                                        readonly
                                    />
                                </div>
                            @endif
                        </div>

                        <button
                            wire:click="deleteApiKey({{ $apiKey->id }})"
                            wire:confirm="Are you sure you want to delete this API key? This action cannot be undone."
                            class="btn btn-error btn-sm"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <h3 class="text-lg font-semibold mt-4">No API Keys Yet</h3>
                    <p class="text-base-content/70">Create your first API key to get started</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($apiKeys->hasPages())
        <div class="mt-6">
            {{ $apiKeys->links() }}
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('apiKeyManager', () => ({
        showKey: false,
        showSignature: false,
    }));
</script>
@endscript

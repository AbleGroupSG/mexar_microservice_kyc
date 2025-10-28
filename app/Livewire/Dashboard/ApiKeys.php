<?php

namespace App\Livewire\Dashboard;

use App\Models\UserApiKey;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('API Keys')]
class ApiKeys extends Component
{
    use WithPagination;

    public string $name = '';
    public string $webhook_url = '';
    public string $signature_key = '';
    public bool $showCreateForm = false;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'signature_key' => ['nullable', 'string', 'min:16', 'max:255'],
        ];
    }

    public function createApiKey(): void
    {
        $this->validate();

        auth()->user()->apiKeys()->create([
            'name' => $this->name,
            'api_key' => 'mexar_' . Str::random(40),
            'signature_key' => $this->signature_key ?: Str::random(32),
            'webhook_url' => $this->webhook_url,
        ]);

        $this->reset(['name', 'webhook_url', 'signature_key', 'showCreateForm']);
        $this->dispatch('api-key-created');
        session()->flash('success', 'API key created successfully!');
    }

    public function deleteApiKey(int $id): void
    {
        $apiKey = auth()->user()->apiKeys()->findOrFail($id);
        $apiKey->delete();

        session()->flash('success', 'API key deleted successfully!');
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = !$this->showCreateForm;
        if (!$this->showCreateForm) {
            $this->reset(['name', 'webhook_url', 'signature_key']);
            $this->resetValidation();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.api-keys', [
            'apiKeys' => auth()->user()->apiKeys()->latest()->paginate(10),
        ]);
    }
}

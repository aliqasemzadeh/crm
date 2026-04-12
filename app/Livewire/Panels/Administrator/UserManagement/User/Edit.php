<?php

namespace App\Livewire\Panels\Administrator\UserManagement\User;

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public int $id;

    public string $mobile = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo;

    public $photos = [];

    #[On('panels.administrator.user-management.user.edit.assign-data')]
    public function assignData($id): void
    {
        $this->user = User::findOrFail($id);
        $this->id = $this->user->id;
        $this->first_name = (string) ($this->user->first_name ?? '');
        $this->last_name = (string) ($this->user->last_name ?? '');
        $this->mobile = (string) $this->user->mobile;
        $this->email = (string) ($this->user->email ?? '');
        $this->password = '';
        $this->password_confirmation = '';
        $this->photo = null;
        $this->photos = [];
        Flux::modal('panels.administrator.user-management.user.edit.modal')->show();
    }

    public function edit(): void
    {
        $this->authorize('administrator_user_management_edit');

        if (! isset($this->user)) {
            return;
        }

        $validated = $this->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'ir_mobile', 'max:255', Rule::unique('users', 'mobile')->ignore($this->user)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'password_confirmation' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'photos.*' => ['nullable', 'image', 'max:10240'],
        ]);

        if ($this->photo) {
            $this->user->avatar = $this->photo->store('avatars', 'public');
        }

        if ($this->photos) {
            $existingSignatures = json_decode($this->user->signature, true) ?? [];
            $newSignatures = [];
            foreach ($this->photos as $photo) {
                $newSignatures[] = $photo->store('signatures', 'public');
            }
            $this->user->signature = json_encode(array_merge($existingSignatures, $newSignatures));
        }

        // Normalize empty strings to null to respect nullable columns and avoid unique('email') collisions on ''
        $firstName = trim((string) ($validated['first_name'] ?? ''));
        $lastName = trim((string) ($validated['last_name'] ?? ''));
        $email = trim((string) ($validated['email'] ?? ''));

        $this->user->first_name = $firstName === '' ? null : $firstName;
        $this->user->last_name = $lastName === '' ? null : $lastName;
        $this->user->mobile = $validated['mobile'];
        $this->user->email = $email === '' ? null : $email;

        if (! empty($validated['password'] ?? '')) {
            // Will be hashed automatically via the model cast
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        $this->dispatch('panels.administrator.user-management.user.index.render');
        Flux::modal('panels.administrator.user-management.user.edit.modal')->close();
    }

    public function removeSignature($path): void
    {
        $this->authorize('administrator_user_management_edit');
        if (! isset($this->user)) {
            return;
        }
        $signatures = json_decode($this->user->signature, true) ?? [];
        $signatures = array_values(array_filter($signatures, fn($sig) => $sig !== $path));
        $this->user->signature = json_encode($signatures);
        $this->user->save();
    }

    public function removeAvatar(): void
    {
        $this->authorize('administrator_user_management_edit');
        if (! isset($this->user)) {
            return;
        }
        $this->user->avatar = null;
        $this->user->save();
        $this->photo = null;
    }

    public function render(): View
    {
        return view('livewire.panels.administrator.user-management.user.edit');
    }
}

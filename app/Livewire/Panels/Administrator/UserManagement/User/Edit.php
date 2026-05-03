<?php

namespace App\Livewire\Panels\Administrator\UserManagement\User;

use App\Models\Issabel\Device;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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

    public ?string $internal_phone_id = null;

    public string $internal_phone_search = '';

    public string $bale_code = '';

    public string $personnel_code = '';

    public string $timex_code = '';

    /** نمایش بلوک شمارهٔ داخلی و کدها در فرم ویرایش (فقط UI). */
    public bool $hrExtrasExpanded = false;

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
        $this->internal_phone_id = $this->user->internal_phone_id;
        $this->internal_phone_search = '';
        $this->bale_code = (string) ($this->user->bale_code ?? '');
        $this->personnel_code = (string) ($this->user->personnel_code ?? '');
        $this->timex_code = (string) ($this->user->timex_code ?? '');
        $this->hrExtrasExpanded = false;
        Flux::modal('panels.administrator.user-management.user.edit.modal')->show();
    }

    /**
     * گزینه‌های شمارهٔ داخلی (Issabel devices) برای combobox با جستجوی سمت سرور.
     *
     * @return Collection<int, Device>
     */
    #[Computed]
    public function internalPhoneDevices(): Collection
    {
        try {
            $term = trim($this->internal_phone_search);

            $query = Device::query()
                ->whereNotNull('user')
                ->orderBy('user')
                ->limit(25);

            if ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('user', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhere('id', 'like', '%'.$term.'%');
                });
            }

            $results = $query->get();

            if ($this->internal_phone_id !== null && $this->internal_phone_id !== ''
                && ! $results->contains(fn (Device $d): bool => (string) $d->id === (string) $this->internal_phone_id)) {
                $extra = Device::query()->find($this->internal_phone_id);
                if ($extra) {
                    $results = $results->prepend($extra)->take(25)->values();
                }
            }

            return $results;
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
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
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'password_confirmation' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'photos.*' => ['nullable', 'image', 'max:10240'],
            'internal_phone_id' => ['nullable', 'string', 'max:255'],
            'bale_code' => ['nullable', 'string', 'max:255'],
            'personnel_code' => ['nullable', 'string', 'max:255'],
            'timex_code' => ['nullable', 'string', 'max:255'],
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

        $internalId = trim((string) ($validated['internal_phone_id'] ?? ''));
        $this->user->internal_phone_id = $internalId === '' ? null : $internalId;

        $bale = trim((string) ($validated['bale_code'] ?? ''));
        $this->user->bale_code = $bale === '' ? null : $bale;

        $personnel = trim((string) ($validated['personnel_code'] ?? ''));
        $this->user->personnel_code = $personnel === '' ? null : $personnel;

        $timex = trim((string) ($validated['timex_code'] ?? ''));
        $this->user->timex_code = $timex === '' ? null : $timex;

        if (! empty($validated['password'] ?? '')) {
            // Will be hashed automatically via the model cast
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        Flux::toast(__('app.user_updated_successfully'));
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
        $signatures = array_values(array_filter($signatures, fn ($sig) => $sig !== $path));
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

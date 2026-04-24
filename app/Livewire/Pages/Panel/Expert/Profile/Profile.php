<?php

namespace App\Livewire\Pages\Panel\Expert\Profile;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\RefreshesFileInputs;
use App\Services\Media\DeferredImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;
    use RefreshesFileInputs;

    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $avatar;
    public $new_avatar;
    public $national_code;
    public $address;
    public $last_login;
    protected DeferredImageUploadService $deferredUploader;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'new_avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:800',
        'national_code' => 'nullable|string',
        'address' => 'nullable|string|max:255',
    ];

    public function boot(DeferredImageUploadService $deferredUploader): void
    {
        $this->deferredUploader = $deferredUploader;
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->avatar = $user->avatar;
        $this->national_code = $user->national_code;
        $this->address = $user->address;
        $this->last_login = $user->last_login;
    }

    public function getAvatarUrlProperty(): string
    {
        return $this->avatar
            ? Storage::disk('myimage')->url(ltrim((string) $this->avatar, '/'))
            : asset('assets/panel/assets/img/avatars/unknow.jpg');
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    private function normalizeNationalCode(): void
    {
        $this->national_code = $this->nullableString($this->national_code);
    }

    private function nullableString($value): ?string
    {
        $normalized = is_string($value) ? trim($value) : null;

        return $normalized !== '' ? $normalized : null;
    }

    public function save(): void
    {
        $this->normalizeNationalCode();
        $this->validate();

        $user = Auth::user();
        $oldAvatarPath = $user->avatar;
        $newAvatarPath = null;

        try {
            if ($this->new_avatar) {
                $newAvatarPath = $this->deferredUploader->store(
                    $this->new_avatar,
                    'avatars/user-' . $user->id . '-' . Str::uuid() . '.webp',
                    'myimage',
                    ['quality' => 40, 'max_width' => 512, 'max_height' => 512]
                );

                $user->avatar = $newAvatarPath;
            }

            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'national_code' => $this->national_code,
                'address' => $this->address,
                'avatar' => $user->avatar,
            ]);
        } catch (\Throwable $exception) {
            if ($newAvatarPath && Storage::disk('myimage')->exists($newAvatarPath)) {
                Storage::disk('myimage')->delete($newAvatarPath);
            }

            $this->toast('error', 'Profile update failed: ' . $exception->getMessage(), false);

            return;
        }

        if ($newAvatarPath && $oldAvatarPath && $oldAvatarPath !== $newAvatarPath && Storage::disk('myimage')->exists($oldAvatarPath)) {
            Storage::disk('myimage')->delete($oldAvatarPath);
        }

        $user = $user->fresh();
        Auth::setUser($user);

        $this->avatar = $user->avatar;
        $this->new_avatar = null;
        $this->refreshFileInputs();

        $this->toast('success', 'Profile updated successfully.');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        $avatarPath = $user->avatar;

        if (! $avatarPath) {
            return;
        }

        $user->update(['avatar' => null]);

        if (Storage::disk('myimage')->exists($avatarPath)) {
            Storage::disk('myimage')->delete($avatarPath);
        }

        $user = $user->fresh();
        Auth::setUser($user);

        $this->avatar = null;
        $this->new_avatar = null;
        $this->refreshFileInputs();

        $this->toast('success', 'Avatar removed successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.profile.profile');
    }
}

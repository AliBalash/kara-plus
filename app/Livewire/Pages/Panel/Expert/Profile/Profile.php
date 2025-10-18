<?php

namespace App\Livewire\Pages\Panel\Expert\Profile;

use App\Services\Media\OptimizedUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $avatar;
    public $new_avatar;
    public $national_code;
    public $address;
    public $last_login;
    protected OptimizedUploadService $imageUploader;

    protected $rules = [
        'first_name'    => 'required|string|max:255',
        'last_name'     => 'required|string|max:255',
        'email'         => 'required|email|max:255',
        'phone'         => 'nullable|string|max:20',
        'new_avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:800',
        'national_code' => 'nullable|string|max:10',
        'address'       => 'nullable|string|max:255',
    ];

    public function boot(OptimizedUploadService $imageUploader): void
    {
        $this->imageUploader = $imageUploader;
    }

    public function mount()
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

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        if ($this->new_avatar) {


            if ($user->avatar && Storage::disk('myimage')->exists($user->avatar)) {
                Storage::disk('myimage')->delete($user->avatar);
            }

            // آپلود عکس جدید
            $avatarPath = $this->imageUploader->store(
                $this->new_avatar,
                'avatars/user_' . $user->id . '_' . time() . '.webp',
                'myimage',
                ['quality' => 40, 'max_width' => 512, 'max_height' => 512]
            );
            $user->avatar = $avatarPath; // اصلاح مسیر ذخیره‌سازی
        }

        // بروزرسانی اطلاعات کاربر
        $user->update([
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'national_code' => $this->national_code,
            'address'       => $this->address,
            'avatar'        => $user->avatar,
        ]);

        session()->flash('message', 'Profile updated successfully.');
    }




    public function render()
    {
        return view('livewire.pages.panel.expert.profile.profile');
    }
}

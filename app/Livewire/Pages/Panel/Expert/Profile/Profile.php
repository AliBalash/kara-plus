<?php

namespace App\Livewire\Pages\Panel\Expert\Profile;

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

    protected $rules = [
        'first_name'    => 'required|string|max:255',
        'last_name'     => 'required|string|max:255',
        'email'         => 'required|email|max:255',
        'phone'         => 'nullable|string|max:20',
        'new_avatar'    => 'nullable|image|max:800',
        'national_code' => 'nullable|string|max:10',
        'address'       => 'nullable|string|max:255',
    ];

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


            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // آپلود عکس جدید
            $avatarPath = $this->new_avatar->store('avatars', 'public');
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

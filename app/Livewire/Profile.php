<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class Profile extends Component
{
    use Toast;

    public string $current_password = '';

    public string $password = '';

    public function ubahPassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8'],
        ]);

        $user = Auth::user();
        $user->password = $this->password; // auto-hash via cast 'hashed'
        $user->save();

        $this->reset(['current_password', 'password']);
        $this->success('Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.profile')->title('Profil Saya');
    }
}

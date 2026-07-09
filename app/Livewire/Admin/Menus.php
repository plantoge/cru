<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use Livewire\Component;
use Mary\Traits\Toast;

class Menus extends Component
{
    use Toast;

    public bool $modal = false;

    public ?string $editId = null;

    public string $nama = '';

    public string $slug = '';

    public string $route = '';

    public string $icon = '';

    public ?string $parent_id = null;

    public int $urutan = 0;

    public bool $aktif = true;

    public function buka(?string $id = null)
    {
        $this->resetErrorBag();
        $this->editId = $id;

        if ($id) {
            $m = Menu::findOrFail($id);
            $this->fill($m->only('nama', 'slug', 'route', 'icon', 'parent_id', 'urutan', 'aktif'));
            $this->route = $m->route ?? '';
            $this->icon = $m->icon ?? '';
        } else {
            $this->reset('nama', 'slug', 'route', 'icon', 'parent_id');
            $this->urutan = ((int) Menu::max('urutan')) + 10;
            $this->aktif = true;
        }

        $this->modal = true;
    }

    public function simpan()
    {
        $data = $this->validate([
            'nama' => 'required|string|max:100',
            'slug' => 'required|alpha_dash|max:60|unique:menus,slug,'.($this->editId ?? 'NULL').',id',
            'route' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:60',
            'parent_id' => 'nullable|uuid',
            'urutan' => 'integer|min:0',
            'aktif' => 'boolean',
        ]);

        // Observer MenuObserver menyinkronkan permission {slug}.{aksi}
        Menu::updateOrCreate(['id' => $this->editId], $data);

        $this->modal = false;
        $this->success('Menu tersimpan; permission tersinkron.');
    }

    public function hapus(string $id)
    {
        abort_unless(auth()->user()->can('menus.delete'), 403);

        Menu::findOrFail($id)->delete(); // observer hapus permission terkait
        $this->success('Menu & permission terkait dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.menus', [
            'menus' => Menu::with('parent')->orderBy('urutan')->get(),
        ])->title('Manajemen Menu');
    }
}

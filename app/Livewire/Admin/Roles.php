<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Roles extends Component
{
    use Toast;

    public string $role = '';

    /** matrix[slug][aksi] = bool */
    public array $matrix = [];

    public function mount()
    {
        $this->role = Role::orderBy('name')->value('name') ?? '';
        $this->muatMatrix();
    }

    public function updatedRole()
    {
        $this->muatMatrix();
    }

    protected function muatMatrix(): void
    {
        if (! $this->role) {
            return;
        }

        $punya = Role::findByName($this->role)->permissions->pluck('name')->flip();

        $this->matrix = [];
        foreach (Menu::orderBy('urutan')->get() as $menu) {
            foreach (Menu::AKSI as $aksi) {
                $this->matrix[$menu->slug][$aksi] = $punya->has("{$menu->slug}.{$aksi}");
            }
        }
    }

    public function centangSemua(string $slug)
    {
        $semua = ! collect($this->matrix[$slug])->every(fn ($v) => $v);
        foreach (Menu::AKSI as $aksi) {
            $this->matrix[$slug][$aksi] = $semua;
        }
    }

    public function simpan()
    {
        abort_unless(auth()->user()->can('roles.update'), 403);

        $permissions = [];
        foreach ($this->matrix as $slug => $aksiMap) {
            foreach ($aksiMap as $aksi => $ya) {
                if ($ya) {
                    $permissions[] = "{$slug}.{$aksi}";
                }
            }
        }

        Role::findByName($this->role)->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->success("Hak akses role \"{$this->role}\" tersimpan.");
    }

    public function render()
    {
        return view('livewire.admin.roles', [
            'semuaRole' => Role::orderBy('name')->pluck('name'),
            'menus' => Menu::orderBy('urutan')->get(),
            'aksi' => Menu::AKSI,
        ])->title('Role & Permission');
    }
}

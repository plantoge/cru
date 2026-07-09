<?php

namespace App\Services;

use App\Models\Menu;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sinkronisasi menu dinamis → permission spatie (prd §5.1):
 * create menu = buat {slug}.read|create|update|delete,
 * rename slug = ganti nama permission, hapus menu = hapus permission.
 */
class MenuPermissionSync
{
    public function created(Menu $menu): void
    {
        foreach ($menu->permissionNames() as $name) {
            Permission::findOrCreate($name);
        }

        $this->flushCache();
    }

    public function slugRenamed(string $slugLama, Menu $menu): void
    {
        foreach (Menu::AKSI as $aksi) {
            Permission::query()
                ->where('name', "{$slugLama}.{$aksi}")
                ->update(['name' => "{$menu->slug}.{$aksi}"]);
        }

        // Pastikan lengkap bila permission lama tidak ada
        $this->created($menu);
    }

    public function deleted(Menu $menu): void
    {
        Permission::query()->whereIn('name', $menu->permissionNames())->delete();

        $this->flushCache();
    }

    protected function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

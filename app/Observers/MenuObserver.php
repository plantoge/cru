<?php

namespace App\Observers;

use App\Models\Menu;
use App\Services\MenuPermissionSync;

class MenuObserver
{
    public function __construct(protected MenuPermissionSync $sync) {}

    public function created(Menu $menu): void
    {
        $this->sync->created($menu);
    }

    public function updated(Menu $menu): void
    {
        if ($menu->wasChanged('slug')) {
            $this->sync->slugRenamed($menu->getOriginal('slug'), $menu);
        }
    }

    public function deleted(Menu $menu): void
    {
        $this->sync->deleted($menu);
    }
}

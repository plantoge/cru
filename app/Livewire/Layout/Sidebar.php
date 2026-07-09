<?php

namespace App\Livewire\Layout;

use App\Models\Menu;
use Illuminate\Support\Collection;
use Livewire\Component;

class Sidebar extends Component
{
    /** Menu aktif yang boleh dilihat user ({slug}.read), hierarkis (prd §5.3). */
    public function menus(): Collection
    {
        $user = auth()->user();

        return Menu::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get()
            ->filter(fn (Menu $m) => $user->can("{$m->slug}.read"))
            ->values();
    }

    public function render()
    {
        $all = $this->menus();

        return view('livewire.layout.sidebar', [
            'roots' => $all->whereNull('parent_id'),
            'children' => $all->whereNotNull('parent_id')->groupBy('parent_id'),
        ]);
    }
}

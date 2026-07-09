<x-mary-menu activate-by-route>
    @foreach ($roots as $menu)
        @if ($children->has($menu->id))
            <x-mary-menu-sub :title="$menu->nama" :icon="$menu->icon ?? 'o-folder'">
                @foreach ($children[$menu->id] as $child)
                    <x-mary-menu-item
                        :title="$child->nama"
                        :icon="$child->icon ?? 'o-minus-small'"
                        :link="$child->route && \Illuminate\Support\Facades\Route::has($child->route) ? route($child->route) : '#'" />
                @endforeach
            </x-mary-menu-sub>
        @else
            <x-mary-menu-item
                :title="$menu->nama"
                :icon="$menu->icon ?? 'o-minus-small'"
                :link="$menu->route && \Illuminate\Support\Facades\Route::has($menu->route) ? route($menu->route) : '#'" />
        @endif
    @endforeach
</x-mary-menu>

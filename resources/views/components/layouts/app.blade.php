<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50">

<x-mary-nav sticky full-width>
    <x-slot:brand>
        <label for="main-drawer" class="lg:hidden mr-3">
            <x-mary-icon name="o-bars-3" class="cursor-pointer" />
        </label>
        <div class="font-bold text-lg">eProposal <span class="text-primary">RSPI</span></div>
    </x-slot:brand>
    <x-slot:actions>
        @auth
            <x-mary-dropdown right>
                <x-slot:trigger>
                    <x-mary-button :label="auth()->user()->name" icon="o-user-circle" class="btn-ghost btn-sm" />
                </x-slot:trigger>
                <x-mary-menu-item title="{{ auth()->user()->getRoleNames()->implode(', ') }}" icon="o-identification" />
                <x-mary-menu-item title="Keluar" icon="o-arrow-right-start-on-rectangle"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />
            </x-mary-dropdown>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        @endauth
    </x-slot:actions>
</x-mary-nav>

<x-mary-main full-width with-nav>
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100">
        <livewire:layout.sidebar />
    </x-slot:sidebar>
    <x-slot:content>
        {{ $slot }}
    </x-slot:content>
</x-mary-main>

<x-mary-toast />
</body>
</html>

<div>
    <x-mary-card title="Diskusi" subtitle="Real-time — hanya Anda & petugas yang menangani proposal ini" shadow>
        <div id="chat-scroll-{{ $proposal->id }}" x-data
            x-on:pesan-terkirim.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
            class="max-h-80 overflow-y-auto space-y-2 mb-3 pr-1">
            @forelse ($riwayat as $pesan)
                @php $milikSaya = $pesan['senderId'] === auth()->id(); @endphp
                <div class="chat {{ $milikSaya ? 'chat-end' : 'chat-start' }}">
                    <div class="chat-header text-xs opacity-60">
                        {{ $milikSaya ? 'Anda' : $pesan['pengirim'] }}
                        <time class="ml-1">{{ $pesan['dibuat'] }}</time>
                    </div>
                    <div class="chat-bubble {{ $milikSaya ? 'chat-bubble-primary' : '' }}">{{ $pesan['pesan'] }}</div>
                </div>
            @empty
                <div class="text-center text-sm opacity-50 py-6">Belum ada pesan. Mulai diskusi di bawah.</div>
            @endforelse
        </div>

        <form wire:submit="kirim" class="flex gap-2">
            <input type="text" wire:model="pesan" placeholder="Tulis pesan..."
                class="input input-bordered w-full @error('pesan') input-error @enderror" maxlength="2000">
            <x-mary-button type="submit" icon="o-paper-airplane" class="btn-primary" spinner="kirim" />
        </form>
        @error('pesan')<div class="text-error text-sm mt-1">{{ $message }}</div>@enderror
    </x-mary-card>
</div>

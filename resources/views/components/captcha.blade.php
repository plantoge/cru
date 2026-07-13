@props(['question', 'answerField' => 'captchaAnswer', 'refreshMethod' => 'regenerateCaptcha'])

<div>
    <label class="text-sm font-medium mb-1 block">Verifikasi: berapa hasil <span class="font-mono">{{ $question }}</span> ?</label>
    <div class="join w-full">
        <input type="number" wire:model="{{ $answerField }}" inputmode="numeric" placeholder="Jawaban"
            class="input join-item w-full @error($answerField) input-error @enderror" required>
        <button type="button" wire:click="{{ $refreshMethod }}" class="btn join-item btn-square" title="Ganti soal">
            <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
        </button>
    </div>
    @error($answerField)<div class="text-error text-sm mt-1">{{ $message }}</div>@enderror
</div>

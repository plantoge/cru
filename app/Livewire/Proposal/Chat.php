<?php

namespace App\Livewire\Proposal;

use App\Events\ProposalMessageSent;
use App\Models\Proposal;
use App\Models\ProposalMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Chat extends Component
{
    public Proposal $proposal;

    public string $pesan = '';

    /** @var array<int, array{id: string, pesan: string, senderId: string, pengirim: string, dibuat: string}> */
    public array $riwayat = [];

    public function mount(Proposal $proposal)
    {
        // Assign dulu sebelum abort — kalau exception dilempar duluan,
        // proses internal Livewire yang butuh akses $this->proposal
        // (mis. saat menangani error) bakal nemu property masih kosong.
        $this->proposal = $proposal;

        abort_unless($proposal->bisaChat(Auth::user()), 403);

        $this->riwayat = $proposal->messages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn (ProposalMessage $m) => [
                'id' => $m->id,
                'pesan' => $m->pesan,
                'senderId' => $m->sender_id,
                'pengirim' => $m->sender?->name ?? 'Pengguna',
                'dibuat' => $m->created_at->format('H:i'),
            ])
            ->values()
            ->all();
    }

    public function kirim()
    {
        abort_unless($this->proposal->bisaChat(Auth::user()), 403);

        $this->validate(['pesan' => 'required|string|max:2000']);

        $user = Auth::user();
        $unit = $this->proposal->user_id === $user->id ? null : $this->proposal->unit_sekarang;

        $message = ProposalMessage::create([
            'proposal_id' => $this->proposal->id,
            'sender_id' => $user->id,
            'sender_unit' => $unit?->value,
            'pesan' => $this->pesan,
        ]);
        $message->load('sender:id,name');

        $this->riwayat[] = [
            'id' => $message->id,
            'pesan' => $message->pesan,
            'senderId' => $message->sender_id,
            'pengirim' => $message->sender->name,
            'dibuat' => $message->created_at->format('H:i'),
        ];

        $this->pesan = '';
        $this->dispatch('pesan-terkirim');

        // toOthers(): pengirim sendiri sudah lihat optimistic update di atas,
        // gak perlu nerima broadcast balik buat pesannya sendiri.
        broadcast(new ProposalMessageSent($message))->toOthers();
    }

    #[On('echo-private:proposal.{proposal.id},ProposalMessageSent')]
    public function pesanMasuk(array $data)
    {
        // Cegah duplikat kalau event nyasar balik ke pengirim sendiri.
        if (collect($this->riwayat)->contains('id', $data['id'])) {
            return;
        }

        $this->riwayat[] = $data;
        $this->dispatch('pesan-terkirim');
    }

    public function render()
    {
        return view('livewire.proposal.chat');
    }
}

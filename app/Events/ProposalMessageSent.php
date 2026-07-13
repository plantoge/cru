<?php

namespace App\Events;

use App\Models\ProposalMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * ShouldBroadcastNow (bukan ShouldBroadcast) — kirim langsung synchronous,
 * tidak butuh queue worker tambahan (project ini belum punya satu pun
 * queue worker berjalan; lihat prd-chat-reverb.md §4.3).
 */
class ProposalMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public ProposalMessage $message) {}

    /** @return array<Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("proposal.{$this->message->proposal_id}")];
    }

    public function broadcastAs(): string
    {
        return 'ProposalMessageSent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'pesan' => $this->message->pesan,
            'senderId' => $this->message->sender_id,
            'pengirim' => $this->message->sender->name,
            'dibuat' => $this->message->created_at->format('H:i'),
        ];
    }
}

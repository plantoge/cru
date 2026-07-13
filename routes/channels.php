<?php

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Channel privat per proposal — otorisasi via Proposal::bisaChat(), SAMA
 * aturan yang dipakai komponen chat (prd-chat-reverb.md §2). Reviewer
 * sengaja TIDAK termasuk (kerahasiaan identitas reviewer).
 */
Broadcast::channel('proposal.{proposalId}', function (User $user, string $proposalId) {
    $proposal = Proposal::find($proposalId);

    return $proposal && $proposal->bisaChat($user);
});

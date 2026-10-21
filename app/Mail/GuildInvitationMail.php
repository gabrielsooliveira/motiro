<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuildInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $inviteUrl;

    public function __construct(
        public Team $guild,
        public TeamInvitation $invitation,
        public ?User $inviter = null,
        public string $plainToken = ''
    ) {
        $token = $plainToken ?: $invitation->token;
        $this->inviteUrl = url("/convites/{$token}");
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Convite para ingressar na guilda {$this->guild->name} — Motirõ",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guild-invitation',
            text: 'emails.guild-invitation-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

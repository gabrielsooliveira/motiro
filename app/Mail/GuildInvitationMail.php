<?php

namespace App\Mail;

use App\Models\Guild;
use App\Models\GuildInvitation;
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
        public Guild $guild,
        public GuildInvitation $invitation,
        public ?User $inviter = null,
        public string $plainToken = ''
    ) {
        $token = $plainToken ?: $invitation->token;
        $this->inviteUrl = url("/convites/{$token}");
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Convite para ingressar na guild {$this->guild->name} — Motirõ",
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

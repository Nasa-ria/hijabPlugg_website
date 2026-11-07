<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a newly added admin with their sign-in details and a temporary password.
 */
class AdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $admin, public string $temporaryPassword, public ?User $invitedBy = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You\'ve been added as a Nasaari Store admin');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-invite', with: [
            'admin' => $this->admin,
            'temporaryPassword' => $this->temporaryPassword,
            'invitedBy' => $this->invitedBy,
            'loginUrl' => route('admin.login'),
            'areas' => $this->admin->is_super_admin
                ? ['Everything (super admin)']
                : collect($this->admin->admin_permissions ?? [])->map(fn ($key) => User::ADMIN_SECTIONS[$key][0] ?? $key)->values()->all(),
        ]);
    }
}

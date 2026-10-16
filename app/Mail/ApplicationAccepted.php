<?php

namespace App\Mail;

use App\Models\User;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $applicant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: Brand::name().' — application accepted',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-accepted',
            with: [
                'appName' => Brand::name(),
                'logoUrl' => Brand::logoUrl(),
                'applicant' => $this->applicant,
                'portalUrl' => url('/admin/login'),
            ],
        );
    }
}

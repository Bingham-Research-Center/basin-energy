<?php

namespace App\Mail;

use App\Models\BroadcastNewsletter;
use App\Models\BroadcastNewsletterDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class MonthlyNewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;
    public $unsubscribeUrl;

    public function __construct(BroadcastNewsletter $newsletter, BroadcastNewsletterDelivery $delivery)
    {
        $this->newsletter = $newsletter;
        $this->unsubscribeUrl = URL::temporarySignedRoute(
            'frontend.newsletter.unsubscribe.email',
            now()->addYear(),
            ['user' => $delivery->user_id]
        );
    }

    public function build()
    {
        return $this->subject('BasinEnergy Monthly Newsletter: '.$this->newsletter->title)
            ->view('mail.broadcast.newsletter');
    }
}

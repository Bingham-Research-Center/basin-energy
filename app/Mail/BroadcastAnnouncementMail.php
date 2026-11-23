<?php

namespace App\Mail;

use App\Models\BroadcastAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BroadcastAnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $announcement;

    public function __construct(BroadcastAnnouncement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function build()
    {
        return $this->subject('BasinEnergy Announcement: '.$this->announcement->title)
            ->view('mail.broadcast.announcement');
    }
}

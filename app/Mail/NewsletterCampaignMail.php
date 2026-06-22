<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public NewsletterCampaign $campaign;
    public ?Subscriber $subscriber;
    public string $unsubscribeUrl;

    public function __construct(NewsletterCampaign $campaign, ?Subscriber $subscriber = null)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->unsubscribeUrl = $subscriber ? route('newsletter.unsubscribe', $subscriber->token) : '#';
    }

    public function build()
    {
        return $this->subject($this->campaign->subject)
            ->view('emails.newsletter')
            ->with([
                'campaign' => $this->campaign,
                'subscriber' => $this->subscriber,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}

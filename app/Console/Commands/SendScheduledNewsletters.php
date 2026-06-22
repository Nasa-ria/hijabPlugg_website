<?php

namespace App\Console\Commands;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledNewsletters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled newsletter campaigns when their send time arrives.';

    public function handle(): int
    {
        $campaigns = NewsletterCampaign::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled newsletters to send.');
            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $subscribers = Subscriber::whereNull('unsubscribed_at')->get();

            foreach ($subscribers as $subscriber) {
                Mail::to($subscriber->email)->queue(new NewsletterCampaignMail($campaign, $subscriber));
            }

            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->info("Sent campaign {$campaign->id}: {$campaign->title}");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterCampaign::query();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('subject', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $newsletters = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.newsletters.index', compact('newsletters'));
    }

    public function subscribers(Request $request)
    {
        $query = Subscriber::query();

        if ($request->filled('search')) {
            $query->where('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('unsubscribed_at');
            } elseif ($request->status === 'unsubscribed') {
                $query->whereNotNull('unsubscribed_at');
            }
        }

        $subscribers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.newsletters.subscribers', compact('subscribers'));
    }

    public function create()
    {
        return view('admin.newsletters.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'preheader' => 'nullable|string|max:255',
            'hero_text' => 'nullable|string|max:1000',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url|max:255',
            'hero_image' => 'nullable|image|max:2048',
            'schedule_at' => 'nullable|date',
            'section_title' => 'nullable|array',
            'section_title.*' => 'nullable|string|max:191',
            'section_body.*' => 'nullable|string|max:1000',
            'section_button_text.*' => 'nullable|string|max:100',
            'section_button_url.*' => 'nullable|url|max:255',
            'section_image.*' => 'nullable|image|max:2048',
        ]);

        $content = [];
        if ($request->filled('section_title')) {
            foreach ($request->section_title as $index => $title) {
                if (! $title && ! $request->section_body[$index]) {
                    continue;
                }
                $imagePath = null;
                if ($request->hasFile("section_image.$index")) {
                    $imagePath = $request->file("section_image.$index")->store('newsletter-images', 'public');
                }

                $content[] = [
                    'title' => $title,
                    'body' => $request->section_body[$index] ?? null,
                    'button_text' => $request->section_button_text[$index] ?? null,
                    'button_url' => $request->section_button_url[$index] ?? null,
                    'image_path' => $imagePath,
                ];
            }
        }

        if ($request->hasFile('hero_image')) {
            $data['hero_image_path'] = $request->file('hero_image')->store('newsletter-images', 'public');
        }

        if ($request->filled('schedule_at')) {
            $scheduledAt = Carbon::parse($request->schedule_at);
            $data['scheduled_at'] = $scheduledAt;
            $data['status'] = $scheduledAt->isFuture() ? 'scheduled' : 'draft';
        } else {
            $data['scheduled_at'] = null;
            $data['status'] = 'draft';
        }

        $data['content'] = $content;

        NewsletterCampaign::create($data);

        return redirect()->route('admin.newsletters.index')->with('success', 'Newsletter campaign saved successfully.');
    }

    public function edit(NewsletterCampaign $newsletter)
    {
        return view('admin.newsletters.edit', compact('newsletter'));
    }

    public function update(Request $request, NewsletterCampaign $newsletter)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'preheader' => 'nullable|string|max:255',
            'hero_text' => 'nullable|string|max:1000',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url|max:255',
            'hero_image' => 'nullable|image|max:2048',
            'schedule_at' => 'nullable|date',
            'section_title' => 'nullable|array',
            'section_title.*' => 'nullable|string|max:191',
            'section_body.*' => 'nullable|string|max:1000',
            'section_button_text.*' => 'nullable|string|max:100',
            'section_button_url.*' => 'nullable|url|max:255',
            'section_image.*' => 'nullable|image|max:2048',
        ]);

        $content = [];
        if ($request->filled('section_title')) {
            foreach ($request->section_title as $index => $title) {
                if (! $title && ! $request->section_body[$index]) {
                    continue;
                }
                $imagePath = $request->input("existing_section_image.$index");
                if ($request->hasFile("section_image.$index")) {
                    $imagePath = $request->file("section_image.$index")->store('newsletter-images', 'public');
                }

                $content[] = [
                    'title' => $title,
                    'body' => $request->section_body[$index] ?? null,
                    'button_text' => $request->section_button_text[$index] ?? null,
                    'button_url' => $request->section_button_url[$index] ?? null,
                    'image_path' => $imagePath,
                ];
            }
        }

        if ($request->hasFile('hero_image')) {
            $data['hero_image_path'] = $request->file('hero_image')->store('newsletter-images', 'public');
        }

        if ($request->filled('schedule_at')) {
            $scheduledAt = Carbon::parse($request->schedule_at);
            $data['scheduled_at'] = $scheduledAt;
            $data['status'] = $scheduledAt->isFuture() ? 'scheduled' : 'draft';
        } else {
            $data['scheduled_at'] = null;
            $data['status'] = 'draft';
        }

        $data['content'] = $content;
        $newsletter->update($data);

        return redirect()->route('admin.newsletters.index')->with('success', 'Newsletter campaign updated successfully.');
    }

    public function destroy(NewsletterCampaign $newsletter)
    {
        $newsletter->delete();

        return redirect()->route('admin.newsletters.index')->with('success', 'Newsletter campaign deleted successfully.');
    }

    public function preview(NewsletterCampaign $newsletter)
    {
        return view('admin.newsletters.preview', compact('newsletter'));
    }

    public function send(Request $request, NewsletterCampaign $newsletter)
    {
        $subscribers = Subscriber::whereNull('unsubscribed_at')->orderByDesc('created_at')->get();
        $shouldSendImmediately = app()->environment('local') || config('queue.default') === 'sync';

        foreach ($subscribers as $subscriber) {
            $message = new NewsletterCampaignMail($newsletter, $subscriber);

            if ($shouldSendImmediately) {
                Mail::to($subscriber->email)->send($message);
            } else {
                Mail::to($subscriber->email)->queue($message);
            }
        }

        $newsletter->update(['status' => 'sent', 'sent_at' => now()]);

        return redirect()->route('admin.newsletters.index')->with('success', 'Newsletter is being sent to all active subscribers.');
    }

    public function sendTest(Request $request, NewsletterCampaign $newsletter)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $message = new NewsletterCampaignMail($newsletter, null);

        if (app()->environment('local') || config('queue.default') === 'sync') {
            Mail::to($request->test_email)->send($message);
        } else {
            Mail::to($request->test_email)->queue($message);
        }

        return back()->with('success', 'Test email sent successfully.');
    }
}

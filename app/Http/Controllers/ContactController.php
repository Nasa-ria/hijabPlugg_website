<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    /**
     * Save a message or feedback from the Contact page, then let the store know by email.
     */
    public function store(Request $request)
    {
        // Honeypot: real visitors never see or fill this field
        if ($request->filled('website')) {
            return redirect()->route('pages.contact')->with('success', 'Thank you! Your message has been sent.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:40',
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'order_number' => 'nullable|string|max:60',
            'rating' => 'nullable|integer|min:1|max:5|required_if:topic,feedback',
            'message' => 'required|string|min:5|max:3000',
        ], [
            'rating.required_if' => 'Please choose a star rating for your feedback.',
        ]);

        if ($data['topic'] !== 'feedback') {
            $data['rating'] = null;
        }

        $message = ContactMessage::create($data);

        // Email the store a copy (best effort: the message is already saved in the admin)
        $to = config('nasaari.contact.email');
        if ($to) {
            try {
                Mail::raw($this->summary($message), function ($mail) use ($to, $message) {
                    $mail->to($to)
                        ->replyTo($message->email, $message->name)
                        ->subject('[Nasaari] ' . $message->topic_label . ' from ' . $message->name);
                });
            } catch (\Throwable $e) {
                Log::warning('Contact message email failed: ' . $e->getMessage(), ['contact_message' => $message->id]);
            }
        }

        $thanks = $data['topic'] === 'feedback'
            ? 'Thank you for your feedback! It helps us make Nasaari better.'
            : 'Thank you! We\'ve received your message and will reply within one working day.';

        return redirect()->route('pages.contact')->with('success', $thanks);
    }

    private function summary(ContactMessage $m): string
    {
        return implode("\n", array_filter([
            'Topic: ' . $m->topic_label,
            $m->rating ? 'Rating: ' . str_repeat('★', $m->rating) . str_repeat('☆', 5 - $m->rating) : null,
            'From: ' . $m->name . ' <' . $m->email . '>',
            $m->phone ? 'Phone: ' . $m->phone : null,
            $m->order_number ? 'Order: ' . $m->order_number : null,
            '',
            $m->message,
            '',
            'Reply to this email to answer, or view it in Admin → Messages: ' . route('admin.messages.show', $m),
        ], fn ($line) => $line !== null));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscriber = Subscriber::firstOrNew(['email' => $request->email]);

        if ($subscriber->exists && ! $subscriber->isUnsubscribed()) {
            return back()->with('error', 'You are already subscribed to our newsletter.');
        }

        $subscriber->token = $subscriber->token ?: Str::random(64);
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return back()->with('success', 'Thanks for subscribing! You will receive our next newsletter.');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return redirect()->route('home')->with('error', 'We could not find your subscription.');
        }

        if ($subscriber->isUnsubscribed()) {
            return redirect()->route('home')->with('success', 'You have already been unsubscribed.');
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect()->route('home')->with('success', 'You have been unsubscribed from the newsletter.');
    }
}

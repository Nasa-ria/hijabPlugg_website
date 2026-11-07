<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Inbox for messages and feedback sent from the Contact page.
 */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'topic' => ['nullable', Rule::in(array_keys(ContactMessage::TOPICS))],
            'unread' => 'nullable|boolean',
        ]);

        $messages = ContactMessage::query()
            ->when($request->topic, fn ($q, $t) => $q->where('topic', $t))
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $averageRating = ContactMessage::where('topic', 'feedback')->whereNotNull('rating')->avg('rating');

        return view('admin.messages.index', compact('messages', 'averageRating'));
    }

    public function show(ContactMessage $message)
    {
        if ($message->isUnread()) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', compact('message'));
    }

    public function markUnread(ContactMessage $message)
    {
        $message->update(['read_at' => null]);

        return redirect()->route('admin.messages.index')->with('success', 'Marked as unread.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}

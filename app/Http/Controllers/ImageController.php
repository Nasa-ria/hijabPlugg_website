<?php

namespace App\Http\Controllers;

use App\Support\DataSaver;
use App\Support\Img;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    /**
     * Make (once) and send a smaller WebP copy of a stored photo.
     * Later requests for the same size are served as a static file (see Img::url).
     */
    public function resize(int $width, string $path)
    {
        abort_unless(in_array($width, Img::WIDTHS, true) && Img::resizable($path), 404);

        try {
            $resized = Img::make($path, $width);
        } catch (\RuntimeException) {
            abort(404); // missing file or not an image
        } catch (\Throwable $e) {
            // The server can't resize (e.g. GD extension missing): send the original instead of a broken image
            report($e);

            return redirect(Img::original($path), 302);
        }

        // Cloud bucket (Supabase in production): send the browser to the copy there
        if (config('filesystems.disks.public.driver') !== 'local') {
            return redirect(Storage::disk('public')->url($resized), 302)
                ->header('Cache-Control', 'public, max-age=86400');
        }

        return response()->file(Storage::disk('public')->path($resized), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * The data-saver switch in the footer: on, off, or automatic.
     */
    public function dataSaver(Request $request)
    {
        $mode = $request->validate(['mode' => ['required', Rule::in(['on', 'off', 'auto'])]])['mode'];

        $cookie = $mode === 'auto'
            ? cookie()->forget(DataSaver::COOKIE)
            : cookie(DataSaver::COOKIE, $mode === 'on' ? '1' : '0', 60 * 24 * 365, '/', null, $request->isSecure(), false, false, 'lax');

        $message = match ($mode) {
            'on' => 'Data saver is on: smaller photos and fewer extras.',
            'off' => 'Data saver is off: full-quality photos.',
            default => 'Data saver will switch on automatically on slow connections.',
        };

        return back()->with('success', $message)->withCookie($cookie);
    }
}

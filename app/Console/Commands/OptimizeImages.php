<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Img;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Makes the smaller WebP copies of every product and blog photo ahead of time,
 * so no customer waits for one to be generated. Safe to run any time; existing copies are kept.
 *   php artisan images:optimize          make missing copies
 *   php artisan images:optimize --prune  also delete copies whose original photo is gone
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--prune : Delete resized copies of photos that no longer exist}';

    protected $description = 'Create compressed WebP copies of product and blog photos for slow connections';

    public function handle(): int
    {
        $paths = collect()
            ->merge(Product::withTrashed()->pluck('main_image'))
            ->merge(ProductVariant::pluck('image'))
            ->merge(ProductImage::pluck('image_path'))
            ->merge(class_exists(Post::class) ? Post::whereNotNull('cover_image')->pluck('cover_image') : [])
            ->filter()->unique()->filter(fn ($p) => Img::resizable($p))->values();

        $made = $failed = 0;
        $bar = $this->output->createProgressBar($paths->count() * count(Img::WIDTHS));

        foreach ($paths as $path) {
            foreach (Img::WIDTHS as $width) {
                try {
                    $existed = Storage::disk('public')->exists(Img::resizedPath($path, $width));
                    Img::make($path, $width);
                    $made += $existed ? 0 : 1;
                } catch (\RuntimeException) {
                    $failed++;
                }
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine();

        $before = $after = 0;
        foreach ($paths as $path) {
            $before += Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : 0;
            $after += Storage::disk('public')->exists(Img::resizedPath($path, 480)) ? Storage::disk('public')->size(Img::resizedPath($path, 480)) : 0;
        }

        $this->info("{$paths->count()} photos: {$made} new copies made" . ($failed ? ", {$failed} could not be read" : '') . '.');
        if ($before > 0) {
            $this->info(sprintf('A typical product card now loads %s instead of %s (%d%% smaller).',
                $this->kb($after / max(1, $paths->count())), $this->kb($before / max(1, $paths->count())), 100 - round($after / $before * 100)));
        }

        if ($this->option('prune')) {
            $keep = $paths->flatMap(fn ($p) => collect(Img::WIDTHS)->map(fn ($w) => Img::resizedPath($p, $w)))->flip();
            $deleted = 0;
            foreach (Storage::disk('public')->allFiles('resized') as $file) {
                if (! isset($keep[$file])) {
                    Storage::disk('public')->delete($file);
                    $deleted++;
                }
            }
            $this->info("Removed {$deleted} unused resized copies.");
        }

        return self::SUCCESS;
    }

    private function kb(float $bytes): string
    {
        return number_format($bytes / 1024, 1) . ' KB';
    }
}

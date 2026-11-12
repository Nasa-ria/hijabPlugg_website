<?php

namespace Tests\Feature;

use App\Support\DataSaver;
use App\Support\Img;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class LowBandwidthTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Http::preventStrayRequests();
        $this->fakeRates();
        Storage::fake('public');
        DataSaver::forget();
    }

    private function photo(string $path = 'products/hijab.jpg', int $width = 2000, int $height = 2500): string
    {
        $file = UploadedFile::fake()->image('hijab.jpg', $width, $height);
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function test_photos_are_resized_to_webp_once_then_served_as_static_files(): void
    {
        $path = $this->photo();

        $url = Img::url($path, 480);
        $this->assertStringContainsString('/img/480/products/hijab.jpg', $url);

        $response = $this->get('/img/480/products/hijab.jpg')->assertOk();
        $this->assertSame('image/webp', $response->headers->get('content-type'));
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('cache-control'));

        Storage::disk('public')->assertExists('resized/480/products/hijab.webp');
        [$w, $h] = getimagesize(Storage::disk('public')->path('resized/480/products/hijab.webp'));
        $this->assertSame([480, 600], [$w, $h]); // aspect ratio kept

        // Now linked directly, without PHP
        $this->assertStringEndsWith('/storage/resized/480/products/hijab.webp', Img::url($path, 480));
    }

    public function test_small_photos_are_never_enlarged(): void
    {
        $path = $this->photo('products/small.jpg', 300, 300);

        $this->get('/img/960/products/small.jpg')->assertOk();

        [$w] = getimagesize(Storage::disk('public')->path('resized/960/products/small.webp'));
        $this->assertSame(300, $w);
    }

    public function test_resize_route_rejects_unsafe_or_unknown_requests(): void
    {
        $this->photo();

        $this->get('/img/500/products/hijab.jpg')->assertNotFound();      // width not allowed
        $this->get('/img/480/../.env')->assertNotFound();
        $this->get('/img/480/products/../../.env')->assertNotFound();
        $this->get('/img/480/products/missing.jpg')->assertNotFound();
        $this->get('/img/480/resized/480/products/hijab.webp')->assertNotFound(); // no resizing of copies
        Storage::disk('public')->put('products/notes.jpg', 'not an image');
        $this->get('/img/480/products/notes.jpg')->assertNotFound();
    }

    public function test_shop_sends_responsive_photos(): void
    {
        $this->product(['name' => 'Light Chiffon', 'main_image' => $this->photo()]);

        $this->get('/')->assertOk()
            ->assertSee('srcset="', false)
            ->assertSee('/img/320/products/hijab.jpg 320w', false)
            ->assertSee('/img/960/products/hijab.jpg 960w', false)
            ->assertDontSee('storage/products/hijab.jpg', false); // never the full-size original
    }

    public function test_save_data_header_switches_on_the_lite_layout(): void
    {
        $this->product(['name' => 'Light Chiffon', 'main_image' => $this->photo()]);

        $this->withHeader('Save-Data', 'on')->get('/')->assertOk()
            ->assertSee('class="scroll-smooth lite"', false)
            ->assertSee('/img/480/products/hijab.jpg 480w', false)
            ->assertDontSee('/img/720/products/hijab.jpg 720w', false) // smaller photos only
            ->assertDontSee('shop-hero__collage', false);             // decorative photos skipped
    }

    public function test_visitor_choice_overrides_automatic_detection(): void
    {
        $this->post(route('data-saver.update'), ['mode' => 'on'])->assertRedirect()->assertCookie(DataSaver::COOKIE, '1', false);
        $this->withUnencryptedCookie(DataSaver::COOKIE, '1')->get('/')->assertSee('class="scroll-smooth lite"', false);

        DataSaver::forget();
        $this->unencryptedCookies = [];
        $this->withUnencryptedCookie(DataSaver::COOKIE, '0')->withHeader('Save-Data', 'on')->get('/')
            ->assertDontSee('class="scroll-smooth lite"', false);

        DataSaver::forget();
        $this->unencryptedCookies = [];
        $this->withUnencryptedCookie(DataSaver::AUTO_COOKIE, '1')->get('/')->assertSee('class="scroll-smooth lite"', false);

        $this->post(route('data-saver.update'), ['mode' => 'turbo'])->assertSessionHasErrors('mode');
    }

    public function test_pages_load_no_third_party_fonts_or_icon_css(): void
    {
        $this->product();

        $this->get('/')->assertOk()
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('cdnjs.cloudflare.com', false)
            ->assertSee('data-saver', false)
            ->assertSee('id="offline-notice"', false);
    }

    public function test_optimize_command_prepares_every_size(): void
    {
        $this->product(['main_image' => $this->photo()]);

        $this->artisan('images:optimize')->assertSuccessful();

        foreach (Img::WIDTHS as $width) {
            Storage::disk('public')->assertExists("resized/{$width}/products/hijab.webp");
        }
    }

    public function test_product_page_only_downloads_the_first_gallery_photo(): void
    {
        $product = $this->product(['main_image' => null, 'stock_quantity' => 0]);
        $this->variant($product, ['color_name' => 'Rose', 'image' => $this->photo('products/variants/rose.jpg')]);
        $this->variant($product, ['color_name' => 'Sage', 'image' => $this->photo('products/variants/sage.jpg')]);

        $html = $this->get(route('products.show', $product))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<img src="[^"]*rose[^"]*"[^>]*data-stage-image#', $html);
        $this->assertMatchesRegularExpression('#<img data-src="[^"]*sage[^"]*"[^>]*data-stage-image#', $html);
    }
}

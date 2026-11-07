{{--
    Colours (variants) for a product. Upload several photos at once and each
    photo becomes its own colour option; existing colours can be renamed,
    restocked, re-photographed or removed below.
--}}
@php
    $variants = isset($product) ? $product->variants : collect();
@endphp

<div class="rounded-2xl border border-cream-300 bg-cream-50/60 p-6 space-y-6">
    <div>
        <p class="font-serif text-lg text-brand-900">Colours</p>
        <p class="text-sm text-gray-500">Each photo you upload becomes a colour option, shown to customers as a small round picture of that hijab.</p>
    </div>
    @if($errors->has('variants.*') || $errors->has('new_colour_images.*') || $errors->has('new_colour_stock'))
        <p class="text-red-500 text-sm">{{ collect($errors->get('variants.*'))->merge($errors->get('new_colour_images.*'))->merge($errors->get('new_colour_stock'))->flatten()->first() }}</p>
    @endif

    {{-- Add colours by uploading photos --}}
    <div class="rounded-lg border-2 border-dashed border-accent-200 bg-accent-50/40 p-5">
        <label for="newColourImages" class="block font-semibold text-brand-900">Add colours: upload one photo per colour</label>
        <p class="mt-1 text-sm text-gray-500">Select several photos at once. The file name becomes the colour name (e.g. <code>misty-blue.jpg</code> → "Misty Blue"); you can rename them after saving.</p>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-[1fr_180px] gap-4 items-end">
            <input id="newColourImages" type="file" name="new_colour_images[]" accept="image/*" multiple class="w-full text-sm">
            <div>
                <label for="newColourStock" class="block text-sm font-semibold mb-1">Stock for each new colour</label>
                <input id="newColourStock" type="number" name="new_colour_stock" min="0" value="{{ old('new_colour_stock', 10) }}" class="input-field !py-2">
            </div>
        </div>
        <div id="newColourPreview" class="mt-4 flex flex-wrap gap-4"></div>
    </div>

    {{-- Existing colours --}}
    @if($variants->isNotEmpty())
        <div class="space-y-3">
            <div class="hidden md:grid grid-cols-[64px_1.3fr_1.1fr_90px_100px_100px_1.2fr_80px] gap-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <span>Swatch</span><span>Colour name</span><span>SKU</span><span>Stock</span><span>Price (₵)</span><span>Cost (₵)</span><span>Replace photo</span><span>Remove</span>
            </div>
            @foreach($variants as $i => $variant)
                <div class="grid grid-cols-2 md:grid-cols-[64px_1.3fr_1.1fr_90px_100px_100px_1.2fr_80px] gap-3 items-center rounded-xl bg-cream-50 p-3">
                    <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant->id }}">
                    <span class="w-14 h-14 rounded-full bg-gray-200 bg-cover bg-center ring-1 ring-black/10"
                          style="background-image: url('{{ $variant->image ? asset('storage/' . $variant->image) : '' }}'); background-size: 260%;"
                          aria-hidden="true"></span>
                    <input type="text" name="variants[{{ $i }}][color_name]" value="{{ $variant->color_name }}" required aria-label="Colour name" class="input-field !py-2">
                    <input type="text" name="variants[{{ $i }}][sku]" value="{{ $variant->sku }}" maxlength="64" aria-label="SKU" class="input-field !py-2 font-mono text-xs">
                    <input type="number" name="variants[{{ $i }}][stock_quantity]" value="{{ $variant->stock_quantity }}" min="0" aria-label="Stock" class="input-field !py-2">
                    <input type="number" name="variants[{{ $i }}][price]" value="{{ $variant->price }}" step="0.01" min="0" placeholder="Same" aria-label="Price" class="input-field !py-2">
                    <input type="number" name="variants[{{ $i }}][cost_price]" value="{{ $variant->cost_price }}" step="0.01" min="0" placeholder="Same" aria-label="Purchase cost" class="input-field !py-2">
                    <input type="file" name="variants[{{ $i }}][image]" accept="image/*" aria-label="Replace photo" class="text-sm">
                    <label class="inline-flex items-center gap-2 text-sm text-red-600">
                        <input type="checkbox" name="variants[{{ $i }}][remove]" value="1" class="rounded border-gray-300 text-accent-600 focus:ring-accent-500"> Remove
                    </label>
                </div>
            @endforeach
            <p class="text-xs text-gray-500">Leave price or cost empty to use the product's. The product's total stock is the sum of its colours.</p>
        </div>
    @endif
</div>

<script>
    // Preview the photos chosen for new colours, with the name each will get
    (function () {
        const input = document.getElementById('newColourImages');
        const preview = document.getElementById('newColourPreview');
        const nameFrom = (file, i) => {
            const base = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').trim();
            return /^(img|dsc|image|photo|pxl|screenshot)?\s*\d+[\w\s]*$/i.test(base) || !base
                ? `Colour ${i + 1}`
                : base.replace(/\b\w/g, c => c.toUpperCase());
        };
        input.addEventListener('change', () => {
            preview.innerHTML = '';
            [...input.files].forEach((file, i) => {
                const url = URL.createObjectURL(file);
                preview.insertAdjacentHTML('beforeend',
                    `<figure class="flex flex-col items-center gap-2 w-24 text-center">
                        <span class="w-14 h-14 rounded-full bg-cover bg-center ring-1 ring-black/10" style="background-image:url('${url}');background-size:260%"></span>
                        <figcaption class="text-xs text-gray-600"></figcaption>
                    </figure>`);
                preview.lastElementChild.querySelector('figcaption').textContent = nameFrom(file, i);
            });
        });
    })();
</script>

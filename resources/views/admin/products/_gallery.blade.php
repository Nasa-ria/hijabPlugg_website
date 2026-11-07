{{--
    Gallery photos: extra pictures shown on the product page (close-ups,
    styled shots, someone wearing it). Separate from the colour photos.
--}}
<div class="rounded-2xl border border-cream-300 bg-cream-50/60 p-6 space-y-4">
    <div>
        <p class="font-serif text-lg text-brand-900">Gallery photos</p>
        <p class="text-sm text-gray-500">Extra photos for the product page, such as fabric close-ups or styled shots. These don't create colours.</p>
    </div>
    @if($errors->has('images.*'))
        <p class="text-red-500 text-sm">{{ collect($errors->get('images.*'))->flatten()->first() }}</p>
    @endif

    @if($product->images->isNotEmpty())
        <div class="flex flex-wrap gap-4">
            @foreach($product->images as $image)
                <label class="flex flex-col items-center gap-2 text-sm">
                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="" class="w-24 h-28 object-cover rounded-lg ring-1 ring-black/10">
                    <span class="inline-flex items-center gap-1.5 text-red-600">
                        <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="rounded border-gray-300 text-accent-600 focus:ring-accent-500"> Remove
                    </span>
                </label>
            @endforeach
        </div>
    @endif

    <div>
        <label for="galleryImages" class="admin-label">Add photos <span class="font-normal text-gray-500">(select several at once)</span></label>
        <input id="galleryImages" type="file" name="images[]" accept="image/*" multiple class="w-full text-sm">
    </div>
</div>

<p class="price-label text-sm text-zinc-500 max-sm:leading-4">
    @lang('shop::app.products.prices.grouped.starting-at')
</p>

@if ($prices['final']['price'] < $prices['regular']['price'])
    <!-- Show Regular Price (Strikethrough) -->
    <p class="final-price font-medium text-zinc-500 line-through max-sm:leading-4"
       aria-label="{{ $prices['regular']['formatted_price'] }}">
        {{ $prices['regular']['formatted_price'] }}
    </p>

    <!-- Show Discounted Price -->
    <p class="font-semibold max-sm:leading-4">
        {{ $prices['final']['formatted_price'] }}
    </p>
@else
    <!-- Show Only Regular Price -->
    <p class="final-price font-semibold max-sm:leading-4">
        {{ $prices['regular']['formatted_price'] }}
    </p>
@endif

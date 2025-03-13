@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@if ($product->type == 'grouped')
    {!! view_render_event('bagisto.shop.products.view.grouped_products.before', ['product' => $product]) !!}

    <div id="grouped-products" v-cloak class="w-[455px] max-w-full bg-white rounded-xl shadow-md border border-gray-200 p-6">
        @php
            // Eager load associated product relations
            $groupedProducts = $product->grouped_products()
                ->with(['associated_product.attribute_values', 'associated_product.attribute_family'])
                ->orderBy('sort_order')
                ->get();
            $defaultProductId = $groupedProducts->first()->associated_product_id ?? null;
        @endphp

        @if ($groupedProducts->count())
            <input type="hidden" name="grouped_product" id="selected_grouped_product" :value="selectedProduct" ref="selected_grouped_product">

            <!-- Power Selection -->
            <div class="flex items-center gap-3">
                <label class="group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 font-medium uppercase text-gray-900 hover:bg-gray-50"
                       :class="powerOption === 'with' ? 'border-transparent !bg-navyBlue text-white' : ''"
                       title="@lang('shop::app.products.view.type.configurable.with-power')">
                    <input type="radio" name="power_option" value="with" v-model="powerOption"
                           class="peer sr-only" />
                    <span class="text-lg max-sm:text-sm">@lang('shop::app.products.view.type.configurable.with-power')</span>
                    <span class="pointer-events-none absolute -inset-px rounded-full" role="presentation"></span>
                </label>

                <label class="group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 font-medium uppercase text-gray-900 hover:bg-gray-50"
                       :class="powerOption === 'without' ? 'border-transparent !bg-navyBlue text-white' : ''"
                       title="@lang('shop::app.products.view.type.configurable.without-power')">
                    <input type="radio" name="power_option" value="without" v-model="powerOption"
                           class="peer sr-only" />
                    <span class="text-lg max-sm:text-sm">@lang('shop::app.products.view.type.configurable.without-power')</span>
                    <span class="pointer-events-none absolute -inset-px rounded-full" role="presentation"></span>
                </label>
            </div>

            <!-- Without Power Mode -->
            <div v-if="powerOption === 'without'" class="mt-6 p-4 bg-gray-50 rounded-lg">
                <label class="block text-sm font-semibold text-gray-800 mb-2">
                    @lang('shop::app.products.view.type.configurable.quantity')
                </label>
                <x-shop::quantity-changer
                    name="qty[{{ $defaultProductId }}]"
                    :value="1"
                    class="w-24 border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring focus:border-blue-500"
                />
            </div>

            <!-- With Power Mode -->
            <div v-else class="mt-6 p-4 bg-gray-50 rounded-lg">
                <!-- Toggle Checkbox -->
                <div class="flex items-center justify-between pb-3 border-b">
                    <p class="text-lg font-semibold text-gray-800">
                        @lang('shop::app.products.view.type.configurable.lens-power-options')
                    </p>
                    <div class="flex items-center">
                        <input id="different-powers-checkbox" type="checkbox" v-model="differentPowers"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="different-powers-checkbox" class="m-2 text-sm font-medium text-gray-900 dark:text-gray-600">
                            @lang('shop::app.products.view.type.configurable.two-different-powers')
                        </label>
                    </div>
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    @lang('shop::app.products.view.type.configurable.default-power-message')
                </p>

                <!-- Single Selection Mode -->
                <div v-if="!differentPowers" class="mt-4">
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        @lang('shop::app.products.view.type.configurable.select-lens-power')
                    </label>
                    <select v-model="selectedProduct" name="grouped_product"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">
                            @lang('shop::app.products.view.type.configurable.select-options')
                        </option>
                        @foreach ($groupedProducts as $groupedProduct)
                            @if ($groupedProduct->associated_product)
                                @if ($loop->first)
                                    @continue
                                @endif
                                @php
                                    $associatedProduct = $groupedProduct->associated_product;
                                    $associatedProduct->loadMissing(['attribute_values', 'attribute_family']);
                                    $attributes = $associatedProduct->attributesToArray();
                                    $optionId = $attributes['sphere_power'] ?? null;
                                    $lensPowerName = $associatedProduct->name; // fallback
                                    if ($optionId && is_numeric($optionId)) {
                                        $option = \Webkul\Attribute\Models\AttributeOptionProxy::find($optionId);
                                        if ($option) {
                                            $translated = $option->translate(app()->getLocale());
                                            $lensPowerName = $translated->label ?? $option->admin_name;
                                        }
                                    }
                                @endphp
                                <option value="{{ $groupedProduct->associated_product_id }}">
                                    {{ $lensPowerName }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <div v-if="selectedProduct" class="mt-4">
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            @lang('shop::app.products.view.type.configurable.quantity')
                        </label>
                        <x-shop::quantity-changer
                            v-bind:name="'qty[' + selectedProduct + ']'"
                            :value="1"
                            class="w-24 border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring focus:border-blue-500"
                        />
                    </div>
                </div>

                <!-- Dual Selection Mode -->
                <div v-else class="mt-4 space-y-6">
                    <!-- Left Lens -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            @lang('shop::app.products.view.type.configurable.left-lens-power')
                        </label>
                        <select v-model="leftProduct" name="left_power"
                                class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">
                                @lang('shop::app.products.view.type.configurable.select-options')
                            </option>
                            @foreach ($groupedProducts as $groupedProduct)
                                @if ($groupedProduct->associated_product)
                                    @if ($loop->first)
                                        @continue
                                    @endif
                                    @php
                                        $associatedProduct = $groupedProduct->associated_product;
                                        $associatedProduct->loadMissing(['attribute_values', 'attribute_family']);
                                        $attributes = $associatedProduct->attributesToArray();
                                        $optionId = $attributes['sphere_power'] ?? null;
                                        $lensPowerName = $associatedProduct->name; // fallback
                                        if ($optionId && is_numeric($optionId)) {
                                            $option = \Webkul\Attribute\Models\AttributeOptionProxy::find($optionId);
                                            if ($option) {
                                                $translated = $option->translate(app()->getLocale());
                                                $lensPowerName = $translated->label ?? $option->admin_name;
                                            }
                                        }
                                    @endphp
                                    <option value="{{ $groupedProduct->associated_product_id }}">
                                        {{ $lensPowerName }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div v-if="leftProduct" class="mt-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Quantity</label>
                            <x-shop::quantity-changer
                                v-bind:name="'qty[' + leftProduct + ']'"
                                :value="1"
                                class="w-24 border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Right Lens -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            @lang('shop::app.products.view.type.configurable.right-lens-power')
                        </label>
                        <select v-model="rightProduct" name="right_power"
                                class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">
                                @lang('shop::app.products.view.type.configurable.select-options')
                            </option>
                            @foreach ($groupedProducts as $groupedProduct)
                                @if ($groupedProduct->associated_product)
                                    @if ($loop->first)
                                        @continue
                                    @endif
                                    @php
                                        $associatedProduct = $groupedProduct->associated_product;
                                        $associatedProduct->loadMissing(['attribute_values', 'attribute_family']);
                                        $attributes = $associatedProduct->attributesToArray();
                                        $optionId = $attributes['sphere_power'] ?? null;
                                        $lensPowerName = $associatedProduct->name; // fallback
                                        if ($optionId && is_numeric($optionId)) {
                                            $option = \Webkul\Attribute\Models\AttributeOptionProxy::find($optionId);
                                            if ($option) {
                                                $translated = $option->translate(app()->getLocale());
                                                $lensPowerName = $translated->label ?? $option->admin_name;
                                            }
                                        }
                                    @endphp
                                    <option value="{{ $groupedProduct->associated_product_id }}">
                                        {{ $lensPowerName }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div v-if="rightProduct" class="mt-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Quantity</label>
                            <x-shop::quantity-changer
                                v-bind:name="'qty[' + rightProduct + ']'"
                                :value="1"
                                class="w-24 border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {!! view_render_event('bagisto.shop.products.view.grouped_products.after', ['product' => $product]) !!}
@endif

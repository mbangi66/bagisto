@if (Webkul\Product\Helpers\ProductType::hasVariants($product->type))
    {!! view_render_event('bagisto.shop.products.view.configurable-options.before', ['product' => $product]) !!}

    <v-product-configurable-options :errors="errors"></v-product-configurable-options>

    {!! view_render_event('bagisto.shop.products.view.configurable-options.after', ['product' => $product]) !!}

    @push('scripts')
        <script type="text/x-template" id="v-product-configurable-options-template">
            <div class="w-[455px] max-w-full max-sm:w-full">
                <input
                    type="hidden"
                    name="selected_configurable_option"
                    id="selected_configurable_option"
                    :value="selectedOptionVariant"
                    ref="selected_configurable_option"
                >

                <!-- Loop through all child attributes -->
                <div class="mt-5" v-for="(attribute, index) in childAttributes" :key="index">
  <!-- For attributes that are not visible, render a hidden input so their value is submitted -->
  <template v-if="!shouldShowAttribute(attribute)">
    <input
      type="hidden"
      :name="'super_attribute[' + attribute.id + ']'"
      :value="attribute.selectedValue"
    >
  </template>

  <!-- For visible attributes, render the full field -->
  <div v-show="shouldShowAttribute(attribute)">
    <!-- Dropdown Options Container for dropdown or text swatch -->
    <template v-if="!attribute.swatch_type || attribute.swatch_type === '' || attribute.swatch_type === 'dropdown'">
      <h2 class="mb-4 text-xl max-sm:mb-1.5 max-sm:text-base max-sm:font-medium">
        @{{ attribute.label }}
      </h2>
      <v-field
        as="select"
        :name="'super_attribute[' + attribute.id + ']'"
        class="custom-select mb-3 block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base text-zinc-500 focus:border-blue-500 focus:ring-blue-500"
        :class="[errors['super_attribute[' + attribute.id + ']'] ? 'border border-red-500' : '']"
        :id="'attribute_' + attribute.id"
        v-model="attribute.selectedValue"
        :rules="(attribute.code === 'sphere_power' && isWithPower) ? 'required' : ''"
        :label="attribute.label"
        :aria-label="attribute.label"
        :disabled="attribute.disabled"
        @change="configure(attribute, $event.target.value)"
      >
        <option
          v-for="(option, index) in attribute.options"
          :value="option.id"
          :key="option.id"
        >
          @{{ option.label }}
        </option>
      </v-field>
    </template>

                        <!-- Swatch Options Container -->
                        <template v-else>
                            <!-- Option Label -->
                            <h2 class="mb-4 text-xl max-sm:mb-2 max-sm:text-base">
                                @{{ attribute.label }}
                            </h2>

                            <!-- Swatch Options -->
                            <div class="flex items-center gap-3">
                                <template v-for="(option, index) in attribute.options" :key="option.id">
                                    <template v-if="option.id">
                                        <!-- Color Swatch Options -->
                                        <label
                                            v-if="attribute.swatch_type === 'color'"
                                            class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none"
                                            :class="{'ring-2 ring-gray-900': option.id === attribute.selectedValue}"
                                            :title="option.label"
                                        >
                                            <v-field
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-slot="{ field }"
                                                rules="required"
                                                :label="attribute.label"
                                                :aria-label="attribute.label"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="'super_attribute[' + attribute.id + ']'"
                                                    :value="option.id"
                                                    v-bind="field"
                                                    :id="'attribute_' + attribute.id"
                                                    :aria-labelledby="'color-choice-' + index + '-label'"
                                                    class="peer sr-only"
                                                    @click="configure(attribute, $event.target.value)"
                                                >
                                            </v-field>
                                            
                                            <span
                                                class="h-8 w-8 rounded-full border border-gray-200 max-sm:h-[25px] max-sm:w-[25px]"
                                                tabindex="0"
                                                :style="{ 'background-color': option.swatch_value }"
                                            ></span>
                                        </label>

                                        <!-- Image Swatch Options -->
                                        <label
                                            v-if="attribute.swatch_type === 'image'"
                                            class="group relative flex h-[60px] w-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-md border bg-white font-medium uppercase text-gray-900 hover:bg-gray-50 sm:py-6"
                                            :class="{'border-navyBlue': option.id === attribute.selectedValue}"
                                            :title="option.label"
                                        >
                                            <v-field
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                v-model="attribute.selectedValue"
                                                :value="option.id"
                                                v-slot="{ field }"
                                                rules="required"
                                                :label="attribute.label"
                                                :aria-label="attribute.label"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="'super_attribute[' + attribute.id + ']'"
                                                    :value="option.id"
                                                    v-bind="field"
                                                    :id="'attribute_' + attribute.id"
                                                    :aria-labelledby="'color-choice-' + index + '-label'"
                                                    class="peer sr-only"
                                                    @click="configure(attribute, $event.target.value)"
                                                >
                                            </v-field>
                                            
                                            <img
                                                :src="option.swatch_value"
                                                :title="option.label"
                                            >
                                        </label>

                                        <!-- Text Swatch Options -->
                                        <label
                                            v-if="attribute.swatch_type === 'text'"
                                            class="group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 font-medium uppercase text-gray-900 hover:bg-gray-50 max-sm:h-fit max-sm:w-fit max-sm:px-3.5 max-sm:py-2"
                                            :class="{'border-transparent !bg-navyBlue text-white': option.id === attribute.selectedValue}"
                                            :title="option.label"
                                        >
                                            <v-field
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-model="attribute.selectedValue"
                                                v-slot="{ field }"
                                                rules="required"
                                                :label="attribute.label"
                                                :aria-label="attribute.label"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="'super_attribute[' + attribute.id + ']'"
                                                    :value="option.id"
                                                    v-bind="field"
                                                    :id="'attribute_' + attribute.id"
                                                    class="peer sr-only"
                                                    :aria-labelledby="'color-choice-' + index + '-label'"
                                                    @click="configure(attribute, $event.target.value)"
                                                >
                                            </v-field>
                                            
                                            <span class="text-lg max-sm:text-sm">
                                                @{{ option.label }}
                                            </span>
                                            
                                            <span
                                                class="pointer-events-none absolute -inset-px rounded-full"
                                                role="presentation"
                                            ></span>
                                        </label>
                                    </template>
                                </template>
                                
                                <span
                                    class="text-sm text-gray-600 max-sm:text-xs"
                                    v-if="! attribute.options.length"
                                >
                                    @lang('shop::app.products.view.type.configurable.select-above-options')
                                </span>
                            </div>
                        </template>
                        
                        <v-error-message
                            :name="'super_attribute[' + attribute.id + ']'"
                            v-slot="{ message }"
                        >
                            <p class="mt-1 text-xs italic text-red-500">
                                @{{ message }}
                            </p>
                        </v-error-message>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            let galleryImages = @json(product_image()->getGalleryImages($product));

            app.component('v-product-configurable-options', {
                template: '#v-product-configurable-options-template',

                props: ['errors'],

                data() {
                    return {
                        config: @json(app('Webkul\Product\Helpers\ConfigurableOption')->getConfigurationConfig($product)),

                        childAttributes: [],

                        possibleOptionVariant: null,

                        selectedOptionVariant: '',

                        galleryImages: [],
                    }
                },

                computed: {
                    isWithPower() {
                            const lensTypeAttr = this.childAttributes.find(attr => attr.code === 'lens_type');
                            if (!lensTypeAttr) {
                                return false;
                            }
                            const selectedOption = lensTypeAttr.options.find(opt => opt.id == lensTypeAttr.selectedValue);
                            return selectedOption && selectedOption.label === 'With Power';
                        }
                    },
                    mounted() {
                        // Clone attributes from config.
                        let attributes = JSON.parse(JSON.stringify(this.config)).attributes.slice();

                        // Custom sort: ensure desired order.
                        attributes.sort((a, b) => {
                            const order = { 
                                'lens_color': 1, 
                                'lens_type': 2, 
                                'two_different_powers': 3, 
                                'sphere_power': 4, 
                                'Sphere_Power_Left_Eye': 5, 
                                'Sphere_Power_Right_Eye': 6 
                            };
                            return (order[a.code] || 99) - (order[b.code] || 99);
                        });

                        let index = attributes.length;

                        // Build childAttributes and initialize options
                        while (index--) {
                            let attribute = attributes[index];

                            // Initialize options with placeholder
                            attribute.options = [{
                                'id': '',
                                'label': "@lang('shop::app.products.view.type.configurable.select-options')",
                                'products': []
                            }];

                            // For the first attribute, fill options immediately.
                            if (!index) {
                                this.fillAttributeOptions(attribute);
                            } else {
                                attribute.disabled = true;
                            }

                            // Link child, previous, and next attributes.
                            attribute = Object.assign(attribute, {
                                childAttributes: this.childAttributes.slice(),
                                prevAttribute: attributes[index - 1],
                                nextAttribute: attributes[index + 1]
                            });

                            this.childAttributes.unshift(attribute);
                        }

                        this.reloadPrice();
                        this.reloadImages();
                    },

                methods: {
                    shouldShowAttribute(attribute) {
                        // Always show the lens type attribute.
                        if (attribute.code === 'lens_type'|| attribute.code === 'lens_color' ) {
                            return true;
                        }

                        // Get the lens type attribute selection.
                        let lensTypeAttr = this.childAttributes.find(attr => attr.code === 'lens_type');
                        if (!lensTypeAttr || !lensTypeAttr.selectedValue) {
                            return false;
                        }

                        let selectedLensType = lensTypeAttr.options.find(opt => opt.id == lensTypeAttr.selectedValue);
                        if (!selectedLensType || selectedLensType.label !== 'With Power') {
                            return false;
                        }

                        // Always show the two_different_powers option.
                        if (attribute.code === 'two_different_powers') {
                            return true;
                        }

                        // For the sphere_power attribute, show it only if two_different_powers is NOT "Yes".
                        if (attribute.code === 'sphere_power') {
                            let twoDiffAttr = this.childAttributes.find(attr => attr.code === 'two_different_powers');
                            if (twoDiffAttr && twoDiffAttr.selectedValue) {
                                let selectedTwoDiff = twoDiffAttr.options.find(opt => opt.id == twoDiffAttr.selectedValue);
                                if (selectedTwoDiff && selectedTwoDiff.label.toLowerCase() === 'yes') {
                                    return false;
                                }
                            }
                            return true;
                        }

                        // For the Sphere_Power_Left_Eye and Sphere_Power_Right_Eye, show only if two_different_powers is "Yes".
                        if (attribute.code === 'Sphere_Power_Left_Eye' || attribute.code === 'Sphere_Power_Right_Eye') {
                            let twoDiffAttr = this.childAttributes.find(attr => attr.code === 'two_different_powers');
                            if (twoDiffAttr && twoDiffAttr.selectedValue) {
                                let selectedTwoDiff = twoDiffAttr.options.find(opt => opt.id == twoDiffAttr.selectedValue);
                                return selectedTwoDiff && selectedTwoDiff.label.toLowerCase() === 'yes';
                            }
                            return false;
                        }

                        return true;
                    },
    
                    configure(attribute, optionId) {
                        this.possibleOptionVariant = this.getPossibleOptionVariant(attribute, optionId);

                        if (optionId) {
                            attribute.selectedValue = optionId;

                            // Only clear subsequent attributes if this isn't the sphere_power attribute.
                            if ( attribute.nextAttribute) {
                                let nextAttr = attribute.nextAttribute;

                                while (nextAttr) {
                                    // Enable and clear the nextAttr.
                                    nextAttr.disabled = false;
                                    this.clearAttributeSelection(nextAttr);

                                    // Fill its options.
                                    this.fillAttributeOptions(nextAttr);

                                    // Reset the child chain below it.
                                    this.resetChildAttributes(nextAttr);

                                    // Move on to the next in the chain.
                                    nextAttr = nextAttr.nextAttribute;
                                }

                            } else {
                                this.selectedOptionVariant = this.possibleOptionVariant;
                            }
                        } else {
                            this.clearAttributeSelection(attribute);
                            if (attribute.nextAttribute) {
                                this.clearAttributeSelection(attribute.nextAttribute);
                                this.resetChildAttributes(attribute);
                            }
                        }

                        // Adjust power-related fields based on two_different_powers selection.
                        this.autoClearPowerAttributes();
                        this.reloadPrice();
                        this.reloadImages();
                    },

                    autoClearPowerAttributes() {
                        let lensTypeAttr = this.childAttributes.find(attr => attr.code === 'lens_type');

                        if (lensTypeAttr) {
                            let selectedLensType = lensTypeAttr.options.find(opt => opt.id == lensTypeAttr.selectedValue);
                            
                            // Flag to note if the user originally chose "Without Power"
                            const wasWithoutPower = selectedLensType && selectedLensType.label === 'Without Power';
                            
                            // Re-read selected lens type after override.
                            selectedLensType = lensTypeAttr.options.find(opt => opt.id == lensTypeAttr.selectedValue);
                            
                            // --- Set default for sphere_power (id 40) ---
                            let sphereAttr = this.childAttributes.find(attr => attr.code === 'sphere_power');
                            console.log("Before default, sphere_power:", sphereAttr);
                            if (sphereAttr && (!sphereAttr.selectedValue || sphereAttr.selectedValue === "")) {
                                let defaultSphere = "";
                                if (sphereAttr.options.length > 1) {
                                    defaultSphere = sphereAttr.options[1].id;
                                } else {
                                    defaultSphere = "49"; // fallback default value
                                }
                                sphereAttr.selectedValue = defaultSphere;
                            }
                            
                            // --- Set default for Sphere_Power_Left_Eye (id 46) ---
                            let leftAttr = this.childAttributes.find(attr => attr.code === 'Sphere_Power_Left_Eye');
                            if (leftAttr && (!leftAttr.selectedValue || leftAttr.selectedValue === "")) {
                                let defaultLeft = "";
                                if (leftAttr.options.length > 1) {
                                    defaultLeft = leftAttr.options[1].id;
                                } else {
                                    defaultLeft = "61";
                                }
                                leftAttr.selectedValue = defaultLeft;
                                leftAttr.disabled = false;
                            }
                            
                            // --- Set default for Sphere_Power_Right_Eye (id 47) ---
                            let rightAttr = this.childAttributes.find(attr => attr.code === 'Sphere_Power_Right_Eye');
                            if (rightAttr && (!rightAttr.selectedValue || rightAttr.selectedValue === "")) {
                                let defaultRight = "";
                                if (rightAttr.options.length > 1) {
                                    defaultRight = rightAttr.options[1].id;
                                } else {
                                    defaultRight = "64";
                                }
                                rightAttr.selectedValue = defaultRight;
                                rightAttr.disabled = false;
                            }
                            
                            // --- Set default for two_different_powers (id 45) ---
                            let twoDiffAttr = this.childAttributes.find(attr => attr.code === 'two_different_powers');
                            if (twoDiffAttr && (!twoDiffAttr.selectedValue || twoDiffAttr.selectedValue === "")) {
                                let defaultTwoDiff = "";
                                // Try to find the option with label "yes" (case-insensitive)
                                let noOption = twoDiffAttr.options.find(opt => opt.label.toLowerCase() === 'yes');
                                if (noOption) {
                                    defaultTwoDiff = noOption.id;
                                } else if (twoDiffAttr.options.length > 1) {
                                    defaultTwoDiff = twoDiffAttr.options[1].id;
                                } else {
                                    defaultTwoDiff = "59";
                                }
                                twoDiffAttr.selectedValue = defaultTwoDiff;
                            }
                            
                            // --- If the original selection was NOT "Without Power" (i.e. the user explicitly chose With Power)
                            // then check two_different_powers and clear left/right if its selection isn't "Yes".
                            if (!wasWithoutPower) {
                                let twoDiffAttrCheck = this.childAttributes.find(attr => attr.code === 'two_different_powers');
                                if (twoDiffAttrCheck) {
                                    let selectedTwoDiff = twoDiffAttrCheck.options.find(opt => opt.id == twoDiffAttrCheck.selectedValue);
                                    if (selectedTwoDiff && selectedTwoDiff.label.toLowerCase() === 'yes') {
                                        // When "Yes" is selected, left/right should show.
                                    } else {
                                        // Otherwise, clear left/right fields.
                                        ['Sphere_Power_Left_Eye', 'Sphere_Power_Right_Eye'].forEach(code => {
                                            let attr = this.childAttributes.find(attr => attr.code === code);
                                            if (attr) {
                                                attr.selectedValue = "";
                                            }
                                        });
                                    }
                                }
                            } else {
                                console.log("Original selection was 'Without Power'; keeping default power values.");
                            }
                        }
                    },

                    getPossibleOptionVariant(attribute, optionId) {
                        let matchedOptions = attribute.options.filter(option => option.id == optionId);
                        if (matchedOptions[0]?.allowedProducts) {
                            return matchedOptions[0].allowedProducts[0];
                        }
                        return undefined;
                    },

                    fillAttributeOptions(attribute) {
                        let options = this.config.attributes.find(tempAttribute => tempAttribute.id === attribute.id)?.options;

                        attribute.options = [{
                            'id': '',
                            'label': "@lang('shop::app.products.view.type.configurable.select-options')",
                            'products': []
                        }];

                        if (!options) {
                            return;
                        }

                        let prevAttributeSelectedOption = attribute.prevAttribute?.options.find(option => option.id == attribute.prevAttribute.selectedValue);

                        let index = 1;

                        for (let i = 0; i < options.length; i++) {
                            let allowedProducts = [];

                            if (prevAttributeSelectedOption) {
                                for (let j = 0; j < options[i].products.length; j++) {
                                    if (prevAttributeSelectedOption.allowedProducts && prevAttributeSelectedOption.allowedProducts.includes(options[i].products[j])) {
                                        allowedProducts.push(options[i].products[j]);
                                    }
                                }
                            } else {
                                allowedProducts = options[i].products.slice(0);
                            }

                            if (allowedProducts.length > 0) {
                                options[i].allowedProducts = allowedProducts;
                                attribute.options[index++] = options[i];
                            }
                        }
                    },

                    resetChildAttributes(attribute) {
                        if (!attribute.childAttributes) {
                            return;
                        }
                        attribute.childAttributes.forEach(function(set) {
                            set.selectedValue = null;
                            set.disabled = true;
                        });
                    },

                    clearAttributeSelection(attribute) {
                        if (!attribute) {
                            return;
                        }
                        attribute.selectedValue = null;
                        this.selectedOptionVariant = null;
                    },

                    reloadPrice() {
                        let visibleAttributes = this.childAttributes.filter(attribute => this.shouldShowAttribute(attribute));
                        let selectedOptionCount = visibleAttributes.filter(attribute => attribute.selectedValue).length;

                        if (visibleAttributes.length === selectedOptionCount) {
                            // All required attributes are selected.
                            this.selectedOptionVariant = this.possibleOptionVariant;
                            document.querySelector('.price-label').style.display = 'none';
                            document.querySelector('.final-price').innerHTML =
                                this.config.variant_prices[this.possibleOptionVariant].final.formatted_price;
                            this.$emitter.emit('configurable-variant-selected-event', this.possibleOptionVariant);
                        } else {
                            // Not all attributes are selected, so clear the variant.
                            this.selectedOptionVariant = '';
                            document.querySelector('.price-label').style.display = 'inline-block';
                            document.querySelector('.final-price').innerHTML = this.config.regular.formatted_price;
                            this.$emitter.emit('configurable-variant-selected-event', 0);
                        }
                    },

                reloadImages() {
                    galleryImages.splice(0, galleryImages.length);

                    if (this.possibleOptionVariant) {
                        this.config.variant_images[this.possibleOptionVariant].forEach(function(image) {
                            galleryImages.push(image);
                        });
                        this.config.variant_videos[this.possibleOptionVariant].forEach(function(video) {
                            galleryImages.push(video);
                        });
                    }

                    this.galleryImages.forEach(function(image) {
                        galleryImages.push(image);
                    });

                    if (galleryImages.length) {
                        this.$parent.$parent.$refs.gallery.media.images = [...galleryImages];
                    }

                    this.$emitter.emit('configurable-variant-update-images-event', galleryImages);
                },
            }
            });
        </script>
    @endpush
@endif

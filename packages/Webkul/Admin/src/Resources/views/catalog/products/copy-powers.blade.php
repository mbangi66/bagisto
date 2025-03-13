<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.copy-powers.title')
    </x-slot:title>

    <!-- Header Section -->
    <div class="grid gap-2.5 mb-4">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div class="grid gap-1.5">
                <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                    @lang('admin::app.catalog.products.copy-powers.title')
                </p>
            </div>
            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.catalog.products.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                    @lang('admin::app.account.edit.back-btn')
                </a>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <x-admin::form method="POST">
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div id="app" class="w-full">
                <v-copy-powers 
                    :products='@json($products->toArray())' 
                    :available-powers='@json($availablePowers)'
                ></v-copy-powers>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <!-- Inline Vue template for the copy powers component -->
        <script type="text/x-template" id="v-copy-powers-template">
            <!-- Using flex layout for left/right columns -->
            <div class="flex flex-row md:flex-row gap-4">
                <!-- Left Column: Form and Powers Selection -->
                <div class="w-full md:w-1/2 box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <form @submit.prevent="submitForm">
                        <!-- Base Product Selector -->
                        <div class="mb-5">
                            <label for="base_product" class="block font-medium mb-2 text-gray-800 dark:text-white">
                                @lang('admin::app.catalog.products.copy-powers.select-base-product')
                            </label>
                            <select 
                                v-model="selectedProductId" 
                                class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-800 bg-white dark:bg-gray-900" 
                                required
                                @change="updateSelectedProduct"
                            >
                                <option disabled value="">
                                    @lang('admin::app.catalog.products.copy-powers.select-product-placeholder')
                                </option>
                                <option v-for="product in productsArray" :key="product.id" :value="product.id">
                                    @{{ product.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Available Powers Section -->
                        <div class="mb-5">
                            <label class="block font-medium mb-2 text-gray-800 dark:text-white">
                                @lang('admin::app.catalog.products.copy-powers.available-powers')
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    type="button" 
                                    v-for="(power, index) in availablePowers" 
                                    :key="index" 
                                    :class="buttonClass(power)" 
                                    class="px-3 py-2 rounded transition-colors text-sm"
                                    @click="togglePower(power)">
                                    @{{ power }}
                                </button>
                            </div>
                        </div>

                        <!-- Selected Powers Count Display -->
                        <div class="mb-4 text-sm" v-if="selectedPowers.length > 0">
                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ selectedPowers.length }} power(s) selected
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="primary-button flex items-center justify-center"
                            :disabled="!selectedProductId || selectedPowers.length === 0"
                        >
                            @lang('admin::app.catalog.products.copy-powers.copy-powers-btn')
                        </button>
                    </form>
                    
                    <!-- Flash Message -->
                    <div v-if="flashMessage" :class="flashMessageClass" class="mt-4 p-2.5 rounded">
                        @{{ flashMessage }}
                    </div>
                </div>

                <!-- Right Column: Selected Product Card View -->
                <div class="w-full md:w-1/2 box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">Product Details</h2>
                    
                    <div v-if="selectedProduct" class="border rounded overflow-hidden">
                        <!-- Product Layout with left/right details -->
                        <div class="flex flex-col md:flex-row gap-4 p-4">
                            <!-- Left Side: Product Info -->
                            <div class="flex flex-col gap-1.5 flex-1">
                                <p class="break-all text-base font-semibold text-gray-800 dark:text-white">
                                    @{{ selectedProduct.name }}
                                </p>
                                
                                <p class="text-gray-600 dark:text-gray-300" v-if="selectedProduct.sku">
                                    SKU: @{{ selectedProduct.sku }}
                                </p>
                                
                                <!-- Fixed Attribute Family Display -->
                                <p class="text-gray-600 dark:text-gray-300" v-if="selectedProduct.attribute_family">
                                    Attribute Family: @{{ selectedProduct.attribute_family.name || selectedProduct.attribute_family }}
                                </p>
                            </div>
                            
                            <!-- Right Side: Product Image & Details -->
                            <div class="flex flex-col gap-1.5">
                                <div class="relative">
                                    <template v-if="selectedProduct.base_image">
                                        <img
                                            class="max-h-[65px] min-h-[65px] min-w-[65px] max-w-[65px] rounded"
                                            :src="selectedProduct.base_image"
                                            alt="Product Image"
                                        />
                                        <span v-if="selectedProduct.images_count" class="absolute bottom-px left-px rounded-full bg-darkPink px-1.5 text-xs font-bold text-white">
                                            @{{ selectedProduct.images_count }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800">
                                            <img src="{{ bagisto_asset('images/product-placeholders/front.svg')}}">
                                            <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                                @lang('admin::app.catalog.products.index.datagrid.product-image')
                                            </p>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="flex flex-col gap-1.5">
                                    <p v-if="selectedProduct.price" class="text-base font-semibold text-gray-800 dark:text-white">
                                        @{{ selectedProduct.price }}
                                    </p>
                                    
                                    <!-- Product Quantity -->
                                    <div v-if="selectedProduct.type && ['configurable', 'bundle', 'grouped'].includes(selectedProduct.type)">
                                        <p class="text-gray-600 dark:text-gray-300">
                                            <span class="text-red-600">N/A</span>
                                        </p>
                                    </div>
                                    <div v-else-if="selectedProduct.quantity !== undefined">
                                        <p class="text-gray-600 dark:text-gray-300" v-if="selectedProduct.quantity > 0">
                                            <span class="text-green-600">
                                                Qty: @{{ selectedProduct.quantity }}
                                            </span>
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-300" v-else>
                                            <span class="text-red-600">
                                                @lang('admin::app.catalog.products.index.datagrid.out-of-stock')
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <p v-if="selectedProduct.id" class="text-gray-600 dark:text-gray-300">
                                        ID: @{{ selectedProduct.id }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Product Info -->
                        <div class="border-t p-4">
                            <div class="flex flex-col gap-1.5">
                                <p v-if="selectedProduct.status !== undefined" :class="[selectedProduct.status ? 'label-active' : 'label-info']">
                                    @{{ selectedProduct.status ? 'Active' : 'Disabled' }}
                                </p>
                                
                                <p v-if="selectedProduct.category_name" class="text-gray-600 dark:text-gray-300">
                                    Category: @{{ selectedProduct.category_name }}
                                </p>
                                
                                <p v-if="selectedProduct.type" class="text-gray-600 dark:text-gray-300">
                                    Type: @{{ selectedProduct.type }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="flex items-center justify-center h-40 text-gray-500 border rounded border-dashed">
                        @lang('admin::app.catalog.products.copy-powers.no-product-selected')
                    </div>
                </div>
            </div>
        </script>

        <!-- Register the component -->
        <script type="module">
            app.component('v-copy-powers', {
                template: '#v-copy-powers-template',
                props: {
                    products: {
                        type: [Array, Object],
                        default: () => []
                    },
                    availablePowers: {
                        type: Array,
                        default: () => []
                    }
                },
                data() {
                    return {
                        selectedProductId: '',
                        selectedPowers: [],
                        flashMessage: '',
                        flashMessageType: '' // 'success' or 'error'
                    }
                },
                computed: {
                    flashMessageClass() {
                        return this.flashMessageType === 'success'
                            ? 'bg-green-50 text-green-600 border border-green-200'
                            : 'bg-red-50 text-red-600 border border-red-200'
                    },
                    productsArray() {
                        return Array.isArray(this.products) ? this.products : Object.values(this.products);
                    },
                    selectedProduct() {
                        return this.productsArray.find(product => product.id == this.selectedProductId);
                    }
                },
                methods: {
                    updateSelectedProduct() {
                        console.log("Selected product ID:", this.selectedProductId);
                    },
                    togglePower(power) {
                        const index = this.selectedPowers.indexOf(power);
                        if (index > -1) {
                            this.selectedPowers.splice(index, 1);
                        } else {
                            this.selectedPowers.push(power);
                        }
                    },
                    buttonClass(power) {
                        return this.selectedPowers.includes(power)
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                    },
                    async submitForm() {
                        if (!this.selectedProductId || this.selectedPowers.length === 0) {
                            this.flashMessage = 'Please select a product and at least one power';
                            this.flashMessageType = 'error';
                            return;
                        }
                        
                        try {
                            const payload = {
                                product_id: this.selectedProductId,
                                powers: this.selectedPowers
                            };
                            const response = await this.$axios.post("{{ route('admin.catalog.products.copy-powers') }}", payload);
                            this.flashMessage = response.data.message || '@lang("admin::app.catalog.products.copy-powers.success-message")';
                            this.flashMessageType = 'success';
                            
                            // Optional: Reset form after successful submission
                            // this.selectedPowers = [];
                        } catch (error) {
                            this.flashMessage = error.response?.data?.error || '@lang("admin::app.catalog.products.copy-powers.error-message")';
                            this.flashMessageType = 'error';
                        }
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

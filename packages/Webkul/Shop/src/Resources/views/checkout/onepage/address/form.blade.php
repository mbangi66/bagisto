@pushOnce('scripts')
    <script type="text/x-template" id="v-checkout-address-form-template">
        <div class="mt-2 max-md:mt-3">
            <!-- Hidden Address ID -->
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            <!-- First & Last Name -->
            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <!-- First Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.first-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.first_name'"
                        ::value="address.first_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.first-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.first-name')"
                    />

                    <x-shop::form.control-group.error ::name="controlName + '.first_name'" />
                </x-shop::form.control-group>

                {!! view_render_event('baguluk.shop.checkout.onepage.address.form.first_name.after') !!}

                <!-- Last Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.last-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.last_name'"
                        ::value="address.last_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.last-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.last-name')"
                    />

                    <x-shop::form.control-group.error ::name="controlName + '.last_name'" />
                </x-shop::form.control-group>

                {!! view_render_event('baguluk.shop.checkout.onepage.address.form.last_name.after') !!}
            </div>

            <!-- Email -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.email')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="required|email"
                    :label="trans('shop::app.checkout.onepage.address.email')"
                    placeholder="email@example.com"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            {!! view_render_event('baguluk.shop.checkout.onepage.address.form.email.after') !!}

            <!-- Street Address -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.street-address')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    :label="trans('shop::app.checkout.onepage.address.street-address')"
                    :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                />

                <x-shop::form.control-group.error
                    class="mb-2"
                    ::name="controlName + '.address.[0]'"
                />

                @if (core()->getConfigData('customer.address.information.street_lines') > 1)
                    @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.address.[{{ $i }}]'"
                            rules="address"
                            :label="trans('shop::app.checkout.onepage.address.street-address')"
                            :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                        />

                        <x-shop::form.control-group.error
                            class="mb-2"
                            ::name="controlName + '.address.[{{ $i }}]'"
                        />
                    @endfor
                @endif
            </x-shop::form.control-group>

            {!! view_render_event('baguluk.shop.checkout.onepage.address.form.address.after') !!}

            <!-- Country & State -->
            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <!-- Country -->
                <x-shop::form.control-group class="!mb-4">
                    <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }} !mt-0">
                        @lang('shop::app.checkout.onepage.address.country')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.country'"
                        v-model="localAddress.country"
                        rules="{{ core()->isCountryRequired() ? 'required' : '' }}"
                        :label="trans('shop::app.checkout.onepage.address.country')"
                    >
                        <option
                            v-for="gccCountry in gccCountries"
                            :key="gccCountry.code"
                            :disabled="gccCountry.disabled"
                            :value="gccCountry.code"
                        >
                            @{{ gccCountry.name }}
                        </option>
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error ::name="controlName + '.country'" />
                </x-shop::form.control-group>

                {!! view_render_event('baguluk.shop.checkout.onepage.address.form.country.after') !!}

                <!-- State -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }} !mt-0">
                        @lang('shop::app.checkout.onepage.address.state')
                    </x-shop::form.control-group.label>

                    <!-- If states available -->
                    <template v-if="states[selectedCountry] && states[selectedCountry].length">
                        <x-shop::form.control-group.control
                            type="select"
                            ::name="controlName + '.state'"
                            v-model="localAddress.state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            :label="trans('shop::app.checkout.onepage.address.state')"
                            :placeholder="trans('shop::app.checkout.onepage.address.state')"
                        >
                            <option value="">
                                @lang('shop::app.checkout.onepage.address.select-state')
                            </option>
                            <option
                                v-for="state in states[selectedCountry]"
                                :key="state.code"
                                :value="state.state_name"
                            >
                                @{{ state.default_name }}
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <!-- Otherwise, show disabled dropdown -->
                    <template v-else>
                        <x-shop::form.control-group.control
                            type="select"
                            disabled
                            :label="trans('shop::app.checkout.onepage.address.state')"
                        >
                            <option>No states found.</option>
                        </x-shop::form.control-group.control>
                    </template>

                    <x-shop::form.control-group.error ::name="controlName + '.state'" />
                </x-shop::form.control-group>
            </div>

            <!-- City & Block -->
            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <!-- City -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.city')
                    </x-shop::form.control-group.label>
                    
                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.city'"
                        v-model="localAddress.city"
                        rules="required"
                        :label="'City'"
                        :placeholder="'Select City'"
                    >
                        <option value="">Select City</option>
                        <option 
                            v-for="city in cities" 
                            :key="city.id" 
                            :value="city.city_name"
                        >
                            @{{ city.city_name }}
                        </option>
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error ::name="controlName + '.city'" />
                </x-shop::form.control-group>

                {!! view_render_event('baguluk.shop.checkout.onepage.address.form.city.after') !!}

                <!-- Block -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        Block
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.block_id'"
                        v-model="localAddress.block_id"
                        rules="required"
                        :label="'Block'"
                        :placeholder="'Select Block'"
                    >
                        <option value="">Select Block</option>
                        <option 
                            v-for="block in blocks" 
                            :key="block.id" 
                            :value="block.id"
                        >
                            @{{ block.name_en }}
                        </option>
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error ::name="controlName + '.block_id'" />
                </x-shop::form.control-group>
            </div>

            <!-- Phone Number -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.telephone')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|numeric"
                    :label="trans('shop::app.checkout.onepage.address.telephone')"
                    :placeholder="trans('shop::app.checkout.onepage.address.telephone')"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            {!! view_render_event('baguluk.shop.checkout.onepage.address.form.phone.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: {
                    type: String,
                    required: true,
                },
                address: {
                    type: Object,
                    default: () => ({
                        id: 0,
                        company_name: '',
                        first_name: '',
                        last_name: '',
                        email: '',
                        address: [],
                        country: '',
                        state: '',
                        city: '',
                        postcode: '',
                        block_id: '',
                        phone: '',
                    }),
                },
            },

            data() {
                return {
                    localAddress: {
                        ...this.address,
                        country: this.address.country || 'KW'
                    },
                    // The GCC countries. We'll disable all but Kuwait:
                    gccCountries: [
                        { id: 121, code: 'BH', name: 'Bahrain', disabled: true },
                        { id: 122, code: 'KW', name: 'Kuwait',  disabled: false },
                        { id: 123, code: 'OM', name: 'Oman',    disabled: true },
                        { id: 124, code: 'QA', name: 'Qatar',   disabled: true },
                        { id: 125, code: 'SA', name: 'Saudi Arabia', disabled: true },
                        { id: 126, code: 'AE', name: 'United Arab Emirates', disabled: true },
                    ],
                    states: {},
                    cities: [],
                    blocks: [],
                };
            },

            computed: {
                selectedCountry() {
                    console.log('Computed selectedCountry:', this.localAddress.country);
                    return this.localAddress.country;
                },
            },

            watch: {
                localAddress: {
                    deep: true,
                    handler(newVal) {
                        this.$emit('update:address', newVal);
                    }
                },
                'localAddress.country'(newCountry) {
                    console.log('Watcher: localAddress.country changed to:', newCountry);
                    if (newCountry) {
                        this.localAddress.state = '';
                        this.cities = [];
                        this.localAddress.city = '';
                        this.blocks = [];
                        this.localAddress.block_id = '';
                        this.loadStates(newCountry);
                    }
                },
                'localAddress.state'(newState) {
                    console.log('Watcher: localAddress.state changed to:', newState);
                    if (newState) {
                        this.localAddress.city = '';
                        this.blocks = [];
                        this.localAddress.block_id = '';
                        this.loadCities(newState);
                    }
                },
                'localAddress.city'(newCity) {
                    console.log('Watcher: localAddress.city changed to:', newCity);
                    if (newCity) {
                        this.localAddress.block_id = '';
                        this.loadBlocks(newCity);
                    }
                },
            },

            mounted() {
                console.log('Component mounted. Address:', this.address);
                this.initStates();
                if (this.address.state) {
                    console.log('Mounted: Loading cities for state:', this.address.state);
                    this.loadCities(this.address.state);
                }
                if (this.address.city) {
                    console.log('Mounted: Loading blocks for city:', this.address.city);
                    this.loadBlocks(this.address.city);
                }
            },

            methods: {
                getCountryId(countryCode) {
                    console.log('getCountryId called with:', countryCode);
                    const country = this.gccCountries.find(c => c.code === countryCode);
                    const id = country ? country.id : null;
                    console.log('getCountryId returning:', id);
                    return id;
                },

                initStates() {
                    console.log('initStates called for country:', this.localAddress.country);
                    const countryId = this.getCountryId(this.localAddress.country);
                    if (countryId) {
                        this.$axios.get('/locations/states/' + countryId)
                            .then(response => {
                                console.log('States response:', response.data);
                                const transformed = response.data.map(item => ({
                                    code: item.id,
                                    default_name: item.state_name,
                                    state_name: item.state_name
                                }));
                                console.log('Transformed states:', transformed);
                                this.states[this.localAddress.country] = transformed;
                                console.log('this.states after initStates:', this.states);
                            })
                            .catch(error => {
                                console.error('Error in initStates:', error);
                            });
                    } else {
                        console.log('No countryId found for country:', this.localAddress.country);
                    }
                },

                loadStates(countryCode) {
                    console.log('loadStates called for country:', countryCode);
                    const countryId = this.getCountryId(countryCode);
                    if (countryId) {
                        this.$axios.get('/locations/states/' + countryId)
                            .then(response => {
                                console.log('States response in loadStates:', response.data);
                                const transformed = response.data.map(item => ({
                                    code: item.id,
                                    default_name: item.state_name,
                                    state_name: item.state_name
                                }));
                                console.log('Transformed states in loadStates:', transformed);
                                this.states[countryCode] = transformed;
                                console.log('this.states after loadStates:', this.states);
                            })
                            .catch(error => {
                                console.error('Error in loadStates:', error);
                            });
                    } else {
                        console.log('No countryId found in loadStates for country:', countryCode);
                        this.states[countryCode] = [];
                    }
                },

                loadCities(stateName) {
                    console.log('loadCities called for stateName:', stateName);
                    // Call endpoint using the state name. Make sure the backend endpoint accepts the state name.
                    this.$axios.get('/locations/cities/' + encodeURIComponent(stateName))
                        .then(response => {
                            console.log('Cities response:', response.data);
                            this.cities = response.data;
                        })
                        .catch(error => {
                            console.error('Error in loadCities:', error);
                        });
                },

                loadBlocks(cityName) {
                    console.log('loadBlocks called for cityName:', cityName);
                    // Call endpoint using the city name. Ensure the backend accepts a city name.
                    this.$axios.get('/locations/blocks/' + encodeURIComponent(cityName))
                        .then(response => {
                            console.log('Blocks response:', response.data);
                            this.blocks = response.data;
                        })
                        .catch(error => {
                            console.error('Error in loadBlocks:', error);
                        });
                },
            },
        });
    </script>
@endPushOnce

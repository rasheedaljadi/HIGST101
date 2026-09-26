@props([
    'name'     => '',
    'value'    => 1,
    'minValue' => 1,
])

<v-quantity-changer
    {{ $attributes->merge(['class' => 'flex items-center border border-navyBlue']) }}
    name="{{ $name }}"
    value="{{ $value }}"
    min-value="{{ $minValue }}"
>
</v-quantity-changer>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-quantity-changer-template"
    >
        <div>
            <span 
                class="icon-minus cursor-pointer text-2xl"
                :class="{'opacity-40 !cursor-not-allowed': isAtMin}"
                role="button"
                tabindex="0"
                aria-label="@lang('shop::app.components.quantity-changer.decrease-quantity')"
                @click="decrease"
            >
            </span>

            <p class="w-auto min-w-[20px] px-1 select-none text-center max-sm:text-sm">
                @{{ quantity }}
            </p>
            
            <span 
                class="icon-plus cursor-pointer text-2xl"
                :class="{'opacity-40 !cursor-not-allowed': isAtMax}"
                role="button"
                tabindex="0"
                aria-label="@lang('shop::app.components.quantity-changer.increase-quantity')"
                @click="increase"
            >
            </span>

            <v-field
                type="hidden"
                :name="name"
                v-model="quantity"
            ></v-field>
        </div>
    </script>

    <script type="module">
        app.component("v-quantity-changer", {
            template: '#v-quantity-changer-template',

            props: ['name', 'value', 'minValue', 'maxValue'],

            data() {
                return  {
                    quantity: parseInt(this.value) || 1,
                }
            },

            computed: {
                isAtMin() {
                    let min = parseInt(this.minValue);
                    return !isNaN(min) && this.quantity <= min;
                },

                isAtMax() {
                    if (this.maxValue === null || this.maxValue === undefined || this.maxValue === '') {
                        return false;
                    }
                    let max = parseInt(this.maxValue);
                    return !isNaN(max) && max > 0 && this.quantity >= max;
                }
            },

            watch: {
                value(newVal) {
                    this.quantity = parseInt(newVal) || 1;
                },

                maxValue(newMax) {
                    if (newMax !== null && newMax !== undefined && newMax !== '') {
                        let max = parseInt(newMax);
                        if (!isNaN(max) && max > 0 && this.quantity > max) {
                            this.quantity = max;
                            this.$emit('change', this.quantity);
                        }
                    }
                }
            },

            methods: {
                increase() {
                    if (this.isAtMax) {
                        return;
                    }

                    this.quantity++;
                    this.$emit('change', this.quantity);
                },

                decrease() {
                    if (this.quantity > (parseInt(this.minValue) || 1)) {
                        this.quantity -= 1;

                        this.$emit('change', this.quantity);
                    }
                },
            }
        });
    </script>
@endpushOnce

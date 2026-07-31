@if (Webkul\Product\Helpers\ProductType::hasVariants($product->type))
    {!! view_render_event('bagisto.shop.products.view.configurable-options.before', ['product' => $product]) !!}

    <v-product-configurable-options :errors="errors"></v-product-configurable-options>

    {!! view_render_event('bagisto.shop.products.view.configurable-options.after', ['product' => $product]) !!}


    @pushonce('scripts')
        <script
            type="text/x-template"
            id="v-product-configurable-options-template"
        >
            <div class="w-[455px] max-w-full max-sm:w-full">
                <input
                    type="hidden"
                    name="selected_configurable_option"
                    id="selected_configurable_option"
                    :value="selectedOptionVariant"
                    ref="selected_configurable_option"
                >

                <div
                    class="mt-5"
                    v-for='(attribute, index) in childAttributes'
                >
                    <!-- Dropdown Options Container -->
                    <template v-if="! attribute.swatch_type || attribute.swatch_type == '' || attribute.swatch_type == 'dropdown'">
                        <!-- Dropdown Label -->
                        <h2 class="mb-4 text-xl max-sm:mb-1.5 max-sm:text-base max-sm:font-medium">
                            @{{ attribute.label }}
                        </h2>
                        
                        <!-- Dropdown Options -->
                        <v-field
                            as="select"
                            :name="'super_attribute[' + attribute.id + ']'"
                            class="custom-select mb-3 block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base text-zinc-500 focus:border-blue-500 focus:ring-blue-500"
                            :class="[errors['super_attribute[' + attribute.id + ']'] ? 'border border-red-500' : '']"
                            :id="'attribute_' + attribute.id"
                            v-model="attribute.selectedValue"
                            rules="required"
                            :label="attribute.label"
                            :aria-label="attribute.label"
                            :disabled="attribute.disabled"
                            @change="configure(attribute, $event.target.value)"
                        >
                            <option
                                v-for='(option, index) in attribute.options'
                                :value="option.id"
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
                        <div class="flex flex-wrap items-center gap-2.5">
                            <template v-for="(option, index) in attribute.options">
                                <template v-if="option.id">
                                    <!-- Color Swatch Options -->
                                    <label
                                        class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-lg p-0.5 focus:outline-none"
                                        :class="{'ring-2 ring-navyBlue' : option.id == attribute.selectedValue}"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'color'"
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
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                class="peer sr-only"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <img
                                            v-if="getOptionImage(option)"
                                            :src="getOptionImage(option)"
                                            :alt="option.label"
                                            :title="option.label"
                                            class="h-11 w-11 rounded-md border border-gray-200 object-cover shadow-sm transition-all hover:scale-105 max-sm:h-9 max-sm:w-9"
                                            :class="{'border-navyBlue border-2': option.id == attribute.selectedValue}"
                                        />

                                        <span
                                            v-else
                                            class="h-8 w-8 rounded-full border border-gray-200 max-sm:h-[25px] max-sm:w-[25px]"
                                            tabindex="0"
                                            :style="{ 'background-color': option.swatch_value }"
                                        ></span>
                                    </label>

                                    <!-- Image Swatch Options -->
                                    <label 
                                        class="group relative flex h-[60px] w-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-md border bg-white font-medium uppercase text-gray-900 hover:bg-gray-50 sm:py-6"
                                        :class="{'border-navyBlue' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'image'"
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
                                            />
                                        </v-field>

                                        <img
                                            :src="getOptionImage(option) || option.swatch_value"
                                            :title="option.label"
                                        />
                                    </label>

                                    <!-- Text Swatch Options -->
                                    <label 
                                        class="group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 font-medium uppercase text-gray-900 hover:bg-gray-50 max-sm:h-fit max-sm:w-fit max-sm:px-3.5 max-sm:py-2"
                                        :class="{'border-transparent !bg-navyBlue text-white' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'text'"
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
                                            />
                                        </v-field>

                                        <span class="text-lg max-sm:text-sm">
                                            @{{ option.label }}
                                        </span>

                                        <span
                                            class="pointer-events-none absolute -inset-px rounded-full"
                                            role="presentation"
                                        >
                                        </span>
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

                mounted() {
                    let attributes = JSON.parse(JSON.stringify(this.config)).attributes.slice();

                    let index = attributes.length;

                    while (index--) {
                        let attribute = attributes[index];

                        attribute.options = [];

                        if (index) {
                            attribute.disabled = true;
                        } else {
                            this.fillAttributeOptions(attribute);
                        }

                        attribute = Object.assign(attribute, {
                            childAttributes: this.childAttributes.slice(),
                            prevAttribute: attributes[index - 1],
                            nextAttribute: attributes[index + 1]
                        });

                        this.childAttributes.unshift(attribute);
                    }

                    // Merge all variant images into gallery so all thumbnails remain visible
                    this.$nextTick(() => {
                        this.initAllVariantImages();
                    });
                },

                methods: {
                    getOptionImage(option) {
                        if (! option) {
                            return null;
                        }

                        if (option.swatch_value && (option.swatch_value.startsWith('http') || option.swatch_value.startsWith('/') || option.swatch_value.includes('storage'))) {
                            return option.swatch_value;
                        }

                        if (option.allowedProducts && option.allowedProducts.length > 0) {
                            for (let i = 0; i < option.allowedProducts.length; i++) {
                                let variantId = option.allowedProducts[i];
                                let images = this.config.variant_images?.[variantId];
                                if (images && images.length > 0) {
                                    return images[0].small_image_url || images[0].medium_image_url || images[0].large_image_url;
                                }
                            }
                        }

                        return null;
                    },

                    initAllVariantImages() {
                        let gallery = this.$parent?.$parent?.$refs?.gallery;

                        if (! gallery || ! gallery.media || ! gallery.media.images) {
                            return;
                        }

                        let existingImages = gallery.media.images;

                        Object.values(this.config.variant_images || {}).forEach(images => {
                            images.forEach(img => {
                                if (! existingImages.some(existing => existing.large_image_url === img.large_image_url || existing.original_image_url === img.original_image_url)) {
                                    existingImages.push(img);
                                }
                            });
                        });
                    },

                    autoSelectFirstOptions() {
                        let currentAttribute = this.childAttributes[0];

                        while (currentAttribute) {
                            let realOptions = currentAttribute.options ? currentAttribute.options.filter(option => option.id) : [];

                            if (realOptions.length > 0) {
                                let firstOption = realOptions[0];

                                this.configure(currentAttribute, firstOption.id);

                                currentAttribute = currentAttribute.nextAttribute;
                            } else {
                                break;
                            }
                        }
                    },

                    configure(attribute, optionId) {
                        this.possibleOptionVariant = this.getPossibleOptionVariant(attribute, optionId);

                        if (optionId) {
                            attribute.selectedValue = optionId;
                            
                            if (attribute.nextAttribute) {
                                attribute.nextAttribute.disabled = false;

                                this.clearAttributeSelection(attribute.nextAttribute);

                                this.fillAttributeOptions(attribute.nextAttribute);

                                this.resetChildAttributes(attribute.nextAttribute);
                            } else {
                                this.selectedOptionVariant = this.possibleOptionVariant;
                            }
                        } else {
                            this.clearAttributeSelection(attribute);

                            this.clearAttributeSelection(attribute.nextAttribute);

                            this.resetChildAttributes(attribute);
                        }

                        this.reloadPrice();
                        
                        this.reloadImages();
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

                        if (! options) {
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
                        if (! attribute.childAttributes) {
                            return;
                        }

                        attribute.childAttributes.forEach(function (set) {
                            set.selectedValue = null;

                            set.disabled = true;
                        });
                    },

                    clearAttributeSelection (attribute) {
                        if (! attribute) {
                            return;
                        }

                        attribute.selectedValue = null;

                        this.selectedOptionVariant = null;
                    },

                    reloadPrice () {
                        let selectedOptionCount = this.childAttributes.filter(attribute => attribute.selectedValue).length;

                        let finalPrice = document.querySelector('.final-price');
                        let regularPrice = document.querySelector('.regular-price');
                        let priceLabel = document.querySelector('.price-label');

                        let configVariant = this.config.variant_prices[this.possibleOptionVariant];

                        if (this.childAttributes.length == selectedOptionCount && configVariant) {
                            if (priceLabel) priceLabel.style.display = 'none';

                            if (parseInt(configVariant.regular.price) > parseInt(configVariant.final.price)) {
                                if (regularPrice) {
                                    regularPrice.style.display = 'block';
                                    regularPrice.innerHTML = configVariant.regular.formatted_price;
                                }

                                if (finalPrice) {
                                    finalPrice.innerHTML = configVariant.final.formatted_price;
                                }
                            } else {
                                if (finalPrice) {
                                    finalPrice.innerHTML = configVariant.regular.formatted_price;
                                }

                                if (regularPrice) {
                                    regularPrice.style.display = 'none';
                                }
                            }

                            this.$emitter.emit('configurable-variant-selected-event', this.possibleOptionVariant);
                        } else {
                            if (priceLabel) priceLabel.style.display = 'inline-block';

                            if (finalPrice && this.config.regular) {
                                finalPrice.innerHTML = this.config.regular.formatted_price;
                            }

                            this.$emitter.emit('configurable-variant-selected-event', 0);
                        }
                    },

                    reloadImages () {
                        let gallery = this.$parent?.$parent?.$refs?.gallery;

                        if (! gallery || ! gallery.media || ! gallery.media.images) {
                            return;
                        }

                        if (this.possibleOptionVariant && this.config.variant_images[this.possibleOptionVariant]) {
                            let variantImages = this.config.variant_images[this.possibleOptionVariant];

                            if (variantImages.length > 0) {
                                let selectedVariantImage = variantImages[0];
                                let baseImages = gallery.media.images;

                                let targetIndex = baseImages.findIndex(img => 
                                    img.large_image_url === selectedVariantImage.large_image_url ||
                                    img.original_image_url === selectedVariantImage.original_image_url ||
                                    img.medium_image_url === selectedVariantImage.medium_image_url
                                );

                                if (targetIndex !== -1) {
                                    gallery.change(baseImages[targetIndex], targetIndex);
                                } else {
                                    baseImages.unshift(selectedVariantImage);
                                    gallery.change(selectedVariantImage, 0);
                                }
                            }
                        }

                        this.$emitter.emit('configurable-variant-update-images-event', gallery.media.images);
                    },
                }
            });

        </script>
    @endpushonce
@endif
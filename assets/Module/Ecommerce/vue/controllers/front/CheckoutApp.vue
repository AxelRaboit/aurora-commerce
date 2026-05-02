<script setup>
import { reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import { useStripeCard } from "@ecommerce/front/checkout/composables/useStripeCard.js";
import { useCheckoutSubmit } from "@ecommerce/front/checkout/composables/useCheckoutSubmit.js";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";

const { t } = useI18n();

const props = defineProps({
    cart: { type: Object, required: true },
    initialForm: { type: Object, required: true },
    requiresShipping: { type: Boolean, default: false },
    countries: { type: [Array, Object], default: () => ({}) },
    stripePublicKey: { type: String, required: true },
    submitPath: { type: String, required: true },
    locale: { type: String, default: "fr" },
});

const form = reactive({ ...props.initialForm });

const countryOptions = computed(() => {
    if (Array.isArray(props.countries)) return props.countries;
    return Object.entries(props.countries).map(([value, label]) => ({ value, label }));
});

const { cardError, confirmPayment } = useStripeCard(props.stripePublicKey, props.locale);
const { errors, stockError, processing, submit } = useCheckoutSubmit(props.submitPath, confirmPayment);

function handleSubmit() {
    submit(form, { name: form.name, email: form.email });
}
</script>

<template>
    <section>
        <h1 class="text-3xl font-bold mb-6">{{ t('front.checkout.title') }}</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div v-if="stockError" class="bg-rose-500/10 border border-rose-500/30 text-rose-300 rounded-xl px-4 py-3 text-sm">
                    {{ stockError }}
                </div>

                <!-- Contact -->
                <div class="bg-surface border border-line rounded-xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-primary">{{ t('front.checkout.contact') }}</h2>
                    <AppInput
                        v-model="form.email"
                        type="email"
                        name="email"
                        :label="t('front.checkout.email')"
                        :error="errors.email"
                        :required="true"
                    />
                    <AppInput
                        v-model="form.name"
                        name="name"
                        :label="t('front.checkout.name')"
                        :error="errors.name"
                        :required="true"
                    />
                </div>

                <!-- Shipping -->
                <div v-if="requiresShipping" class="bg-surface border border-line rounded-xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-primary">{{ t('front.checkout.shipping') }}</h2>
                    <AppInput
                        v-model="form.addressLine1"
                        name="addressLine1"
                        :label="t('front.checkout.address_line_1')"
                        :error="errors.addressLine1"
                        :required="true"
                    />
                    <AppInput v-model="form.addressLine2" name="addressLine2" :label="t('front.checkout.address_line_2')" :error="errors.addressLine2" />
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <AppInput
                                v-model="form.city"
                                name="city"
                                :label="t('front.checkout.city')"
                                :error="errors.city"
                                :required="true"
                            />
                        </div>
                        <AppInput
                            v-model="form.postalCode"
                            name="postalCode"
                            :label="t('front.checkout.postal_code')"
                            :error="errors.postalCode"
                            :required="true"
                        />
                    </div>
                    <AppMultiselect
                        v-model="form.country"
                        :label="t('front.checkout.country')"
                        :placeholder="t('front.checkout.country_placeholder')"
                        :options="countryOptions"
                        :error="errors.country"
                        :required="true"
                    />
                    <AppTextarea v-model="form.notes" name="notes" :label="t('front.checkout.notes')" :rows="3" />
                </div>

                <!-- Digital -->
                <div v-else class="bg-surface border border-line rounded-xl p-6 space-y-3">
                    <h2 class="text-lg font-semibold text-primary">{{ t('front.checkout.digital_delivery') }}</h2>
                    <p class="text-sm text-secondary">{{ t('front.checkout.digital_delivery_hint') }}</p>
                    <AppTextarea v-model="form.notes" name="notes" :label="t('front.checkout.notes')" :rows="3" />
                </div>

                <!-- Stripe -->
                <div class="bg-surface border border-line rounded-xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-primary">{{ t('front.checkout.payment') }}</h2>
                    <p class="text-xs text-muted">{{ t('front.checkout.demo_card_hint') }}</p>
                    <div id="card-element" class="px-3 py-3 rounded-md bg-surface-2 border border-line" :class="{ 'border-red-500': cardError }" />
                    <p v-if="cardError" class="text-xs text-red-500">{{ cardError }}</p>
                </div>
            </div>

            <!-- Summary -->
            <aside>
                <div class="bg-surface border border-line rounded-xl p-6 space-y-3 lg:sticky lg:top-4">
                    <h2 class="text-lg font-semibold text-primary mb-3">{{ t('front.checkout.summary') }}</h2>
                    <ul class="space-y-2 text-sm border-b border-line pb-3">
                        <li v-for="item in cart.items" :key="item.id" class="flex justify-between gap-3">
                            <span class="text-secondary">{{ item.title }} × {{ item.quantity }}</span>
                            <span class="text-primary tabular-nums shrink-0">{{ formatMoney(item.subtotal, item.currencySymbol) }}</span>
                        </li>
                    </ul>
                    <div class="flex items-center justify-between pt-2">
                        <span class="font-semibold text-primary">{{ t('front.cart.total') }}</span>
                        <span class="text-xl font-bold text-accent">{{ formatMoney(cart.total, cart.currencySymbol) }}</span>
                    </div>
                    <AppButton
                        variant="accent"
                        size="lg"
                        :loading="processing"
                        :disabled="processing"
                        class="w-full"
                        v-on:click="handleSubmit"
                    >
                        {{ processing ? t('front.checkout.processing') : t('front.checkout.confirm') }}
                    </AppButton>
                </div>
            </aside>
        </div>
    </section>
</template>

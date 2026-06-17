<script setup lang="ts">
// Modale de réservation à 3 étapes : choix des dates -> paiement Stripe ->
// confirmation. Avant Stripe, "Confirm" créait directement la réservation en
// statut "pending" ; maintenant, aucune réservation n'existe en base tant
// que le paiement n'a pas réellement réussi (vérifié côté serveur) — voir
// BookingService::createBooking().
import { ref, computed, nextTick, watch } from 'vue';
import type { StripeElements, StripePaymentElement } from '@stripe/stripe-js';
import { router } from '@inertiajs/vue3';
import { useStripe } from '@/composables/useStripe';
import api from '@/lib/api';

interface Room {
    id: number;
    name: string;
    price_per_night: string;
}

const { room, modelValue } = defineProps<{
    room: Room | null;
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

// "step" pilote quel contenu de la modale est affiché — une seule modale,
// trois écrans, plutôt que trois v-dialog séparées à orchestrer entre elles.
type Step = 'dates' | 'payment' | 'confirmed';
const step = ref<Step>('dates');

const checkIn = ref('');
const checkOut = ref('');
const notes = ref('');
const error = ref('');
const loading = ref(false);

// Renseignés par /api/payments/intent, nécessaires pour afficher le
// formulaire de carte Stripe puis pour créer la réservation après paiement.
const clientSecret = ref('');
const paymentIntentId = ref('');
const amount = ref(0);

// Détails de la réservation créée, affichés sur l'écran de confirmation.
const confirmedBooking = ref<{ checkIn: string; checkOut: string; amount: number } | null>(null);

// Référence vers la <div> où Stripe va monter visuellement son formulaire de
// carte — Stripe Elements ne fonctionne pas comme un composant Vue classique,
// il a besoin d'un vrai noeud DOM existant sur lequel s'attacher via .mount().
const paymentElementContainer = ref<HTMLDivElement | null>(null);
let elements: StripeElements | null = null;
let paymentElement: StripePaymentElement | null = null;

function close() {
    emit('update:modelValue', false);
}

// Remet tout à zéro quand la modale se ferme, pour repartir propre la
// prochaine fois (sinon on garderait les dates/erreurs d'une réservation
// précédente en mémoire).
watch(
    () => modelValue,
    (isOpen) => {
        if (!isOpen) {
            step.value = 'dates';
            checkIn.value = '';
            checkOut.value = '';
            notes.value = '';
            error.value = '';
            clientSecret.value = '';
            paymentElement = null;
            elements = null;
        }
    },
);

const nights = computed(() => {
    if (!checkIn.value || !checkOut.value) return 0;

    const diff = new Date(checkOut.value).getTime() - new Date(checkIn.value).getTime();

    return Math.max(0, Math.round(diff / (1000 * 60 * 60 * 24)));
});

// Étape 1 -> 2 : demande à Stripe (via notre backend) de préparer un paiement
// pour le montant exact de ce séjour, avant d'afficher le formulaire de carte.
async function goToPayment() {
    if (!room) return;

    error.value = '';
    loading.value = true;

    try {
        const { data } = await api.post('/payments/intent', {
            room_id: room.id,
            check_in: checkIn.value,
            check_out: checkOut.value,
        });

        clientSecret.value = data.client_secret;
        paymentIntentId.value = data.payment_intent_id;
        amount.value = data.amount;
        step.value = 'payment';

        // nextTick : on attend que Vue ait fini de rendre le <div v-if="step
        // === 'payment'"> avant d'essayer de monter Stripe dedans — sinon le
        // noeud DOM n'existe pas encore au moment de l'appel.
        await nextTick();
        await mountPaymentElement();
    } catch (e: any) {
        const errors = e.response?.data?.errors;
        error.value = errors ? Object.values(errors).flat().join(' ') : 'Unable to start payment.';
    } finally {
        loading.value = false;
    }
}

async function mountPaymentElement() {
    const stripe = await useStripe();

    if (!stripe || !paymentElementContainer.value) return;

    // appearance: 'night' = thème sombre Stripe, cohérent avec le thème
    // Vuetify "dark" utilisé partout ailleurs dans l'app (plugins/vuetify.ts).
    elements = stripe.elements({
        clientSecret: clientSecret.value,
        appearance: { theme: 'night' },
    });

    paymentElement = elements.create('payment');
    paymentElement.mount(paymentElementContainer.value);
}

// Étape 2 -> 3 : confirme le paiement auprès de Stripe, puis (seulement si
// le paiement a vraiment réussi) crée la réservation côté Laravel.
async function pay() {
    if (!room) return;

    const stripe = await useStripe();

    if (!stripe || !elements) return;

    error.value = '';
    loading.value = true;

    try {
        // redirect: 'if_required' = ne quitte la page que si le moyen de
        // paiement l'exige vraiment (ex: certaines 3D Secure) ; sinon le
        // résultat revient directement en JS, sans rechargement de page.
        const result = await stripe.confirmPayment({
            elements,
            redirect: 'if_required',
        });

        if (result.error) {
            error.value = result.error.message ?? 'Payment failed.';

            return;
        }

        if (result.paymentIntent?.status !== 'succeeded') {
            error.value = 'Payment was not completed.';

            return;
        }

        // Le paiement Stripe a réussi — on crée maintenant la réservation.
        // Le serveur revérifie tout (statut + montant exact auprès de Stripe
        // lui-même) avant de créer quoi que ce soit ; ce n'est jamais cette
        // requête frontend, seule, qui "fait confiance" au paiement.
        await api.post('/bookings', {
            room_id: room.id,
            check_in: checkIn.value,
            check_out: checkOut.value,
            notes: notes.value || undefined,
            payment_intent_id: result.paymentIntent.id,
        });

        confirmedBooking.value = {
            checkIn: checkIn.value,
            checkOut: checkOut.value,
            amount: amount.value,
        };
        step.value = 'confirmed';
    } catch (e: any) {
        const errors = e.response?.data?.errors;
        error.value = errors ? Object.values(errors).flat().join(' ') : 'Booking failed.';
    } finally {
        loading.value = false;
    }
}

function goToDashboard() {
    close();
    router.visit('/dashboard');
}
</script>

<template>
    <v-dialog :model-value="modelValue" max-width="500" persistent @update:model-value="emit('update:modelValue', $event)">
        <v-card rounded="lg" class="pa-4">
            <!-- Étape 1 : dates -->
            <template v-if="step === 'dates'">
                <v-card-title>Book {{ room?.name }}</v-card-title>

                <v-card-text>
                    <v-alert v-if="error" type="error" density="compact" class="mb-4">
                        {{ error }}
                    </v-alert>

                    <v-text-field v-model="checkIn" label="Check-in" type="date" variant="outlined" class="mb-2" />
                    <v-text-field v-model="checkOut" label="Check-out" type="date" variant="outlined" class="mb-2" />
                    <v-textarea v-model="notes" label="Notes (optional)" variant="outlined" rows="2" />

                    <p v-if="nights > 0 && room" class="text-body-2 text-medium-emphasis">
                        {{ nights }} night{{ nights > 1 ? 's' : '' }} × {{ room.price_per_night }} €
                    </p>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="close">Cancel</v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :loading="loading"
                        :disabled="!checkIn || !checkOut"
                        @click="goToPayment"
                    >
                        Continue to payment
                    </v-btn>
                </v-card-actions>
            </template>

            <!-- Étape 2 : paiement Stripe -->
            <template v-else-if="step === 'payment'">
                <v-card-title>Payment</v-card-title>

                <v-card-text>
                    <v-alert v-if="error" type="error" density="compact" class="mb-4">
                        {{ error }}
                    </v-alert>

                    <p class="text-h6 font-weight-bold mb-4">{{ amount }} €</p>

                    <!-- Stripe monte son propre formulaire de carte ici via
                         JS, pas via du template Vue classique. -->
                    <div ref="paymentElementContainer"></div>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="step = 'dates'">Back</v-btn>
                    <v-btn color="primary" variant="flat" :loading="loading" @click="pay">
                        Pay {{ amount }} €
                    </v-btn>
                </v-card-actions>
            </template>

            <!-- Étape 3 : confirmation -->
            <template v-else>
                <v-card-text class="text-center py-6">
                    <v-icon icon="mdi-check-circle" color="success" size="64" class="mb-4" />
                    <h2 class="text-h5 font-weight-bold mb-2">Booking confirmed!</h2>
                    <p v-if="confirmedBooking" class="text-body-2 text-medium-emphasis">
                        {{ confirmedBooking.checkIn }} → {{ confirmedBooking.checkOut }} — {{ confirmedBooking.amount }} €
                    </p>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="close">Close</v-btn>
                    <v-btn color="primary" variant="flat" @click="goToDashboard">View my bookings</v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

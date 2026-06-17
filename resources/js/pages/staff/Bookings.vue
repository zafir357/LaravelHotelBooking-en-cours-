<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import api from '@/lib/api';

interface Booking {
    id: number;
    check_in: string;
    check_out: string;
    total_price: string;
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
    notes: string | null;
    room: {
        id: number;
        name: string;
        type: string;
    };
    // Présent uniquement parce que BookingController::index() charge la
    // relation "user" pour les requêtes admin/receptionist (voir
    // BookingResource::toArray() — whenLoaded('user')). Optionnel ici par
    // sécurité côté TypeScript, même si en pratique toujours présent sur cette page.
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

const bookings = ref<Booking[]>([]);
const loading = ref(true);
const actionError = ref('');

// "user" sert à savoir si on affiche le bouton "Cancel" (réservé à l'admin —
// voir BookingPolicy::delete(), qui refuse ce rôle à un receptionist).
const { user } = useAuth();

const statusColors: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'error',
    completed: 'grey',
};

async function fetchBookings() {
    loading.value = true;

    try {
        const { data } = await api.get('/bookings');
        bookings.value = data.data;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchBookings);

// "Approve" = passer une réservation de pending à confirmed. Autorisé pour
// admin ET receptionist (BookingPolicy::update() accepte les deux tant que
// le statut actuel est pending ou confirmed).
async function approveBooking(booking: Booking) {
    actionError.value = '';

    try {
        await api.patch(`/bookings/${booking.id}`, { status: 'confirmed' });
        booking.status = 'confirmed';
    } catch (e: any) {
        actionError.value = e.response?.data?.message ?? 'Action failed.';
    }
}

// "Mark completed" = confirmed -> completed, typiquement après le séjour.
async function completeBooking(booking: Booking) {
    actionError.value = '';

    try {
        await api.patch(`/bookings/${booking.id}`, { status: 'completed' });
        booking.status = 'completed';
    } catch (e: any) {
        actionError.value = e.response?.data?.message ?? 'Action failed.';
    }
}

// Annulation — admin et receptionist y ont droit (BookingPolicy::delete()),
// tant que la réservation n'est pas déjà terminée/annulée.
async function cancelBooking(booking: Booking) {
    if (!confirm('Cancel this booking?')) return;

    actionError.value = '';

    try {
        await api.delete(`/bookings/${booking.id}`);
        booking.status = 'cancelled';
    } catch (e: any) {
        actionError.value = e.response?.data?.message ?? 'Action failed.';
    }
}
</script>

<template>
    <div>
        <v-sheet color="grey-lighten-5">
            <v-container fluid class="py-10 px-6">
                <div class="mb-8">
                    <div class="text-overline text-primary mb-1">Staff</div>
                    <h1 class="text-h4 font-weight-bold">Manage bookings</h1>
                </div>

                <v-alert v-if="actionError" type="error" density="compact" class="mb-4">
                    {{ actionError }}
                </v-alert>

                <v-row v-if="loading">
                    <v-col v-for="n in 3" :key="n" cols="12">
                        <v-skeleton-loader type="list-item-avatar-three-line" />
                    </v-col>
                </v-row>

                <v-row v-else-if="!bookings.length">
                    <v-col cols="12" class="text-center text-medium-emphasis py-12">
                        No bookings yet.
                    </v-col>
                </v-row>

                <v-row v-else>
                    <v-col v-for="booking in bookings" :key="booking.id" cols="12" md="6" lg="4">
                        <v-card elevation="0" rounded="lg" class="h-100 d-flex flex-column">
                            <v-card-item>
                                <div class="d-flex align-start justify-space-between ga-2">
                                    <v-card-title class="pa-0" style="white-space: normal; line-height: 1.3;">
                                        {{ booking.room.name }}
                                    </v-card-title>
                                    <v-chip size="small" :color="statusColors[booking.status]" variant="tonal" class="shrink-0 mt-1">
                                        {{ booking.status }}
                                    </v-chip>
                                </div>
                                <v-card-subtitle class="pa-0 mt-1">
                                    {{ booking.check_in }} → {{ booking.check_out }}
                                </v-card-subtitle>
                            </v-card-item>

                            <v-card-text class="flex-grow-1">
                                <p v-if="booking.user" class="text-body-2 mb-1">
                                    <v-icon icon="mdi-account-outline" size="16" class="mr-1" />
                                    {{ booking.user.name }} ({{ booking.user.email }})
                                </p>
                                <p class="text-body-2 text-medium-emphasis mb-0" v-if="booking.notes">
                                    {{ booking.notes }}
                                </p>
                                <span class="text-h6 font-weight-bold">{{ booking.total_price }} €</span>
                            </v-card-text>

                            <v-divider />
                            <v-card-actions class="px-4 py-3 flex-wrap ga-2">
                                <v-btn
                                    v-if="booking.status === 'pending'"
                                    size="small"
                                    color="success"
                                    variant="flat"
                                    rounded="lg"
                                    class="text-none"
                                    @click="approveBooking(booking)"
                                >
                                    Approve
                                </v-btn>

                                <v-btn
                                    v-if="booking.status === 'confirmed'"
                                    size="small"
                                    color="primary"
                                    variant="flat"
                                    rounded="lg"
                                    class="text-none"
                                    @click="completeBooking(booking)"
                                >
                                    Mark completed
                                </v-btn>

                                <v-spacer />

                                <!-- Admin ET receptionist peuvent annuler (voir
                                     BookingPolicy::delete()), mais pas une
                                     réservation déjà annulée ou terminée — ça
                                     n'aurait pas de sens. -->
                                <v-btn
                                    v-if="(user?.role === 'admin' || user?.role === 'receptionist') && booking.status !== 'cancelled' && booking.status !== 'completed'"
                                    size="small"
                                    color="error"
                                    variant="text"
                                    class="text-none"
                                    @click="cancelBooking(booking)"
                                >
                                    Cancel
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-sheet>
    </div>
</template>

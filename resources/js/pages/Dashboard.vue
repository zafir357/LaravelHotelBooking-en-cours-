<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import api from '@/lib/api';



// Interface = la "forme" attendue d'une réservation venant de l'API
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
        images: string[];
    };
}

const { user, fetchUser, logout } = useAuth();
const bookings = ref<Booking[]>([]);
const loading = ref(true);

// Couleurs Vuetify selon le statut — utilisé pour les v-chip
const statusColors: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'error',
    completed: 'grey',
};

// computed() = recalculé automatiquement chaque fois que "bookings" change
// (ex: après cancelBooking()), sans qu'on ait besoin de le refaire à la main.
// Trois groupes plutôt que deux : on ne voulait pas perdre la visibilité sur
// les réservations passées (annulées/terminées) en les faisant disparaître.
const pendingBookings = computed(() =>
    bookings.value.filter((b) => b.status === 'pending'),
);
const confirmedBookings = computed(() =>
    bookings.value.filter((b) => b.status === 'confirmed'),
);
const historyBookings = computed(() =>
    bookings.value.filter((b) => b.status === 'cancelled' || b.status === 'completed'),
);

onMounted(async () => {
    // 1. Vérifie l'authentification — si pas de token valide, redirige
    const currentUser = await fetchUser();

    if (!currentUser) {
        // router.visit() = navigation Inertia (pas de rechargement complet)
        router.visit('/login');
        return; // arrête l'exécution, pas besoin de charger les bookings
    }

    // 2. Charge les réservations du user connecté
    // api = notre instance axios avec l'intercepteur (header Authorization auto)
    try {
        const { data } = await api.get('/bookings');
        bookings.value = data.data; // .data car BookingResource::collection() wrap dans "data"
    } finally {
        loading.value = false;
    }
});

// Annule une réservation
async function cancelBooking(id: number) {
    // confirm() = popup natif du navigateur, simple pour un portfolio
    if (!confirm('Annuler cette réservation ?')) return;

    await api.delete(`/bookings/${id}`);

    // Met à jour localement sans recharger toute la liste
    const booking = bookings.value.find((b) => b.id === id);
    if (booking) booking.status = 'cancelled';
}
</script>

<template>
    <v-app>
        <!-- Navigation -->
        <v-app-bar color="white" elevation="1" height="72">
            <v-container class="d-flex align-center">
                <v-icon icon="mdi-bed-king-outline" size="28" color="primary" class="mr-2" />
                <span class="text-h6 font-weight-bold">Maison Bellevue</span>

                <v-spacer />

                <v-btn variant="text" class="text-none mr-2" href="/">Home</v-btn>

                <template v-if="user">
                    <span class="text-body-2 mr-4">Hi, {{ user.name }}</span>
                    <v-btn variant="outlined" class="text-none" rounded="lg" @click="logout">
                        Log out
                    </v-btn>
                </template>
            </v-container>
        </v-app-bar>

        <v-main>
            <v-container class="py-8">
                <h1 class="text-h4 font-weight-bold mb-6">My bookings</h1>

                <!-- État de chargement -->
                <v-row v-if="loading">
                    <v-col v-for="n in 2" :key="n" cols="12">
                        <v-skeleton-loader type="list-item-avatar-three-line" />
                    </v-col>
                </v-row>

                <!-- Aucune réservation -->
                <v-row v-else-if="!bookings.length">
                    <v-col cols="12" class="text-center py-12">
                        <v-icon icon="mdi-calendar-blank-outline" size="64" color="grey-lighten-1" class="mb-4" />
                        <p class="text-h6 text-medium-emphasis mb-4">No bookings yet</p>
                        <v-btn color="primary" variant="flat" rounded="lg" href="/#rooms">
                            Browse rooms
                        </v-btn>
                    </v-col>
                </v-row>

                <template v-else>
                    <!-- Section 1 : en attente de validation par le staff
                         (receptionist/admin) — voir /staff/bookings. -->
                    <div v-if="pendingBookings.length" class="mb-8">
                        <h2 class="text-h6 font-weight-bold mb-3">Pending approval</h2>
                        <v-row>
                            <v-col v-for="booking in pendingBookings" :key="booking.id" cols="12" md="6">
                                <v-card elevation="0" rounded="lg" class="d-flex">
                                    <v-img
                                        :src="booking.room.images?.[0] || '/storage/rooms/placeholder.jpg'"
                                        width="140"
                                        cover
                                        class="flex-shrink-0"
                                    />
                                    <div class="flex-grow-1">
                                        <v-card-item>
                                            <div class="d-flex align-center justify-space-between">
                                                <v-card-title class="pa-0">{{ booking.room.name }}</v-card-title>
                                                <v-chip size="small" :color="statusColors[booking.status]" variant="tonal">
                                                    {{ booking.status }}
                                                </v-chip>
                                            </div>
                                            <v-card-subtitle class="pa-0 mt-1">
                                                {{ booking.check_in }} → {{ booking.check_out }}
                                            </v-card-subtitle>
                                        </v-card-item>

                                        <v-card-text class="pt-0">
                                            <span class="text-h6 font-weight-bold">{{ booking.total_price }} €</span>
                                        </v-card-text>

                                        <v-card-actions>
                                            <v-btn size="small" color="error" variant="text" @click="cancelBooking(booking.id)">
                                                Cancel
                                            </v-btn>
                                        </v-card-actions>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Section 2 : validées par le staff — prêtes pour le séjour. -->
                    <div v-if="confirmedBookings.length" class="mb-8">
                        <h2 class="text-h6 font-weight-bold mb-3">Booking validated</h2>
                        <v-row>
                            <v-col v-for="booking in confirmedBookings" :key="booking.id" cols="12" md="6">
                                <v-card elevation="0" rounded="lg" class="d-flex">
                                    <v-img
                                        :src="booking.room.images?.[0] || '/storage/rooms/placeholder.jpg'"
                                        width="140"
                                        cover
                                        class="flex-shrink-0"
                                    />
                                    <div class="flex-grow-1">
                                        <v-card-item>
                                            <div class="d-flex align-center justify-space-between">
                                                <v-card-title class="pa-0">{{ booking.room.name }}</v-card-title>
                                                <v-chip size="small" :color="statusColors[booking.status]" variant="tonal">
                                                    {{ booking.status }}
                                                </v-chip>
                                            </div>
                                            <v-card-subtitle class="pa-0 mt-1">
                                                {{ booking.check_in }} → {{ booking.check_out }}
                                            </v-card-subtitle>
                                        </v-card-item>

                                        <v-card-text class="pt-0">
                                            <span class="text-h6 font-weight-bold">{{ booking.total_price }} €</span>
                                        </v-card-text>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Section 3 : historique (annulées/terminées) — pas demandé
                         explicitement, mais on évite de faire disparaître ces
                         réservations sans aucune trace nulle part. -->
                    <div v-if="historyBookings.length">
                        <h2 class="text-h6 font-weight-bold mb-3 text-medium-emphasis">History</h2>
                        <v-row>
                            <v-col v-for="booking in historyBookings" :key="booking.id" cols="12" md="6">
                                <v-card elevation="0" rounded="lg" class="d-flex" style="opacity: 0.7">
                                    <v-img
                                        :src="booking.room.images?.[0] || '/storage/rooms/placeholder.jpg'"
                                        width="140"
                                        cover
                                        class="flex-shrink-0"
                                    />
                                    <div class="flex-grow-1">
                                        <v-card-item>
                                            <div class="d-flex align-center justify-space-between">
                                                <v-card-title class="pa-0">{{ booking.room.name }}</v-card-title>
                                                <v-chip size="small" :color="statusColors[booking.status]" variant="tonal">
                                                    {{ booking.status }}
                                                </v-chip>
                                            </div>
                                            <v-card-subtitle class="pa-0 mt-1">
                                                {{ booking.check_in }} → {{ booking.check_out }}
                                            </v-card-subtitle>
                                        </v-card-item>

                                        <v-card-text class="pt-0">
                                            <span class="text-h6 font-weight-bold">{{ booking.total_price }} €</span>
                                        </v-card-text>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                    </div>
                </template>
            </v-container>
        </v-main>
    </v-app>
</template>

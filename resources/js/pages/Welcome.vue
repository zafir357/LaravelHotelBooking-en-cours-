<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import BookingDialog from '@/components/BookingDialog.vue';

interface Room {
    id: number;
    name: string;
    type: string;
    description: string;
    price_per_night: string;
    capacity: number;
    images: string[];
}

const rooms = ref<Room[]>([]);
const loading = ref(true);

// "user" est partagé avec PublicLayout (qui appelle déjà fetchUser() au
// montage) — pas besoin de le refaire ici, juste lire la valeur réactive.
const { user } = useAuth();

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/rooms');
        rooms.value = data.data;
    } finally {
        loading.value = false;
    }
});

const typeLabels: Record<string, string> = {
    single: 'Single room',
    double: 'Double room',
    suite: 'Suite',
};

const selectedRoom = ref<Room | null>(null);
const showBookingDialog = ref(false);

// Tout le flow paiement (intent Stripe -> carte -> confirmation) vit
// maintenant dans BookingDialog.vue, partagé avec Rooms.vue — avant, ce
// bloc dupliquait presque mot pour mot le même code dans les deux pages.
function openBooking(room: Room) {
    if (!user.value) {
        router.visit('/login');
        return;
    }
    selectedRoom.value = room;
    showBookingDialog.value = true;
}
</script>

<template>
    <!-- Plus de <v-app>/<v-app-bar>/<v-footer> ici : PublicLayout.vue les
         fournit désormais. Cette page ne contient plus que son propre
         contenu, exactement comme une page enveloppée par AppLayout. -->
    <div>
            <!-- Hero -->
            <v-sheet color="grey-darken-4" class="position-relative" style="overflow: hidden;">
                <v-img
                    :src="rooms[0]?.images?.[0] || '/storage/rooms/placeholder.jpg'"
                    height="560"
                    cover
                    gradient="to bottom, rgba(0,0,0,.35), rgba(0,0,0,.65)"
                >
                    <v-container class="fill-height">
                        <v-row align="center" class="fill-height">
                            <v-col cols="12" md="8" lg="6">
                                <div class="text-overline text-amber-lighten-2 mb-2 letter-spacing">
                                    Welcome to Maison Bellevue
                                </div>
                                <h1 class="text-h2 text-md-h1 font-weight-bold text-white mb-4" style="line-height: 1.15">
                                    A quiet stay, beautifully made
                                </h1>
                                <p class="text-h6 text-grey-lighten-2 font-weight-regular mb-8" style="max-width: 520px">
                                    Thoughtfully designed rooms, warm hospitality, and a calm
                                    setting — everything you need for a memorable stay.
                                </p>
                                <div class="d-flex flex-wrap ga-3">
                                    <v-btn
                                        size="large"
                                        color="primary"
                                        variant="flat"
                                        rounded="lg"
                                        class="text-none px-6"
                                        href="/rooms"
                                    >
                                        Explore rooms
                                    </v-btn>
                                    <v-btn
                                        v-if="!user"
                                        size="large"
                                        variant="outlined"
                                        rounded="lg"
                                        class="text-none px-6 text-white"
                                        href="/register"
                                    >
                                        Create an account
                                    </v-btn>
                                </div>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-img>
            </v-sheet>

            <!-- Highlights -->
            <v-container class="py-12">
                <v-row>
                    <v-col cols="12" sm="4" v-for="item in [
                        { icon: 'mdi-map-marker-outline', title: 'Prime location', text: 'Steps away from the city centre and main attractions.' },
                        { icon: 'mdi-shield-check-outline', title: 'Secure booking', text: 'Encrypted payments and instant confirmation.' },
                        { icon: 'mdi-headset', title: '24/7 support', text: 'Our team is available around the clock for any request.' },
                    ]" :key="item.title">
                        <div class="d-flex flex-column align-center text-center px-4">
                            <v-avatar color="primary" variant="tonal" size="56" class="mb-4">
                                <v-icon :icon="item.icon" size="28" />
                            </v-avatar>
                            <h3 class="text-subtitle-1 font-weight-bold mb-1">{{ item.title }}</h3>
                            <p class="text-body-2 text-medium-emphasis">{{ item.text }}</p>
                        </div>
                    </v-col>
                </v-row>
            </v-container>

            <!-- Rooms -->
            <v-sheet color="grey-lighten-5" id="rooms">
                <v-container class="py-12">
                    <div class="text-center mb-10">
                        <div class="text-overline text-primary mb-1">Accommodation</div>
                        <h2 class="text-h4 font-weight-bold mb-2">Our rooms &amp; suites</h2>
                        <p class="text-body-1 text-medium-emphasis mx-auto" style="max-width: 520px">
                            Each room is designed for comfort, with a calm palette and
                            everything you need for a relaxing stay.
                        </p>
                    </div>

                    <v-row v-if="loading">
                        <v-col v-for="n in 3" :key="n" cols="12" md="4">
                            <v-skeleton-loader type="image, article" />
                        </v-col>
                    </v-row>

                    <!-- La homepage ne montre qu'un APERÇU de 3 chambres, jamais
                         toute la liste — même si l'API a renvoyé plus de résultats.
                         slice(0, 3) coupe le tableau aux 3 premiers éléments sans
                         modifier "rooms" lui-même (slice ne mute pas l'original,
                         contrairement à splice). La liste complète avec filtres
                         vit sur la page /rooms (Rooms.vue). -->
                    <v-row v-else-if="rooms.length">
                        <v-col v-for="room in rooms.slice(0, 3)" :key="room.id" cols="12" md="4">
                            <v-card elevation="0" rounded="lg" class="h-100 d-flex flex-column">
                                <v-img
                                    :src="room.images?.[0] || '/storage/rooms/placeholder.jpg'"
                                    height="220"
                                    cover
                                />
                                <v-card-item>
                                    <div class="d-flex align-center justify-space-between">
                                        <v-card-title class="pa-0">{{ room.name }}</v-card-title>
                                        <v-chip size="small" color="primary" variant="tonal">
                                            {{ typeLabels[room.type] ?? room.type }}
                                        </v-chip>
                                    </div>
                                    <v-card-subtitle class="pa-0 mt-1">
                                        <v-icon icon="mdi-account-outline" size="16" class="mr-1" />
                                        {{ room.capacity }} guests
                                    </v-card-subtitle>
                                </v-card-item>
                                <v-card-text class="flex-grow-1">
                                    <p class="text-body-2 text-medium-emphasis">
                                        {{ room.description }}
                                    </p>
                                </v-card-text>
                                <v-divider />
                                <v-card-actions class="px-4 py-3">
                                    <div>
                                        <span class="text-h6 font-weight-bold">{{ room.price_per_night }} €</span>
                                        <span class="text-body-2 text-medium-emphasis"> / night</span>
                                    </div>
                                    <v-spacer />
                                    <v-btn color="primary" variant="flat" rounded="lg" class="text-none" @click="openBooking(room)">
                                        Book now
                                    </v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row v-else>
                        <v-col cols="12" class="text-center text-medium-emphasis py-8">
                            No rooms available at the moment.
                        </v-col>
                    </v-row>

                    <!-- Bouton "View more" : visible seulement s'il y a au moins
                         une chambre à montrer (sinon ça n'aurait aucun sens
                         d'inviter à "voir plus" sur une liste vide). Lien simple
                         <a href> plutôt qu'un <Link> Inertia ici, car /rooms est
                         une page Inertia complète (pas un fragment), donc un
                         rechargement de page classique convient. -->
                    <div v-if="rooms.length" class="text-center mt-10">
                        <v-btn
                            size="large"
                            variant="outlined"
                            color="primary"
                            rounded="lg"
                            class="text-none px-8"
                            href="/rooms"
                        >
                            View more rooms
                        </v-btn>
                    </div>
                </v-container>
            </v-sheet>

            <!-- CTA -->
            <v-container class="py-14 text-center" id="about">
                <h2 class="text-h4 font-weight-bold mb-3">Ready for your next stay?</h2>
                <p class="text-body-1 text-medium-emphasis mb-6 mx-auto" style="max-width: 480px">
                    Create an account to book rooms, manage your reservations and
                    receive confirmation emails instantly.
                </p>
                <v-btn
                    v-if="!user"
                    size="large" color="primary" variant="flat" rounded="lg" class="text-none px-8" href="/register"
                >
                    Get started
                </v-btn>
            </v-container>

        <BookingDialog v-model="showBookingDialog" :room="selectedRoom" />
    </div>
</template>

<style scoped>
.letter-spacing {
    letter-spacing: 2px;
}
</style>

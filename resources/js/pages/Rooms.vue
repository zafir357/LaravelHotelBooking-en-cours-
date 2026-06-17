<script setup lang="ts">
// axios = la librairie qui fait les appels HTTP vers /api/rooms.
// On utilise axios directement (pas le composable "api") ici parce que cette
// route est PUBLIQUE (pas besoin d'être connecté pour voir les chambres) —
// "api" (lib/api.ts) sert pour les routes qui nécessitent l'auth (ex: créer une réservation).
import axios from 'axios';
// ref() = état réactif Vue (équivalent d'une variable qui, quand elle change,
// déclenche un re-rendu du template). watch() = exécute du code quand une ref change.
// onMounted() = code qui s'exécute une fois, quand le composant est affiché à l'écran
// (l'équivalent de document.ready côté Laravel/jQuery classique, mais pour ce composant Vue).
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import BookingDialog from '@/components/BookingDialog.vue';

// Décrit la forme des données qu'on reçoit de l'API /api/rooms (via RoomResource
// côté Laravel). TypeScript nous avertira si on essaie d'accéder à un champ
// qui n'existe pas (ex: room.foo), avant même d'exécuter le code dans le navigateur.
interface Room {
    id: number;
    name: string;
    type: string;
    description: string;
    price_per_night: string;
    capacity: number;
    images: string[];
}

// La liste des chambres actuellement affichées à l'écran.
const rooms = ref<Room[]>([]);
// true pendant qu'on attend la réponse de l'API — sert à afficher les
// "skeleton loaders" (rectangles gris animés) à la place des cartes de chambres.
const loading = ref(true);

// "user" est partagé avec PublicLayout (qui appelle déjà fetchUser() au
// montage) — utilisé ici uniquement pour bloquer la réservation si pas connecté.
const { user } = useAuth();

// Traduit le code technique stocké en base ("single", "double", "suite") en
// libellé lisible affiché à l'utilisateur. Évite d'avoir des "if/else" dans le template.
const typeLabels: Record<string, string> = {
    single: 'Single room',
    double: 'Double room',
    suite: 'Suite',
};

// Options du menu déroulant <v-select> pour filtrer par type. "value: null" pour
// la première option = "ne filtre pas sur le type", correspond à null côté backend.
const typeOptions = [
    { title: 'Any type', value: null },
    { title: 'Single room', value: 'single' },
    { title: 'Double room', value: 'double' },
    { title: 'Suite', value: 'suite' },
];

// L'état de TOUS les filtres de la sidebar, regroupé dans un seul objet réactif.
// Pourquoi un seul objet plutôt que 6 ref() séparées ? Parce qu'on veut surveiller
// (watch) TOUS les filtres en même temps avec un seul watcher (voir plus bas),
// au lieu d'en écrire un par champ.
const filters = ref({
    type: null as string | null,
    minPrice: null as number | null,
    maxPrice: null as number | null,
    capacity: null as number | null,
    checkIn: '',
    checkOut: '',
});

// Va chercher la liste des chambres correspondant aux filtres actuels.
// Appelée au chargement de la page ET chaque fois qu'un filtre change (voir watch ci-dessous).
async function fetchRooms() {
    loading.value = true;

    try {
        // axios transforme automatiquement l'objet "params" en query string,
        // ex: { type: 'suite', min_price: 100 } devient ?type=suite&min_price=100
        // dans l'URL réellement envoyée. Les valeurs "undefined" sont automatiquement
        // omises par axios — donc si checkIn est vide, "check_in" n'apparaît pas du
        // tout dans l'URL (important : ça évite d'envoyer check_in='' au backend,
        // qui pourrait être interprété comme une vraie valeur de filtre).
        const { data } = await axios.get('/api/rooms', {
            params: {
                type: filters.value.type,
                min_price: filters.value.minPrice,
                max_price: filters.value.maxPrice,
                capacity: filters.value.capacity,
                check_in: filters.value.checkIn || undefined,
                check_out: filters.value.checkOut || undefined,
            },
        });
        // Laravel's API Resource collections enveloppent toujours la réponse
        // dans une clé "data" (convention JSON:API) — d'où data.data ici.
        rooms.value = data.data;
    } finally {
        // "finally" garantit que loading repasse à false même si la requête
        // échoue (sinon le skeleton loader resterait affiché pour toujours en cas d'erreur réseau).
        loading.value = false;
    }
}

// Remet tous les filtres à zéro (bouton "Reset" dans la sidebar).
// Remplacer l'objet entier (plutôt que remettre chaque champ un par un)
// déclenche un seul re-render au lieu de 6 mises à jour successives.
function resetFilters() {
    filters.value = {
        type: null,
        minPrice: null,
        maxPrice: null,
        capacity: null,
        checkIn: '',
        checkOut: '',
    };
}

// Le "debounce" : sans lui, taper "150" dans le champ prix déclencherait 3 appels
// API (un après le "1", un après le "15", un après le "150"). Ici, on annule
// (clearTimeout) le précédent timer chaque fois qu'un filtre change, et on ne
// programme l'appel réel qu'après 400ms d'inactivité — donc un seul appel API
// une fois que l'utilisateur a fini de taper.
// { deep: true } est nécessaire car "filters" est un objet : par défaut, Vue ne
// surveille que le remplacement complet de l'objet, pas les changements à
// l'intérieur de ses propriétés (ex: filters.value.type = 'suite' ne serait
// pas détecté sans deep: true).
let debounceTimer: ReturnType<typeof setTimeout>;
watch(filters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchRooms, 400);
}, { deep: true });

// Au premier affichage de la page : charge la liste de chambres sans aucun
// filtre appliqué (filters est encore à ses valeurs par défaut à ce stade).
// fetchUser() n'a plus besoin d'être appelé ici, PublicLayout s'en charge déjà.
onMounted(() => {
    fetchRooms();
});

const selectedRoom = ref<Room | null>(null);
const showBookingDialog = ref(false);

// Tout le flow paiement (intent Stripe -> carte -> confirmation) vit dans
// BookingDialog.vue, partagé avec Welcome.vue.
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
         fournit désormais (même principe que Welcome.vue). -->
    <div>
            <v-sheet color="grey-lighten-5">
                <!-- fluid = retire la largeur max par défaut de Vuetify
                     (~1280px) qui laissait un grand espace vide à droite sur
                     les écrans larges. La page prend maintenant toute la
                     largeur disponible. -->
                <v-container fluid class="py-10 px-6">
                    <div class="mb-8">
                        <div class="text-overline text-primary mb-1">Accommodation</div>
                        <h1 class="text-h4 font-weight-bold">All rooms &amp; suites</h1>
                    </div>

                    <v-row>
                        <!-- Filter sidebar -->
                        <v-col cols="12" md="3">
                            <v-card elevation="0" rounded="lg" class="pa-4">
                                <div class="d-flex align-center justify-space-between mb-4">
                                    <span class="text-subtitle-1 font-weight-bold">Filters</span>
                                    <v-btn variant="text" size="small" class="text-none" @click="resetFilters">
                                        Reset
                                    </v-btn>
                                </div>

                                <v-select
                                    v-model="filters.type"
                                    :items="typeOptions"
                                    item-title="title"
                                    item-value="value"
                                    label="Room type"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-2"
                                />

                                <v-text-field
                                    v-model.number="filters.minPrice"
                                    label="Min price / night"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-2"
                                />

                                <v-text-field
                                    v-model.number="filters.maxPrice"
                                    label="Max price / night"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-2"
                                />

                                <v-text-field
                                    v-model.number="filters.capacity"
                                    label="Guests (min capacity)"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-2"
                                />

                                <v-divider class="my-3" />

                                <v-text-field
                                    v-model="filters.checkIn"
                                    label="Check-in"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-2"
                                />

                                <v-text-field
                                    v-model="filters.checkOut"
                                    label="Check-out"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                />
                            </v-card>
                        </v-col>

                        <!-- Results -->
                        <v-col cols="12" md="9">
                            <v-row v-if="loading">
                                <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
                                    <v-skeleton-loader type="image, article" />
                                </v-col>
                            </v-row>

                            <v-row v-else-if="rooms.length">
                                <v-col v-for="room in rooms" :key="room.id" cols="12" sm="6" lg="4">
                                    <v-card elevation="0" rounded="lg" class="h-100 d-flex flex-column">
                                        <v-img
                                            :src="room.images?.[0] || '/storage/rooms/placeholder.jpg'"
                                            height="200"
                                            cover
                                        />
                                        <v-card-item>
                                            <!-- align-start (pas align-center) + flex-wrap : permet au
                                                 titre de chambre de passer sur 2 lignes si besoin sans
                                                 écraser le chip à côté. v-card-title a par défaut
                                                 white-space: nowrap + ellipsis (une seule ligne tronquée
                                                 "..."), d'où "Chambre Double 2..." avant ce fix — l'override
                                                 inline style="white-space: normal" l'annule explicitement. -->
                                            <div class="d-flex align-start justify-space-between ga-2">
                                                <v-card-title class="pa-0" style="white-space: normal; line-height: 1.3;">
                                                    {{ room.name }}
                                                </v-card-title>
                                                <v-chip size="small" color="primary" variant="tonal" class="shrink-0 mt-1">
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
                                    No rooms match these filters.
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-container>
            </v-sheet>

        <BookingDialog v-model="showBookingDialog" :room="selectedRoom" />
    </div>
</template>

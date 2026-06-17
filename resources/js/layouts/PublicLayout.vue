<script setup lang="ts">
// Layout partagé par TOUTES les pages publiques Vuetify (Welcome, Rooms, et
// toute future page du même genre). Avant ce fichier, le <v-app-bar> et le
// <v-footer> étaient copiés-collés dans chaque page — ici, ce code n'existe
// plus qu'à un seul endroit, comme AppLayout.vue pour les pages "dashboard".
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';

// "user" est un ref PARTAGÉ entre tous les composants qui appellent useAuth()
// (défini une seule fois au niveau module dans useAuth.ts) — donc peu importe
// que ce soit ce layout OU une page qui appelle fetchUser(), le résultat est
// visible partout où "user" est lu, de façon réactive.
const { user, logout, fetchUser } = useAuth();

// Centralisé ici : avant, Welcome.vue ET Rooms.vue appelaient chacun
// fetchUser() dans leur propre onMounted — dupliqué pour rien, puisque
// TOUTES les pages publiques passent forcément par ce layout.
onMounted(() => {
    fetchUser();
});
</script>

<template>
    <v-app>
        <v-app-bar color="white" elevation="1" height="72">
            <v-container class="d-flex align-center">
                <v-icon icon="mdi-bed-king-outline" size="28" color="primary" class="mr-2" />
                <span
                    class="text-h6 font-weight-bold"
                    style="cursor: pointer"
                    @click="router.visit('/')"
                >
                    Maison Bellevue
                </span>

                <v-spacer />

                <v-btn variant="text" class="text-none mr-2" href="/rooms">Rooms</v-btn>
                <v-btn variant="text" class="text-none mr-2" href="/#about">About</v-btn>

                <template v-if="user">
                    <v-btn variant="text" class="text-none mr-2" href="/dashboard">My bookings</v-btn>
                    <span class="text-body-2 mr-4">Hi, {{ user.name }}</span>
                    <v-btn variant="outlined" class="text-none" rounded="lg" @click="logout">
                        Log out
                    </v-btn>
                </template>

                <template v-else>
                    <v-btn variant="text" class="text-none mr-4" href="/login">Log in</v-btn>
                    <v-btn color="primary" variant="flat" class="text-none" rounded="lg" href="/register">
                        Book a stay
                    </v-btn>
                </template>
            </v-container>
        </v-app-bar>

        <v-main>
            <!-- Le contenu propre à chaque page (hero, grille de chambres,
                 filtres...) s'insère ici via le <slot />. -->
            <slot />
        </v-main>

        <v-footer color="grey-darken-4" class="text-grey-lighten-1 py-8">
            <v-container>
                <v-row>
                    <v-col cols="12" md="6" class="d-flex align-center">
                        <v-icon icon="mdi-bed-king-outline" class="mr-2" />
                        <span class="font-weight-bold text-white">Maison Bellevue</span>
                    </v-col>
                    <v-col cols="12" md="6" class="text-md-right text-body-2">
                        © {{ new Date().getFullYear() }} Maison Bellevue. All rights reserved.
                    </v-col>
                </v-row>
            </v-container>
        </v-footer>
    </v-app>
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import api from '@/lib/api';

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

const { user, fetchUser, logout } = useAuth();

onMounted(async () => {
    await fetchUser();

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
const checkIn = ref('');
const checkOut = ref('');
const bookingError = ref('');
const bookingLoading = ref(false);

function openBooking(room: Room) {
    if (!user.value) {
        router.visit('/login');
        return;
    }
    selectedRoom.value = room;
    showBookingDialog.value = true;
}

async function confirmBooking() {
    bookingError.value = '';
    bookingLoading.value = true;

    try {
        await api.post('/bookings', {
            room_id: selectedRoom.value?.id,
            check_in: checkIn.value,
            check_out: checkOut.value,
        });

        showBookingDialog.value = false;
        router.visit('/dashboard');
    } catch (e: any) {
        const errors = e.response?.data?.errors;
        bookingError.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Booking failed.';
    } finally {
        bookingLoading.value = false;
    }
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

                <v-btn variant="text" class="text-none mr-2" href="#rooms">Rooms</v-btn>
                <v-btn variant="text" class="text-none mr-2" href="#about">About</v-btn>

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
                                        href="#rooms"
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

                    <v-row v-else-if="rooms.length">
                        <v-col v-for="room in rooms" :key="room.id" cols="12" md="4">
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
        </v-main>

        <!-- Footer -->
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

        <!-- Booking modal -->
        <v-dialog v-model="showBookingDialog" max-width="500">
            <v-card rounded="lg" class="pa-4">
                <v-card-title>Book {{ selectedRoom?.name }}</v-card-title>

                <v-card-text>
                    <v-alert v-if="bookingError" type="error" density="compact" class="mb-4">
                        {{ bookingError }}
                    </v-alert>

                    <v-text-field
                        v-model="checkIn"
                        label="Check-in"
                        type="date"
                        variant="outlined"
                        class="mb-2"
                    />
                    <v-text-field
                        v-model="checkOut"
                        label="Check-out"
                        type="date"
                        variant="outlined"
                        class="mb-2"
                    />
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showBookingDialog = false">Cancel</v-btn>
                    <v-btn color="primary" variant="flat" :loading="bookingLoading" @click="confirmBooking">
                        Confirm
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<style scoped>
.letter-spacing {
    letter-spacing: 2px;
}
</style>

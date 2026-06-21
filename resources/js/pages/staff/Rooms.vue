<script setup lang="ts">
// Gestion des chambres (CRUD) réservée à la réceptionniste — backend déjà
// protégé par RoomPolicy::create()/update()/delete() (isReceptionist()), pas
// seulement par cette page Vue.
import { ref, onMounted } from 'vue';
import api from '@/lib/api';

interface Room {
    id: number;
    name: string;
    type: string;
    description: string | null;
    price_per_night: string;
    capacity: number;
    is_available: boolean;
    images: string[];
}

const rooms = ref<Room[]>([]);
const loading = ref(true);
const actionError = ref('');

// Une seule modale pour créer ET éditer — "editingId" vaut null en création,
// l'id de la chambre en édition. Évite de dupliquer le formulaire.
const showDialog = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const form = ref({
    name: '',
    type: 'single' as 'single' | 'double' | 'suite',
    description: '',
    price_per_night: null as number | null,
    capacity: null as number | null,
    images: '',
});

async function fetchRooms() {
    loading.value = true;

    try {
        const { data } = await api.get('/rooms');
        rooms.value = data.data;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchRooms);

function openCreate() {
    editingId.value = null;
    form.value = { name: '', type: 'single', description: '', price_per_night: null, capacity: null, images: '' };
    showDialog.value = true;
}

function openEdit(room: Room) {
    editingId.value = room.id;
    form.value = {
        name: room.name,
        type: room.type as 'single' | 'double' | 'suite',
        description: room.description ?? '',
        price_per_night: Number(room.price_per_night),
        capacity: room.capacity,
        // Les images sont stockées en tableau d'URLs côté backend — ici on les
        // édite comme une simple liste séparée par des retours à la ligne,
        // plus simple à manipuler dans un <v-textarea> qu'un vrai tableau dynamique.
        images: room.images.join('\n'),
    };
    showDialog.value = true;
}

async function save() {
    actionError.value = '';
    saving.value = true;

    const payload = {
        name: form.value.name,
        type: form.value.type,
        description: form.value.description || null,
        price_per_night: form.value.price_per_night,
        capacity: form.value.capacity,
        images: form.value.images.split('\n').map((url) => url.trim()).filter(Boolean),
    };

    try {
        if (editingId.value) {
            await api.put(`/rooms/${editingId.value}`, payload);
        } else {
            await api.post('/rooms', payload);
        }

        showDialog.value = false;
        await fetchRooms();
    } catch (e: any) {
        const errors = e.response?.data?.errors;
        actionError.value = errors ? Object.values(errors).flat().join(' ') : 'Unable to save this room.';
    } finally {
        saving.value = false;
    }
}

async function removeRoom(room: Room) {
    if (!confirm(`Delete "${room.name}"? This cannot be undone.`)) return;

    actionError.value = '';

    try {
        await api.delete(`/rooms/${room.id}`);
        rooms.value = rooms.value.filter((r) => r.id !== room.id);
    } catch (e: any) {
        actionError.value = e.response?.data?.message ?? 'Unable to delete this room.';
    }
}
</script>

<template>
    <div>
        <v-sheet color="grey-lighten-5">
            <v-container fluid class="py-10 px-6">
                <div class="d-flex align-center justify-space-between mb-8">
                    <div>
                        <div class="text-overline text-primary mb-1">Staff</div>
                        <h1 class="text-h4 font-weight-bold">Manage rooms</h1>
                    </div>
                    <v-btn color="primary" variant="flat" rounded="lg" class="text-none" @click="openCreate">
                        Add a room
                    </v-btn>
                </div>

                <v-alert v-if="actionError" type="error" density="compact" class="mb-4">
                    {{ actionError }}
                </v-alert>

                <v-row v-if="loading">
                    <v-col v-for="n in 3" :key="n" cols="12" md="4">
                        <v-skeleton-loader type="image, article" />
                    </v-col>
                </v-row>

                <v-row v-else-if="!rooms.length">
                    <v-col cols="12" class="text-center text-medium-emphasis py-12">
                        No rooms yet.
                    </v-col>
                </v-row>

                <v-row v-else>
                    <v-col v-for="room in rooms" :key="room.id" cols="12" sm="6" lg="4">
                        <v-card elevation="0" rounded="lg" class="h-100 d-flex flex-column">
                            <v-img :src="room.images?.[0] || '/storage/rooms/placeholder.jpg'" height="160" cover />
                            <v-card-item>
                                <div class="d-flex align-start justify-space-between ga-2">
                                    <v-card-title class="pa-0" style="white-space: normal; line-height: 1.3;">
                                        {{ room.name }}
                                    </v-card-title>
                                    <v-chip size="small" :color="room.is_available ? 'success' : 'grey'" variant="tonal" class="shrink-0 mt-1">
                                        {{ room.is_available ? 'Available' : 'Unavailable' }}
                                    </v-chip>
                                </div>
                                <v-card-subtitle class="pa-0 mt-1">
                                    {{ room.type }} — {{ room.capacity }} guests
                                </v-card-subtitle>
                            </v-card-item>
                            <v-card-text class="flex-grow-1">
                                <span class="text-h6 font-weight-bold">{{ room.price_per_night }} €</span>
                                <span class="text-body-2 text-medium-emphasis"> / night</span>
                            </v-card-text>
                            <v-divider />
                            <v-card-actions class="px-4 py-3">
                                <v-btn size="small" variant="text" class="text-none" @click="openEdit(room)">Edit</v-btn>
                                <v-spacer />
                                <v-btn size="small" color="error" variant="text" class="text-none" @click="removeRoom(room)">
                                    Delete
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-sheet>

        <v-dialog v-model="showDialog" max-width="520">
            <v-card rounded="lg" class="pa-4">
                <v-card-title>{{ editingId ? 'Edit room' : 'Add a room' }}</v-card-title>
                <v-card-text>
                    <v-text-field v-model="form.name" label="Name" variant="outlined" class="mb-2" />
                    <v-select
                        v-model="form.type"
                        :items="[{ title: 'Single room', value: 'single' }, { title: 'Double room', value: 'double' }, { title: 'Suite', value: 'suite' }]"
                        item-title="title"
                        item-value="value"
                        label="Type"
                        variant="outlined"
                        class="mb-2"
                    />
                    <v-textarea v-model="form.description" label="Description" variant="outlined" rows="2" class="mb-2" />
                    <v-text-field v-model.number="form.price_per_night" label="Price / night (€)" type="number" variant="outlined" class="mb-2" />
                    <v-text-field v-model.number="form.capacity" label="Capacity (guests)" type="number" variant="outlined" class="mb-2" />
                    <v-textarea
                        v-model="form.images"
                        label="Image URLs (one per line)"
                        variant="outlined"
                        rows="2"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showDialog = false">Cancel</v-btn>
                    <v-btn color="primary" variant="flat" :loading="saving" @click="save">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

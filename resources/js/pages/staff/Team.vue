<script setup lang="ts">
// Permet à une réceptionniste d'inviter un nouveau compte receptionist.
// Pas de mot de passe à saisir ici : ReceptionistController::store() en
// génère un aléatoire et inutilisable, puis envoie un lien (via
// Notification, voir SetPasswordInvite) que l'invité utilisera pour choisir
// lui-même son mot de passe — voir auth/SetPassword.vue.
import { ref } from 'vue';
import api from '@/lib/api';

const name = ref('');
const email = ref('');
const loading = ref(false);
const error = ref('');
const sent = ref(false);

async function invite() {
    error.value = '';
    loading.value = true;

    try {
        await api.post('/staff/receptionists', { name: name.value, email: email.value });
        sent.value = true;
        name.value = '';
        email.value = '';
    } catch (e: any) {
        const errors = e.response?.data?.errors;
        error.value = errors ? Object.values(errors).flat().join(' ') : 'Unable to send the invitation.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <v-sheet color="grey-lighten-5">
            <v-container fluid class="py-10 px-6">
                <div class="mb-8">
                    <div class="text-overline text-primary mb-1">Staff</div>
                    <h1 class="text-h4 font-weight-bold">Team</h1>
                </div>

                <v-row>
                    <v-col cols="12" md="6">
                        <v-card elevation="0" rounded="lg" class="pa-4">
                            <v-card-title class="px-0">Invite a receptionist</v-card-title>

                            <v-alert v-if="sent" type="success" density="compact" class="mb-4">
                                Invitation sent. In local/dev, the link is written to
                                <code>storage/logs/laravel.log</code> (MAIL_MAILER=log) instead of
                                a real email.
                            </v-alert>
                            <v-alert v-if="error" type="error" density="compact" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-text-field v-model="name" label="Name" variant="outlined" class="mb-2" />
                            <v-text-field v-model="email" label="Email" type="email" variant="outlined" class="mb-4" />

                            <v-btn color="primary" variant="flat" rounded="lg" class="text-none" :loading="loading" @click="invite">
                                Send invitation
                            </v-btn>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-sheet>
    </div>
</template>

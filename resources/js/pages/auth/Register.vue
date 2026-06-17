<script setup lang="ts">
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { register } = useAuth();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const loading = ref(false);

// Après inscription réussie — affiche le message de vérification
const registered = ref(false);

async function handleSubmit() {
    error.value = '';
    loading.value = true;

    try {
        await register(name.value, email.value, password.value, passwordConfirmation.value);

        // Pas de redirect — on affiche le message "vérifie ton email"
        registered.value = true;

    } catch (e: any) {
        const errors = e.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat().join(' ');
        } else {
            error.value = 'Registration failed.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <v-app>
        <v-main class="d-flex align-center justify-center" style="min-height: 100vh">
            <v-card width="400" elevation="2" rounded="lg" class="pa-6">

                <!-- Après inscription : message de vérification -->
                <template v-if="registered">
                    <v-card-title class="text-h5 font-weight-bold mb-4">
                        Check your email
                    </v-card-title>

                    <v-alert type="success" variant="tonal" class="mb-4">
                        Account created! We sent a verification link to
                        <strong>{{ email }}</strong>.
                        Click the link in the email to activate your account.
                    </v-alert>

                    <v-btn
                        color="primary"
                        variant="flat"
                        block
                        href="/login"
                    >
                        Go to login
                    </v-btn>
                </template>

                <!-- Formulaire d'inscription -->
                <template v-else>
                    <v-card-title class="text-h5 font-weight-bold mb-4">
                        Create an account
                    </v-card-title>

                    <v-alert v-if="error" type="error" density="compact" class="mb-4">
                        {{ error }}
                    </v-alert>

                    <v-form @submit.prevent="handleSubmit">
                        <v-text-field
                            v-model="name"
                            label="Name"
                            variant="outlined"
                            class="mb-2"
                            required
                        />

                        <v-text-field
                            v-model="email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            class="mb-2"
                            required
                        />

                        <v-text-field
                            v-model="password"
                            label="Password"
                            type="password"
                            variant="outlined"
                            class="mb-2"
                            required
                        />

                        <v-text-field
                            v-model="passwordConfirmation"
                            label="Confirm password"
                            type="password"
                            variant="outlined"
                            class="mb-2"
                            required
                        />

                        <v-btn
                            type="submit"
                            color="primary"
                            variant="flat"
                            block
                            size="large"
                            :loading="loading"
                        >
                            Register
                        </v-btn>
                    </v-form>

                    <div class="text-center mt-4">
                        <span class="text-body-2">Already have an account?</span>
                        <v-btn variant="text" size="small" href="/login">Log in</v-btn>
                    </div>
                </template>

            </v-card>
        </v-main>
    </v-app>
</template>

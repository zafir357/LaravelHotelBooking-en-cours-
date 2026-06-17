<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { login } = useAuth();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleSubmit() {
    error.value = '';
    loading.value = true;

    try {
        await login(email.value, password.value);
        // Redirige vers la page d'accueil après login
        router.visit('/');
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Login failed.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <v-app>
        <v-main class="d-flex align-center justify-center" style="min-height: 100vh">
            <v-card width="400" elevation="2" rounded="lg" class="pa-6">
                <v-card-title class="text-h5 font-weight-bold mb-4">
                    Log in
                </v-card-title>

                <v-alert v-if="error" type="error" density="compact" class="mb-4">
                    {{ error }}
                </v-alert>

                <v-form @submit.prevent="handleSubmit">
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

                    <v-btn
                        type="submit"
                        color="primary"
                        variant="flat"
                        block
                        size="large"
                        :loading="loading"
                    >
                        Log in
                    </v-btn>
                </v-form>

                <div class="text-center mt-4">
                    <span class="text-body-2">Don't have an account?</span>
                    <v-btn variant="text" size="small" href="/register">Register</v-btn>
                </div>
            </v-card>
        </v-main>
    </v-app>
</template>

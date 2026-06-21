<script setup lang="ts">
// Page affichée quand une nouvelle réceptionniste clique sur le lien reçu
// par email (voir ReceptionistController::store() côté backend). Pas de
// useAuth() ici : cette personne n'a PAS encore de session, le lien
// lui-même (signature cryptographique dans l'URL, vérifiée par le
// middleware "signed") est sa seule preuve d'identité à ce stade.
import { Form, Head, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Set your password',
        description: 'Choose a password to activate your receptionist account.',
    },
});

defineProps<{ name: string }>();

// page.url = chemin + query string ACTUELS (donc avec la même signature et
// la même date d'expiration que celles validées pour afficher ce
// formulaire). Soumettre vers cette même URL, en POST, est ce qui permet au
// middleware "signed" de revalider exactement la même signature côté
// serveur — voir le commentaire sur la route dans routes/web.php.
const currentUrl = usePage().url;
</script>

<template>
    <Head title="Set your password" />

    <Form :action="currentUrl" method="post" v-slot="{ errors, processing }">
        <div class="space-y-6">
            <p class="text-sm text-muted-foreground">
                Welcome, {{ name }}. Choose a password to activate your account.
            </p>

            <div class="grid gap-2">
                <Label htmlFor="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    autofocus
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label htmlFor="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Set password and continue
            </Button>
        </div>
    </Form>
</template>

<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Vérifiez votre e-mail" description="Veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer.">
        <Head title="Vérification de l'e-mail" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-success">
            Un nouveau lien de vérification a été envoyé à l'adresse e-mail fournie lors de votre inscription.
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Renvoyer l'e-mail de vérification
            </Button>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm"> Se déconnecter </TextLink>
        </form>
    </AuthLayout>
</template>

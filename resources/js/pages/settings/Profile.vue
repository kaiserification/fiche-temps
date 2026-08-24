<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    matricule: user.matricule ?? '',
    profil: user.profil ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Informations du profil" description="Mettez à jour votre nom et votre adresse e-mail" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Nom complet" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adresse e-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Adresse e-mail"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="matricule">Matricule</Label>
                        <Input id="matricule" class="mt-1 block w-full" v-model="form.matricule" placeholder="Ex: EMP-12345" />
                        <InputError class="mt-2" :message="form.errors.matricule" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="profil">Profil</Label>
                        <Input id="profil" class="mt-1 block w-full" v-model="form.profil" placeholder="Ex: Développeur Senior" />
                        <InputError class="mt-2" :message="form.errors.profil" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="mt-2 text-sm text-foreground">
                            Votre adresse e-mail n'est pas vérifiée.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="focus:outline-hidden rounded-md text-sm text-muted-foreground underline hover:text-foreground focus:ring-2 focus:ring-offset-2"
                            >
                                Cliquez ici pour renvoyer l'e-mail de vérification.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-success">
                            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Enregistrer</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-muted-foreground">Enregistré.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

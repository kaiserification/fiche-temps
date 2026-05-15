<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FolderOpen, Save, Settings, User } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    settings: { sites_dir: string };
    user: { name: string; matricule: string; profil: string };
}

const props = defineProps<Props>();

const savedGit  = ref(false);
const savedUser = ref(false);

const gitForm = useForm({ sites_dir: props.settings.sites_dir });
const userForm = useForm({
    name:      props.user.name,
    matricule: props.user.matricule,
    profil:    props.user.profil,
});

function submitGit() {
    gitForm.patch(route('app-settings.update'), {
        preserveScroll: true,
        onSuccess: () => { savedGit.value = true; setTimeout(() => (savedGit.value = false), 2500); },
    });
}

function submitUser() {
    userForm.patch(route('app-settings.update-user'), {
        preserveScroll: true,
        onSuccess: () => { savedUser.value = true; setTimeout(() => (savedUser.value = false), 2500); },
    });
}
</script>

<template>
    <Head title="Paramètres" />

    <div class="min-h-screen bg-gray-50 transition-colors dark:bg-gray-900">
        <!-- Top bar -->
        <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('fiche.index')"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                            <Settings class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <h1 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Paramètres</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-3xl px-6 py-8 space-y-6">

            <!-- Section : Informations personnelles -->
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-gray-400" />
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Informations personnelles</h2>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Ces informations apparaissent sur vos fiches de temps exportées.
                    </p>
                </div>

                <form @submit.prevent="submitUser" class="px-6 py-5 space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Prénom &amp; Nom</label>
                        <input
                            v-model="userForm.name"
                            type="text"
                            required
                            placeholder="Ex: Jean DUPONT"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                            :class="{ 'border-red-400': userForm.errors.name }"
                        />
                        <p v-if="userForm.errors.name" class="text-xs text-red-600 dark:text-red-400">{{ userForm.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Matricule</label>
                        <input
                            v-model="userForm.matricule"
                            type="text"
                            placeholder="Ex: EMP-12345"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                            :class="{ 'border-red-400': userForm.errors.matricule }"
                        />
                        <p v-if="userForm.errors.matricule" class="text-xs text-red-600 dark:text-red-400">{{ userForm.errors.matricule }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Fonction / Profil</label>
                        <input
                            v-model="userForm.profil"
                            type="text"
                            placeholder="Ex: Développeur Senior"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                            :class="{ 'border-red-400': userForm.errors.profil }"
                        />
                        <p v-if="userForm.errors.profil" class="text-xs text-red-600 dark:text-red-400">{{ userForm.errors.profil }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="userForm.processing"
                            class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <Save class="h-3.5 w-3.5" />
                            Enregistrer
                        </button>
                        <Transition enter-active-class="transition-opacity duration-150" leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-to-class="opacity-0">
                            <span v-if="savedUser" class="text-sm text-emerald-600 dark:text-emerald-400">Enregistré.</span>
                        </Transition>
                    </div>
                </form>
            </div>

            <!-- Section : Git -->
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <FolderOpen class="h-4 w-4 text-gray-400" />
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Répertoire des projets Git</h2>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Chemin absolu vers le dossier contenant vos dépôts Git.
                    </p>
                </div>

                <form @submit.prevent="submitGit" class="px-6 py-5 space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Chemin</label>
                        <input
                            v-model="gitForm.sites_dir"
                            type="text"
                            required
                            placeholder="Ex: C:\Herd\Sites"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                            :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-100': gitForm.errors.sites_dir }"
                        />
                        <p v-if="gitForm.errors.sites_dir" class="text-xs text-red-600 dark:text-red-400">{{ gitForm.errors.sites_dir }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="gitForm.processing"
                            class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <Save class="h-3.5 w-3.5" />
                            Enregistrer
                        </button>
                        <Transition enter-active-class="transition-opacity duration-150" leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-to-class="opacity-0">
                            <span v-if="savedGit" class="text-sm text-emerald-600 dark:text-emerald-400">Enregistré.</span>
                        </Transition>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>

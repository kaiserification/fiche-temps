<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, FolderOpen, Key, PenLine, Save, Trash2, User } from 'lucide-vue-next';
import SignaturePad from 'signature_pad';
import { nextTick, onMounted, ref } from 'vue';
import DirectoryBrowser from '../components/DirectoryBrowser.vue';

interface Props {
    settings: { sites_dir: string };
    user: { name: string; matricule: string; profil: string; signature: string };
    hasGitToken: boolean;
    plainTextToken: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Application', href: '/settings/application' }];

const showBrowser = ref(false);
const savedGit = ref(false);
const savedUser = ref(false);
const savedSignature = ref(false);
const signatureEmpty = ref(true);
const tokenCopied = ref(false);
const hasGitToken = ref(props.hasGitToken);
const tokenProcessing = ref(false);

const signatureCanvas = ref<HTMLCanvasElement | null>(null);
let padInstance: SignaturePad | null = null;

const gitForm = useForm({ sites_dir: props.settings.sites_dir });
const userForm = useForm({
    name: props.user.name,
    matricule: props.user.matricule,
    profil: props.user.profil,
});
const signatureForm = useForm({ signature: '' });

onMounted(async () => {
    await nextTick();
    const canvas = signatureCanvas.value;
    if (!canvas) return;

    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d')!.scale(ratio, ratio);

    padInstance = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    padInstance.addEventListener('endStroke', () => {
        signatureEmpty.value = padInstance!.isEmpty();
    });

    if (props.user.signature) {
        await padInstance.fromDataURL(props.user.signature);
        signatureEmpty.value = false;
    }
});

function clearSignature() {
    padInstance?.clear();
    signatureEmpty.value = true;
}

function submitGit() {
    gitForm.patch(route('app-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            savedGit.value = true;
            setTimeout(() => (savedGit.value = false), 2500);
        },
    });
}

function generateGitToken() {
    tokenProcessing.value = true;
    router.post(
        route('app-settings.git-token.generate'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                hasGitToken.value = true;
            },
            onFinish: () => {
                tokenProcessing.value = false;
            },
        },
    );
}

function revokeGitToken() {
    tokenProcessing.value = true;
    router.delete(route('app-settings.git-token.revoke'), {
        preserveScroll: true,
        onSuccess: () => {
            hasGitToken.value = false;
        },
        onFinish: () => {
            tokenProcessing.value = false;
        },
    });
}

async function copyToken() {
    if (!props.plainTextToken) return;
    await navigator.clipboard.writeText(props.plainTextToken);
    tokenCopied.value = true;
    setTimeout(() => (tokenCopied.value = false), 2000);
}

function submitUser() {
    userForm.patch(route('app-settings.update-user'), {
        preserveScroll: true,
        onSuccess: () => {
            savedUser.value = true;
            setTimeout(() => (savedUser.value = false), 2500);
        },
    });
}

function submitSignature() {
    signatureForm.signature = padInstance && !padInstance.isEmpty() ? padInstance.toDataURL('image/png') : '';
    signatureForm.patch(route('app-settings.update-signature'), {
        preserveScroll: true,
        onSuccess: () => {
            savedSignature.value = true;
            setTimeout(() => (savedSignature.value = false), 2500);
        },
    });
}
</script>

<template>
    <Head title="Application" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="flex flex-col gap-6">
                <!-- Informations personnelles -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <CardTitle class="text-sm font-semibold">Informations personnelles</CardTitle>
                        </div>
                        <CardDescription>Ces informations apparaissent sur vos fiches de temps exportées.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitUser" class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <Label>Prénom &amp; Nom</Label>
                                <Input v-model="userForm.name" type="text" required placeholder="Ex: Jean DUPONT" />
                                <InputError :message="userForm.errors.name" />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label>Matricule</Label>
                                <Input v-model="userForm.matricule" type="text" placeholder="Ex: EMP-12345" />
                                <InputError :message="userForm.errors.matricule" />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label>Fonction / Profil</Label>
                                <Input v-model="userForm.profil" type="text" placeholder="Ex: Développeur Senior" />
                                <InputError :message="userForm.errors.profil" />
                            </div>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="userForm.processing">
                                    <Save class="h-3.5 w-3.5" />
                                    Enregistrer
                                </Button>
                                <Transition
                                    enter-active-class="transition-opacity duration-150"
                                    leave-active-class="transition-opacity duration-300"
                                    enter-from-class="opacity-0"
                                    leave-to-class="opacity-0"
                                >
                                    <span v-if="savedUser" class="text-sm text-success">Enregistré.</span>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Signature -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <PenLine class="h-4 w-4 text-muted-foreground" />
                            <CardTitle class="text-sm font-semibold">Signature</CardTitle>
                        </div>
                        <CardDescription>Dessinez votre signature. Elle apparaîtra en bas du PDF exporté.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="relative h-36 overflow-hidden rounded-lg border border-border bg-muted/40">
                            <canvas ref="signatureCanvas" class="absolute inset-0 h-full w-full touch-none" />
                            <span v-if="signatureEmpty" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-muted-foreground">
                                Signez ici avec la souris ou le doigt...
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button type="button" variant="outline" @click="clearSignature">
                                <Trash2 class="h-3.5 w-3.5" />
                                Effacer
                            </Button>
                            <Button type="button" :disabled="signatureForm.processing" @click="submitSignature">
                                <Save class="h-3.5 w-3.5" />
                                Enregistrer
                            </Button>
                            <Transition
                                enter-active-class="transition-opacity duration-150"
                                leave-active-class="transition-opacity duration-300"
                                enter-from-class="opacity-0"
                                leave-to-class="opacity-0"
                            >
                                <span v-if="savedSignature" class="text-sm text-success">Enregistré.</span>
                            </Transition>
                        </div>
                    </CardContent>
                </Card>

                <!-- Répertoire Git -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FolderOpen class="h-4 w-4 text-muted-foreground" />
                            <CardTitle class="text-sm font-semibold">Répertoire des projets Git</CardTitle>
                        </div>
                        <CardDescription>Chemin absolu vers le dossier contenant vos dépôts Git.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitGit" class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <Label>Chemin</Label>
                                <div class="flex gap-2">
                                    <Input v-model="gitForm.sites_dir" type="text" required placeholder="Ex: C:\Herd\Sites" class="min-w-0 flex-1 font-mono" />
                                    <Button type="button" variant="outline" class="shrink-0" @click="showBrowser = true">
                                        <FolderOpen class="h-4 w-4" />
                                        Parcourir
                                    </Button>
                                </div>
                                <InputError :message="gitForm.errors.sites_dir" />
                            </div>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="gitForm.processing">
                                    <Save class="h-3.5 w-3.5" />
                                    Enregistrer
                                </Button>
                                <Transition
                                    enter-active-class="transition-opacity duration-150"
                                    leave-active-class="transition-opacity duration-300"
                                    enter-from-class="opacity-0"
                                    leave-to-class="opacity-0"
                                >
                                    <span v-if="savedGit" class="text-sm text-success">Enregistré.</span>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Jeton API -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Key class="h-4 w-4 text-muted-foreground" />
                            <CardTitle class="text-sm font-semibold">Jeton API (script Git)</CardTitle>
                        </div>
                        <CardDescription>
                            Utilisé par le script local <code>scripts/git-to-fiche</code> pour générer des tâches depuis vos commits sans exposer vos fichiers au
                            serveur.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="plainTextToken" class="space-y-2 rounded-lg border border-warning/30 bg-warning-muted p-3">
                            <p class="text-xs font-medium text-warning">Copiez ce jeton maintenant, il ne sera plus jamais affiché.</p>
                            <div class="flex gap-2">
                                <code class="min-w-0 flex-1 truncate rounded-md bg-background px-2.5 py-2 text-xs text-foreground">{{ plainTextToken }}</code>
                                <Button type="button" variant="outline" class="shrink-0" @click="copyToken">
                                    <Copy class="h-3.5 w-3.5" />
                                    {{ tokenCopied ? 'Copié' : 'Copier' }}
                                </Button>
                            </div>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Statut :
                            <span :class="hasGitToken ? 'text-success' : 'text-muted-foreground'">
                                {{ hasGitToken ? 'Jeton actif' : 'Aucun jeton actif' }}
                            </span>
                        </p>

                        <div class="flex items-center gap-3">
                            <Button type="button" :disabled="tokenProcessing" @click="generateGitToken">
                                <Key class="h-3.5 w-3.5" />
                                {{ hasGitToken ? 'Régénérer un jeton' : 'Générer un jeton' }}
                            </Button>
                            <Button v-if="hasGitToken" type="button" variant="outline" :disabled="tokenProcessing" @click="revokeGitToken">
                                <Trash2 class="h-3.5 w-3.5" />
                                Révoquer
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>

    <DirectoryBrowser
        :show="showBrowser"
        :initial-path="gitForm.sites_dir"
        @select="
            gitForm.sites_dir = $event;
            showBrowser = false;
        "
        @close="showBrowser = false"
    />
</template>

<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarSeparator } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { NotebookText } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Mes fiches',
        href: '/dashboard',
        icon: NotebookText,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('fiche.index')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem class="flex items-center justify-between gap-2 px-1 group-data-[collapsible=icon]:flex-col">
                    <span class="text-xs font-medium text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden">Thème</span>
                    <ThemeToggle />
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarSeparator class="my-0" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const showingSidebar = ref(false);
const page = usePage();
const user = page.props.auth.user;

router.on('navigate', () => {
    showingSidebar.value = false;
});

const flashSuccess = ref(page.props.flash.success);
const flashError = ref(page.props.flash.error);

const clearFlash = () => {
    flashSuccess.value = null;
    flashError.value = null;
};

watch(() => page.props.flash, (newFlash) => {
    flashSuccess.value = newFlash.success;
    flashError.value = newFlash.error;

    if (newFlash.success || newFlash.error) {
        setTimeout(clearFlash, 3000);
    }
}, { deep: true });

onMounted(() => {
    if (flashSuccess.value || flashError.value) {
        setTimeout(clearFlash, 3000);
    }
});

const toggleSidebar = () => {
    showingSidebar.value = !showingSidebar.value;
};

const navItems = {
    admin: [
        { name: 'Dashboard', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        { name: 'Années', route: 'admin.years', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { name: 'Classes', route: 'admin.classes', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
        { name: 'Matières', route: 'admin.subjects', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
        { name: 'Professeurs', route: 'admin.profs', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
        { name: 'Attributions', route: 'admin.assignments', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
        { name: 'Étudiants', route: 'admin.students', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
        { name: 'Inscriptions', route: 'admin.enrollments', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
        { name: 'Promotions', route: 'admin.promote', icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' },
    ],
    prof: [
        { name: 'Dashboard', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    ]
};

const activeItems = navItems[user.role] || [];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex text-gray-900">
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:flex lg:flex-col lg:w-72 lg:fixed lg:inset-y-0 bg-indigo-900 shadow-xl border-r border-indigo-800 z-50">
            <div class="flex items-center h-16 flex-shrink-0 px-6 bg-indigo-950">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="h-8 w-auto fill-current text-indigo-300" />
                    <span class="text-white font-black text-xl tracking-tighter uppercase">ECOLE 2.0</span>
                </Link>
            </div>
            <div class="flex-1 flex flex-col overflow-y-auto mt-4 px-4 pb-4 space-y-1">
                <Link v-for="item in activeItems" :key="item.name" :href="route(item.route)" 
                    :class="[route().current(item.route) ? 'bg-indigo-700 text-white shadow-lg' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white', 'group flex items-center px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200']">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0 transition-colors" :class="[route().current(item.route) ? 'text-white' : 'text-indigo-400 group-hover:text-indigo-300']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </div>
            <div class="p-4 bg-indigo-950 border-t border-indigo-800">
                <div class="flex items-center gap-3 px-2 py-3 bg-indigo-900 rounded-2xl shadow-inner border border-indigo-800">
                    <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold shadow-md border-2 border-indigo-400">
                        {{ user.nom.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-white truncate">{{ user.nom }}</p>
                        <p class="text-xs font-bold text-indigo-400 truncate uppercase tracking-widest">{{ user.username }}</p>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" class="p-2 text-indigo-400 hover:text-white transition-colors duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Sidebar Mobile (Overlay + Panel) -->
        <div class="relative z-[100] lg:hidden" role="dialog" aria-modal="true">
            <Transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="showingSidebar" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm" @click="toggleSidebar"></div>
            </Transition>

            <Transition
                enter-active-class="transition ease-in-out duration-300 transform"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in-out duration-300 transform"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div v-show="showingSidebar" class="fixed inset-0 flex z-50" @click.self="toggleSidebar">
                    <div class="relative flex w-full max-w-xs flex-1 flex-col bg-indigo-900 pt-5 pb-4 shadow-2xl">
                        <Transition
                            enter-active-class="ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="ease-in-out duration-300"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div v-show="showingSidebar" class="absolute top-0 right-0 -mr-12 pt-2">
                                <button type="button" class="ml-1 flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="toggleSidebar">
                                    <span class="sr-only">Close sidebar</span>
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </Transition>

                        <div class="flex flex-shrink-0 items-center px-6 mb-8">
                            <ApplicationLogo class="h-10 w-auto text-indigo-300" />
                            <span class="ml-3 text-white font-black text-xl tracking-tighter uppercase">ECOLE 2.0</span>
                        </div>
                        <div class="mt-5 h-0 flex-1 overflow-y-auto px-4 space-y-1">
                            <Link v-for="item in activeItems" :key="item.name" :href="route(item.route)" @click="showingSidebar = false"
                                :class="[route().current(item.route) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white', 'group flex items-center px-4 py-4 text-base font-bold rounded-xl']">
                                <svg class="mr-4 h-6 w-6 flex-shrink-0" :class="[route().current(item.route) ? 'text-white' : 'text-indigo-400 group-hover:text-indigo-300']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                {{ item.name }}
                            </Link>
                        </div>
                        <div class="p-6 border-t border-indigo-800">
                             <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-lg shadow-lg border-2 border-indigo-400">
                                    {{ user.nom.charAt(0) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-black truncate">{{ user.nom }}</p>
                                    <span class="text-xs text-indigo-400 font-bold uppercase truncate tracking-widest">{{ user.username }}</span>
                                </div>
                                <Link :href="route('logout')" method="post" as="button" class="p-2 text-indigo-300 hover:text-white transition">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Main Wrapper -->
        <div class="flex flex-col flex-1 lg:pl-72 min-w-0">
            <!-- Topbar mobile -->
            <div class="sticky top-0 z-40 flex h-16 flex-shrink-0 bg-white shadow-sm border-b border-gray-200 lg:hidden">
                <button type="button" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 lg:hidden" @click="toggleSidebar">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex flex-1 justify-between px-4 sm:px-6">
                    <div class="flex items-center">
                         <span class="text-gray-900 font-black text-lg tracking-tight uppercase">ECOLE 2.0</span>
                    </div>
                    <div class="flex items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold border-2 border-indigo-100 shadow-sm leading-none">
                                    {{ user.nom.charAt(0) }}
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Topbar Desktop -->
            <div class="hidden lg:flex sticky top-0 z-40 h-16 bg-white/80 backdrop-blur-md border-b border-gray-200 justify-end items-center px-8">
                <div class="flex items-center gap-6">
                    <div class="flex flex-col text-right min-w-0 overflow-hidden">
                        <span class="text-sm font-black text-gray-900 truncate">{{ user.nom }}</span>
                        <span class="text-[0.65rem] font-bold text-gray-400 uppercase truncate tracking-widest">{{ user.username }}</span>
                    </div>
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="h-10 w-10 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-200 border-2 border-white transform hover:scale-105 transition-transform">
                                {{ user.nom.charAt(0) }}
                            </button>
                        </template>
                        <template #content>
                             <div class="px-4 py-2 text-xs text-gray-400 border-b border-gray-100 uppercase font-black tracking-widest">Ma Session</div>
                            <DropdownLink :href="route('profile.edit')">Paramètres</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-bold">Déconnexion</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>

            <!-- Page Heading -->
            <header class="bg-white/50 border-b border-gray-100" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-12">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 relative focus:outline-none py-10">
                <!-- Flash Messages Toast -->
                <div v-if="flashSuccess || flashError" class="fixed top-20 right-8 z-[60] flex flex-col gap-3 max-w-sm w-full pointer-events-none">
                    <div v-if="flashSuccess" class="bg-emerald-500 text-white p-4 rounded-2xl shadow-2xl flex items-center gap-3 animate-in fade-in slide-in-from-right-4 duration-300 pointer-events-auto border-2 border-emerald-400">
                        <div class="bg-emerald-600 rounded-lg p-1">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <p class="font-bold text-sm">{{ flashSuccess }}</p>
                        <button @click="flashSuccess = null" class="ml-auto text-white/50 hover:text-white transition">✕</button>
                    </div>
                    <div v-if="flashError" class="bg-rose-500 text-white p-4 rounded-2xl shadow-2xl flex items-center gap-3 animate-in fade-in slide-in-from-right-4 duration-300 pointer-events-auto border-2 border-rose-400">
                        <div class="bg-rose-600 rounded-lg p-1">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <p class="font-bold text-sm">{{ flashError }}</p>
                        <button @click="flashError = null" class="ml-auto text-white/50 hover:text-white transition">✕</button>
                    </div>
                </div>

                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    activeYear: Object,
    classesWithCounts: Array,
});

const page = usePage();
const adminName = computed(() => page.props.auth?.user?.name || page.props.auth?.user?.username || 'Administrateur');
const shortName = computed(() => adminName.value.split(' ')[0]);

const girlsPct = computed(() => {
    if (!props.stats.students) return 0;
    return Math.round((props.stats.girls / props.stats.students) * 100);
});

const newPct = computed(() => {
    const total = props.stats.nouveaux + props.stats.redoublants;
    if (!total) return 0;
    return Math.round((props.stats.nouveaux / total) * 100);
});

const maxClassCount = computed(() => {
    if (!props.classesWithCounts?.length) return 1;
    return Math.max(...props.classesWithCounts.map(c => c.count), 1);
});

const quickLinks = [
    { label: 'Années Scolaires', sub: 'Gestion des années', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', href: 'admin.years', color: 'teal' },
    { label: 'Classes', sub: 'Organiser les classes', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', href: 'admin.classes', color: 'violet' },
    { label: 'Matières', sub: 'Programme scolaire', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', href: 'admin.subjects', color: 'sky' },
    { label: 'Professeurs', sub: 'Corps enseignant', icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', href: 'admin.profs', color: 'emerald' },
    { label: 'Attributions', sub: 'Matières & profs', icon: 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2', href: 'admin.assignments', color: 'rose' },
    { label: 'Élèves', sub: 'Gérer les dossiers', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', href: 'admin.students', color: 'indigo' },
    { label: 'Inscriptions', sub: 'Gérer les dossiers', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', href: 'admin.enrollments', color: 'amber' },
    { label: 'Promotions', sub: 'Passage de classe', icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', href: 'admin.promote', color: 'orange' },
];

const colorMap = {
    indigo: { bg: 'bg-indigo-50', text: 'text-indigo-700', icon: 'bg-indigo-100 text-indigo-600', border: 'border-indigo-100', hover: 'hover:border-indigo-300 hover:bg-indigo-50/50' },
    violet: { bg: 'bg-violet-50', text: 'text-violet-700', icon: 'bg-violet-100 text-violet-600', border: 'border-violet-100', hover: 'hover:border-violet-300 hover:bg-violet-50/50' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700', icon: 'bg-emerald-100 text-emerald-600', border: 'border-emerald-100', hover: 'hover:border-emerald-300 hover:bg-emerald-50/50' },
    amber: { bg: 'bg-amber-50', text: 'text-amber-700', icon: 'bg-amber-100 text-amber-600', border: 'border-amber-100', hover: 'hover:border-amber-300 hover:bg-amber-50/50' },
    rose: { bg: 'bg-rose-50', text: 'text-rose-700', icon: 'bg-rose-100 text-rose-600', border: 'border-rose-100', hover: 'hover:border-rose-300 hover:bg-rose-50/50' },
    sky:  { bg: 'bg-sky-50',  text: 'text-sky-700',  icon: 'bg-sky-100 text-sky-600',  border: 'border-sky-100',  hover: 'hover:border-sky-300 hover:bg-sky-50/50' },
    teal:   { bg: 'bg-teal-50',   text: 'text-teal-700',   icon: 'bg-teal-100 text-teal-600',   border: 'border-teal-100',   hover: 'hover:border-teal-300 hover:bg-teal-50/50' },
    orange: { bg: 'bg-orange-50', text: 'text-orange-700', icon: 'bg-orange-100 text-orange-600', border: 'border-orange-100', hover: 'hover:border-orange-300 hover:bg-orange-50/50' },
};

const getCurrentHour = () => new Date().getHours();
const greeting = computed(() => {
    const h = getCurrentHour();
    if (h < 12) return 'Bonjour';
    if (h < 18) return 'Bon après-midi';
    return 'Bonsoir';
});
</script>

<template>
    <Head title="Tableau de bord Admin" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black leading-tight text-gray-800 uppercase tracking-tight">
                Tableau de bord
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8">

                <!-- ─── Hero Banner ─── -->
                <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-700 rounded-3xl overflow-hidden shadow-2xl shadow-indigo-200 p-6 sm:p-10 text-white">
                    <!-- Decorative blobs -->
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-24 w-40 h-40 bg-violet-400/20 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-indigo-100 text-base sm:text-lg font-medium max-w-md">
                                Bienvenue dans votre espace d'administration. Tout est sous contrôle.
                            </p>
                        </div>
                        <div v-if="activeYear" class="flex-shrink-0 bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-6 py-4 text-center">
                            <p class="text-[0.6rem] font-black uppercase tracking-widest text-indigo-200 mb-1">Année scolaire active</p>
                            <p class="text-2xl font-black text-white">{{ activeYear.name }}</p>
                            <span class="mt-1 inline-flex items-center gap-1.5 text-[0.65rem] font-black uppercase tracking-widest text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                En cours
                            </span>
                        </div>
                        <div v-else class="flex-shrink-0 bg-amber-400/20 border border-amber-300/30 rounded-2xl px-6 py-4 text-center">
                            <p class="text-xs font-black text-amber-200 uppercase tracking-widest">Aucune année active</p>
                            <Link :href="route('admin.years')" class="mt-2 inline-block text-xs font-black text-amber-300 underline">Configurer →</Link>
                        </div>
                    </div>
                </div>

                <!-- ─── Key Stats Grid ─── -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Students -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="p-2.5 bg-indigo-50 rounded-xl">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-[0.6rem] font-black uppercase tracking-widest text-gray-300">Total</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ stats.students }}</p>
                        <p class="text-xs font-bold text-gray-400 mt-1">Élèves inscrits</p>
                        <div class="mt-3 flex items-center gap-3 text-[0.65rem] font-black">
                            <span class="text-blue-600">♂ {{ stats.boys }} Garçons</span>
                            <span class="text-pink-500">♀ {{ stats.girls }} Filles</span>
                        </div>
                    </div>

                    <!-- Classes -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="p-2.5 bg-violet-50 rounded-xl">
                                <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span class="text-[0.6rem] font-black uppercase tracking-widest text-gray-300">Actives</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ stats.classes }}</p>
                        <p class="text-xs font-bold text-gray-400 mt-1">Classes en service</p>
                        <div class="mt-3 text-[0.65rem] font-black text-violet-600">
                            {{ stats.assignments }} attributions actives
                        </div>
                    </div>

                    <!-- Profs -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="p-2.5 bg-emerald-50 rounded-xl">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-[0.6rem] font-black uppercase tracking-widest text-gray-300">Corps</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ stats.profs }}</p>
                        <p class="text-xs font-bold text-gray-400 mt-1">Professeurs actifs</p>
                        <div class="mt-3 text-[0.65rem] font-black text-emerald-600">
                            {{ stats.subjects }} matières enseignées
                        </div>
                    </div>

                    <!-- Enrollment breakdown -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="p-2.5 bg-amber-50 rounded-xl">
                                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span class="text-[0.6rem] font-black uppercase tracking-widest text-gray-300">Année</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ stats.enrolled }}</p>
                        <p class="text-xs font-bold text-gray-400 mt-1">Inscrits cette année</p>
                        <div class="mt-3 flex items-center gap-3 text-[0.65rem] font-black">
                            <span class="text-emerald-600">✦ {{ stats.nouveaux }} Nouveaux</span>
                            <span class="text-orange-500">↩ {{ stats.redoublants }} Redoub.</span>
                        </div>
                    </div>
                </div>

                <!-- ─── Bottom: Class Breakdown + Quick Access ─── -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                    <!-- Class Breakdown (left, 3/5) -->
                    <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight">Répartition par classe</h3>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">Effectifs pour l'année en cours</p>
                            </div>
                            <Link :href="route('admin.students')" class="text-[0.65rem] font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
                                Voir tout →
                            </Link>
                        </div>

                        <div v-if="classesWithCounts && classesWithCounts.length" class="space-y-3.5">
                            <div v-for="cls in classesWithCounts" :key="cls.id" class="group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center bg-indigo-50 text-indigo-700 rounded-lg px-2.5 py-0.5 text-xs font-black min-w-[60px] text-center group-hover:bg-indigo-100 transition">
                                            {{ cls.nom }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-black text-gray-700">
                                        {{ cls.count }} <span class="text-gray-300 font-medium">élève{{ cls.count !== 1 ? 's' : '' }}</span>
                                    </span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full transition-all duration-700 ease-out"
                                        :style="{ width: maxClassCount > 0 ? (cls.count / maxClassCount * 100) + '%' : '0%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10 text-gray-300">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                            <p class="text-sm font-bold">Aucune donnée pour cette année</p>
                        </div>

                        <!-- Gender bar -->
                        <div v-if="stats.students > 0" class="mt-6 pt-5 border-t border-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[0.65rem] font-black uppercase tracking-widest text-gray-400">Répartition par genre</span>
                                <div class="flex items-center gap-3 text-[0.65rem] font-black">
                                    <span class="text-blue-500">♂ {{ 100 - girlsPct }}% Garçons</span>
                                    <span class="text-pink-500">♀ {{ girlsPct }}% Filles</span>
                                </div>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden flex">
                                <div class="h-full bg-blue-400 transition-all duration-700" :style="{ width: (100 - girlsPct) + '%' }"></div>
                                <div class="h-full bg-pink-400 transition-all duration-700" :style="{ width: girlsPct + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Access (right, 2/5) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight mb-1">Accès rapide</h3>
                        <p class="text-xs text-gray-400 font-medium mb-5">Navigation vers les modules</p>

                        <div class="space-y-2">
                            <Link
                                v-for="link in quickLinks"
                                :key="link.label"
                                :href="route(link.href)"
                                class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 group"
                                :class="[colorMap[link.color].border, colorMap[link.color].hover]"
                            >
                                <div class="p-2 rounded-lg flex-shrink-0 transition-colors" :class="colorMap[link.color].icon">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-black text-gray-800 group-hover:text-gray-900 leading-none">{{ link.label }}</p>
                                    <p class="text-[0.6rem] text-gray-400 font-medium mt-0.5">{{ link.sub }}</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

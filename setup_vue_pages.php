<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$profDir = __DIR__ . '/resources/js/Pages/Prof';
@mkdir($adminDir, 0777, true);
@mkdir($profDir, 0777, true);

$templates = [
    "$adminDir/Classes.vue" => [
        'title' => 'Gestion des Classes',
        'var' => 'classes',
        'cols' => ['ID' => 'id', 'Nom' => 'nom']
    ],
    "$adminDir/Subjects.vue" => [
        'title' => 'Gestion des Matières',
        'var' => 'subjects',
        'cols' => ['ID' => 'id', 'Nom' => 'nom', 'Coefficient' => 'coefficient']
    ],
    "$adminDir/Students.vue" => [
        'title' => 'Gestion des Élèves',
        'var' => 'students',
        'cols' => ['Matricule' => 'matricule', 'Nom' => 'nom', 'Prénom' => 'prenom', 'Sexe' => 'sexe']
    ],
    "$adminDir/Profs.vue" => [
        'title' => 'Gestion des Professeurs',
        'var' => 'profs',
        'cols' => ['Nom Complet' => 'nom', 'Nom utilisateur' => 'username', 'Grade' => 'grade', 'Statut' => 'is_active']
    ],
    "$adminDir/Years.vue" => [
        'title' => 'Années Scolaires',
        'var' => 'years',
        'cols' => ['Nom' => 'name', 'Statut' => 'is_active', 'Début' => 'start_date', 'Fin' => 'end_date']
    ],
    "$profDir/Evaluations.vue" => [
        'title' => 'Mes Évaluations',
        'var' => 'evaluations',
        'cols' => ['Nom' => 'nom', 'Type' => 'type', 'Période' => 'periode', 'Date' => 'date']
    ],
    "$profDir/Grades.vue" => [
        'title' => 'Saisie des Notes',
        'var' => 'grades',
        'cols' => ['ID' => 'id']
    ],
];

foreach ($templates as $file => $config) {
    if ($file === "$adminDir/Students.vue") {
        $content = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    students: Array,
});
</script>

<template>
    <Head title="Gestion des Élèves" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestion des Élèves
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Élèves</h3>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition">
                            + Nouvel Élève
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Matricule</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom Complet</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Sexe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe actuelle</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="student in students" :key="student.id" class="hover:bg-indigo-50 transition cursor-pointer">
                                    <td class="p-4 text-gray-500 font-mono text-sm">{{ student.matricule || 'N/A' }}</td>
                                    <td class="p-4 font-medium text-gray-900">{{ student.nom }} {{ student.prenom }}</td>
                                    <td class="p-4 text-gray-600">
                                        <span :class="student.sexe === 'M' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'" class="px-2 py-1 rounded-full text-xs font-bold">
                                            {{ student.sexe }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-gray-600">
                                        <span v-if="student.enrollments && student.enrollments.length > 0" class="bg-gray-100 text-gray-800 px-2 py-1 rounded-md text-xs font-bold border border-gray-200">
                                            {{ student.enrollments[student.enrollments.length - 1]?.classe?.nom || 'Non défini' }}
                                        </span>
                                        <span v-else class="text-gray-400 italic text-sm">Aucune classe</span>
                                    </td>
                                    <td class="p-4 text-right space-x-3">
                                        <button class="text-indigo-600 hover:text-indigo-900 font-medium transition text-sm">Dossier</button>
                                        <button class="text-red-600 hover:text-red-900 font-medium transition text-sm">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0">
                                    <td colspan="5" class="p-8 text-center text-gray-500">Aucun élève trouvé.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
EOT;
    } else {
        $colsTh = '';
        $colsTd = '';
        foreach ($config['cols'] as $label => $prop) {
            $colsTh .= "                                    <th class=\"p-4 font-semibold border-b border-gray-200\">{$label}</th>\n";
            if ($prop === 'is_active') {
                $colsTd .= <<<EOT
                                    <td class="p-4 text-gray-600">
                                        <span v-if="item.is_active" class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold">Actif</span>
                                        <span v-else class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-bold">Inactif</span>
                                    </td>\n
EOT;
            } else {
                $colsTd .= "                                    <td class=\"p-4 font-medium text-gray-900\">{{ item.{$prop} }}</td>\n";
            }
        }
        
        $colspan = count($config['cols']) + 1;

        $content = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    {$config['var']}: Array,
});
</script>

<template>
    <Head title="{$config['title']}" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {$config['title']}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des {$config['title']}</h3>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition transform hover:-translate-y-0.5">
                            + Ajouter
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
{$colsTh}                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in {$config['var']}" :key="item.id" class="hover:bg-indigo-50 transition cursor-pointer">
{$colsTd}                                    <td class="p-4 text-right space-x-2">
                                        <button class="text-indigo-600 hover:text-indigo-900 font-medium transition">Éditer</button>
                                        <button class="text-red-600 hover:text-red-900 font-medium transition">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="!{$config['var']} || {$config['var']}.length === 0">
                                    <td colspan="{$colspan}" class="p-8 text-center text-gray-500">Aucune donnée disponible.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
EOT;
    }

    file_put_contents($file, $content);
}

// Special case: Admin/Promote.vue route which had no var returned in controller
file_put_contents("$adminDir/Promote.vue", <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Promotion des Étudiants" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Promotion Automatique
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden p-8 text-center border-t-4 border-indigo-500">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Fonctionnalité en cours de développement</h3>
                    <p class="text-gray-600 mb-6">Cette page permettra de promouvoir les élèves vers la classe supérieure en fin d'année.</p>
                    <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium cursor-not-allowed opacity-50">Lancer la promotion</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
EOT
);

echo "Vue Pages generated.\n";

<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$adminControllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';
$layoutPath = __DIR__ . '/resources/js/Layouts/AuthenticatedLayout.vue';

// 1. Web Routes
$webRoutesContent = file_get_contents($webRoutesPath);
if (strpos($webRoutesContent, 'promote.process') === false) {
    $routesReplacement = <<<EOT
    Route::get('/promote', [AdminController::class, 'promote'])->name('promote');
    Route::post('/promote', [AdminController::class, 'processPromote'])->name('promote.process');
EOT;
    $webRoutesContent = str_replace(
        "Route::get('/promote', [AdminController::class, 'promote'])->name('promote');", 
        $routesReplacement, 
        $webRoutesContent
    );
    file_put_contents($webRoutesPath, $webRoutesContent);
}

// 2. AdminController Promote logics
$adminControllerContent = file_get_contents($adminControllerPath);

$promoteLogic = <<<EOT
    private function getAnnualAverage(\$student_id, \$school_year_id) {
        \$sem1 = \$this->calculatePeriodStats(\$student_id, 'Semestre 1', \$school_year_id);
        \$sem2 = \$this->calculatePeriodStats(\$student_id, 'Semestre 2', \$school_year_id);
        if (\$sem1 === null && \$sem2 === null) return null;
        if (\$sem2 === null) return \$sem1;
        if (\$sem1 === null) return \$sem2;
        return round(((\$sem1 * 2) + \$sem2) / 3, 2);
    }

    private function getNextClassName(\$currentClass) {
        \$map = [
            '6ème' => '5ème', '6e' => '5e', '6' => '5',
            '5ème' => '4ème', '5e' => '4e', '5' => '4',
            '4ème' => '3ème', '4e' => '3e', '4' => '3',
            '3ème' => '2nde', '3e' => '2nde', '3' => '2nde',
            '2nde' => '1ère', '1ère' => 'Terminale', '1ere' => 'Terminale'
        ];
        foreach(\$map as \$k => \$v) {
            if(str_starts_with(strtolower(\$currentClass), strtolower(\$k))) {
                return str_ireplace(\$k, \$v, \$currentClass);
            }
        }
        return null;
    }

    public function promote()
    {
        \$activeYear = App\Models\SchoolYear::where('is_active', true)->first();
        if(!\$activeYear) \$activeYear = App\Models\SchoolYear::first();
        if(!\$activeYear) return redirect()->route('admin.dashboard');

        \$enrollments = App\Models\StudentEnrollment::with(['student', 'classe'])
            ->where('school_year_id', \$activeYear->id)
            ->limit(50)
            ->get();

        \$preview = [];
        foreach (\$enrollments as \$e) {
            \$avg = \$this->getAnnualAverage(\$e->student_id, \$activeYear->id);
            \$decision = 'Inconnu';
            \$color = 'text-gray-500';
            
            if (\$avg !== null) {
                if (\$avg >= 10) {
                    \$next = \$this->getNextClassName(\$e->classe->nom);
                    if (\$next) {
                        \$decision = "Passage en \$next";
                        \$color = 'text-green-600';
                    } else {
                        \$decision = "Fin de cursus (Diplômé?)";
                        \$color = 'text-blue-600';
                    }
                } else {
                    \$decision = "Redoublement (\{\$e->classe->nom\})";
                    \$color = 'text-red-600';
                }
            }

            \$preview[] = [
                'id' => \$e->id,
                'nom' => \$e->student->nom . ' ' . \$e->student->prenom,
                'classe' => \$e->classe->nom,
                'avg' => \$avg !== null ? \$avg : 'N/A',
                'decision' => \$decision,
                'color' => \$color
            ];
        }

        // Suggest name
        \$suggested = '';
        \$parts = explode('-', \$activeYear->name);
        if (count(\$parts) == 2 && is_numeric(\$parts[0])) {
            \$suggested = (intval(\$parts[0]) + 1) . '-' . (intval(\$parts[1]) + 1);
        }

        return Inertia::render('Admin/Promote', [
            'activeYear' => \$activeYear,
            'suggestedName' => \$suggested,
            'preview' => \$preview
        ]);
    }

    public function processPromote(Request \$request)
    {
        \$request->validate([
            'target_year_name' => 'required|string',
            'current_year_id' => 'required|exists:school_years,id'
        ]);

        \$target_year_name = \$request->target_year_name;
        \$current_year_id = \$request->current_year_id;

        \$targetYear = App\Models\SchoolYear::firstOrCreate(
            ['name' => \$target_year_name],
            ['is_active' => false]
        );

        \$enrollments = App\Models\StudentEnrollment::with(['student', 'classe'])
            ->where('school_year_id', \$current_year_id)
            ->get();

        \$promoted = 0;
        \$repeated = 0;

        foreach (\$enrollments as \$e) {
            \$avg = \$this->getAnnualAverage(\$e->student_id, \$current_year_id);
            if (\$avg !== null && \$avg >= 10) {
                \$next = \$this->getNextClassName(\$e->classe->nom);
                if (\$next) {
                    \$nextClass = App\Models\Classe::firstOrCreate(['nom' => \$next]);
                    App\Models\StudentEnrollment::updateOrCreate([
                        'student_id' => \$e->student_id,
                        'school_year_id' => \$targetYear->id
                    ], ['class_id' => \$nextClass->id]);
                    \$promoted++;
                    continue;
                }
            }
            
            // Repeat or fin de cursus (we just re-enroll them if avg < 10)
            if (\$avg !== null && \$avg < 10) {
                App\Models\StudentEnrollment::updateOrCreate([
                    'student_id' => \$e->student_id,
                    'school_year_id' => \$targetYear->id
                ], ['class_id' => \$e->class_id]);
                \$repeated++;
            }
        }

        return redirect()->back();
    }
EOT;

if (strpos($adminControllerContent, 'function getAnnualAverage(') === false) {
    $adminControllerContent = preg_replace('/public function promote\(\)\s*\{\s*return Inertia::render\(\'Admin\/Promote\'\);\s*\}/s', $promoteLogic, $adminControllerContent);
    file_put_contents($adminControllerPath, $adminControllerContent);
}

// 3. Mod Layout if missing promote
$layoutContent = file_get_contents($layoutPath);
if (strpos($layoutContent, 'admin.promote') === false) {
    $layoutContent = str_replace(
        "<NavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</NavLink>", 
        "<NavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</NavLink>\n                                    <NavLink :href=\"route('admin.promote')\" :active=\"route().current('admin.promote')\">Promotions</NavLink>", 
        $layoutContent
    );
     $layoutContent = str_replace(
        "<ResponsiveNavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</ResponsiveNavLink>", 
        "<ResponsiveNavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</ResponsiveNavLink>\n                            <ResponsiveNavLink :href=\"route('admin.promote')\" :active=\"route().current('admin.promote')\">Promotions</ResponsiveNavLink>", 
        $layoutContent
    );
    file_put_contents($layoutPath, $layoutContent);
}

// 4. Create Promote.vue
$promoteVue = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    activeYear: Object,
    suggestedName: String,
    preview: Array
});

const form = useForm({
    current_year_id: props.activeYear ? props.activeYear.id : '',
    target_year_name: props.suggestedName
});

const runPromotion = () => {
    if (confirm('Lancer la promotion globale ? Cela génèrera les inscriptions pour l\'année cible.')) {
        form.post(route('admin.promote.process'), {
            onSuccess: () => alert('Promotion terminée avec succès !')
        });
    }
};
</script>

<template>
    <Head title="Promotion des Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Promotion des Élèves</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Parameters Card -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-600">
                    <h3 class="text-lg font-bold text-gray-900">Lancer les promotions pour {{ activeYear ? activeYear.name : 'Unknown' }}</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        Les élèves obtiendront automatiquement leurs affectations de la nouvelle année selon leur moyenne annuelle.<br>
                        <strong>Passage:</strong> Moyenne ≥ 10 | <strong>Redoublement:</strong> Moyenne < 10
                    </p>
                    
                    <div class="mt-6 flex items-end gap-4 max-w-md">
                        <div class="flex-grow">
                            <InputLabel value="Année Scolaire de destination" />
                            <TextInput v-model="form.target_year_name" type="text" class="mt-1 block w-full" placeholder="Ex: 2024-2025" />
                        </div>
                        <PrimaryButton @click="runPromotion" :disabled="form.processing" class="mb-1 bg-green-600 hover:bg-green-700">
                            Exécuter la Promotion
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Aperçu (Échantillon sur {{ preview.length }} élèves)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-4 border-b border-gray-200">Élève</th>
                                    <th class="p-4 border-b border-gray-200">Classe Actuelle</th>
                                    <th class="p-4 border-b border-gray-200">Moy. Annuelle</th>
                                    <th class="p-4 border-b border-gray-200 text-right">Décision Projetée</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in preview" :key="item.id" class="hover:bg-slate-50">
                                    <td class="p-4 font-bold text-gray-800">{{ item.nom }}</td>
                                    <td class="p-4 text-gray-600">{{ item.classe }}</td>
                                    <td class="p-4 font-black bg-indigo-50 text-indigo-900 border-x border-gray-100">{{ item.avg }}</td>
                                    <td class="p-4 font-bold text-right text-sm" :class="item.color">{{ item.decision }}</td>
                                </tr>
                                <tr v-if="preview.length === 0">
                                    <td colspan="4" class="p-8 text-center text-gray-400 font-bold">Aucune inscription dans l'année active.</td>
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
file_put_contents($adminDir . '/Promote.vue', $promoteVue);

echo "Promotions Setup OK.";

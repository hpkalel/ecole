# Documentation Technique : Projet Ecole 2.0

Cette documentation décrit de A à Z l'architecture, la structure et le fonctionnement du projet Laravel/Vue.js de gestion scolaire. Conçue pour une prise en main rapide de la base de code, elle détaille le back-end, le front-end ainsi que les grandes fonctionnalités de l'application.

> [!NOTE]
> Le système repose sur la stack **VILT** : **V**ue.js 3, **I**nertia.js, **L**aravel 11, et **T**ailwind CSS.

---

## 1. Architecture Générale

Le fonctionnement principal du projet repose sur **Inertia.js**, qui fait office de pont entre Laravel (Back-end) et Vue.js (Front-end).
- **Pas d'API REST stricte** : Au lieu de retourner du JSON qu'une application single-page (SPA) irait consommer, les contrôleurs Laravel retournent directement des objets `Inertia::render('Dossier/Vue', [...])` qui injectent les données via les `Props` dans Vue.js.
- **Routage** : Toutes les routes sont définies côté serveur dans `routes/web.php`.
- **Authentification** : Gérée par Laravel Breeze.

---

## 2. Base de Données et Modèles (Back-end)

L'application tourne autour d'un schéma relationnel pensé pour gérer la progression des élèves année après année.

### 2.1 Modèles Clés (Dossier `app/Models/`)

| Modèle | Description | Relations Principales |
|---|---|---|
| `User` | Authentification. Possède un rôle (`admin` ou `prof`). | `ProfController` se base sur l'id professeur. |
| `SchoolYear` | Année scolaire (ex: 2023-2024). Il y a toujours **une seule** année active à la fois. | Liée aux `StudentEnrollment`, `Assignment`, `Evaluation`. |
| `Student` | Informations personnelles des étudiants (Nom, Prénom, Sexe). | - |
| `Classe` | Nom d'une classe (ex: "6ème A"). | - |
| `Subject` | Matière étudiée (ex: "Mathématiques", "Français"). | - |
| `StudentEnrollment` | La table pivot par excellence. Relie un `Student`, une `Classe`, et une `SchoolYear`. Elle gère le statut "Nouveau" ou "Redoublant". | Appartient à Student, Classe, SchoolYear. |
| `Assignment` | Attributions. Associe un Professeur (`User`), une `Matière`, une `Classe` pour une `Année`. | - |
| `ClassPrincipal` | Définit le "Professeur Principal" d'une `Classe` pour une `Année`. | - |
| `Evaluation` | Un devoir, contôle ou examen créé par un professeur (lié à un `Assignment`). | Contient plusieurs `Grade` (Notes). |
| `Grade` | La note mathématique finale d'un étudiant sur une `Evaluation`. | - |
| `BehaviorGrade` | Note de conduite, gérée par le professeur principal de la classe. | Liée à un `Student_id`, `Classe_id`, `SchoolYear_id`. |

> [!IMPORTANT]
> **Le rôle de la `SchoolYear`**
> Presque toutes les données opérationnelles (`StudentEnrollment`, `Assignment`, `Evaluation`) sont attachées dynamiquement à l'année scolaire **active**. Ceci garantit que chaque année est archivée proprement et ne se mélange pas.

---

## 3. Le Routage (`routes/web.php`)

Le fichier de routes est organisé par middlewares et préfixes pour protéger les accès :

1. **Dashboard (Redirection) :**
   La route `/dashboard` inspecte le rôle de l'utilisateur. Si `admin`, il redirige vers `admin.dashboard`, sinon `prof.dashboard`.
2. **Modules Administration (Préfixe `/admin`) :**
   - Protégé par les middlewares `auth`, `role:admin`, et `activeYear`.
   - Gère les Crud complets : `Classes`, `Matières`, `Étudiants`, `Professeurs`, `Attributions`, `Inscriptions`, `Années Scolaires`.
   - Lance la logique métier forte : `/bulletin/{id}` pour les notes, `/promote` pour la montée de niveau des élèves.
3. **Modules Professeurs (Préfixe `/prof`) :**
   - Protégé par `auth`, `role:prof`, et `activeYear`.
   - Gère les évaluations, la saisie des notes (`/grades`), et la conduite pour le prof principal (`/conduite`).

---

## 4. Les Contrôleurs principaux

### `AdminController.php`
C'est le plus gros moteur de l'application. Il contient :
- **Les méthodes de listing** : Retournent les composants front-ends (via `Inertia::render()`) accompagnées de requêtes SQL complètes et souvent complexes (eager loading des relations).
- **Les méthodes de mutation** : `store`, `update`, `destroy`. Validation stricte (`$request->validate()`). Particulièrement, `trim()` ou `strtoupper()` sont utilisés pour normaliser les données à l'entrée.
- **`bulletin()`** : Fonction phare qui compile toutes les notes, coefficients (fixés à 1), moyennes générales, et le classement des élèves d'une classe.
- **`processPromote()`** : Algorithme lourd de fin d'année. Clôture l'année active, créé des incriptions dans la nouvelle année selon la moyenne finale de chaque élève (Moyenne >= 10 = Passage, sinon = Redoublement).

### `ProfController.php`
Dédié aux actions des enseignants :
- **`dashboard()`** : Fournit un planning dynamique (Emploi du temps basé sur les `Assignments`) du prof pour l'année courante.
- **`evaluations()`** : Crée les "Devoirs" (Contrôle 1, Examen Semestre 1, etc.).
- **`grades()`** : Affiche la liste des élèves pour une évaluation donnée pour y insérer les notes.
- **`conduite()`** : Le professeur peut noter la conduite/comportement des élèves, **seulement s'il est classé comme professeur principal** de la classe par l'Admin.

---

## 5. L'architecture Front-end (Vue.js)

Les fichiers sources Frontend sont dans `resources/js/`.

### 5.1 Structure du Projet Front
- `Pages/` : Contient les différentes vues correspondants à des routes (ex: `Pages/Admin/Dashboard.vue`, `Pages/Prof/Grades.vue`).
- `Layouts/` : Contient l'ossature du site (`AuthenticatedLayout.vue` avec la Topbar, la Sidebar dynamique et les composants de notifications Flash).
- `Components/` : Divers boutons, inputs, et Modals partagés et ré-utilisables pour assurer une UI commune.

### 5.2 Fonctionnement d'une Page type (Master-Detail Structure)
Plusieurs modules complexes (comme Classes, Inscriptions, Étudiants) utilisent un design Master-Détail.
1. La page Vue.js reçoit une liste globale de Laravel sous forme de **Props**. (Ex: Liste de tous les élèves).
2. L'interface affiche un tableau esthétique à gauche, et un composant de formulaire à droite.
3. Quand un utilisateur clique sur un enregistrement ("Éditer"), on met à jour un objet de données réactif Vue (`selectedItem`). L'interface de droite bascule d'une vue de "Création" à une vue "D'édition".
4. Au clic de soumission, on exécute `form.post()` ou `form.patch()` vers Laravel qui opère en tâche de fond (SPA paradigm) et ramène la page à jour, ou renvoie un `InputError` (erreurs de validation).

### 5.3 Les variables et Notifications "Flash"
Le passage de message de succès/erreur se fait sans rechargement lourd.
- Backend : `return redirect()->back()->with('success', 'Bien enregistré');`
- Frontend : `AuthenticatedLayout.vue` écoute dynamiquement `usePage().props.flash`. Si présent, un ruban (toast) HTML de succès ou d'erreur s'anime en haut à droite.

---

## 6. L'Esthétique & UI/UX

L'application bénéficie d'un soin particulier avec **Tailwind CSS**. Elle est entièrement modulaire, full "responsive" (mobile & web), et utilise beaucoup de classes utilitaires graphiques pour offrir un rendu premium (bordures douces `rounded-xl`, ombrages complexes `shadow-lg`, fond colorés et dynamiques `hover:bg-indigo-50`, animations CSS `transition-all duration-300`).

---

## 7. Fonctionnalités "Sensibles"

1. **La Promotion (`Promote.vue` / `AdminController@processPromote`)**
   L'action promotion clôture instantanément la `SchoolYear` `is_active` à faux. Seul l'Administrateur devrait lancer ceci en fin d'année. Elle lit les moyennes de tous, bascule le statut (Nouveau/Redoublant) et l'insert dans la classe choisie pour l'année d'après.
2. **Gestion de classe (`Admin/Classes.vue`)**
   Les classes utilisent un champ texte simple. Il ne s'agit pas d'id imbriquées (Grade, Niveau, Option) mais juste d'un label "6e A". La casse est conservée de manière stricte (jamais de transformations sauvages UpperCase/LowerCase sur les noms de classes).
3. **Bulletins (`Admin/Bulletin.vue`)**
   Ce module est configuré pour de l'impression physique. Au moment de "Générer", l'interface Vue.js cache `NavigationBar`, cache `Sidebar` et utilise un layout brut, en noir et blanc formatté au standard "A4 Print" grâce à Tailwind customisé (`@media print`).

## 8. Commandes Courantes Habituelles
- **Lancer le serveur DEV :** `php artisan serve` pour le back-end et `npm run dev` pour le front-end Vue.js.
- **Vider le cache d'application :** `php artisan optimize:clear` (utile si Laravel ou Vue semble désynchronisé).
- **Mettre à jour les dépendances :** `composer update` et `npm update`.

---

## 9. Tutoriel : Installation et Lancement de A à Z

Si vous reprenez ce projet sur une nouvelle machine ou souhaitez le déployer de zéro, voici la marche à suivre étape par étape.

### Étape 1 : Prérequis système
Assurez-vous d'avoir installé sur votre machine :
- **PHP** (8.2 ou supérieur)
- **Composer** (Gestionnaire de paquets PHP)
- **Node.js** et **NPM** (Gestionnaire de paquets JavaScript)
- Un serveur de base de données (ex: **MySQL** ou **MariaDB** via XAMPP, WAMP, ou Laragon).

### Étape 2 : Récupération du projet
Si le projet est sur un dépôt Git, clonez-le et entrez dans le dossier :
```bash
git clone <url-du-depot> ecole
cd ecole
```

### Étape 3 : Installation des dépendances Back-end (PHP/Laravel)
Ces commandes installent le framework Laravel et les librairies du projet.
```bash
composer install
```

### Étape 4 : Installation des dépendances Front-end (Vue/Inertia/Tailwind)
Ces commandes téléchargent et installent Vue.js, et tous les outils d'interface.
```bash
npm install
```

### Étape 5 : Configuration de l'environnement
Créez votre propre fichier de configuration locale en dupliquant le fichier d'exemple fourni par Laravel.
```bash
cp .env.example .env
```
*(Sous Windows, utilisez `copy .env.example .env` ou `copy NUL .env` puis copiez le contenu manuellement si besoin).*

Ouvrez le fichier `.env` nouvellement créé dans votre éditeur, et modifiez la section Database avec vos informations locales :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_nom_de_base_de_donnees
DB_USERNAME=root
DB_PASSWORD=
```
*N'oubliez pas de créer la base de données vide au prélable dans votre outil SQL (phpMyAdmin, etc.).*

### Étape 6 : Génération de la clé de l'application
Générez la clé de sécurité pour votre application Laravel.
```bash
php artisan key:generate
```

### Étape 7 : Migration et Seed (Création de la Base de Données)
Cette commande crée toutes les tables nécessaires dans votre base de données, et y insère les données initiales de test (l'admin par défaut, une année scolaire, etc.).
```bash
php artisan migrate:fresh --seed
```
*(Le `--seed` est crucial pour avoir le compte Administrateur par défaut défini dans les DatabaseSeeders).*

### Étape 8 : Lier le stockage (Storage Link)
Pour que les images, fichiers uploadés soient accessibles publiquement :
```bash
php artisan storage:link
```

### Étape 9 : Compilation Front-end et Lancement du Front
Construisez les assets Vue/TailwindCSS (en mode développement pour voir vos changements en direct) :
```bash
npm run dev
```

### Étape 10 : Lancement du serveur Back-end
Ouvrez un **nouveau terminal** (laissez tourner `npm run dev` dans le premier) et lancez le serveur PHP local de Laravel :
```bash
php artisan serve
```

### Étape 11 : Accéder à l'application
Ouvrez votre navigateur web et rendez-vous sur :
`http://localhost:8000`

Vous pouvez maintenant vous connecter avec les identifiants créés par le *seeder* lors de l'Étape 7. (Par défaut, regardez le fichier `database/seeders/DatabaseSeeder.php` pour connaître l'email et le mot de passe administrateur).

---

## 10. Tutoriel : Construire ce projet de A à Z (Le cycle de développement)

Si vous souhaitez recréer cette application (ou une architecture similaire) à partir de zéro, voici l'ordre exact dans lequel le développement a été conduit, listant les commandes essentielles de Laravel utilisées à chaque étape.

### Étape 1 : Initialisation du Projet Laravel
Commencez par créer un nouveau projet Laravel vierge.
```bash
composer create-project laravel/laravel ecole
cd ecole
```

### Étape 2 : Installation du starter-kit (Breeze avec Vue.js & Inertia)
Pour mettre en place le système d'authentification et l'architecture "VILT" (Vue - Inertia - Laravel - Tailwind) d'un seul coup :
```bash
composer require laravel/breeze --dev
php artisan breeze:install vue
```
*Le système va vous demander si vous voulez le support SSR (Server-Side Rendering) ou TypeScript, vous pouvez choisir 'No' pour une stack plus simple (ce qui a été fait ici).*

Ensuite, installez les paquets node et compilez les assets initiaux :
```bash
npm install
npm run build
```

### Étape 3 : Création des Migrations et Modèles
Plutôt que de faire le frontend immédiatement, il faut concevoir la structure des données. On crée tous nos Modèles avec leur fichier de Migration (`-m`).
```bash
# Exemple de création de modèles clés
php artisan make:model SchoolYear -m
php artisan make:model Classe -m
php artisan make:model Subject -m
php artisan make:model Student -m
php artisan make:model StudentEnrollment -m
php artisan make:model Assignment -m
php artisan make:model Evaluation -m
php artisan make:model Grade -m
php artisan make:model BehaviorGrade -m
php artisan make:model ClassPrincipal -m
```
Ensuite, on ouvre les différents fichiers générés dans `database/migrations/` pour définir les colonnes exactes (noms, contraintes, clés étrangères), puis on ouvre les fichiers dans `app/Models/` pour définir les relations (`hasMany`, `belongsTo`) et l'attribut `$fillable`.

### Étape 4 : Définition des Rôles (Middleware)
Pour différencier l'Administrateur du Professeur, on ajoute une colonne `role` à la table `users`.
On crée ensuite un Middleware pour sécuriser et bloquer l'accès à certaines routes selon le rôle :
```bash
php artisan make:middleware RoleMiddleware
```
Puis on l'enregistre (souvent via `bootstrap/app.php` depuis Laravel 11) pour s'en servir dans les routes.

### Étape 5 : Implémentation des Contrôleurs
Maintenant que la base de données est modélisée, il faut créer les contrôleurs qui feront le lien entre les vues (Vue.js) et la base.
```bash
php artisan make:controller AdminController
php artisan make:controller ProfController
```
Dans ces contrôleurs, on commence à écrire les méthodes (`classes()`, `students()`, etc.) qui font des requêtes avec l'ORM Eloquent et renvoient les données avec `Inertia::render('NomDeLaPage', ['donnees' => $data]);`.

### Étape 6 : Création des Routes (`routes/web.php`)
On lie ces fonctions à des URLs de façon sécurisée dans `routes/web.php` :
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/classes', [AdminController::class, 'classes'])->name('admin.classes');
    Route::post('/classes', [AdminController::class, 'storeClasse']);
    // ...
});
```

### Étape 7 : Développement du Front-end (Les vues Vue.js)
Dans le répertoire `resources/js/Pages/Admin/` (à créer manuellement), on construit chaque écran, par exemple `Classes.vue`.
Dans ce projet, de nombreux modules utilisent une approche **Master-Detail** :
- Une section à gauche liste les données (tableau + boucle `v-for`).
- Une section à droite continent un formulaire pour Créer ou Modifier une entrée (en combinant le composant avec le helper `useForm` fourni par Inertia).

### Étape 8 : Modularité et suppression globale
Pendant le développement on évite la répétition. Au lieu de recréer des modales de suppressions sur chaque page, un composant global `DeleteModal.vue` est créé dans `resources/js/Components/`.
Il est importé dans chaque page et déclenché via une route DELETE, souvent nommée `destroy...`.
```bash
# Pas de commande artisan, création manuelle de : 
# resources/js/Components/DeleteModal.vue
```

### Étape 9 : Logique Métier Avancée (Bulletins & Promotions)
Une fois les opérations de base dites "CRUD" (Créer, Lire, Mettre à jour, Supprimer) fonctionnelles, on s'attaque aux fonctionnalités phares de l'école :
- **Algorithme du Bulletin de notes (`bulletin()`)** : Trie tous les étudiants, calcule les moyennes en agrégeant les Notes liées aux Évaluations, et affecte un rang global et par matière via un algorithme PHP au sein du contrôleur Admin.
- **Interface d'impression** : Personnalisation de l'affichage avec des classes utilitaires (`print:hidden`, `@media print`) pour masquer la barre de navigation et formater le bulletin sur un format "A4" propre à l'impression.
- **Système de promotion** (`processPromote()`) : Script lourd qui clôture l'année, analyse la moyenne finale de chaque élève, et génère de nouvelles inscriptions (`StudentEnrollment`) dans l'année suivante, en leur affectant le statut "Nouveau" ou "Redoublant".

### Étape 10 : Peaufinage et Déploiement local
- Ajout de seeders (`php artisan make:seeder`) pour pré-remplir la base (créer un Admin, créer une année scolaire 2023-2024 par défaut) afin de ne pas bloquer les développeurs subséquents.
- Optimisation visuelle globale pour les mobiles (utiliser des balises `<div class="overflow-x-auto">` autour des tableaux, afficher des listes au lieu de colonnes sur petits écrans grâce à TailwindCSS).
- Configuration finale de l'écouteur `flash` (messages de succès interactifs) de la session Laravel vers la structure racine `AuthenticatedLayout.vue`.

---

## 11. Chronologie Exacte de Développement (L'ordre de codage)

Si vous devez recoder ce site pas à pas et ne savez pas par quel fichier commencer, voici la **chronologie exacte** recommandée (des fondations vers le visuel).

### A. La Couche Base de Données (Les Fondations)
On ne touche pas au visuel ni aux pages web tant que l'architecture des données n'est pas solide.
1. **Migrations** : Vous commencez dans `database/migrations/`.
   - L'ordre compte : Créez d'abord les tables sans clés étrangères `SchoolYear`, `Classe`, `Subject`. 
   - Puis les tables avec dépendances : `Students`, `Users` (ajout de la colonne rôle).
   - Enfin les tables de jointure : `StudentEnrollments`, `Assignments`, `Evaluations`, `Grades`, `BehaviorGrades`.
2. **Modèles** : Allez dans `app/Models/`. Pour chaque table, ouvrez le fichier (ex: `Classe.php`) et écrivez la propriété `$fillable` (les champs modifiables) et les méthodes de relations (ex: `public function students() { return $this->hasMany(...); }`).

### B. La Couche Backend (Le Contrôle)
Ici on prépare les routes URL et on trie les données issues de la base pour les envoyer au front-end Vue.js.
1. **Middlewares** : Créez `RoleMiddleware.php` pour que `$request->user()->role` gère les permissions et empêche un Professeur d'accéder au backend Administrateur.
2. **Routes** : Ouvrez `routes/web.php`. Créez le squelette des routes en groupes protégés par middleware. Écrivez toutes vos routes : `Route::get('/admin/classes', ...);`.
3. **Contrôleurs** : Créez `app/Http/Controllers/AdminController.php`. Écrivez la méthode pour chaque route. 
   - Ex : `public function classes() { $data = Classe::all(); return Inertia::render('Admin/Classes', ['classes' => $data]); }`.
   - Laissez les méthodes soumises à de gros algorithmes (comme `bulletin()`) vides pour les coder à la toute fin.

### C. La Couche Front-end (Les Vues Web)
Maintenant on réceptionne les variables PHP (Inertia Props) côté Vue.js pour faire les fenêtres de l'interface.
1. **Layout Principal** : Allez dans `resources/js/Layouts/AuthenticatedLayout.vue`. C'est là que l'on construit la Sidebar commune, la Navbar et le design de fond (Background).
2. **Les Vues Simples (CRUD basiques)** : 
   - Créez `resources/js/Pages/Admin/Classes.vue`.
   - Créez `resources/js/Pages/Admin/Subjects.vue`.
   - Créez `resources/js/Pages/Admin/Years.vue`.
   - Mettez en place la structure Master-Detail : Un `<table>` à gauche et un formulaire `useForm` de Inertia à droite.
3. **Les Vues Complexes (Relations et Listes Déroulantes)** :
   - `Admin/Students.vue` (nécessite de lier les élèves à des classes, donc des menus `<select>`).
   - `Admin/Enrollments.vue` (module de transfert d'élèves).
   - `Admin/Assignments.vue` (croisement entre un Prof, une Matière, et une Classe).

### D. La Logique Métier Indispensable (Le "Cerveau" de l'école)
C'est la dernière étape. L'interface est belle, maintenant on code les fonctionnalités qui demandent des mathématiques ou des filtres SQL.
1. **L'Espace Professeur** : `ProfController.php` et `resources/js/Pages/Prof/`. On s'assure qu'un prof ne voit que SES évaluations via une condition `where('prof_id', Auth::id())`.
2. **Les Évaluations & Notes** : Saisié des contrôles et enregistrement de requêtes multiples pour les notes (Table `grades`). Création du calcul de la note de Conduite.
3. **Le Bulletin Central** :
   - Écriture de `bulletin()` dans `AdminController` : Requête SQL lourde `\DB::select(...)` pour récupérer 100% des notes du semestre, calcul des moyennes et des coefficients en PHP.
   - Création de `resources/js/Pages/Admin/Bulletin.vue` conçu avec un design strict spécialement pour les imprimantes (`@media print`).
4. **Promotion de fin d'année** : Écriture de `processPromote()` afin de clore l'année active par un clic de bouton et générer toutes les nouvelles inscriptions automatiquement.

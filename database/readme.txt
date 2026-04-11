Vous pouvez créer un admin en une seule ligne de commande. Ouvrez votre terminal dans le dossier du projet et tapez :
php artisan tinker
Puis, une fois dans Tinkert, tapez ceci (remplacez les valeurs au besoin) :

App\Models\User::create(['username' => 'admin', 'nom' => 'Directeur', 'password' => Hash::make('votre_mot_de_passe'), 'role' => 'admin', 'is_active' => true]);
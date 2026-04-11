<?php
// views/register_prof.php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

// 1. Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Vérification CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Requête invalide.");
    }

    $code = trim($_POST['code']);
    $nom = trim($_POST['nom']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $grade = trim($_POST['grade']);
    $statut = trim($_POST['statut']);
    $corps = trim($_POST['corps']);

    if (!empty($code) && !empty($nom) && !empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            // Validation complexe du mot de passe
        $pass_valid = true;
        if (strlen($password) < 8) $pass_valid = false;
        if (!preg_match("/[A-Z]/", $password)) $pass_valid = false;
        if (!preg_match("/[a-z]/", $password)) $pass_valid = false;
        if (!preg_match("/[0-9]/", $password)) $pass_valid = false;
        if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) $pass_valid = false;

        if (!$pass_valid) {
            $error = "Le mot de passe ne respecte pas les conditions de sécurité.";
        } else {
            // 1. Vérifier le code
            $stmt = $pdo->prepare("SELECT id FROM invitations WHERE code = ? AND is_used = 0");
            $stmt->execute([$code]);
            $invitation = $stmt->fetch();

                if ($invitation) {
                    // 2. Vérifier si l'username existe déjà
                    $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $checkUser->execute([$username]);
                    
                    if (!$checkUser->fetch()) {
                        // 3. Créer le compte
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        try {
                            $pdo->beginTransaction();

                            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, nom, role, code_invitation, grade, statut, corps) VALUES (?, ?, ?, 'prof', ?, ?, ?, ?)");
                            $insert->execute([$username, $hash, $nom, $code, $grade, $statut, $corps]);

                            // Marquer le code comme utilisé
                            $update = $pdo->prepare("UPDATE invitations SET is_used = 1 WHERE id = ?");
                            $update->execute([$invitation['id']]);

                            $pdo->commit();
                            $_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                            header("Location: register_prof.php");
                            exit;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            error_log($e->getMessage());
                            $error = "Une erreur est survenue. Veuillez réessayer.";
                        }
                    } else {
                        $error = "Ce nom d'utilisateur est déjà pris.";
                    }
                } else {
                    $error = "Code d'invitation invalide ou déjà utilisé.";
                }
            }
        }
    } else {
        $error = "Tous les champs sont obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Professeur - Gestion Bulletins</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">

    <div class="login-card card">
        <h2>Bienvenue 👋</h2>
        <p class="text-muted">Inscription Professeur</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php 
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);
        if ($success): ?>
            <div class="alert alert-success text-center">
                <?php echo htmlspecialchars($success); ?> <br>
                <a href="login.php" class="btn w-full mt-4">Se connecter</a>
            </div>
        <?php else: ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group" style="text-align: left;">
                <label for="code">Code d'invitation (Reçu du Directeur)</label>
                <input type="text" id="code" name="code" required placeholder="ex: AB12CD34">
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="nom">Nom Complet</label>
                <input type="text" id="nom" name="nom" required placeholder="ex: M. Dupont">
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" placeholder="Entrez votre identifiant" required>
            </div>
            
            <div class="form-group" style="text-align: left;">
                <label for="password">Mot de passe</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
                
                <div id="password-requirements" class="mt-2" style="font-size: 0.85rem; background: var(--bg-color); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <p style="font-weight: 600; color: var(--text-main); margin-bottom: 5px;">Le mot de passe doit contenir :</p>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li id="req-length" class="text-muted"><span class="icon">○</span> Au moins 8 caractères</li>
                        <li id="req-upper" class="text-muted"><span class="icon">○</span> Une lettre majuscule</li>
                        <li id="req-lower" class="text-muted"><span class="icon">○</span> Une lettre minuscule</li>
                        <li id="req-number" class="text-muted"><span class="icon">○</span> Un chiffre</li>
                        <li id="req-special" class="text-muted"><span class="icon">○</span> Un caractère spécial</li>
                    </ul>
                </div>
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="confirm_password">Confirmez le mot de passe</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
                <div id="match-requirement" class="mt-1 text-muted" style="font-size: 0.85rem;">
                    <span class="icon">○</span> Les mots de passe correspondent
                </div>
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="grade">Grade</label>
                <input type="text" id="grade" name="grade" placeholder="ex: Professeur Certifié">
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="statut">Statut</label>
                <input type="text" id="statut" name="statut" placeholder="ex: Titulaire">
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="corps">Corps</label>
                <input type="text" id="corps" name="corps" placeholder="ex: Enseignement Secondaire">
            </div>

            <button type="submit" class="btn w-full mt-2">
                Créer mon compte
            </button>
        </form>

        <p class="text-center mt-4">
            Déjà un compte ? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Se connecter</a>
        </p>
        <?php endif; ?>
    </div>
</div>

    <style>
        .requirement-met {
            color: var(--success) !important;
            font-weight: 500;
        }
        .requirement-met .icon {
            color: var(--success);
        }
    </style>

    <script>
        const passwordInput = document.getElementById('password');
        const requirements = {
            length: { el: document.getElementById('req-length'), regex: /.{8,}/ },
            upper: { el: document.getElementById('req-upper'), regex: /[A-Z]/ },
            lower: { el: document.getElementById('req-lower'), regex: /[a-z]/ },
            number: { el: document.getElementById('req-number'), regex: /[0-9]/ },
            special: { el: document.getElementById('req-special'), regex: /[!@#$%^&*(),.?":{}|<>]/ }
        };
        const confirmInput = document.getElementById('confirm_password');
        const matchReq = document.getElementById('match-requirement');

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const btn = input.nextElementSibling;
            const icon = btn.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />';
            }
        }

        function validatePassword() {
            const val = passwordInput.value;
            const confirmVal = confirmInput.value;
            
            for (const key in requirements) {
                const req = requirements[key];
                if (req.regex.test(val)) {
                    req.el.classList.add('requirement-met');
                    req.el.querySelector('.icon').textContent = '●';
                } else {
                    req.el.classList.remove('requirement-met');
                    req.el.querySelector('.icon').textContent = '○';
                }
            }

            // Validation de la correspondance
            if (confirmVal && val === confirmVal) {
                matchReq.classList.add('requirement-met');
                matchReq.querySelector('.icon').textContent = '●';
            } else {
                matchReq.classList.remove('requirement-met');
                matchReq.querySelector('.icon').textContent = '○';
            }
        }

        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
    </script>

</body>
</html>

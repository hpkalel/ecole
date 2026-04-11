<?php
// views/login.php
session_start();
require_once '../config/database.php';

// 1. Protection contre le brute force
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

$lockout_wait = 0;
if ($_SESSION['login_attempts'] >= 5) {
    if (isset($_SESSION['last_attempt'])) {
        $wait = 60 - (time() - $_SESSION['last_attempt']);
        if ($wait > 0) {
            $lockout_wait = $wait;
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

// 2. Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Récupération de l'erreur en session (Flash message)
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Toujours prioriser le message de lockout s'il est actif
if ($lockout_wait > 0) {
    $error = "Trop de tentatives. Veuillez patienter " . $lockout_wait . " secondes.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Bloquer la soumission si en lockout
    if ($lockout_wait > 0) {
        header("Location: login.php");
        exit;
    } else {
        // Vérification CSRF
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            die("Requête invalide.");
        }
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, nom, is_active FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success - Check if active
            if (!$user['is_active']) {
                $error = "Ce compte a été désactivé. Veuillez contacter l'administration.";
            } else {
                // Réinitialise les tentatives après un succès
                $_SESSION['login_attempts'] = 0;
                
                // 3. Régénérer l'ID de session après login
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nom'] = $user['nom'];

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: prof_dashboard.php");
                }
                exit;
            }
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $_SESSION['login_error'] = "Nom d'utilisateur ou mot de passe incorrect.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
        header("Location: login.php");
        exit;
    }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Bulletins</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">

    <div class="login-card card">
        <h2>Bienvenue 👋</h2>
        <p class="text-muted">Connectez-vous à votre espace web</p>
        
        <?php if ($error): ?>
            <div id="lockoutAlert" class="alert alert-error text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
            </div>

            <button type="submit" class="btn w-full mt-2">
                Se connecter
            </button>
        </form>

        <p class="text-center mt-4">
            Professeur ? <a href="register_prof.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Créer un compte</a>
        </p>
    </div>

    <?php if ($lockout_wait > 0): ?>
    <script>
        const submitBtn = document.querySelector('button[type="submit"]');
        let waitTime = <?php echo $lockout_wait; ?>;
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-secondary'); // Optionnel: change le style
            
            const timer = setInterval(() => {
                waitTime--;
                if (waitTime <= 0) {
                    clearInterval(timer);
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.textContent = "Se connecter";
                    
                    // Cacher le message d'erreur
                    const alert = document.getElementById('lockoutAlert');
                    if (alert) alert.style.display = 'none';
                } else {
                    submitBtn.textContent = `Patientez ${waitTime}s...`;
                }
            }, 1000);
        }
    </script>
    <?php endif; ?>

    <script>
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
    </script>

</body>
</html>

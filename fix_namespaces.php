<?php
$adminPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$profPath = __DIR__ . '/app/Http/Controllers/ProfController.php';

function fixNamespaces($path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        // Add preceding backslash to App\Models if missing
        $content = preg_replace('/(?<!\\\\)App\\\\Models/', '\\\App\Models', $content);
        file_put_contents($path, $content);
    }
}

fixNamespaces($adminPath);
fixNamespaces($profPath);

echo "Namespaces fixed.";

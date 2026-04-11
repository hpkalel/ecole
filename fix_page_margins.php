<?php
$pagesDir = __DIR__ . '/resources/js/Pages';
$dirs = [$pagesDir . '/Admin', $pagesDir . '/Prof', $pagesDir];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = scandir($dir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.vue')) {
            $path = $dir . '/' . $file;
            $content = file_get_contents($path);
            
            // 1. Fix margins in containers
            // Look for mx-auto max-w-7xl and ensure px-4 is present
            $content = preg_replace(
                '/class="([^"]*?)mx-auto max-w-7xl(?!.*?px-4)([^"]*?)"/', 
                'class="$1mx-auto max-w-7xl px-4$2"', 
                $content
            );
            
            // 2. Specific for "sm:px-6 lg:px-8" -> "sm:px-6 lg:px-8 px-4"
            $content = str_replace('sm:px-6 lg:px-8', 'px-4 sm:px-6 lg:px-8', $content);
            
            // 3. Improve Cards styling (shadow-md -> shadow-sm border border-gray-100)
            // $content = str_replace('shadow-md', 'shadow-sm border border-gray-100', $content);
            
            file_put_contents($path, $content);
        }
    }
}

echo "Pages updated with responsive margins.";

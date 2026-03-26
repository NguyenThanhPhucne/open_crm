<?php
$dir = '/Users/phucnguyen/Downloads/open_crm/web/modules/custom';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/\.libraries\.yml$/', $file->getFilename())) {
        $content = file_get_contents($file->getPathname());
        $new_content = preg_replace('/version:\s+[0-9\.]+/', 'version: 4.0', $content);
        if ($content !== $new_content) {
            file_put_contents($file->getPathname(), $new_content);
            echo "Updated " . $file->getFilename() . "\n";
        }
    }
}

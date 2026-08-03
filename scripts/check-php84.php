<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este verificador deve ser executado pelo terminal.\n");
    exit(1);
}

if (PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80500) {
    fwrite(STDERR, "PHP 8.4.x obrigatorio; versao atual: " . PHP_VERSION . "\n");
    exit(1);
}

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
$excludedDirectories = ['.git', 'dist', 'vendor'];
$files = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    $topDirectory = explode('/', $relative, 2)[0];
    if (in_array($topDirectory, $excludedDirectories, true)) {
        continue;
    }

    $files[] = $file->getPathname();
}

sort($files);
$failures = 0;

foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        $failures++;
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
    }
    $output = [];
}

if ($failures > 0) {
    fwrite(STDERR, "Falha de sintaxe em {$failures} arquivo(s).\n");
    exit(1);
}

fwrite(STDOUT, 'PHP ' . PHP_VERSION . ': ' . count($files) . " arquivos validados.\n");

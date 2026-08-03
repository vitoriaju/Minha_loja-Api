[CmdletBinding()]
param(
    [string] $OutputPath = "dist/minha-loja-production.zip"
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$output = [System.IO.Path]::GetFullPath((Join-Path $projectRoot $OutputPath))
$distRoot = Join-Path $projectRoot 'dist'
$staging = Join-Path $distRoot 'production-package'

$publicDirectories = @(
    'api',
    'assets',
    'config',
    'controllers',
    'models',
    'vendor',
    'views'
)

$rootFiles = @(
    '.htaccess',
    'index.php',
    'pdo.php',
    'utils.php',
    'verifica_sessao.php'
)

New-Item -ItemType Directory -Path $distRoot -Force | Out-Null

if (Test-Path -LiteralPath $staging) {
    Remove-Item -LiteralPath $staging -Recurse -Force
}
New-Item -ItemType Directory -Path $staging | Out-Null

foreach ($directory in $publicDirectories) {
    $source = Join-Path $projectRoot $directory
    if (-not (Test-Path -LiteralPath $source -PathType Container)) {
        throw "Diretorio obrigatorio nao encontrado: $directory"
    }
    Copy-Item -LiteralPath $source -Destination $staging -Recurse
}

foreach ($file in $rootFiles) {
    $source = Join-Path $projectRoot $file
    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
        throw "Arquivo obrigatorio nao encontrado: $file"
    }
    Copy-Item -LiteralPath $source -Destination $staging
}

$forbiddenNames = @('.env', '.git', 'database', 'docs', 'gera_hash.php', 'teste_nfe.xml')
$leaks = Get-ChildItem -LiteralPath $staging -Recurse -Force | Where-Object {
    $_.Name -in $forbiddenNames -or $_.Extension -eq '.sql'
}

if ($leaks) {
    $found = ($leaks.FullName -join ', ')
    throw "Conteudo proibido encontrado no pacote: $found"
}

$outputDirectory = Split-Path -Parent $output
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
if (Test-Path -LiteralPath $output) {
    Remove-Item -LiteralPath $output -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory(
    $staging,
    $output,
    [System.IO.Compression.CompressionLevel]::Optimal,
    $false
)

Remove-Item -LiteralPath $staging -Recurse -Force
Write-Output "Pacote de producao criado em: $output"

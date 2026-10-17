<?php
function resolveSiteImagePath(?string $imagePath): ?string
{
    $imagePath = trim((string) $imagePath);
    if ($imagePath === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $imagePath)) {
        return $imagePath;
    }

    $normalizedPath = str_replace('\\', '/', $imagePath);
    $parts = parse_url($normalizedPath);
    $relativePath = ltrim($parts['path'] ?? '', '/');
    if (
        !str_starts_with($relativePath, 'assets/img/')
        || in_array('..', explode('/', $relativePath), true)
    ) {
        return null;
    }

    $projectRoot = dirname(__DIR__);
    $absolutePath = $projectRoot . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($absolutePath) && str_starts_with($relativePath, 'assets/img/')) {
        $imageRoot = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img';
        $fileName = basename($relativePath);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imageRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strcasecmp($file->getFilename(), $fileName) === 0) {
                $absolutePath = $file->getPathname();
                $relativePath = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($absolutePath, strlen($projectRoot) + 1)
                );
                break;
            }
        }
    }

    if (!is_file($absolutePath)) {
        return null;
    }

    return $relativePath
        . (isset($parts['query']) ? '?' . $parts['query'] : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

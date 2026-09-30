<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Return the shared PDO connection configured in database.php.
 */
function getPdo(): PDO
{
    return $GLOBALS['pdo'];
}

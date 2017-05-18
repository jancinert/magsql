<?php

namespace Magsql\Driver;

use PDO;
use Exception;

class PDODriverFactory
{
    /**
     * @codeCoverageIgnore
     */
    public static function create(PDO $pdo)
    {
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driverName) {
            case 'mysql':
                return new PDOMySQLDriver($pdo);
                break;
            case 'pgsql':
                return new PDOPgSQLDriver($pdo);
                break;
            case 'sqlite':
                return new PDOSQLiteDriver($pdo);
                break;
            default:
                throw new Exception('Unsupported PDO driver');
                break;
        }
    }
}

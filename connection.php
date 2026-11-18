<?php

require_once __DIR__ . '/privateConfig.php';
class Database
{
    public static $connection = null;

    public static function setupConnection()
    {
        if (self::$connection instanceof mysqli) {
            return;
        }

        $host = getenv("ZEYARA_DB_HOST");
        $user = getenv("ZEYARA_DB_USER");
        $password = getenv("ZEYARA_DB_PASS");
        $database = getenv("ZEYARA_DB_NAME");
        $port = getenv("ZEYARA_DB_PORT");

        if (
            $host === false || $host === "" ||
            $user === false || $user === "" ||
            $password === false ||
            $database === false || $database === ""
        ) {
            throw new RuntimeException(
                "Database configuration is missing."
            );
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        self::$connection = @new mysqli(
            $host,
            $user,
            $password,
            $database,
            ($port !== false && $port !== "")
                ? (int) $port
                : 3306
        );

        if (self::$connection->connect_errno) {
            self::$connection = null;

            throw new RuntimeException(
                "Database connection is unavailable."
            );
        }

        if (!self::$connection->set_charset("utf8mb4")) {
            self::$connection->close();
            self::$connection = null;

            throw new RuntimeException(
                "Database character configuration failed."
            );
        }
    }

    public static function iud($q)
    {
        self::setupConnection();

        return self::$connection->query($q);
    }

    public static function search($q)
    {
        self::setupConnection();

        return self::$connection->query($q);
    }
}

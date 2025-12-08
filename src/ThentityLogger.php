<?php

namespace Thentity;

class ThentityLogger
{
    protected static string $logFile = __DIR__ . '/thentity_errors.log';

    /**
     * Configure le fichier de log
     */
    public static function setLogFile(string $filePath): void
    {
        self::$logFile = $filePath;
    }

    /**
     * Enregistre un message dans le log
     */
    public static function log(string $message, string $level = 'ERROR'): void
    {
        $date = date('Y-m-d H:i:s');
        echo $line = "[$date][$level] $message\n";
        //error_log($line, 3, self::$logFile);
    }

    /**
     * Enregistre une exception dans le log
     */
    public static function logException(\Throwable $e): void
    {
        $msg = sprintf(
            "%s: %s in %s on line %d\nStack trace:\n%s\n",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        self::log($msg, 'EXCEPTION');
    }

    /**
     * Enregistre une erreur SQL dans le log
     */
    public static function logSqlError(string $query, array $params = [], array $errorInfo = []): void
    {
        $paramStr = json_encode($params);
        $errorStr = json_encode($errorInfo);
        $msg = "SQL Error\nQuery: $query\nParams: $paramStr\nErrorInfo: $errorStr";
        self::log($msg, 'SQL');
    }
}

#!/usr/bin/env php
<?php

use Thentity\ThentityGenerator;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);
require_once __DIR__ . '/../src/ThentityGenerator.php';


// Vérifie si un chemin de config est passé en argument
$configPath = $argv[1] ?? __DIR__ . '/../config/thentity.config.php';

if (!file_exists($configPath)) {
    echo "Fichier de config introuvable : $configPath\n";
    exit(1);
}

// Charger le fichier de config
$config = require $configPath;

// Connexion PDO
try {
    $dbConf = $config['db'];
    $pdo = new PDO(
        "mysql:host={$dbConf['host']};dbname={$dbConf['name']};charset={$dbConf['charset']}",
        $dbConf['user'],
        $dbConf['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si une table est passée en 2ᵉ argument, on ne génère que celle-là
    $tableName = $argv[2] ?? null;

    if ($tableName) {
       $GEN = new ThentityGenerator($pdo, $config);
           $GEN->generateThentity($tableName);
           $GEN->generateConfig();
    } else {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
           $GEN = new ThentityGenerator($pdo, $config);
           $GEN->generateThentity($table);
           $GEN->generateConfig();
        }
    }

    echo "Génération terminée.\n";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage() . "\n";
    exit(1);
}


<?php
$config =  [
    // Connection DB
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'thentity',
        'user' => 'root',
        'pass' => 'test',
        'charset' => 'utf8mb4',
    ],

    // Dossier de destination pour les classes abstraites et concrètes
    'output' => [
        'config_dir' => __DIR__ . '/../tests/Entity/',
        'abstract_dir' => __DIR__ . '/../tests/Thentity/ThentityAbstract/',
        'concrete_dir' => __DIR__ . '/../tests/Entity/',
    ],

    // Nom du namespace
    'namespace' => 'Entity',

    // Options
    'use_cache' => true,
];
// Génération du DSN
$dbConf = $config['db'];
$dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['name']};charset={$dbConf['charset']}";

// Création de l'objet PDO
try {
    $config['PDO'] = new PDO($dsn, $dbConf['user'], $dbConf['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion PDO : " . $e->getMessage());
}
return $config;

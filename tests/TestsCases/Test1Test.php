<?php
namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\EntityConfig;

/**
 * ============================================================================
 * Test Class for Test1 Entity
 * ============================================================================
 * Cette classe teste toutes les fonctionnalités de base de l'entité Test1
 * qui représente la table principale de notre système de test.
 * 
 * @package Tests\Entity
 *
 */
 #[CoversMethod("Entity\Test1")]
class Test1Test extends TestCase
{
    private static $pdo;
    
    /**
     * Configuration initiale avant tous les tests de cette classe
     * Cette méthode est appelée UNE SEULE FOIS avant tous les tests
     * 
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "INITIALISATION DES TESTS POUR TEST1\n";
        echo str_repeat("=", 80) . "\n";
        
        // Récupération de la connexion PDO
        self::$pdo = EntityConfig::getPDOLink();
        echo "✓ Connexion PDO établie\n";
        
        // Nettoyage des tables (ordre important à cause des FK)
        echo "\n→ Nettoyage des tables existantes...\n";
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        self::$pdo->exec('TRUNCATE TABLE test_3');
        echo "  ✓ Table test_3 nettoyée\n";
        self::$pdo->exec('TRUNCATE TABLE test_2');
        echo "  ✓ Table test_2 nettoyée\n";
        self::$pdo->exec('TRUNCATE TABLE test_1');
        echo "  ✓ Table test_1 nettoyée\n";
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        // Insertion des données de test
        echo "\n→ Insertion des données de test...\n";
        self::$pdo->exec("
            INSERT INTO test_1 (id, title, description, active) VALUES
            (1, 'Test A', 'Description du test A', 1),
            (2, 'Test B', NULL, 0),
            (3, 'Test C', 'Lorem ipsum dolor sit amet', 1)
        ");
        echo "  ✓ 3 enregistrements insérés dans test_1\n";
        echo str_repeat("=", 80) . "\n\n";
    }
    
    /**
     * TEST 1.1 : Vérification du nom de la table
     * ----------------------------------------
     * Vérifie que la méthode getTableName() retourne bien 'test_1'
     * C'est un test fondamental qui valide la configuration de base
     * 
     * @return void
     */
    public function testGetTableName()
    {
        echo "\n[TEST 1.1] Vérification du nom de table\n";
        
        $tableName = Test1::getTableName();
        echo "  → Nom de table récupéré : '$tableName'\n";
        
        $this->assertEquals('test_1', $tableName);
        echo "  ✓ Le nom de table est correct : test_1\n";
    }
    
    /**
     * TEST 1.2 : Vérification de la clé primaire
     * -----------------------------------------
     * Vérifie que la méthode getPrimaryKey() retourne bien 'id'
     * Important pour toutes les opérations CRUD
     * 
     * @return void
     */
    public function testGetPrimaryKey()
    {
        echo "\n[TEST 1.2] Vérification de la clé primaire\n";
        
        $primaryKey = Test1::getPrimaryKey();
        echo "  → Clé primaire récupérée : '$primaryKey'\n";
        
        $this->assertEquals('id', $primaryKey);
        echo "  ✓ La clé primaire est correcte : id\n";
    }
    
    /**
     * TEST 1.3 : Vérification de la structure des colonnes
     * --------------------------------------------------
     * Teste la méthode getTableKeys() qui retourne la structure complète
     * de la table avec toutes les métadonnées des colonnes
     * 
     * @return void
     */
    public function testGetTableKeys()
    {
        echo "\n[TEST 1.3] Vérification de la structure des colonnes\n";
        
        $keys = Test1::getTableKeys();
        echo "  → Nombre de colonnes : " . count($keys) . "\n";
        
        // Test de présence des colonnes
        $this->assertIsArray($keys);
        echo "  ✓ getTableKeys() retourne bien un tableau\n";
        
        $expectedColumns = ['id', 'title', 'description', 'created_at', 'active'];
        foreach ($expectedColumns as $column) {
            $this->assertArrayHasKey($column, $keys);
            echo "  ✓ Colonne '$column' présente\n";
        }
        
        // Vérification détaillée de la clé primaire
        echo "\n  → Analyse de la colonne 'id' (clé primaire):\n";
        $this->assertTrue($keys['id']['primary_key']);
        echo "    ✓ Marquée comme primary_key\n";
        $this->assertTrue($keys['id']['auto_increment']);
        echo "    ✓ Auto-incrémentée\n";
        $this->assertEquals('int', $keys['id']['type']);
        echo "    ✓ Type : int\n";
    }
    
    /**
     * TEST 1.4 : Chargement d'une entité existante
     * ------------------------------------------
     * Teste l'instanciation d'un objet Test1 avec un ID existant
     * Vérifie que toutes les données sont correctement chargées
     * 
     * @return void
     */
    public function testLoadEntity()
    {
        echo "\n[TEST 1.4] Chargement d'une entité existante (ID=1)\n";
        
        $test1 = new Test1(1);
        echo "  → Entité instanciée\n";
        
        $this->assertInstanceOf(Test1::class, $test1);
        echo "  ✓ L'objet est bien une instance de Test1\n";
        
        // Vérification de chaque propriété
        echo "\n  → Vérification des propriétés:\n";
        $this->assertEquals(1, $test1->id);
        echo "    ✓ id = {$test1->id}\n";
        $this->assertEquals('Test A', $test1->title);
        echo "    ✓ title = '{$test1->title}'\n";
        $this->assertEquals('Description du test A', $test1->description);
        echo "    ✓ description = '{$test1->description}'\n";
        $this->assertEquals(1, $test1->active);
        echo "    ✓ active = {$test1->active}\n";
    }
    
    /**
     * TEST 1.5 : Conversion en tableau
     * ------------------------------
     * Teste la méthode toArray() qui convertit l'entité en tableau associatif
     * Utile pour l'export JSON ou le passage de données
     * 
     * @return void
     */
    public function testToArray()
    {
        echo "\n[TEST 1.5] Conversion de l'entité en tableau\n";
        
        $test1 = new Test1(1);
        $array = $test1->toArray();
        
        echo "  → Conversion effectuée\n";
        echo "  → Contenu du tableau:\n";
        echo "    " . json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        $this->assertIsArray($array);
        echo "  ✓ toArray() retourne bien un tableau\n";
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
         $this->assertArrayHasKey('active', $array);
        echo "  ✓ Toutes les clés sont présentes\n";
        
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Test A', $array['title']);
        echo "  ✓ Les valeurs correspondent aux données en base\n";
    }
    
    /**
     * TEST 1.6 : Accès dynamique aux propriétés
     * ---------------------------------------
     * Vérifie que les propriétés sont accessibles directement via $obj->property
     * Teste le mécanisme de propriétés dynamiques de PHP
     * 
     * @return void
     */
    public function testPropertyAccess()
    {
        echo "\n[TEST 1.6] Accès dynamique aux propriétés\n";
        
        $test1 = new Test1(1);
        
        echo "  → Test d'accès aux propriétés:\n";
        echo "    \$test1->id = {$test1->id}\n";
        $this->assertEquals(1, $test1->id);
        
        echo "    \$test1->title = '{$test1->title}'\n";
        $this->assertEquals('Test A', $test1->title);
        
        echo "    \$test1->description = '{$test1->description}'\n";
        $this->assertEquals('Description du test A', $test1->description);
        
        echo "    \$test1->active = {$test1->active}\n";
        $this->assertEquals(1, $test1->active);
        
        echo "  ✓ Tous les accès aux propriétés fonctionnent\n";
    }
    
    /**
     * TEST 1.7 : Gestion des champs NULL
     * --------------------------------
     * Teste le comportement avec des valeurs NULL dans la base de données
     * Important pour la gestion des champs optionnels
     * 
     * @return void
     */
    public function testNullableField()
    {
        echo "\n[TEST 1.7] Gestion des champs NULL (ID=2)\n";
        
        $test1 = new Test1(2);
        echo "  → Entité chargée (Test B sans description)\n";
        
        $this->assertEquals(2, $test1->id);
        echo "  ✓ id = {$test1->id}\n";
        
        $this->assertEquals('Test B', $test1->title);
        echo "  ✓ title = '{$test1->title}'\n";
        
        $this->assertNull($test1->description);
        echo "  ✓ description = NULL (champ nullable)\n";
        
        $this->assertEquals(0, $test1->active);
        echo "  ✓ active = {$test1->active} (inactif)\n";
/*
        $T = new Test1();
        $T->title = 'test';
        $T->save();
        */
    }
}
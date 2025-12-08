<?php
namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\EntityConfig;

/**
 * ============================================================================
 * Test Class for Test2 Entity
 * ============================================================================
 * Cette classe teste l'entité Test2 qui contient des types de données
 * spéciaux comme ENUM, DECIMAL et JSON, ainsi qu'une relation FK vers Test1
 * 
 * @package Tests\Entity
 */
 #[CoversMethod("Entity\Test2")]
class Test2Test extends TestCase
{
    private static $pdo;
    
    /**
     * Configuration initiale pour les tests Test2
     * Insère les données dans test_2 après la configuration de Test1
     * 
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "INITIALISATION DES TESTS POUR TEST2\n";
        echo str_repeat("=", 80) . "\n";
        
        self::$pdo = EntityConfig::getPDOLink();
        
        // Insertion des données de test pour test_2
        echo "→ Insertion des données de test dans test_2...\n";
        self::$pdo->exec("
            INSERT INTO test_2 (id, test_1_id, amount, status, meta) VALUES
            (1, 1, 10.50, 'ok', '{\"note\": \"paiement validé\", \"tags\": [\"green\"]}'),
            (2, 1, 99.99, 'pending', NULL),
            (3, 2, 5.00, 'failed', '{\"error\": \"CB refusée\"}')
        ");
        echo "  ✓ 3 enregistrements insérés dans test_2\n";
        echo str_repeat("=", 80) . "\n\n";
    }
    
    /**
     * TEST 2.1 : Vérification du nom de la table Test2
     * 
     * @return void
     */
    public function testGetTableName()
    {
        echo "\n[TEST 2.1] Vérification du nom de table\n";
        
        $tableName = Test2::getTableName();
        echo "  → Nom de table : '$tableName'\n";
        
        $this->assertEquals('test_2', $tableName);
        echo "  ✓ Nom correct : test_2\n";
    }
    
    /**
     * TEST 2.2 : Vérification de la clé primaire Test2
     * 
     * @return void
     */
    public function testGetPrimaryKey()
    {
        echo "\n[TEST 2.2] Vérification de la clé primaire\n";
        
        $primaryKey = Test2::getPrimaryKey();
        echo "  → Clé primaire : '$primaryKey'\n";
        
        $this->assertEquals('id', $primaryKey);
        echo "  ✓ Clé primaire correcte : id\n";
    }
    
    /**
     * TEST 2.3 : Structure des colonnes avec types spéciaux
     * ---------------------------------------------------
     * Test particulièrement important pour Test2 car cette table contient :
     * - Un champ ENUM (status) avec des valeurs définies
     * - Un champ DECIMAL (amount) avec précision et échelle
     * - Un champ JSON (meta)
     * 
     * @return void
     */
    public function testGetTableKeys()
    {
        echo "\n[TEST 2.3] Vérification de la structure (types spéciaux)\n";
        
        $keys = Test2::getTableKeys();
        echo "  → Nombre de colonnes : " . count($keys) . "\n";
        
        // Vérification de base
        $this->assertIsArray($keys);
        $this->assertArrayHasKey('id', $keys);
        $this->assertArrayHasKey('test_1_id', $keys);
        $this->assertArrayHasKey('amount', $keys);
        $this->assertArrayHasKey('status', $keys);
        $this->assertArrayHasKey('meta', $keys);
        echo "  ✓ Toutes les colonnes présentes\n";
        
        // Test détaillé du type ENUM
        echo "\n  → Analyse du champ ENUM 'status':\n";
        $this->assertEquals('enum', $keys['status']['data_type']);
        echo "    ✓ data_type = enum\n";
        $this->assertArrayHasKey('values', $keys['status']);
        echo "    ✓ Possède un tableau 'values'\n";
        $this->assertEquals(['pending', 'ok', 'failed'], $keys['status']['values']);
        echo "    ✓ Valeurs possibles : " . implode(', ', $keys['status']['values']) . "\n";
        
        // Test détaillé du type DECIMAL
        echo "\n  → Analyse du champ DECIMAL 'amount':\n";
        $this->assertEquals('decimal', $keys['amount']['data_type']);
        echo "    ✓ data_type = decimal\n";
        $this->assertEquals('float', $keys['amount']['type']);
        echo "    ✓ type PHP = float\n";
        $this->assertEquals(10, $keys['amount']['precision']);
        echo "    ✓ precision = 10 (10 chiffres au total)\n";
        $this->assertEquals(2, $keys['amount']['scale']);
        echo "    ✓ scale = 2 (2 décimales)\n";
    }
    
    /**
     * TEST 2.4 : Chargement d'une entité Test2
     * 
     * @return void
     */
    public function testLoadEntity()
    {
        echo "\n[TEST 2.4] Chargement d'une entité Test2 (ID=1)\n";
        
        $test2 = new Test2(1);
        echo "  → Entité instanciée\n";
        
        $this->assertInstanceOf(Test2::class, $test2);
        echo "  ✓ Instance de Test2\n";
        
        echo "\n  → Propriétés chargées:\n";
        $this->assertEquals(1, $test2->id);
        echo "    ✓ id = {$test2->id}\n";
        $this->assertEquals(1, $test2->test_1_id);
        echo "    ✓ test_1_id = {$test2->test_1_id} (FK vers Test1)\n";
        $this->assertEquals(10.50, $test2->amount);
        echo "    ✓ amount = {$test2->amount} €\n";
        $this->assertEquals('ok', $test2->status);
        echo "    ✓ status = '{$test2->status}'\n";
    }
    
    /**
     * TEST 2.5 : Gestion des valeurs ENUM
     * ---------------------------------
     * Vérifie que les 3 valeurs possibles de l'ENUM sont correctement gérées
     * 
     * @return void
     */
    public function testEnumField()
    {
        echo "\n[TEST 2.5] Test des différentes valeurs ENUM\n";
        
        $test2_1 = new Test2(1);
        $this->assertEquals('ok', $test2_1->status);
        echo "  ✓ Enregistrement #1 : status = 'ok'\n";
        
        $test2_2 = new Test2(2);
        $this->assertEquals('pending', $test2_2->status);
        echo "  ✓ Enregistrement #2 : status = 'pending'\n";
        
        $test2_3 = new Test2(3);
        $this->assertEquals('failed', $test2_3->status);
        echo "  ✓ Enregistrement #3 : status = 'failed'\n";
        
        echo "  → Les 3 valeurs ENUM sont correctement gérées\n";
    }
    
    /**
     * TEST 2.6 : Précision des champs DECIMAL
     * -------------------------------------
     * Vérifie que les valeurs décimales sont correctement stockées et récupérées
     * 
     * @return void
     */
    public function testDecimalField()
    {
        echo "\n[TEST 2.6] Test de la précision DECIMAL\n";
        
        $test2_1 = new Test2(1);
        $this->assertEquals(10.50, $test2_1->amount);
        echo "  ✓ Montant #1 : {$test2_1->amount} € (2 décimales)\n";
        
        $test2_2 = new Test2(2);
        $this->assertEquals(99.99, $test2_2->amount);
        echo "  ✓ Montant #2 : {$test2_2->amount} € (précision maximale)\n";
        
        echo "  → La précision DECIMAL(10,2) est respectée\n";
    }
    
    /**
     * TEST 2.7 : Gestion des champs JSON
     * --------------------------------
     * Vérifie que les données JSON sont correctement gérées (NULL et valeurs)
     * 
     * @return void
     */
    public function testJsonField()
    {
        echo "\n[TEST 2.7] Test des champs JSON\n";
        
        $test2_1 = new Test2(1);
        $this->assertNotNull($test2_1->meta);
        echo "  ✓ Enregistrement #1 : meta contient des données JSON\n";
        if ($test2_1->meta) {
            $decoded = json_decode($test2_1->meta, true);
            echo "    → Contenu : " . json_encode($decoded, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        $test2_2 = new Test2(2);
        $this->assertNull($test2_2->meta);
        echo "  ✓ Enregistrement #2 : meta = NULL\n";
        
        echo "  → Les champs JSON nullable sont bien gérés\n";
    }
    
    /**
     * TEST 2.8 : Validation des clés étrangères
     * ---------------------------------------
     * Vérifie que la relation avec Test1 est correcte
     * 
     * @return void
     */
    public function testForeignKey()
    {
        echo "\n[TEST 2.8] Test de la clé étrangère vers Test1\n";
        
        $test2 = new Test2(1);
        $this->assertEquals(1, $test2->test_1_id);
        echo "  → Test2(1) pointe vers Test1(1)\n";
        
        // Vérifier que la relation existe réellement
        $test1 = new Test1($test2->test_1_id);
        $this->assertEquals('Test A', $test1->title);
        echo "  ✓ Relation valide : Test2(1) -> Test1(1) '{$test1->title}'\n";
        
        echo "  → La contrainte de clé étrangère fonctionne\n";
    }
}

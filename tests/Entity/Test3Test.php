<?php
namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;
use Entity\EntityConfig;

/**
 * ============================================================================
 * Test Class for Test3 Entity
 * ============================================================================
 * Cette classe teste l'entité Test3 qui contient :
 * - Une contrainte UNIQUE sur le champ 'code'
 * - Une FK nullable vers Test2
 * - Des champs booléens
 * Elle permet également de tester la chaîne complète Test3 -> Test2 -> Test1
 * 
 * @package Tests\Entity
 */
#[CoversMethod("Entity\Test3")]
class Test3Test extends TestCase
{
    private static $pdo;
    
    /**
     * Configuration initiale pour les tests Test3
     * 
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "INITIALISATION DES TESTS POUR TEST3\n";
        echo str_repeat("=", 80) . "\n";
        
        self::$pdo = EntityConfig::getPDOLink();
        
        // Insertion des données de test pour test_3
        echo "→ Insertion des données de test dans test_3...\n";
        self::$pdo->exec("
            INSERT INTO test_3 (id, test_2_id, code, label, enabled) VALUES
            (1, 1, 'ABC123', 'Premier élément', 1),
            (2, NULL, 'XYZ999', 'Sans relation', 0),
            (3, 2, 'HELLO', 'Élément lié à test_2 #2', 1)
        ");
        echo "  ✓ 3 enregistrements insérés dans test_3\n";
        echo "    - 2 avec FK vers test_2\n";
        echo "    - 1 sans relation (FK NULL)\n";
        echo str_repeat("=", 80) . "\n\n";
    }
    
    /**
     * TEST 3.1 : Vérification du nom de table
     * 
     * @return void
     */
    public function testGetTableName()
    {
        echo "\n[TEST 3.1] Vérification du nom de table\n";
        
        $tableName = Test3::getTableName();
        echo "  → Nom de table : '$tableName'\n";
        
        $this->assertEquals('test_3', $tableName);
        echo "  ✓ Nom correct : test_3\n";
    }
    
    /**
     * TEST 3.2 : Vérification de la clé primaire
     * 
     * @return void
     */
    public function testGetPrimaryKey()
    {
        echo "\n[TEST 3.2] Vérification de la clé primaire\n";
        
        $primaryKey = Test3::getPrimaryKey();
        echo "  → Clé primaire : '$primaryKey'\n";
        
        $this->assertEquals('id', $primaryKey);
        echo "  ✓ Clé primaire correcte : id\n";
    }
    
    /**
     * TEST 3.3 : Structure avec contrainte UNIQUE
     * -----------------------------------------
     * Test important pour vérifier que la contrainte UNIQUE sur 'code'
     * est bien détectée dans les métadonnées
     * 
     * @return void
     */
    public function testGetTableKeys()
    {
        echo "\n[TEST 3.3] Vérification de la structure (contrainte UNIQUE)\n";
        
        $keys = Test3::getTableKeys();
        echo "  → Nombre de colonnes : " . count($keys) . "\n";
        
        $this->assertIsArray($keys);
        $this->assertArrayHasKey('id', $keys);
        $this->assertArrayHasKey('test_2_id', $keys);
        $this->assertArrayHasKey('code', $keys);
        $this->assertArrayHasKey('label', $keys);
        $this->assertArrayHasKey('enabled', $keys);
        echo "  ✓ Toutes les colonnes présentes\n";
        
        // Vérification de la contrainte UNIQUE
        echo "\n  → Analyse du champ 'code' (UNIQUE):\n";
        $this->assertTrue($keys['code']['unique']);
        echo "    ✓ Marqué comme UNIQUE (pas de doublons possibles)\n";
        $this->assertEquals(32, $keys['code']['max_length']);
        echo "    ✓ Longueur max : 32 caractères\n";
    }
    
    /**
     * TEST 3.4 : Chargement d'une entité Test3
     * 
     * @return void
     */
    public function testLoadEntity()
    {
        echo "\n[TEST 3.4] Chargement d'une entité Test3 (ID=1)\n";
        
        $test3 = new Test3(1);
        echo "  → Entité instanciée\n";
        
        $this->assertInstanceOf(Test3::class, $test3);
        echo "  ✓ Instance de Test3\n";
        
        echo "\n  → Propriétés chargées:\n";
        $this->assertEquals(1, $test3->id);
        echo "    ✓ id = {$test3->id}\n";
        $this->assertEquals(1, $test3->test_2_id);
        echo "    ✓ test_2_id = {$test3->test_2_id} (FK vers Test2)\n";
        $this->assertEquals('ABC123', $test3->code);
        echo "    ✓ code = '{$test3->code}' (UNIQUE)\n";
        $this->assertEquals('Premier élément', $test3->label);
        echo "    ✓ label = '{$test3->label}'\n";
        $this->assertEquals(1, $test3->enabled);
        echo "    ✓ enabled = {$test3->enabled}\n";
    }
    
    /**
     * TEST 3.5 : Unicité des codes
     * --------------------------
     * Vérifie que tous les codes en base sont bien différents
     * grâce à la contrainte UNIQUE
     * 
     * @return void
     */
    public function testUniqueField()
    {
        echo "\n[TEST 3.5] Test de l'unicité des codes\n";
        
        $test3_1 = new Test3(1);
        $test3_2 = new Test3(2);
        $test3_3 = new Test3(3);
        
        echo "  → Codes en base:\n";
        echo "    - Test3(1).code = '{$test3_1->code}'\n";
        echo "    - Test3(2).code = '{$test3_2->code}'\n";
        echo "    - Test3(3).code = '{$test3_3->code}'\n";
        
        // Vérifier que tous les codes sont différents
        $this->assertNotEquals($test3_1->code, $test3_2->code);
        $this->assertNotEquals($test3_1->code, $test3_3->code);
        $this->assertNotEquals($test3_2->code, $test3_3->code);
        
        echo "  ✓ Tous les codes sont différents (contrainte UNIQUE respectée)\n";
    }
    
    /**
     * TEST 3.6 : Clé étrangère nullable
     * -------------------------------
     * Teste le cas d'une FK qui peut être NULL (ON DELETE SET NULL)
     * 
     * @return void
     */
    public function testNullableForeignKey()
    {
        echo "\n[TEST 3.6] Test de la clé étrangère nullable\n";
        
        $test3 = new Test3(2);
        
        $this->assertEquals(2, $test3->id);
        echo "  → Test3(2) chargé\n";
        
        $this->assertNull($test3->test_2_id);
        echo "  ✓ test_2_id = NULL (pas de relation)\n";
        
        $this->assertEquals('XYZ999', $test3->code);
        echo "  ✓ code = '{$test3->code}'\n";
        
        echo "  → Un Test3 peut exister sans être lié à un Test2\n";
    }
    
    /**
     * TEST 3.7 : Champs booléens (TINYINT)
     * ----------------------------------
     * Vérifie la gestion des booléens (stockés en TINYINT en MySQL)
     * 
     * @return void
     */
    public function testBooleanField()
    {
        echo "\n[TEST 3.7] Test des champs booléens (enabled)\n";
        
        $test3_1 = new Test3(1);
        $this->assertEquals(1, $test3_1->enabled);
        echo "  ✓ Test3(1).enabled = 1 (activé)\n";
        
        $test3_2 = new Test3(2);
        $this->assertEquals(0, $test3_2->enabled);
        echo "  ✓ Test3(2).enabled = 0 (désactivé)\n";
        
        echo "  → Les booléens TINYINT(1) sont correctement gérés\n";
    }
    
    /**
     * TEST 3.8 : Chaîne complète de relations
     * -------------------------------------
     * Test le plus important : vérifie qu'on peut suivre toute la chaîne
     * Test3 -> Test2 -> Test1 en traversant les FK
     * 
     * @return void
     */
    public function testCompleteRelationChain()
    {
        echo "\n[TEST 3.8] Test de la chaîne complète Test3 -> Test2 -> Test1\n";
        echo str_repeat("-", 70) . "\n";
        
        // Charger Test3
        $test3 = new Test3(1);
        $this->assertEquals(1, $test3->test_2_id);
        echo "  [1] Test3(1) chargé\n";
        echo "      → code = '{$test3->code}'\n";
        echo "      → test_2_id = {$test3->test_2_id}\n";
        
        // Suivre la FK vers Test2
        $test2 = new Test2($test3->test_2_id);
        $this->assertEquals(1, $test2->test_1_id);
        echo "\n  [2] Test2({$test3->test_2_id}) chargé via FK\n";
        echo "      → amount = {$test2->amount} €\n";
        echo "      → status = '{$test2->status}'\n";
        echo "      → test_1_id = {$test2->test_1_id}\n";
        
        // Suivre la FK vers Test1
        $test1 = new Test1($test2->test_1_id);
        echo "\n  [3] Test1({$test2->test_1_id}) chargé via FK\n";
        echo "      → title = '{$test1->title}'\n";
        echo "      → description = '{$test1->description}'\n";
        
        // Vérifications finales
        $this->assertEquals('ABC123', $test3->code);
        $this->assertEquals(10.50, $test2->amount);
        $this->assertEquals('Test A', $test1->title);
        
        echo "\n  ✓ CHAÎNE COMPLÈTE VALIDÉE :\n";
        echo "    Test3 '{$test3->code}' -> Test2 {$test2->amount}€ -> Test1 '{$test1->title}'\n";
        echo str_repeat("-", 70) . "\n";
    }
}

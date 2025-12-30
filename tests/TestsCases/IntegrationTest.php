<?php 

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;
use Entity\EntityConfig;

/**
 * ============================================================================
 * Test Class for Integration Tests
 * ============================================================================
 * Cette classe effectue des tests d'intégration qui vérifient le bon
 * fonctionnement global du système Thentity avec toutes les entités ensemble
 * 
 * @package Tests\Entity
 */
class IntegrationTest extends TestCase
{
    /**
     * TEST INT.1 : Chargement simultané de plusieurs entités
     * ----------------------------------------------------
     * Vérifie qu'on peut charger plusieurs entités en même temps
     * sans conflit ni problème de ressources
     * 
     * @return void
     */
    public function testMultipleEntitiesLoad()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTS D'INTÉGRATION\n";
        echo str_repeat("=", 80) . "\n";
        echo "\n[TEST INT.1] Chargement simultané de plusieurs entités\n";
        
        $test1 = new Test1(1);
        echo "  → Test1(1) instancié\n";
        
        $test2 = new Test2(1);
        echo "  → Test2(1) instancié\n";
        
        $test3 = new Test3(1);
        echo "  → Test3(1) instancié\n";
        
        $this->assertInstanceOf(Test1::class, $test1);
        $this->assertInstanceOf(Test2::class, $test2);
        $this->assertInstanceOf(Test3::class, $test3);
        
        echo "  ✓ Les 3 entités coexistent sans problème\n";
    }
    
    /**
     * TEST INT.2 : Export en tableau de toutes les entités
     * --------------------------------------------------
     * Vérifie que toArray() fonctionne pour chaque entité
     * 
     * @return void
     */
    public function testToArrayOnAllEntities()
    {
        echo "\n[TEST INT.2] Export toArray() sur toutes les entités\n";
        
        $test1 = new Test1(1);
        $test2 = new Test2(1);
        $test3 = new Test3(1);
        
        $array1 = $test1->toArray();
        $array2 = $test2->toArray();
        $array3 = $test3->toArray();
        
        echo "  → Test1 converti en tableau\n";
        $this->assertIsArray($array1);
        $this->assertArrayHasKey('id', $array1);
        
        echo "  → Test2 converti en tableau\n";
        $this->assertIsArray($array2);
        $this->assertArrayHasKey('id', $array2);
        
        echo "  → Test3 converti en tableau\n";
        $this->assertIsArray($array3);
        $this->assertArrayHasKey('id', $array3);
        
        echo "  ✓ toArray() fonctionne sur toutes les entités\n";
        
        // Affichage pour debug
        echo "\n  → Exemple de structure Test1:\n";
        echo "    " . json_encode($array1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    /**
     * TEST INT.3 : Validation de la connexion PDO
     * -----------------------------------------
     * Vérifie que la connexion PDO est bien configurée et fonctionnelle
     * 
     * @return void
     */
    public function testPDOConnection()
    {
        echo "\n[TEST INT.3] Validation de la connexion PDO\n";
        
        $pdo = EntityConfig::getPDOLink();
        
        $this->assertInstanceOf(\PDO::class, $pdo);
        echo "  ✓ getPDOLink() retourne une instance PDO\n";
        
        // Vérifier que la connexion fonctionne
        $stmt = $pdo->query('SELECT 1 as test');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch();
        $this->assertEquals(1, $result['test']);
        echo "  ✓ La connexion PDO est active et fonctionnelle\n";
        
        // Vérifier la base de données
        $stmt = $pdo->query('SELECT DATABASE() as db');
        $result = $stmt->fetch();
        echo "  → Base de données : {$result['db']}\n";
    }
    
    /**
     * TEST INT.4 : Vérification de l'existence des tables
     * -------------------------------------------------
     * S'assure que toutes les tables nécessaires existent en base
     * 
     * @return void
     */
    public function testAllTablesExist()
    {
        echo "\n[TEST INT.4] Vérification de l'existence des tables\n";
        
        $pdo = EntityConfig::getPDOLink();
        
        $tables = ['test_1', 'test_2', 'test_3'];
        
        echo "  → Tables à vérifier : " . implode(', ', $tables) . "\n";
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $result = $stmt->fetch();
            $this->assertNotFalse($result, "Table $table should exist");
            echo "  ✓ Table '$table' existe\n";
        }
        
        echo "  → Toutes les tables Thentity sont présentes\n";
    }
}

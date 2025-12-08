<?php
// run
// vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Entity
namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;
use Entity\EntityConfig;


error_reporting(E_ALL);
ini_set('display_errors', '1');
// vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Entity
require_once __DIR__.'/../src/ThentityLogger.php';
require_once __DIR__.'/../src/ThentityAbstract.php';
require_once __DIR__.'/../src/ThentityCondition.php';
require_once __DIR__.'/../src/ThentityDB.php'; 
require_once __DIR__.'/../src/ThentityException.php'; 
require_once __DIR__.'/../src/ThentityRelation.php'; 
require_once __DIR__.'/../src/ThentityRelationConditionGroup.php';

require_once __DIR__.'/Entity/EntityConfig.php';

require_once __DIR__.'/Thentity/ThentityAbstract/Test1.php';
require_once __DIR__.'/Entity/Test1.php';

require_once __DIR__.'/Thentity/ThentityAbstract/Test2.php';
require_once __DIR__.'/Entity/Test2.php';

require_once __DIR__.'/Thentity/ThentityAbstract/Test3.php';
require_once __DIR__.'/Entity/Test3.php';

require __DIR__ . '/../vendor/autoload.php';


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

/**
 * ============================================================================
 * Test Class for Edge Cases
 * ============================================================================
 * Cette classe teste les cas limites et les situations d'erreur
 * pour s'assurer que le système gère correctement les cas exceptionnels
 * 
 * @package Tests\Entity
 */
class EdgeCasesTest extends TestCase
{
    /**
     * TEST EDGE.1 : Chargement d'une entité inexistante
     * -----------------------------------------------
     * Vérifie que le système lève une exception quand on essaie
     * de charger un ID qui n'existe pas
     * 
     * @return void
     */
    public function testLoadNonExistentEntity()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTS DES CAS LIMITES\n";
        echo str_repeat("=", 80) . "\n";
        echo "\n[TEST EDGE.1] Tentative de chargement d'une entité inexistante\n";
        
        echo "  → Tentative de charger Test1(999999)...\n";
        
        $this->expectException(\Exception::class);
        echo "  → Une exception est attendue\n";
        
        $test1 = new Test1(999999);
        
        // Cette ligne ne devrait jamais être atteinte
        echo "  ✗ ERREUR : Aucune exception levée !\n";
    }
    
    /**
     * TEST EDGE.2 : Gestion des descriptions vides
     * -----------------------------------------
     * Vérifie que les champs NULL sont bien gérés (pas de valeur vide)
     * 
     * @return void
     */
    public function testEmptyDescription()
    {
        echo "\n[TEST EDGE.2] Gestion des champs NULL\n";
        
        $test1 = new Test1(2);
        echo "  → Test1(2) chargé (Test B)\n";
        
        $this->assertNull($test1->description);
        echo "  ✓ description = NULL (pas une chaîne vide)\n";
        
        // Vérifier que c'est bien NULL et pas ""
        $this->assertNotEquals('', $test1->description);
        echo "  ✓ NULL ≠ chaîne vide (distinction correcte)\n";
    }
    
    /**
     * TEST EDGE.3 : Validation des valeurs par défaut
     * ---------------------------------------------
     * Vérifie que les valeurs par défaut sont correctement définies
     * dans les métadonnées des tables
     * 
     * @return void
     */
    public function testDefaultValues()
    {
        echo "\n[TEST EDGE.3] Validation des valeurs par défaut\n";
        
        echo "\n  → Valeurs par défaut de Test1:\n";
        $keys1 = Test1::getTableKeys();
        $this->assertEquals(1, $keys1['active']['default']);
        echo "    ✓ active = 1 (actif par défaut)\n";
        
        echo "\n  → Valeurs par défaut de Test2:\n";
        $keys2 = Test2::getTableKeys();
        $this->assertEquals(0, $keys2['amount']['default']);
        echo "    ✓ amount = 0.00 (montant par défaut)\n";
        $this->assertEquals('pending', $keys2['status']['default']);
        echo "    ✓ status = 'pending' (statut par défaut)\n";
        
        echo "\n  → Les valeurs par défaut sont correctement configurées\n";
    }
    
    /**
     * TEST EDGE.4 : Validation des champs obligatoires
     * ----------------------------------------------
     * Vérifie que les champs marqués comme 'mandatory' sont bien identifiés
     * 
     * @return void
     */
    public function testMandatoryFields()
    {
        echo "\n[TEST EDGE.4] Validation des champs obligatoires (mandatory)\n";
        
        echo "\n  → Champs obligatoires de Test1:\n";
        $keys1 = Test1::getTableKeys();
        $this->assertTrue($keys1['title']['mandatory']);
        echo "    ✓ title est obligatoire\n";
        
        echo "\n  → Champs obligatoires de Test2:\n";
        $keys2 = Test2::getTableKeys();
        $this->assertTrue($keys2['test_1_id']['mandatory']);
        echo "    ✓ test_1_id est obligatoire (FK NOT NULL)\n";
        
        echo "\n  → Champs obligatoires de Test3:\n";
        $keys3 = Test3::getTableKeys();
        $this->assertTrue($keys3['code']['mandatory']);
        echo "    ✓ code est obligatoire\n";
        $this->assertTrue($keys3['label']['mandatory']);
        echo "    ✓ label est obligatoire\n";
        
        echo "\n  → Les contraintes NOT NULL sont correctement détectées\n";
        echo str_repeat("=", 80) . "\n";
    }

}
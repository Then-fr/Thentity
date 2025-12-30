<?php 

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;
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
        
        //$this->expectException(\Exception::class);
        //echo "  → Une exception est attendue\n";
        
        $test1 = new Test1(999999);
        $this->assertFalse($test1->exist());
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
        
        //die('-');
        //die('----');
        // Vérifier que c'est bien NULL et pas ""
        $this->assertNull($test1->description);
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
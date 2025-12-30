<?php

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;

/**
 * ============================================================================
 * Test Class for Crud Cases
 * ============================================================================
 * Cette classe teste les cas limites et les situations d'erreur
 * pour s'assurer que le système gère correctement les cas exceptionnels
 */
class CrudCasesTest extends TestCase
{
    /**
     * TEST CRUD GLOBAL
     */
    public function testCrudCreateReadUpdateDelete()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST CRUD COMPLET\n";
        echo str_repeat("=", 80) . "\n";

        // Création
        echo "\n[CREATE] Création d’une ligne Test1\n";

        $Test1 = new Test1();
        $Test1->title = 'Titre test';
        $Test1->description = 'test';
        $Test1->created_at = '2025-12-12 12:12:12';
        $Test1->active = 1;

        // Avant création : doit être false
        $this->assertFalse($Test1->exist(), "Une entité neuve ne doit pas exister en BDD");

        // Création
        $id_test_1 = $Test1->create();
        $this->assertNotEmpty($id_test_1, "La création doit retourner un ID valide");

        // Après création : doit être true
        $this->assertTrue($Test1->exist(), "L'entité doit exister après create()");

        // Vérification cohérence clé primaire
        $this->assertEquals($id_test_1, $Test1->getPrimaryValue());
        $this->assertEquals($id_test_1, $Test1->{$Test1->getPrimaryKey()});

        // Lecture
        echo "[READ] Vérification de la lecture\n";
        $list = Test1::listBy(['id' => [$id_test_1]]);

        $this->assertNotEmpty($list, "La ligne créée doit apparaître dans listBy()");

        foreach ($list as $l) {
            $this->assertEquals($Test1->id, $l->id, "L'ID doit correspondre");
            $this->assertEquals('test', $l->description, "La description initiale doit correspondre");
        }

        // Update
        echo "[UPDATE] Mise à jour de la ligne\n";

        $Test1->description = 'test edit';
        $this->assertNotEmpty($Test1->update(), "update() ne doit pas renvoyer vide");

        $list = Test1::listBy(['id' => [$id_test_1]]);
        foreach ($list as $l) {
            $this->assertEquals('test edit', $l->description, "La description modifiée doit être persistée");
        }

        // Delete
        echo "[DELETE] Suppression de la ligne\n";

        $this->assertNotEmpty($Test1->delete(), "delete() ne doit pas renvoyer vide");

        $list = Test1::listBy(['id' => [$id_test_1]]);
        $this->assertEmpty($list, "Après suppression, listBy() doit être vide");
    }


    /**
     * TEST EDGE : Création sans champ requis
     * -------------------------------------
     * Vérifie que le système empêche la création lorsque les champs obligatoires
     * ne sont pas fournis.
     */
    public function testMissingRequiredFields()
    {
        echo "\n[TEST EDGE] Champs requis manquants\n";

        // title est mandatory + not nullable
        $t = new Test1();
        $t->description = "desc ok";

        $this->expectException(\Exception::class);
        $t->create(); // doit lever une exception
    }


    /**
     * TEST EDGE : Champs invalides / types incorrects
     */
    public function testInvalidFieldType()
    {
        echo "\n[TEST EDGE] Type invalide pour un champ\n";

        $t = new Test1();
        $t->title = "ok";
        $t->description = "test";

        // active = tinyint => on essaie un type impossible
        $t->active = "INVALID_STRING";

        $this->expectException(\Exception::class);
        $t->create();
    }


    /**
     * TEST EDGE : Update sans modification
     */
    public function testUpdateWithoutChanges()
    {
        echo "\n[TEST EDGE] Update sans aucun changement\n";

        $t = new Test1();
        $t->title = "test";
        $t->description = "unchanged";
        $t->created_at = "2025-12-12 12:00:00";

        $id = $t->create();

        $this->assertTrue($t->exist());

        // update sans rien changer
        $result = $t->update();
        $this->assertNotEmpty($result, "update() doit fonctionner même sans modification");
    }


    /**
     * TEST EDGE : Suppression multiple
     */
    public function testDeleteTwice()
    {
        echo "\n[TEST EDGE] Suppression multiple\n";

        $t = new Test1();
        $t->title = "test";
        $t->description = "desc";
        $t->created_at = "2025-12-12 12:00:00";

        $t->create();

        $this->assertTrue($t->exist());

        $t->delete();

        // Deuxième delete : on s’attend à un comportement contrôlé
        $this->assertFalse($t->exist(), "Après delete(), l'entité ne doit plus exister");

        // Si delete() relance une exception, tu adapteras ici
        $t->delete(); // ne doit rien casser
        $this->assertFalse($t->exist());
    }
}

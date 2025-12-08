#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$namespace = 'Entity';
$srcDir = __DIR__ . '/../src/Entity/';
$testsDir = __DIR__ . '/../tests/Entity/';

// Crée le dossier de test si nécessaire
if (!is_dir($testsDir)) {
    mkdir($testsDir, 0777, true);
}

// Parcourt toutes les classes dans src/Entity
$files = glob($srcDir . '*.php');

foreach ($files as $file) {
    $className = pathinfo($file, PATHINFO_FILENAME);
    $fullClassName = "$namespace\\$className";
    $testClassName = $className . 'Test';
    $testFilePath = $testsDir . $testClassName . '.php';

    if (file_exists($testFilePath)) {
        echo "Test déjà existant pour $className, skip\n";
        continue;
    }

    $testContent = "<?php\n";
    $testContent .= "namespace $namespace\Tests;\n\n";
    $testContent .= "use PHPUnit\\Framework\\TestCase;\n";
    $testContent .= "use $fullClassName;\n\n";
    $testContent .= "class $testClassName extends TestCase\n{\n";
    $testContent .= "    protected function setUp(): void\n    {\n";
    $testContent .= "        // Optionnel: nettoyer la table avant chaque test\n";
    $testContent .= "    }\n\n";

    // Méthode create
    $testContent .= "    public function testCreate(): void\n    {\n";
    $testContent .= "        \$entity = new $className();\n";
    $testContent .= "        // TODO: assigner des valeurs pour les champs obligatoires\n";
    $testContent .= "        \$id = \$entity->save();\n";
    $testContent .= "        \$this->assertNotNull(\$id);\n";
    $testContent .= "    }\n\n";

    // Méthode read
    $testContent .= "    public function testRead(): void\n    {\n";
    $testContent .= "        \$entity = $className::getOneBy([]);\n";
    $testContent .= "        \$this->assertInstanceOf($className::class, \$entity);\n";
    $testContent .= "    }\n\n";

    // Méthode update
    $testContent .= "    public function testUpdate(): void\n    {\n";
    $testContent .= "        \$entity = $className::getOneBy([]);\n";
    $testContent .= "        if (\$entity) {\n";
    $testContent .= "            // TODO: modifier un champ\n";
    $testContent .= "            \$entity->save();\n";
    $testContent .= "            \$this->assertTrue(true);\n";
    $testContent .= "        } else {\n";
    $testContent .= "            \$this->markTestSkipped('Aucune entité trouvée pour update');\n";
    $testContent .= "        }\n";
    $testContent .= "    }\n\n";

    // Méthode delete
    $testContent .= "    public function testDelete(): void\n    {\n";
    $testContent .= "        \$entity = $className::getOneBy([]);\n";
    $testContent .= "        if (\$entity) {\n";
    $testContent .= "            \$id = \$entity->id;\n";
    $testContent .= "            \$entity->delete();\n";
    $testContent .= "            \$this->assertNull($className::getOneBy(['id'=>\$id]));\n";
    $testContent .= "        } else {\n";
    $testContent .= "            \$this->markTestSkipped('Aucune entité trouvée pour delete');\n";
    $testContent .= "        }\n";
    $testContent .= "    }\n";

    $testContent .= "}\n";

    file_put_contents($testFilePath, $testContent);
    echo "Test généré pour $className dans $testFilePath\n";
}

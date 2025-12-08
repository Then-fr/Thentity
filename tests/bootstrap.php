<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Entity
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

use Entity\EntityConfig;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;

$config = [
    'pdo' => EntityConfig::getPDOLink(),
];


$Test1 = new Test1(1);
var_dump($Test1->toArray());
echo '---------------<br>';

$Test2 = new Test2(1);
var_dump($Test2->toArray());
echo '---------------<br>';


$Test3 = new Test3(1);
var_dump($Test3->toArray());
echo '---------------<br>';

<?php

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;
use Entity\Test2;
use Entity\Test3;
use Thentity\ThentityRelation;
use Thentity\ThentityCondition;
use Thentity\ThentityException;

/**
 * ============================================================================
 * Test Class for Relation & Condition Cases - EXTENDED
 * ============================================================================
 *
 * Cette classe teste toutes les combinaisons de conditions et de relations
 * entre Test1, Test2 et Test3 via ThentityRelation et ThentityCondition
 * avec un dataset large et des cas extrêmes
 */


// voici le shchema de chaque entité pour rappel:
/*  Test1
        protected static $tableKeys = [
            'id' => [
                'data_type' => 'int',
                'type' => 'int',
                'nullable' => false,
                'auto_increment' => true,
                'precision' => 10,
                'primary_key' => true,
            ],
            'title' => [
                'data_type' => 'varchar',
                'type' => 'string',
                'nullable' => false,
                'mandatory' => true,
                'max_length' => 255,
            ],
            'description' => [
                'data_type' => 'text',
                'type' => 'string',
                'max_length' => 65535,
            ],
            'created_at' => [
                'data_type' => 'datetime',
                'type' => 'string',
                'nullable' => false,
                'default' => 'current_timestamp()',
            ],
            'active' => [
                'data_type' => 'tinyint',
                'type' => 'int',
                'nullable' => false,
                'default' => 1,
                'precision' => 3,
            ],
        ];
        */

/* Test2
        $tableKeys = [
            'id' => [
                'data_type' => 'int',
                'type' => 'int',
                'nullable' => false,
                'auto_increment' => true,
                'precision' => 10,
                'primary_key' => true,
            ],
            'test_1_id' => [
                'data_type' => 'int',
                'type' => 'int',
                'nullable' => false,
                'mandatory' => true,
                'precision' => 10,
            ],
            'amount' => [
                'data_type' => 'decimal',
                'type' => 'float',
                'nullable' => false,
                'default' => 0,
                'precision' => 10,
                'scale' => 2,
            ],
            'status' => [
                'data_type' => 'enum',
                'type' => 'enum',
                'values' => ['pending', 'ok', 'failed'],
                'nullable' => false,
                'default' => 'pending',
                'max_length' => 7,
            ],
            'meta' => [
                'data_type' => 'longtext',
                'type' => 'string',
                'max_length' => 4294967295,
            ],
            'updated_at' => [
                'data_type' => 'timestamp',
                'type' => 'string',
            ],
        ];
*/

/* Test3

  'id' => [
                'data_type' => 'int',
                'type' => 'int',
                'nullable' => false,
                'auto_increment' => true,
                'precision' => 10,
                'primary_key' => true,
            ],
            'test_2_id' => [
                'data_type' => 'int',
                'type' => 'int',
                'precision' => 10,
            ],
            'code' => [
                'data_type' => 'varchar',
                'type' => 'string',
                'nullable' => false,
                'mandatory' => true,
                'max_length' => 32,
                'unique' => true,
            ],
            'label' => [
                'data_type' => 'varchar',
                'type' => 'string',
                'nullable' => false,
                'mandatory' => true,
                'max_length' => 255,
            ],
            'enabled' => [
                'data_type' => 'tinyint',
                'type' => 'int',
                'nullable' => false,
                'default' => 1,
                'precision' => 3,
            ],
            'created_at' => [
                'data_type' => 'datetime',
                'type' => 'string',
                'nullable' => false,
                'default' => 'current_timestamp()',
            ],
        ];
        */
class RelationTest extends TestCase
{
    protected $prefix;
    protected $dataset = [];

    protected function setUp(): void
    {
        $this->prefix = 'rt_ext_' . uniqid() . '_';

        // Cleanup existant
        foreach (Test3::listBy(['code LIKE' => [$this->prefix . '%']]) as $t) $t->delete();
        foreach (Test2::listBy(['meta LIKE' => ['%'.$this->prefix.'%"']]) as $t) $t->delete();
        foreach (Test1::listBy(['title LIKE' => [$this->prefix . '%']]) as $t) $t->delete();

        // Création dataset large et varié
        for ($i = 0; $i < 10; $i++) {
            $t1 = new Test1();
            $t1->title = $this->prefix . "T1_$i";
            $t1->description = ($i % 2 === 0) ? "desc even $i" : "desc odd $i";
            $t1->created_at = date('Y-m-d H:i:s', strtotime("+$i day"));
            $t1->active = ($i % 3 === 0) ? 0 : 1;
            $t1->create();
            $this->dataset['Test1'][] = $t1;

            $t2 = new Test2();
            $t2->test_1_id = $t1->id;
            $t2->amount = rand(5, 100);
            $t2->status = ['pending', 'ok', 'failed'][$i % 3];
            // <-- meta au format JSON
            $t2->meta = json_encode([
                'note' => $this->prefix . "T2_$i",
                'flag' => ($i % 2 === 0),
                'values' => [$i, $i * 2, $i * 3]
            ]);
            $t2->updated_at = date('Y-m-d H:i:s');
            $t2->create();
            $this->dataset['Test2'][] = $t2;

            $t3 = new Test3();
            $t3->test_2_id = $t2->id;
            $t3->code = $this->prefix . "CODE_$i";
            $t3->label = "Label $i";
            $t3->enabled = ($i % 2);
            $t3->created_at = date('Y-m-d H:i:s');
            $t3->create();
            $this->dataset['Test3'][] = $t3;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->dataset as $rows) {
            foreach ($rows as $e) if ($e->exist()) $e->delete();
        }
        $this->dataset = [];
    }

    /**
     * ============================================================================
     * Test Relations avec conditions étendues, opérateurs extrêmes et combinaisons
     * ============================================================================
     */
    public function testRelationsExtended()
    {
        $t1 = new Test1(); //$this->dataset['Test1'][0];
        $relation = new ThentityRelation($t1, 'Test1');        
        $relation->addJoin(new Test2(), 'Test2', 'Test2.test_1_id=Test1.id');
        //$condition = new ThentityCondition($relation);

        // --- Opérateur simple
        $conditionsGroup = $relation->addCondition('Test2.amount>=:amount', ['amount' => 50]);
        $res1 = $relation->find();
        $this->assertNotEmpty($res1);


        // --- BETWEEN
        $conditionsGroup->wrapWithGroupCondition('OR')
            ->addCondition('Test2.amount BETWEEN :min AND :max', ['min' => 20, 'max' => 70]);
        $res2 = $relation->find();
        $this->assertNotEmpty($res2);

        

        $t1 = new Test1(); //$this->dataset['Test1'][0];
        $relation = new ThentityRelation($t1, 'Test1');        
        $relation->addJoin(new Test2(), 'Test2', 'Test2.test_1_id=Test1.id');
        $relation->addInnerJoin(new Test3(), 'Test3', 'Test3.test_2_id=Test2.id');

        // --- IN
        $codes = array_map(fn($t3) => $t3->code, $this->dataset['Test3']);
        // Génération dynamique des placeholders pour codes
        $placeholderscode = [];
        $paramscode = [];
        foreach ($codes as $i => $id) {
            $ph = ":code_$i";
            $placeholderscode[] = $ph;
            $paramscode["code_$i"] = $id;
        }


        $relation
        ->getGroupCondition()->wrapWithGroupCondition('AND')
            ->addCondition('Test3.code IN('. implode(',', $placeholderscode) .')', $paramscode);
        $res3 = $relation->find();
    

        $this->assertCount(count($codes), $res3);
        

        $t1 = new Test1(); //$this->dataset['Test1'][0];
        $relation = new ThentityRelation($t1, 'Test1');        
        $relation->addJoin(new Test2(), 'Test2', 'Test2.test_1_id=Test1.id');
        $relation->addJoin(new Test3(), 'Test3', 'Test3.test_2_id=Test2.id');
        // --- LIKE / NOT LIKE
        $relation->getGroupCondition()->wrapWithGroupCondition('AND')
            ->addCondition('Test1.title LIKE :like', ['like' => '%'.$this->prefix . '%'])
            ->addCondition('Test2.meta NOT LIKE :notlike', ['notlike' => '%odd%']);
        $res4 = $relation->find();
        $this->assertNotEmpty($res4);

        // --- IS NULL / IS NOT NULL
        $relation->getGroupCondition()->wrapWithGroupCondition('OR')
            ->addCondition('Test3.label IS NOT NULL')
            ->addCondition('Test3.label IS NULL');
        $res5 = $relation->find();
        $this->assertNotEmpty($res5);

        // --- Combinaison AND / OR multiples
        $relation->getGroupCondition()->wrapWithGroupCondition('OR')
            ->addCondition('Test2.status=:status', ['status' => 'pending'])
            ->addCondition('Test2.amount<:amount', ['amount' => 30])
            ->addGroupCondition('AND')->addCondition('Test3.enabled=:enabled', ['enabled' => 1]);
        $res6 = $relation->find();
        $this->assertNotEmpty($res6);

        // --- Test jointures
        $relation->addJoin(new Test3(), 'T3', 'T3.test_2_id=Test2.id');
        $relation->addLeftJoin(new Test3(), 'T3L', 'T3L.test_2_id=Test2.id');
        $relation->addInnerJoin(new Test3(), 'T3I', 'T3I.test_2_id=Test2.id');
        $res7 = $relation->find();
        $this->assertNotEmpty($res7);

        // --- Order / Limit / Offset
        $relation->addOrders([ 'Test2.meta' => 'DESC', 'Test2.amount' => 'DESC'])->addLimit(3)->addOffset(2);
        $res8 = $relation->find();
        $this->assertCount(3, $res8, 'Limit + Offset devrait renvoyer exactement 3 résultats');
    }
}

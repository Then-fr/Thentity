<?php

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use Entity\Test1;

/**
 * Tests des opérateurs de condition en isolation (évite le "bruit" dans la table).
 *
 * Strategy:
 * - create a dataset with a unique title prefix (uniqid)
 * - query using title LIKE 'prefix%' so we only count our rows
 * - test all operators and combinations requested
 */
class ConditionTest extends TestCase
{
    /** @var string unique prefix used in titles to isolate dataset */
    protected $prefix;

    /** @var array dataset rows metadata (id, created_at, title, description, active) */
    protected $dataset = [];

    /** Number of rows to create */
    protected $rows = 10;

    protected function setUp(): void
    {
        // unique prefix to isolate data
        $this->prefix = 'test_' . uniqid() . '_';

        // base date for predictable created_at values
        $baseTs = strtotime('2025-01-01 00:00:00');

        // create dataset
        for ($i = 0; $i < $this->rows; $i++) {
            $t = new Test1();
            $t->title = $this->prefix . $i; // unique per dataset and row
            // alternate description null/non-null
            $t->description = ($i % 2 === 0) ? null : "desc-$i";
            $t->active = ($i % 3 === 0) ? 0 : 1;
            $t->created_at = date('Y-m-d H:i:s', $baseTs + $i * 3600); // hourly steps

            $id = $t->create();

            // store metadata
            $this->dataset[$i] = [
                'id' => $id,
                'created_at' => $t->created_at,
                'title' => $t->title,
                'description' => $t->description,
                'active' => $t->active,
                'index' => $i,
            ];
        }
    }

    protected function tearDown(): void
    {
        // cleanup created rows
        foreach ($this->dataset as $r) {
            $e = new Test1($r['id']);
            if ($e->exist()) {
                $e->delete();
            }
        }
    }

    protected function prefixLike(): string
    {
        return $this->prefix . '%';
    }

    public function testEqualsAndInequalityOperators()
    {
        // choose row 3 as reference
        $ref = $this->dataset[3];

        // EQUALS
        $res = Test1::listBy([
            'title' => [$ref['title']], // title exactly
        ]);
        $this->assertCount(1, $res, 'exact match by title');

        // using created_at exact match
        $res = Test1::listBy([
            'created_at' => [$ref['created_at']],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertCount(1, $res, 'created_at exact match restricted by prefix');

        // NOT EQUALS != or <>
        $resAll = Test1::listBy(['title LIKE' => [$this->prefixLike()]]);
        $total = count($resAll);
        $resNot = Test1::listBy([
            'title !=' => [$ref['title']],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertEquals($total - 1, count($resNot), 'title != should exclude the single row');

        $resNot2 = Test1::listBy([
            'title <>' => [$ref['title']],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertEquals($total - 1, count($resNot2), 'title <> should behave as !=');
    }

    public function testComparisonOperators()
    {
        // use created_at ordering to test > < >= <=
        $date3 = $this->dataset[3]['created_at'];
        $date6 = $this->dataset[6]['created_at'];

        // created_at >
        $res = Test1::listBy([
            'created_at >' => [$date3],
            'title LIKE' => [$this->prefixLike()],
        ]);
        // rows with index 4..9 => 6 rows
        $this->assertCount(6, $res, 'created_at > date3');

        // created_at >=
        $res = Test1::listBy([
            'created_at >=' => [$date3],
            'title LIKE' => [$this->prefixLike()],
        ]);
        // rows 3..9 => 7 rows
        $this->assertCount(7, $res, 'created_at >= date3');

        // created_at <
        $res = Test1::listBy([
            'created_at <' => [$date6],
            'title LIKE' => [$this->prefixLike()],
        ]);
        // rows 0..5 => 6 rows
        $this->assertCount(6, $res, 'created_at < date6');

        // created_at <=
        $res = Test1::listBy([
            'created_at <=' => [$date6],
            'title LIKE' => [$this->prefixLike()],
        ]);
        // rows 0..6 => 7 rows
        $this->assertCount(7, $res, 'created_at <= date6');
    }

    public function testInAndBetweenOperators()
    {
        // pick some ids
        $ids = [
            $this->dataset[0]['id'],
            $this->dataset[5]['id'],
            $this->dataset[9]['id'],
        ];

        $res = Test1::listBy([
            'id IN' => $ids,
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertCount(3, $res, 'id IN must return the 3 rows');

        // BETWEEN created_at from index 2 to 6 -> should include 2,3,4,5,6 => 5 rows
        $d1 = $this->dataset[2]['created_at'];
        $d2 = $this->dataset[6]['created_at'];
        $res = Test1::listBy([
            'created_at BETWEEN' => [$d1, $d2],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertCount(5, $res, 'BETWEEN should include endpoints');
    }

    public function testLikeAndNotLikeAndIsNullIsNotNull()
    {
        // LIKE using prefix
        $res = Test1::listBy([
            'title LIKE' => [$this->prefixLike()],
        ]);
        $this->assertCount($this->rows, $res, 'title LIKE prefix must return all rows in dataset');

        // NOT LIKE: exclude those starting with prefixAND "Item 1" impossible here,
        // test NOT LIKE by excluding one exact pattern
        $res = Test1::listBy([
            'title NOT LIKE' => [$this->prefix . '0'],
            'title LIKE' => [$this->prefixLike()],
        ]);
        // should be rows - possibly excluding exact title ending with '0'
        $expected = $this->rows - 1;
        $this->assertCount($expected, $res, 'title NOT LIKE single exact pattern');

        // IS NULL (description null) -> half the rows (even indices)
        $res = Test1::listBy([
            'description IS NULL' => [],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $expectedNull = 0;
        foreach ($this->dataset as $r) {
            if ($r['description'] === null) $expectedNull++;
        }
        $this->assertCount($expectedNull, $res, 'description IS NULL count');

        // IS NOT NULL
        $res = Test1::listBy([
            'description IS NOT NULL' => [],
            'title LIKE' => [$this->prefixLike()],
        ]);
        $expectedNotNull = $this->rows - $expectedNull;
        $this->assertCount($expectedNotNull, $res, 'description IS NOT NULL count');
    }

    public function testGetOneByAndGetOneByOrNewBehavior()
    {
        // getOneBy should return a matching entity or null
        $d = $this->dataset[4];
        $one = Test1::getOneBy([
            'title' => [$d['title']]
        ]);
        $this->assertNotNull($one);
        $this->assertTrue($one->exist());

        // non-existing
        $none = Test1::getOneBy([
            'title' => ['does_not_exist_' . uniqid()],
        ]);
        $this->assertNull($none);

        // getOneByOrNew -> returns new object (exist() false) if no match
        $new = Test1::getOneByOrNew([
            'title' => ['does_not_exist_' . uniqid()],
        ]);
        $this->assertFalse($new->exist());

        // getOneByOrNew -> returns existing if match
        $existing = Test1::getOneByOrNew([
            'id =' => [$this->dataset[2]['id']],
        ]);
        $this->assertTrue($existing->exist());
    }
}

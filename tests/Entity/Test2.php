<?php

namespace Entity;

/**
 * Class Test2
 * @property int $id
 * @property int $test_1_id
 * @property float $amount
 * @property string $status
 * @property string $meta
 * @property string $updated_at
*/
Class Test2  extends \Thentity\Test2 { 
    public static function getPDOLink(): \PDO
    {
        return EntityConfig::getPDOLink();
    }

}

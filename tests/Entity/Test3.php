<?php

namespace Entity;

/**
 * Class Test3
 * @property int $id
 * @property int $test_2_id
 * @property string $code
 * @property string $label
 * @property int $enabled
 * @property string $created_at
*/
Class Test3  extends \Thentity\Test3 { 
    public static function getPDOLink(): \PDO
    {
        return EntityConfig::getPDOLink();
    }

}

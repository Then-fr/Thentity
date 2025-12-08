<?php

namespace Entity;

/**
 * Class Test1
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $created_at
 * @property int $active
*/
Class Test1  extends \Thentity\Test1 { 
    public static function getPDOLink(): \PDO
    {
        return EntityConfig::getPDOLink();
    }

}

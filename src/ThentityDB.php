<?php

namespace Thentity;

use PDO;

class ThentityDB { 

    static $PDO;

    public static function getPDOLink() {
        return static::$PDO;
    }

}
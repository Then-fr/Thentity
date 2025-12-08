<?php

namespace Thentity;

use PDO;

abstract class ThentityAbstract
{
    private bool $exist = false;
    protected static $primaryKey;
    private ?string $primaryKeyValue = null;
    protected static string $tableName;
    private array $attributes = [];
    private array $dirtyAttributes = []; // Suivi des propriétés modifiées
    private ?bool $cache = null;
    private $isCacheRequested = false;
    private static array $_attributes = []; // cache
    protected static $debug = false;
    protected static PDO $PDO;

     /** @var string|ExceptionHandlerInterface */
    //protected static string $exceptionHandler = \Thentity\ThentityException::class;


    public static function setDebug(bool $debug)
    {
        self::$debug = $debug;
    }

    protected static function escapeSqlColumnName($column_name)
    {
        $column_name = explode('.', $column_name);
        $escaped_column_name = [];
        foreach ($column_name as $k => $v) {
            $escaped_column_name[] = '`' . trim($v, '`') . '`';
        }
        return implode('.', $escaped_column_name);
    }

    protected static function getDb()
    {
        return static::getPDOLink();
    }

    public function __construct(?string $id_primary = null, bool $cache = true)
    {
        if ($id_primary) {
            $this->isCacheRequested = $cache;
            $this->primaryKeyValue = $id_primary;
        }
    }

    // renvoie true si $name correspond a une colonne de la table de l'entité
    public function isField($name)
    {
        return isset(static::getTableKeys()[$name]);
    }

    // charge l'objet si nécessaire 
    protected function lazyLoad()
    {
        $this->setObj($this->primaryKeyValue);
    }

    protected function activeCache(bool $cache = true)
    {
        // activation du cache uniquement si clé primaire défini
        if (!$this->cache && $this->exist() && $cache) {
            if (empty(static::$_attributes[static::class])) {
                static::$_attributes[static::class] = [];
            }
            $attributes = $this->attributes;
            static::$_attributes[static::class][$this->primaryKeyValue] = $attributes;
            $this->attributes = &static::$_attributes[static::class][$this->primaryKeyValue];
            $this->cache = true;
        }
    }

    protected function &_getAttributes()
    {
        return $this->attributes;
    }

    protected function &_getdirtyAttributes()
    {
        return $this->dirtyAttributes;
    }

    private function _clearDirtyAttributes(): void
    {
        $this->dirtyAttributes = [];
    }

    public function clearDirtyAttributes(): void
    {
        $this->_clearDirtyAttributes();
    }

    public function getPrimaryValue()
    {
        return $this->primaryKeyValue;
    }

    /**
     * existe dans la base de donnée
     */
    public function exist()
    {
        $this->setObj($this->primaryKeyValue);
        return $this->exist;
    }

    protected function setObj($id_primary)
    {
        if ($this->exist) {
            return $this;
        }
        if ($this->primaryKeyValue && ((string)$this->primaryKeyValue !== (string)$id_primary)) {
            throw new ThentityException('Thentity déjà initialisée');
        }
        if ($this->cache && !empty($this->_getAttributes())) {
            return $this;
        }
        if (empty($id_primary)) {
            return null;
        }
        $this->primaryKeyValue = $id_primary;
        $this->exist = false;

        $stmt = static::getDb()->prepare("SELECT * FROM " . static::getTableName() . " WHERE " . static::getPrimaryKey() . " = :id");
        if (!$stmt) {
            throw new ThentityException("Échec de la préparation de la requête : " . static::getPDOLink()->errorInfo()[2]);
        }
        $stmt->bindParam(':id', $id_primary, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $this->primaryKeyValue = $data[static::getPrimaryKey()];
            $this->exist = true;
            $this->activeCache($this->isCacheRequested);
            foreach ($data as $k => $v) {
                if ($k !== static::getPrimaryKey()) {
                    //$this->$k = $v;
                    // assignement directe sans passer par __set()
                    $this->_getAttributes()[$k] = $v;
                }
            }
        }
    }

    public function __isset(string $name)
    {
        // lazy loading
        if ($this->isField($name)) {
            $this->lazyLoad();
        }

        if ($name === static::getPrimaryKey()) return ($this->primaryKeyValue) ? true : false;

        $dirtyAttributes = $this->_getdirtyAttributes();
        if (array_key_exists($name, $dirtyAttributes)) {
            return isset($dirtyAttributes[$name]) ? true : false;
        }

        return (isset($this->_getAttributes()[$name])) ? true : false;
    }

    public function __get(string $name)
    {
        // lazy loading
        if ($this->isField($name)) {
            $this->lazyLoad();
        }

        if ($name === static::getPrimaryKey()) return $this->primaryKeyValue;
        $dirtyAttributes = $this->_getdirtyAttributes();
        if (array_key_exists($name, $dirtyAttributes)) {
            return $dirtyAttributes[$name];
        }

        return $this->_getAttributes()[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {        
        // lazy loading
        if ($this->isField($name)) {  
            $this->lazyLoad();
        }
         
        if ($name === static::getPrimaryKey()) {
            // if ( $this->primaryKeyValue === $value ) return;
            if (!empty($this->primaryKeyValue)) {
                throw new ThentityException('La valeur de la clé primaire ne peut pas être modifiée');
            } else {
                $this->primaryKeyValue = $value;
            }
        }      

        // Validation des champs enum
        if ($this->isField($name) && $this->isEnumField($name)) {
            if (!$this->validateEnumValue($name, $value)) {
                $allowedValues = $this->getEnumValues($name);
                $allowedValuesStr = implode(', ', $allowedValues);
                throw new ThentityException("Valeur invalide pour le champ enum '$name'. Valeurs autorisées : $allowedValuesStr");
            }
        }

        if ( !array_key_exists($name, $this->_getAttributes()) || $this->_getAttributes()[$name] !== $value) {
            $this->_getAttributes()[$name] = $value;
            $this->_getdirtyAttributes()[$name] = $value;
        }
    }

    public function getDirtyAttributes(): array
    {
        return $this->_getdirtyAttributes();
    }

    public function toArray(): array
    {
        $this->lazyLoad();
        $array = array_merge($this->_getdirtyAttributes(), $this->_getAttributes());
        if ($this->primaryKeyValue) {
            $array[static::getPrimaryKey()] = $this->primaryKeyValue;
        }
        return $array;
    }

    public function fromArray(array $data): self
    {
        return $this->hydrateFromArray($data);
    }

    /*
    * Fill l'array data, en restant cohérant avec les données de la DB
    *
    */
    public function hydrateFromArray(array $data): self
    {
        $primaryKey = static::getPrimaryKey();
        $hasPrimaryInData = isset($data[$primaryKey]);

        // Vérifie si l'objet existe réellement en DB
        if ($this->exist()) {
            // Si le tableau contient une clé primaire différente
            if ($hasPrimaryInData && $data[$primaryKey] !== $this->primaryKeyValue) {
                throw new ThentityException("Impossible de modifier la clé primaire existante via fillFromArray");
            }
        }

        // Cas où l'objet n'a pas de primaryKeyValue mais qu'on en a une dans $data
        if ($hasPrimaryInData) {
            $this->setObj($data[$primaryKey]);
        }

        // Conserver les anciennes données et appliquer les nouvelles valeurs par dessus
        $old_data = $this->toArray();
        $mergedData = array_merge($old_data, $data);
        foreach ($mergedData as $key => $value) {
            if ($key === $primaryKey) {
                continue; // On ne touche pas à la primaryKey
            }
            if ($this->isField($key)) {
                $this->__set($key, $value);
            }
        }
        return $this;
    }

    public function update(): bool
    {
        if (empty($this->primaryKeyValue)) {
            throw new ThentityException("Impossible de mettre à jour : clé primaire non définie.");
        }

        $tableKeys =  static::getTableKeys();
        $dirtyAttributes = $this->getDirtyAttributes();
        $fields = [];
        $params = [];
        foreach ($tableKeys as $key => $options) {
            if (!array_key_exists($key, $dirtyAttributes)) {
                continue;
            }

            // Validation des champs enum avant mise à jour
            if (isset($options['type']) && $options['type'] === 'enum') {
                if (!$this->validateEnumValue($key, $dirtyAttributes[$key])) {
                    $allowedValues = $options['values'] ?? [];
                    $allowedValuesStr = implode(', ', $allowedValues);
                    throw new ThentityException("Valeur invalide pour le champ enum '$key'. Valeurs autorisées : $allowedValuesStr");
                }
            }

            $fields[] = static::escapeSqlColumnName($key) . " = :$key";
            $params[":$key"] = $dirtyAttributes[$key];
        }

        if (empty($fields)) {
            return true;
        }

        $query = "UPDATE " . static::getTableName() . " SET " . implode(", ", $fields) . " WHERE " . static::escapeSqlColumnName(static::getPrimaryKey()) . " = :primaryKey";
        $params[":primaryKey"] = $this->primaryKeyValue;

        $stmt = static::getDb()->prepare($query);
        if (!$stmt) {
            throw new ThentityException("Échec de la préparation de la requête : " . static::getPDOLink()->errorInfo()[2]);
        }

        $result = $stmt->execute($params);
        if ($result) {
            $this->clearDirtyAttributes();
        } else {
            $errorInfo = $stmt->errorInfo();
            echo "Erreur SQL : " . $errorInfo[2];            
            throw new ThentityException("Erreur d'exécution : " . print_r($stmt->errorInfo(), true));
            // echo "Code d'erreur : " . $stmt->errno;
        }

        return $result;
    }

    public function delete(): bool
    {
        if (empty($this->primaryKeyValue)) {
            throw new ThentityException("Impossible de supprimer : clé primaire non définie.");
        }

        $query = "DELETE FROM " . static::getTableName() . " WHERE " . static::escapeSqlColumnName(static::getPrimaryKey()) . " = :primaryKey";
        $params[":primaryKey"] = $this->primaryKeyValue;

        $stmt = static::getDb()->prepare($query);
        if (!$stmt) {
            throw new ThentityException("Échec de la préparation de la requête : " . static::getPDOLink()->errorInfo()[2]);
        }

        $result = $stmt->execute($params);
        if ($result) {
            $this->clearDirtyAttributes();
            $this->exist = false;
        } else {
            $errorInfo = $stmt->errorInfo();
            // echo "Erreur SQL : " . $errorInfo[2];
            throw new ThentityException("Erreur d'exécution : " . print_r($stmt->errorInfo(), true));
        }
        return $result;
    }

    public function create(): ?string
    {
        $attributes = $this->toArray();
        $tableKeys = static::getTableKeys();
        $fields = [];
        $placeholders = [];
        $params = [];

        foreach ($tableKeys as $key => $options) {
            if (!array_key_exists($key, $attributes)) {
                if (array_key_exists('default', $options)) {
                    // @todo supprimmer le défaut, il est déjà côté mysql
                    // permettre une surcharge de la valeur pour php 
                    // fix pour le cas current_timestamp
                    if ($options['default'] !== 'current_timestamp()') {
                        $attributes[$key] = $options['default'];
                    }
                } else if (array_key_exists('mandatory', $options) && $options['mandatory']) {
                    throw new ThentityException("Le champ '$key' est requis et n'a pas de valeur définie.");
                } else {
                    continue;
                }
            }

            // Validation des champs enum avant insertion
            if (isset($options['type']) && $options['type'] === 'enum') {
                if (!$this->validateEnumValue($key, $attributes[$key])) {
                    $allowedValues = $options['values'] ?? [];
                    $allowedValuesStr = implode(', ', $allowedValues);
                    throw new ThentityException("Valeur invalide pour le champ enum '$key'. Valeurs autorisées : $allowedValuesStr");
                }
            }

            $fields[] = static::escapeSqlColumnName($key);
            $placeholders[] = ":$key";
            $params[":$key"] = $attributes[$key];
        }

        $query = "INSERT INTO " . static::getTableName() . " (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = static::getDb()->prepare($query);
        if (!$stmt) {
            throw new ThentityException("Échec de la préparation de la requête : " . static::getPDOLink()->errorInfo()[2]);
        }

        $result = $stmt->execute($params);

        if ($result) {
            // si la clé était fournie à la création
            // ou si c'est un auto increment
            if (empty($this->primaryKeyValue)) {
                $this->primaryKeyValue = static::getDb()->lastInsertId();
            }
            $this->exist = true;
            return $this->primaryKeyValue;
        } else {           
            throw new ThentityException("Erreur d'exécution : " . print_r($stmt->errorInfo(), true));
        }

        return null;
    }

    public function save(): ?string
    {
        if ($this->primaryKeyValue) {
            return $this->update() ? $this->primaryKeyValue : null;
        } else {
            return $this->create();
        }
    }

    abstract static public function getPDOLink(): PDO;
    public function setPDOLink(PDO $PDO) {
        static::$PDO = $PDO;
    }

    abstract static public function getPrimaryKey(): string;

    abstract static public function getTableName(): string;

    abstract static public function getTableKeys(): array;
    
    // si c'est une copie primaryKeyValue doit être indéfini
    public function copy()
    {
        $New = new static();
        $val = $this->toArray();
        foreach ($val as $k => $v) {
            if ($k !== $this->getPrimaryKey()) {
                // @todo revoir l'assignement
                $New->{$k} = $v;
            }
        }
        return $New;
    }

    // support ENUM
    /**
     * Valide une valeur enum selon la configuration du champ
     */
    protected function validateEnumValue(string $fieldName, $value): bool
    {
        $tableKeys = static::getTableKeys();

        if (!isset($tableKeys[$fieldName])) {
            return false;
        }

        $fieldConfig = $tableKeys[$fieldName];

        // Vérifier si c'est un champ enum
        if (!isset($fieldConfig['type']) || $fieldConfig['type'] !== 'enum') {
            return true; // Pas un enum, validation OK
        }

        // Vérifier si les valeurs enum sont définies
        if (!isset($fieldConfig['values']) || !is_array($fieldConfig['values'])) {
            throw new ThentityException("Configuration enum invalide pour le champ '$fieldName' : valeurs non définies");
        }

        // Valider la valeur
        return in_array($value, $fieldConfig['values'], true);
    }

    /**
     * Obtient les valeurs autorisées pour un champ enum
     */
    public function getEnumValues(string $fieldName): ?array
    {
        $tableKeys = static::getTableKeys();

        if (!isset($tableKeys[$fieldName])) {
            return null;
        }

        $fieldConfig = $tableKeys[$fieldName];

        if (!isset($fieldConfig['type']) || $fieldConfig['type'] !== 'enum') {
            return null;
        }

        return $fieldConfig['values'] ?? null;
    }

    /**
     * Vérifie si un champ est de type enum
     */
    public function isEnumField(string $fieldName): bool
    {
        $tableKeys = static::getTableKeys();

        if (!isset($tableKeys[$fieldName])) {
            return false;
        }

        $fieldConfig = $tableKeys[$fieldName];

        return isset($fieldConfig['type']) && $fieldConfig['type'] === 'enum';
    }


    // support condition | list
    public static function listBy(array $options, array $orders = [], int $limit = 0, int $offset = 0)
    {
        $Thentity = new static();
        $Relation = new ThentityRelation($Thentity, 'list');
        $Condition = new ThentityCondition($Relation);
        $Condition->addConditions($options)
                  ->addOrders($orders)
                  ->addLimit($limit)
                  ->addOffset($offset);  

        return $Condition->find();
    }

    public static function listToArray($r_list)
    {
        $list = [];
        if (!empty($r_list)) {
            foreach ($r_list as $v) {
                if ($v instanceof static) {
                    $list[] = $v->toArray();
                }
            }
        }
        return $list;
    }

    public static function getOneBy(array $options)
    {
        $list = static::listBy($options);
        return $list[0] ?? null;
    }

    public static function getOneByOrNew(array $options)
    {
        return static::getOneBy($options) ?? new static();
    }
}
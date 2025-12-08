<?php

namespace Thentity;

use Exception;
use PDO;

class ThentityRelation
{
    protected array $entities = [];
    protected array $joins = [];
    protected array $conditions = [];
    protected array $conditions_or = [];
    protected array $selects = [];
    protected array $orders = [];
    protected array $params = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    public $query = null;
    protected ThentityAbstract $fromThentity;
    protected string $fromAlias;
    protected ThentityRelationConditionGroup $rootGroup;
    const SEP = "-#-";

    protected $queryHistory = [];

    protected static function escapeSqlColumnName($column_name)
    {
        $column_name = explode('.', $column_name);
        $escaped_column_name = [];
        foreach ($column_name as $k => $v) {
            $escaped_column_name[] = '`' . trim($v, '`') . '`';
        }
        return implode('.', $escaped_column_name);
    }

    public function getAlias()
    {
        return $this->fromAlias;
    }

    public function getThentity()
    {
        return $this->fromThentity;
    }

    // renvoie true si $fieldName est un nom de colonne existant dans une des table de la relation
    public function isField($fieldName)
    {
        $fieldName = explode('.', $fieldName);
        $alias = null;
        if (count($fieldName) > 3) {
            throw new Exception('isField wrong field name ' . $fieldName);
        } else if (count($fieldName) === 2) {
            $alias = $fieldName[0];
            $field = $fieldName[1];
        } else {
            $field = $fieldName[0];
        }

        if (!empty($alias)) {
            if (isset($this->entities[$alias])) {
                return $this->entities[$alias]->isField($field);
            }
        } else {
            return $this->fromThentity->isField($field);
        }
        return false;
    }

    public function __construct(ThentityAbstract $Thentity, string $alias, string $type_relation = 'AND')
    {
        $this->fromThentity = $Thentity;
        $this->fromAlias = $alias;
        $this->entities[$alias] = $Thentity;
        $this->selects[$alias] = $Thentity::getPrimaryKey();
        $this->rootGroup = new ThentityRelationConditionGroup($type_relation);
    }

    public function addJoin(ThentityAbstract $Thentity, string $alias, string $onClause): self
    {
        $this->selects[$alias] = $Thentity::getPrimaryKey();
        $this->entities[$alias] = $Thentity;
        $this->joins[] = [
            'type'   => 'INNER',
            'Thentity' => $Thentity,
            'alias' => $alias,
            'on' => $onClause,
        ];
        return $this;
    }

    public function addSelect(string $column, ?string $alias = null): self
    {
        $this->selects[$alias ?? $column] = $column;
        return $this;
    }

    public function removeSelect(string $alias): self
    {
        unset($this->selects[$alias]);
        return $this;
    }


    public function addCondition(string $sql, array $params = []): self
    {
        $this->rootGroup->addCondition($sql, $params);
        return $this;
    }

    public function addGroupCondition(string $type = 'AND'): ThentityRelationConditionGroup
    {
        return $this->rootGroup->addGroupCondition($type);
    }

    // encapsule les condition existante dans un nouveau groupe
    public function wrapWithGroupCondtion($type = 'AND')
    {
        $parentGroup = $this->getGroupCondition()->wrapWithGroupCondtion($type);
        $this->setGroupCondition($parentGroup);
        return $parentGroup;
    }


    public function getGroupCondition()
    {
        return $this->rootGroup;
    }

    public function setGroupCondition(ThentityRelationConditionGroup $group): ThentityRelationConditionGroup
    {
        return $this->rootGroup = $group;
    }

    /**
     * $order = [ 'key' => 'DESC' ]
     */
    public function addOrder($fieldName, ?string $direction = 'ASC', ?string $alias = null)
    {

        // backward
        if (is_array($fieldName)) {
            $order = $fieldName;
            $fieldName = key($order);
            $direction = reset($order);
            $alias = null;
            //error_log('addOrder use of array is deprecated');
        }

        if (is_string($fieldName)) {
            if (!in_array(strtoupper($direction), ['DESC', 'ASC', 'RAND()'])) {
                throw new ThentityException('Order bad args direction');
            }
            $f = explode('.', $fieldName);
            $field = $f[0];
            if (!empty($f[1])) {
                $alias = $f[0];
                $field = $f[1];
            }
            $this->orders[] = [
                'alias' => $alias,
                'field' => $field,
                'direction' => $direction
            ];
            return $this;
        }
    }

    public function addLimit(int $limit, int $offset): self
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    private function getAliasName($col, $alias)
    {
        if (isset($this->entities[$alias])) {
            return $alias . '.' . $this->entities[$alias]::getPrimaryKey() . " AS " . static::escapeSqlColumnName($alias . static::SEP . $col);
        } else if ($col !== $alias) {
            return $col . ' AS ' . $alias;
        } else {
            return $col;
        }
    }

    protected function getOrderFieldName(array $order)
    {
        return !empty($order['alias'])
            ? $order['alias'] . '.' . $order['field']
            : $order['field'];
    }

    public function find(array $options = []): array
    {
        $query = "SELECT ";
        $query .= implode(", ", array_map(fn($col, $alias) => $this->getAliasName($col, $alias), $this->selects, array_keys($this->selects)));
        $query .= " FROM " . $this->buildFromClause();

        $conditionSql = $this->rootGroup->toSql();

        if (!empty($conditionSql)) {
            $query .= " WHERE " . $conditionSql;
        }
        $this->params = $this->rootGroup->getParams();

        if (!empty($this->orders)) {
            $query .= " ORDER BY " . implode(", ", array_map(fn($order) => " " . static::escapeSqlColumnName($this->getOrderFieldName($order)) . " " . $order['direction'], $this->orders));
        }

        if ($this->limit) {
            $query .= " LIMIT " . (int) $this->limit;
            if ($this->offset) {
                $query .= " OFFSET " . (int) $this->offset;
            }
        }

        $this->query = $query;
        $dbh = $this->fromThentity::getPDOLink();
        $errormode = $dbh->getAttribute(PDO::ATTR_ERRMODE);
        $stmt = $dbh->prepare($query);

        if (!empty($this->params)) {
            foreach ($this->params as $pk => $pv) {
                $stmt->bindParam(':' . ltrim($pk, ':'), $this->params[$pk], PDO::PARAM_STR);
            }
        }

        $this->queryHistory[] = [$query, $this->params];

        if (!empty($options['debug']) && $options['debug']) {
            ThentityLogger::log($query, 'DEBUG');
            ThentityLogger::log(print_r($this->params, true, 'DEBUG'));
        }

        $success = $stmt->execute();
        if (!$success && !$errormode) {
            if (!empty($options['debug']) && $options['debug']) {
                ThentityLogger::log(print_r($stmt->debugDumpParams(), true, 'DEBUG'));
            }
            throw new ThentityException($stmt->errorInfo()[0] . '  ' . $stmt->errorInfo()[2] . ' ' . $query);
        }

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $r = $row;

            foreach ($this->entities as $alias => $Thentity) {
                if (isset($row[$alias . static::SEP . $Thentity::getPrimaryKey()])) {
                    $r[$alias] = new $Thentity($row[$alias . static::SEP . $Thentity::getPrimaryKey()]);
                    unset($r[$alias . static::SEP . $Thentity::getPrimaryKey()]);
                }
            }
            $results[] = $r;
        }


        return $results;
    }

    public function queryHistory($index = null)
    {
        if ($index !== null && !empty($this->queryHistory[$index])) {
            return $this->queryHistory[$index];
        }
        return $this->queryHistory;
    }

    // retourne le nombre maximum de ligne (pour une pagination)
    public function foundRows(): int
    {
        $hold_limit = $this->limit;
        $hold_offset = $this->offset;
        $hold_selects = $this->selects;
        $this->addSelect('COUNT(*)', 'countRows');
        $r = $this->find();
        $r = ($r) ? $r[0]['countRows'] : 0;
        $this->limit = $hold_limit;
        $this->offset = $hold_offset;
        $this->selects = $hold_selects;
        return $r;
    }

    private function buildFromClause(): string
    {
        $from = [];

        $from[] = $this->fromThentity::getTableName() . " AS " . static::escapeSqlColumnName($this->fromAlias);

        foreach ($this->joins as $join) {
            $from[] = $join['type'] . " JOIN " . $join['Thentity']::getTableName() . " AS " . static::escapeSqlColumnName($join['alias']) . " ON " . $join['on'];
        }
        return implode(" ", $from);
    }
}

<?php

namespace Thentity;

use Exception;

class ThentityCondition 
{
    protected $Relation;
    protected $Thentity;
    protected $orders;
    protected $limit = 0;
    protected $offset = 0;
    protected $mappedFields = [];
    protected $conditions;
    protected static $debug = false;

    public function __construct(ThentityRelation $Relation)
    {

        $this->Relation = $Relation;
        $this->Thentity = $Relation->getThentity();
    }

    public function mapField($fieldFrom, $fieldTo)
    {
        $this->mappedFields[$fieldFrom] = $fieldTo;
        return $this;
    }

    public function addLimit(int $limit = 0)
    {
        $this->limit = $limit;
        return $this;
    }

    public function addOffset(int $offset = 0)
    {
        $this->offset = $offset;
        return $this;
    }

    public function addConditions(array $conditions)
    {
        
        $numericKeys = array_filter(array_keys($conditions), 'is_int');
        if (!empty($numericKeys)) {
            throw new Exception('Options must use string keys only eg: [field => value].');
        }

        foreach ($conditions as $k => $v) {
            $this->conditions[] = self::normalizeCondition($k, $v);
        }
        foreach ($this->conditions as $k => $condition) {
            $this->conditions[$k]['field'] = $this->filterMappedField($this->conditions[$k]['field']);
        }
        return $this;
    }

    public function addOrders(array $orders)
    {
        $numericKeys = array_filter(array_keys($orders), 'is_int');
        if (!empty($numericKeys)) {
            throw new Exception('Orders must use string keys only eg: [field => direction].');
        }

        foreach ($orders as $f => $d) {
            $order = self::normalizeOrder($f, $d);

            $order['field']  = $this->filterMappedField($order['field']);
            $this->orders[$order['field']] =  $order;
        }
        return $this;
    }

    // renvoie la liste des colonnes dans le set de condition ou order    
    public function allFields()
    {
        $allFields = [];
        foreach ($this->conditions as $condition) {
            $allFields[$condition['field']] = $condition['field'];
        }
        foreach ($this->orders as $order) {
            $allFields[$order['field']] = $order['field'];
        }
        return $allFields;
    }

    // renvoie la liste des colones qui match avec $neededFields
    public function hasFields(array $neededFields)
    {
        $allFields = $this->allFields();
        /*$allMappedFields = [];
        foreach ( $allFields as $field ) {            
            $allMappedFields[$field] = $field;
        }*/
        return array_intersect($neededFields,  array_keys($allFields));
    }

    protected function filterMappedField(string $fieldName)
    {
        if (!empty($this->mappedFields[$fieldName])) {
            return $this->mappedFields[$fieldName];
        }
        return $fieldName;
    }

    protected function normalizeOrder($field, $direction)
    {
        $order = [
            'field' => $field,
            'direction' => $direction
        ];
        return $order;
    }

    protected static function normalizeCondition(string $condition, $values): array
    {
        $condition = trim($condition);
        if ( $values === null ) {
            $values = [''];
        }
        else if (is_scalar($values) ) {
            $values = [$values];
        }
        
        $operators = [
            '>' => [],
            '<' => [],
            '>=' => [],
            '<=' => [],
            '=' => [],
            '!=' => [],
            '<>' => [],
            'IS' => ['NOT' => ['NULL' => []], 'NULL' => []],
            'NOT' => ['IN' => [], 'LIKE' => []],
            'IN' => [],
            'LIKE' => [],
            'BETWEEN' => [],
        ];

        $parts = preg_split('/\s+/', $condition);
        // Aucun opérateur, opérateur implicite "="
        if (count($parts) === 1) {
            $fieldName = $parts[0];
            $operator = '=';
        } else {
            $fieldName = array_shift($parts);
            $node = $operators;
            $opParts = [];
            foreach ($parts as $i => $part) {
                $upper = strtoupper($part);
                if (isset($node[$upper])) {
                    $opParts[] = $upper;
                    $node = $node[$upper];
                } else {
                    break;
                }
            }
            $operator = implode(' ', $opParts);
        }

        $placeholders_values = [];
        $i = 0;
        foreach ($values as $k => $v) {            
            $p_fieldName = preg_replace('/[^A-Z0-9_-]/i', '_', $fieldName);
            $placeholder = ':' . $p_fieldName . '_' . self::short_id($operator) . '_' . $i++;
            $placeholders_values[$placeholder] = $v;
        }

        $r = ['field' => $fieldName,  'op' => $operator, 'placeholders_values' => $placeholders_values];
        if (empty($r['op'])) {
            throw new ThentityException("Clé de condition invalide, aucun champ trouvé dans '$condition'");
        }
        return $r;
    }

    protected static function short_id($str, $length = 8)
    {
        $hash = md5($str);
        $base36 = base_convert(substr($hash, 0, 15), 16, 36);
        return substr($base36, 0, $length);
    }


    public function _buildCondition()
    {
        $relation = $this->Relation;
        $options = $this->conditions;
        foreach ($options as $operation) {
            //$operation['field'] = $this->filterMappedField($operation['field']);
            $full_fn = explode('.', $operation['field']);
            if (!empty($full_fn[1])) {
                $full_field_name = $full_fn[0] . '.' . $full_fn[1];
            } else {
                $full_field_name = $this->Relation->getAlias() . '.' . $operation['field'];
            }
            if ($relation->isField($full_field_name)) {
                // cas de opérateur ou la valeur n'est pas nécessaire
                $op_ignore_val = ['IS NULL', 'IS NOT NULL'];
                if (in_array($operation['op'], $op_ignore_val)) {
                    $relation->addCondition($full_field_name . ' ' . $operation['op']);
                }
                // requete IN
                else if (substr($operation['op'], -2) === 'IN') {
                    $relation->addCondition($full_field_name . ' ' . $operation['op'] . '(' . implode(',', array_keys($operation['placeholders_values'])) . ')', $operation['placeholders_values']);
                } 
                // BETWEEN
                elseif (substr($operation['op'], -7) === 'BETWEEN') {
                        if (count($operation['placeholders_values']) !== 2) {
                            throw new Exception('Operator BETWEEN need exactly two values, ' . count($operation['placeholders_values']) . ' values founds');
                        }
                        $plk = array_keys($operation['placeholders_values']);
                        $relation->addCondition($full_field_name . ' ' . $operation['op'] . ' ' . $plk[0] . ' AND ' . $plk[1], $operation['placeholders_values']);
                }
                
                else if (count($operation['placeholders_values']) > 1) {                    
                    throw new Exception('Multiple values not supported by operator ' . $operation['op']);                    
                }
                // opérateur simple < != ... 
                else {
                    $relation->addCondition($full_field_name . ' ' . $operation['op'] . ' ' . array_key_first($operation['placeholders_values']), $operation['placeholders_values']);
                }
            } else {
                throw new \Exception('Unknow colum :' . $full_field_name);
            }
        }
        return $this;
    }

    public function find(array $opts=[])
    {
        $relation = $this->Relation;
        $this->_buildCondition();
        $debug = ( !empty($opts['debug']) ) ? (int) $opts['debug'] : self::$debug;

        foreach ($this->orders as $order) {
            $full_fn = explode('.', $order['field']);
            if (!empty($full_fn[1])) {
                $full_field_name = $full_fn[0] . '.' . $full_fn[1];
            } else {
                $full_field_name = $this->Relation->getAlias() . '.' . $order['field'];
            }
            $this->Relation->addOrder($full_field_name, $order['direction']);
        }

        $relation->addLimit($this->limit, $this->offset);
        $r_list = $relation->find(['debug' => $debug]);
        $list = [];
        $alias = $relation->getAlias();        
        if (!empty($r_list)) {
            foreach ($r_list as $v) {
                $class = get_class($this->Thentity);
                if ($v[$alias] instanceof $class) {
                    $list[] = $v[$alias];
                }
            }
        }
        return $list;
    }
}

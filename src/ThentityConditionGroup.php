<?php

namespace Thentity;

class ThentityConditionGroup
{
    protected string $type; // AND / OR
    protected array $conditions = []; // conditions 
    protected array $params = [];
    protected array $groups = []; // sous-groupes (enfants)
    protected string $_buildQuery = '';
    protected $_buildParams = [];
    protected ThentityRelation $ThentityRelation;
    protected ?ThentityConditionGroup $parent = null;

    public function __construct(ThentityRelation $ThentityRelation, string $type = 'AND', ?ThentityConditionGroup $parent = null)
    {
        $this->ThentityRelation = $ThentityRelation;
        $this->setTypeCondition($type);

        if ($parent !== null) {
            $parent->addChild($this);
        }
    }

    public function getRelation()
    {
        return $this->ThentityRelation;
    }

    // return all fields used by conditions
    public function getallFields() {

        return [];
    }


    public function setTypeCondition(string $type)
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['AND', 'OR'])) {
            throw new ThentityException('Bad condition type');
        }
        $this->type = $type;
        return $this;
    }


    public function addCondition($conditions = null, $params = [])
    {
        $condition = new ThentityCondition($this);
        if (is_array($conditions) && !empty($conditions)) {
            $condition->setArrayConditions($conditions);
        } else if (!empty($conditions) && is_string($conditions)) {
            $condition->setCondition($conditions, $params);
        }
        $this->conditions[] = $condition;
        return $this;
    }

    public function addGroupCondition(string $type = 'AND'): self
    {
        $group = new self($this->getRelation(), $type);
        $this->addChild($group);
        return $group;
    }

    /**
     * 
     * @return the new wrapper group 
     */
    public function wrapWithGroupCondition(string $type = 'AND'): self
    {
        $newParent = new self($this->ThentityRelation, $type);
        $this->setParent($newParent); // on détache
        return $newParent;
    }

    protected function addChild(self $group): void
    {
        if ($group === $this) {
            throw new \LogicException("Cannot add a group as its own child or descendant.");
        }

        // Affecte ce groupe comme parent
        $group->parent = $this;
        $this->groups[] = $group;
    }


    private function setParent(?self $parent): void
    {

        if ($this->getRelation()->getGroupCondition() === $this) {
            $this->getRelation()->setGroupCondition($parent);
        }

        $parent->addChild($this); // gère tout
    }


    public function getParams(): array
    {
        $params = $this->params;
        foreach ($this->groups as $group) {
            $params = array_merge($params, $group->getParams());
        }
        return $params;
    }

    public function buildConditions(): array
    {
        $r = [
            'sql' => [],
            'params' => []
        ];
        $parts = [];


        foreach ($this->conditions as $cond) {
            $parts[] = implode(' ' . $this->type . ' ', $cond->getConditions());
            $r['params'] += $cond->getParams();
        }

        foreach ($this->groups as $group) {
            $sql = $group->buildConditions();
            if (!empty($sql)) {
                $parts[] = "\r\n  (" . $sql['sql'] . ")";
                $r['params'] += $sql['params'];
            }
        }
        $r['sql'] = implode(" {$this->type} ", $parts);
        if (empty($r['sql'])) {
            return [];
        }

        return $r;
    }
}

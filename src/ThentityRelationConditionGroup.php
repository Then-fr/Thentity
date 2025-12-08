<?php 
namespace Thentity;

class ThentityRelationConditionGroup
{
    protected string $type; // AND / OR
    protected array $conditions = []; // conditions simples
    protected array $params = [];
    protected array $groups = []; // sous-groupes (enfants)
    protected ?ThentityRelationConditionGroup $parent = null;

    public function __construct(string $type = 'AND', ?ThentityRelationConditionGroup $parent = null)
    {
        $this->type = strtoupper($type);
        if ( !in_array($this->type, ['AND', 'OR']) ) {
            throw new \LogicException("Bad relation type.");
        }
        if ($parent !== null) {
            $parent->addChild($this);
        }
    }

    public function addCondition(string $sql, array $params = []): self
    {
        $this->conditions[] = $sql;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function addGroupCondition(string $type = 'AND'): self
    {
        $group = new self($type);
        $this->addChild($group);
        return $group;
    }

    public function addChild(self $group): void
    {
        if ($group === $this || $this->isDescendantOf($group)) {
            throw new \LogicException("Cannot add a group as its own child or descendant.");
        }

        // Nettoie son ancien parent
        $group->removeFromParent();

        // Affecte ce groupe comme parent
        $group->parent = $this;
        $this->groups[] = $group;
    }

    public function setChildOf(self $parent): void
    {
        $parent->addChild($this);
    }

    public function wrapWithGroupCondtion(string $type = 'AND'): self
    {
            $newParent = new self($type);
            $this->setParent(null); // on détache
            $newParent->addChild($this); // on le met sous nouveau parent
            return $newParent;
    }

    private function setParent(?self $parent): void
    {
        // cas du unset
        if ($parent === null) {
            $this->removeFromParent();
            return;
        }

        $parent->addChild($this); // gère tout
    }

    private function removeFromParent(): void
    {
        if ($this->parent !== null) {
            foreach ($this->parent->groups as $k => $child) {
                if ($child === $this) {
                    unset($this->parent->groups[$k]);
                    $this->parent->groups = array_values($this->parent->groups); // reindex proprement
                    break;
                }
            }
            $this->parent = null;
        }
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getParams(): array
    {
        $params = $this->params;
        foreach ($this->groups as $group) {
            $params = array_merge($params, $group->getParams());
        }
        return $params;
    }

    public function toSql(): string
    {
        $parts = [];

        foreach ($this->conditions as $cond) {
            $parts[] = "($cond)";
        }

        foreach ($this->groups as $group) {
            $sql = $group->toSql();
            if (!empty($sql)) {
                $parts[] = "($sql)";
            }
        }

        return implode(" {$this->type} ", $parts);
    }

    public function isDescendantOf(self $ancestor): bool
    {
        $current = $this->parent;
        while ($current !== null) {
            if ($current === $ancestor) return true;
            $current = $current->parent;
        }
        return false;
    }

    public function hasAncestor(self $target): bool
    {
        return $this->isDescendantOf($target);
    }
}

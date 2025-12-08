<?php

namespace Thentity;

trait ThentityModSoftDelete
{
    /**
     * Nom du champ utilisé pour le soft delete
     * Par défaut : deleted_at
     */
    protected static string $softDeleteField = 'deleted_at';

    /**
     * Configure le nom du champ soft delete
     */
    public static function setSoftDeleteField(string $fieldName): void
    {
        static::$softDeleteField = $fieldName;
    }

    /**
     * Supprime l'entité en soft delete (met à jour le champ configuré)
     */
    public function delete(): bool
    {
        if (!$this->exist()) {
            throw new ThentityException("Impossible de supprimer : l'objet n'existe pas en base.");
        }

        $field = static::$softDeleteField;
        if (!$this->isField($field)) {
            throw new ThentityException("Soft delete impossible : champ '$field' inexistant.");
        }

        $this->$field = date('Y-m-d H:i:s');
        return $this->update();
    }

    /**
     * Restaure un enregistrement soft deleted
     */
    public function restore(): bool
    {
        if (!$this->exist()) {
            throw new ThentityException("Impossible de restaurer : l'objet n'existe pas en base.");
        }

        $field = static::$softDeleteField;
        if (!$this->isField($field)) {
            throw new ThentityException("Restore impossible : champ '$field' inexistant.");
        }

        $this->$field = null;
        return $this->update();
    }

    /**
     * Vérifie si l'entité est supprimée (soft deleted)
     */
    public function isDeleted(): bool
    {
        $field = static::$softDeleteField;
        if (!$this->isField($field)) {
            return false;
        }

        return !empty($this->$field);
    }

    /**
     * Modifie les requêtes listBy / getOneBy pour exclure par défaut les soft deleted
     */
    public static function listBy(array $options = [], array $orders = [], int $limit = 0, int $offset = 0)
    {
        $field = static::$softDeleteField;
        if ((new static())->isField($field)) {
            $options[$field] = null; // exclut les soft deleted
        }

        return parent::listBy($options, $orders, $limit, $offset);
    }

    public static function getOneBy(array $options = [])
    {
        $field = static::$softDeleteField;
        if ((new static())->isField($field)) {
            $options[$field] = null; // exclut les soft deleted
        }

        return parent::getOneBy($options);
    }
}

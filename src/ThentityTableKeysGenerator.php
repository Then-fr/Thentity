<?php
namespace Thentity;

trait ThentityTableKeysGenerator
{
    /**
     * Génère dynamiquement les clés de la table.
     *
     * @return array<string, array<string, string>> Les clés de la table.
     */
    static public function generateTableKeys(): array
    {
        $pdo = self::getPDOLink();
        $tableName = self::getTableName();
        $query = "
            SELECT COLUMN_NAME, DATA_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = :table_name
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['table_name' => $tableName]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $keys = [];
        foreach ($columns as $column) {
            $keys[$column['COLUMN_NAME']] = [
                'type' => $column['DATA_TYPE'],
                'nom' => $column['COLUMN_NAME'],
            ];
        }

        return $keys;
    }
}
 
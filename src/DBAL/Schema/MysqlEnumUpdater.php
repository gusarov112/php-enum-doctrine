<?php
declare(strict_types=1);

namespace Gusarov112\PhpEnumDoctrine\DBAL\Schema;

use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Gusarov112\Enum\Enum;
use Gusarov112\PhpEnumDoctrine\DBAL\EnumType;
use PDO;

class MysqlEnumUpdater implements PlatformEventEnumValueUpdaterInterface
{
    public function getPlatformName(): string
    {
        return 'mysql';
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs, Table $table, Column $column)
    {
        /** @var EnumType $type */
        $type = $column->getType();
        /** @var Enum $enumClassName */
        $enumClassName = $type->getEnumClassName();

        $query = $eventArgs->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select('column_type')
            ->from('information_schema.COLUMNS')
            ->where('TABLE_NAME = :table AND COLUMN_NAME = :column')
            ->setParameters(
                [
                    'table' => $table->getName(),
                    'column' => $column->getName(),
                ]
            )
        ;
        $statement = $query->execute();
        $result = $statement->fetch(PDO::FETCH_NUM);
        if (false !== $result) {
            $columnType = $result[0];
            $dbEnum = preg_replace('/^enum\(|\)$/i', '', $columnType);
            $currentDbEnumValues = array_map(
                function ($value) {
                    return trim($value, '\'"');
                },
                explode(',', $dbEnum)
            );
            $phpEnumValues = array_values($enumClassName::toArray());
            if (!empty(array_diff($phpEnumValues, $currentDbEnumValues))) {
                $resultEnumValues = array_merge($currentDbEnumValues, array_diff($phpEnumValues, $currentDbEnumValues));
                $enumTypeDeclaration = sprintf('ENUM(%s)', '"'.implode('","', $resultEnumValues).'"');
                $column->setCustomSchemaOption('enumTypeDeclaration', $enumTypeDeclaration);
            }
        }
    }

    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $eventArgs)
    {
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $eventArgs)
    {
    }

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $eventArgs)
    {
    }
}

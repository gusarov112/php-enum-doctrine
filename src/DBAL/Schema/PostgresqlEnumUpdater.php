<?php
declare(strict_types=1);

namespace Gusarov112\PhpEnumDoctrine\DBAL\Schema;

use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Generator;
use Gusarov112\Enum\Enum;
use Gusarov112\PhpEnumDoctrine\DBAL\EnumType;
use PDO;

class PostgresqlEnumUpdater implements PlatformEventEnumValueUpdaterInterface
{
    private static $sqlCache = [];

    public function getPlatformName(): string
    {
        return 'postgresql';
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $eventArgs)
    {
        foreach ($this->getSqlGeneratorFromCustomOptions($eventArgs->getColumnDiff()->column) as $sql) {
            $eventArgs->addSql($sql);
        }
    }

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $eventArgs)
    {
        foreach ($this->getSqlGeneratorFromCustomOptions($eventArgs->getColumn()) as $sql) {
            $eventArgs->addSql($sql);
        }
    }

    private function getSqlGeneratorFromCustomOptions(Column $column): Generator
    {
        if ($column->getType() instanceof EnumType) {
            if ($column->hasCustomSchemaOption('addSql')) {
                foreach ($column->getCustomSchemaOption('addSql') as $sql) {
                    if (!isset(self::$sqlCache[$sql])) {
                        self::$sqlCache[$sql] = $sql;
                        yield $sql;
                    }
                }
            }
        }
    }

    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $eventArgs)
    {
        foreach ($this->getSqlGeneratorFromCustomOptions($eventArgs->getColumn()) as $sql) {
            $eventArgs->addSql($sql);
        }
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
            ->select('unnest(enum_range(NULL::'.$type->getName().'))')
        ;
        try {
            $statement = $query->execute();
            $currentDbEnumValues = [];
            while ($item = $statement->fetch(PDO::FETCH_NUM)) {
                $currentDbEnumValues[] = $item[0];
            }
            $phpEnumValues = array_values($enumClassName::toArray());
            $newEnumValues = array_diff($phpEnumValues, $currentDbEnumValues);
            $addSql = [];
            foreach ($newEnumValues as $resultEnumValue) {
                $addSql[] = 'ALTER TYPE '.$type->getName().' ADD VALUE \''.$resultEnumValue.'\'';
            }
            $column->setCustomSchemaOption('addSql', $addSql);
            $column->setCustomSchemaOption('enumTypeDeclaration', $type->getName());
        } catch (DriverException $e) {
            $column->setCustomSchemaOption('enumTypeDeclaration', 'VARCHAR');
            $column->setCustomSchemaOption(
                'addSql',
                [
                    'CREATE TYPE '.$type->getName().' AS ENUM (\''.implode(
                        "','",
                        array_values($enumClassName::toArray())
                    ).'\');',
                    'ALTER TABLE '.$table->getName().' ALTER COLUMN '.$column->getName()
                    .' TYPE '.$type->getName()
                    .' USING '.$column->getName().'::'.$type->getName(),
                ]
            );
        }
    }
}

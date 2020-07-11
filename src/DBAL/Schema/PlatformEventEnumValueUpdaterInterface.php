<?php
declare(strict_types=1);

namespace Gusarov112\PhpEnumDoctrine\DBAL\Schema;

use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

interface PlatformEventEnumValueUpdaterInterface
{
    public function getPlatformName(): string;

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs, Table $table, Column $column);

    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $eventArgs);

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $eventArgs);

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $eventArgs);
}

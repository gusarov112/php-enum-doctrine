<?php
declare(strict_types=1);

namespace Gusarov112\PhpEnumDoctrine\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Gusarov112\PhpEnumDoctrine\DBAL\EnumType;
use Gusarov112\PhpEnumDoctrine\DBAL\Schema\PlatformEventEnumValueUpdaterInterface;

class EnumUpdaterSubscriber implements EventSubscriber
{
    /**
     * @var PlatformEventEnumValueUpdaterInterface[]
     */
    private $updaters;

    public function __construct(PlatformEventEnumValueUpdaterInterface ...$updaters)
    {
        foreach ($updaters as $updater) {
            $this->updaters[$updater->getPlatformName()] = $updaters;
        }
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $eventArgs)
    {
        $updater = $this->getUpdater($eventArgs->getPlatform()->getName());
        if ($updater) {
            $updater->onSchemaAlterTableChangeColumn($eventArgs);
        }
    }

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $eventArgs)
    {
        $updater = $this->getUpdater($eventArgs->getPlatform()->getName());
        if ($updater) {
            $updater->onSchemaAlterTableAddColumn($eventArgs);
        }
    }

    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $eventArgs)
    {
        $updater = $this->getUpdater($eventArgs->getPlatform()->getName());
        if ($updater) {
            $updater->onSchemaCreateTableColumn($eventArgs);
        }
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs)
    {
        foreach ($eventArgs->getSchema()->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                if ($column->getType() instanceof EnumType) {
                    $platform = $eventArgs->getEntityManager()->getConnection()->getDatabasePlatform();
                    $updater = $this->getUpdater($platform->getName());
                    if ($updater) {
                        $updater->postGenerateSchema($eventArgs, $table, $column);
                    }
                }
            }
        }
    }

    private function getUpdater(string $platform): ?PlatformEventEnumValueUpdaterInterface
    {
        return $this->updaters[$platform] ?? null;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onSchemaAlterTableChangeColumn,
            Events::onSchemaAlterTableAddColumn,
            Events::onSchemaCreateTableColumn,
            ToolEvents::postGenerateSchema,
        ];
    }
}

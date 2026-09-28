<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Domain\Model\References;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * Adds the foreign key (and its index) of every column marked with #[References], so a plain id that points
 * into another bounded context keeps the same constraint an association would have created.
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class CrossContextForeignKeys
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        foreach ($args->getEntityManager()->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->isMappedSuperclass || !$schema->hasTable($metadata->getTableName())) {
                continue;
            }
            $table = $schema->getTable($metadata->getTableName());
            foreach ($metadata->getReflectionClass()->getProperties() as $property) {
                foreach ($property->getAttributes(References::class) as $attribute) {
                    $reference = $attribute->newInstance();
                    $column = $metadata->getColumnName($property->getName());
                    $table->addForeignKeyConstraint(
                        $reference->table,
                        [$column],
                        ['id'],
                        null === $reference->onDelete ? [] : ['onDelete' => $reference->onDelete],
                    );
                }
            }
        }
    }
}

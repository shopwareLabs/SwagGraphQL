<?php declare(strict_types=1);

namespace SwagGraphQL\Test\_fixtures;

use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;

class MappingEntity extends MappingEntityDefinition
{

    public static function getEntityName(): string
    {
        return 'mapping';
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('association_id', 'associationId', AssociationEntity::class))->setFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(AssociationEntity::class))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('many_to_many_id', 'manyToManyId', ManyToManyEntity::class))->setFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ManyToManyEntity::class))->setFlags(new PrimaryKey(), new Required()),

            new ManyToOneAssociationField('association', 'association_id', AssociationEntity::class, false),
            new ManyToOneAssociationField('manyToMany', 'many_to_many_id', ManyToManyEntity::class, false),
        ]);
    }
}
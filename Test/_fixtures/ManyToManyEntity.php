<?php declare(strict_types=1);

namespace SwagGraphQL\Test\_fixtures;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;

class ManyToManyEntity extends EntityDefinition
{

    public static function getEntityName(): string
    {
        return 'many_to_many';
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new VersionField(),
            new ManyToManyAssociationField(
                'association',
                AssociationEntity::class,
                MappingEntity::class,
                false,
                'many_to_many_id',
                'association_id'
            )
        ]);
    }
}
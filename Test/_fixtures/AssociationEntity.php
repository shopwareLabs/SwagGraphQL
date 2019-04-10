<?php declare(strict_types=1);

namespace SwagGraphQL\Test\_fixtures;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class AssociationEntity extends EntityDefinition
{

    public static function getEntityName(): string
    {
        return 'association';
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new ManyToManyAssociationField(
                'manyToMany',
                ManyToManyEntity::class,
                MappingEntity::class,
                'association_id',
                'many_to_many_id'
            ),
            new FkField(
                'many_to_one_id',
                'manyToOneId',
                ManyToOneEntity::class
            ),
            new ReferenceVersionField(ManyToOneEntity::class),
            new ManyToOneAssociationField(
                'manyToOne',
                'many_to_one',
                ManyToOneEntity::class
            )
        ]);
    }
}
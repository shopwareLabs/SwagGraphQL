<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\TypeRegistry;
use SwagGraphQL\Test\_fixtures\AssociationEntity;
use SwagGraphQL\Test\_fixtures\BaseEntity;
use SwagGraphQL\Test\_fixtures\BaseEntityWithDefaults;
use SwagGraphQL\Test\_fixtures\ManyToManyEntity;
use SwagGraphQL\Test\_fixtures\ManyToOneEntity;
use SwagGraphQL\Test\_fixtures\MappingEntity;
use SwagGraphQL\Test\Traits\SchemaTestTrait;
use SwagGraphQL\Types\DateType;
use SwagGraphQL\Types\JsonType;

class TypeRegistryTest extends TestCase
{
    use SchemaTestTrait;

    /** @var MockObject */
    private $definitionRegistry;

    /** @var TypeRegistry */
    private $typeRegistry;

    public function setUp()
    {
        $this->definitionRegistry = $this->createMock(DefinitionRegistry::class);
        $this->typeRegistry = new TypeRegistry($this->definitionRegistry, new CustomTypes());
    }

    public function testGetQueryForBaseEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([BaseEntity::class]);

        $this->definitionRegistry->expects($this->once())
            ->method('get')
            ->with(BaseEntity::getEntityName())
            ->willReturn(BaseEntity::class);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(1, $query->getFields());

        $baseField = $query->getField(BaseEntity::getEntityName());
        $this->assertConnectionObject([
            'id' => NonNull::class,
            'versionId' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $baseField->getType());
        
        $this->assertConnectionArgs($baseField);
    }

    public function testGetQueryForAssociationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([AssociationEntity::class, ManyToManyEntity::class, ManyToOneEntity::class, MappingEntity::class]);

        $this->definitionRegistry->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [AssociationEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()]
            )->willReturnOnConsecutiveCalls(
                AssociationEntity::class,
                ManyToManyEntity::class,
                ManyToOneEntity::class
            );

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(3, $query->getFields());

        $associationField = $query->getField(AssociationEntity::getEntityName());
        static::assertConnectionObject([
            'manyToMany' => ObjectType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => ObjectType::class
        ], $associationField->getType());

        $manyToManyField = $query->getField(ManyToManyEntity::getEntityName());
        static::assertConnectionObject([
            'association' => ObjectType::class,
        ], $manyToManyField->getType());


        $manyToOneField = $query->getField(ManyToOneEntity::getEntityName());
        static::assertConnectionObject([
            'association' => ObjectType::class,
        ], $manyToOneField->getType());
    }

    public function testGetQueryIgnoresTranslationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([ProductTranslationDefinition::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetQueryIgnoresMappingEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([ProductCategoryDefinition::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationForBaseEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([BaseEntity::class]);

        $this->definitionRegistry->expects($this->exactly(2))
            ->method('get')
            ->with(BaseEntity::getEntityName())
            ->willReturn(BaseEntity::class);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(1, $query->getFields());

        $baseField = $query->getField('upsert_' . BaseEntity::getEntityName());
        $this->assertObject([
            'id' => NonNull::class,
            'versionId' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $baseField->getType());

        $this->assertInputArgs([
            'id' => IDType::class,
            'versionId' => IDType::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $baseField);
    }

    public function testGetMutationForAssociationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([AssociationEntity::class, ManyToManyEntity::class, ManyToOneEntity::class, MappingEntity::class]);

        $this->definitionRegistry->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                [AssociationEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()],
                [AssociationEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()]
            )->willReturnOnConsecutiveCalls(
                AssociationEntity::class,
                ManyToManyEntity::class,
                ManyToOneEntity::class,
                AssociationEntity::class,
                ManyToManyEntity::class,
                ManyToOneEntity::class
            );

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(3, $query->getFields());

        $associationField = $query->getField('upsert_' . AssociationEntity::getEntityName());
        static::assertObject([
            'manyToMany' => ObjectType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => ObjectType::class
        ], $associationField->getType());
        static::assertConnectionObject([
            'association' => ObjectType::class,
        ], $associationField->getType()->getField('manyToMany')->getType());
        static::assertInputArgs([
            'id' => IDType::class,
            'versionId' => IDType::class,
            'manyToMany' => ListOfType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => InputObjectType::class
        ], $associationField);

        $manyToManyField = $query->getField('upsert_' . ManyToManyEntity::getEntityName());
        static::assertObject([
            'association' => ObjectType::class,
        ], $manyToManyField->getType());
        static::assertConnectionObject([
            'manyToMany' => ObjectType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => ObjectType::class
        ], $manyToManyField->getType()->getField('association')->getType());
        static::assertInputArgs([
            'id' => IDType::class,
            'versionId' => IDType::class,
            'association' => ListOfType::class,
        ], $manyToManyField);


        $manyToOneField = $query->getField('upsert_' . ManyToOneEntity::getEntityName());
        static::assertObject([
            'association' => ObjectType::class,
        ], $manyToOneField->getType());
        static::assertConnectionObject([
            'manyToMany' => ObjectType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => ObjectType::class
        ], $manyToOneField->getType()->getField('association')->getType());
        static::assertInputArgs([
            'id' => IDType::class,
            'versionId' => IDType::class,
            'association' => ListOfType::class,
        ], $manyToOneField);
    }

    public function testGetMutationIgnoresTranslationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([ProductTranslationDefinition::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationIgnoresMappingEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([ProductCategoryDefinition::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationWithDefault()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([BaseEntityWithDefaults::class]);

        $this->definitionRegistry->expects($this->exactly(2))
            ->method('get')
            ->with(BaseEntityWithDefaults::getEntityName())
            ->willReturn(BaseEntityWithDefaults::class);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(1, $query->getFields());

        $baseField = $query->getField('upsert_' . BaseEntityWithDefaults::getEntityName());
        $this->assertObject([
            'id' => NonNull::class,
            'versionId' => NonNull::class,
            'string' => StringType::class
        ], $baseField->getType());

        $this->assertInputArgs([
            'id' => IDType::class,
            'versionId' => IDType::class,
            'string' => StringType::class
        ], $baseField);

        var_dump($baseField);
        $this->assertDefault(
            'test',
            $baseField->getArg('string')
        );
    }
}
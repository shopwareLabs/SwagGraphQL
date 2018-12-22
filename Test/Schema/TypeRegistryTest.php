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
use SwagGraphQL\Schema\Mutation;
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

        $this->definitionRegistry->expects($this->exactly(3))
            ->method('get')
            ->with(BaseEntity::getEntityName())
            ->willReturn(BaseEntity::class);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(2, $query->getFields());

        $upsert = new Mutation(Mutation::ACTION_UPSERT, BaseEntity::getEntityName());
        $upsertField = $query->getField($upsert->getName());
        $this->assertObject([
            'id' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $upsertField->getType());

        $this->assertInputArgs([
            'id' => IDType::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $upsertField);

        $delete = new Mutation(Mutation::ACTION_DELETE, BaseEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(IDType::class, $deleteField->getArg('id')->getType());
    }

    public function testGetMutationForAssociationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getElements')
            ->willReturn([AssociationEntity::class, ManyToManyEntity::class, ManyToOneEntity::class, MappingEntity::class]);

        $this->definitionRegistry->expects($this->exactly(9))
            ->method('get')
            ->withConsecutive(
                [AssociationEntity::getEntityName()],
                [AssociationEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()],
                [AssociationEntity::getEntityName()],
                [ManyToManyEntity::getEntityName()],
                [ManyToOneEntity::getEntityName()]
            )->willReturnOnConsecutiveCalls(
                AssociationEntity::class,
                AssociationEntity::class,
                ManyToManyEntity::class,
                ManyToManyEntity::class,
                ManyToOneEntity::class,
                ManyToOneEntity::class,
                AssociationEntity::class,
                ManyToManyEntity::class,
                ManyToOneEntity::class
            );

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(6, $query->getFields());

        $association = new Mutation(Mutation::ACTION_UPSERT, AssociationEntity::getEntityName());
        $associationField = $query->getField($association->getName());
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
            'manyToMany' => ListOfType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => InputObjectType::class
        ], $associationField);

        $delete = new Mutation(Mutation::ACTION_DELETE, AssociationEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(IDType::class, $deleteField->getArg('id')->getType());

        $manyToMany = new Mutation(Mutation::ACTION_UPSERT, ManyToManyEntity::getEntityName());
        $manyToManyField = $query->getField($manyToMany->getName());
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
            'association' => ListOfType::class,
        ], $manyToManyField);

        $delete = new Mutation(Mutation::ACTION_DELETE, ManyToManyEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(IDType::class, $deleteField->getArg('id')->getType());

        $manyToOne = new Mutation(Mutation::ACTION_UPSERT, ManyToOneEntity::getEntityName());
        $manyToOneField = $query->getField($manyToOne->getName());
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
            'association' => ListOfType::class,
        ], $manyToOneField);

        $delete = new Mutation(Mutation::ACTION_DELETE, ManyToOneEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(IDType::class, $deleteField->getArg('id')->getType());
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

        $this->definitionRegistry->expects($this->exactly(3))
            ->method('get')
            ->with(BaseEntityWithDefaults::getEntityName())
            ->willReturn(BaseEntityWithDefaults::class);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(2, $query->getFields());

        $upsert = new Mutation(Mutation::ACTION_UPSERT, BaseEntityWithDefaults::getEntityName());
        $baseField = $query->getField($upsert->getName());
        static::assertObject([
            'id' => NonNull::class,
            'string' => StringType::class
        ], $baseField->getType());

        static::assertInputArgs([
            'id' => IDType::class,
            'string' => StringType::class
        ], $baseField);

        static::assertDefault(
            'test',
            $baseField->getArg('string')
        );

        $delete = new Mutation(Mutation::ACTION_DELETE, BaseEntityWithDefaults::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(IDType::class, $deleteField->getArg('id')->getType());
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use Doctrine\Common\Inflector\Inflector;
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
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagGraphQL\Schema\CustomFieldRegistry;
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
    use SchemaTestTrait, KernelTestBehaviour;

    /** @var MockObject */
    private $definitionRegistry;

    /** @var TypeRegistry */
    private $typeRegistry;

    public function setUp(): void
    {
        $this->definitionRegistry = $this->createMock(DefinitionRegistry::class);
        $this->typeRegistry = new TypeRegistry(
            $this->definitionRegistry,
            new CustomTypes(),
            new CustomFieldRegistry(),
            new CustomFieldRegistry()
        );
    }

    public function testGetQueryForBaseEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([BaseEntity::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(2, $query->getFields());

        $expectedFields = [
            'id' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ];

        $fieldName = Inflector::camelize(BaseEntity::getEntityName());
        $baseField = $query->getField($fieldName);
        static::assertObject($expectedFields, $baseField->getType());
        static::assertInputArgs([
            'id' => NonNull::class,
        ], $baseField);

        $pluralizedName = Inflector::pluralize($fieldName);
        $baseField = $query->getField($pluralizedName);
        static::assertConnectionObject($expectedFields, $baseField->getType());
        static::assertConnectionArgs($baseField);
    }

    public function testGetQueryForAssociationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([AssociationEntity::class, ManyToManyEntity::class, ManyToOneEntity::class, MappingEntity::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(6, $query->getFields());

        $expectedFields = [
            'manyToMany' => ObjectType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => ObjectType::class
        ];
        $fieldName = Inflector::camelize(AssociationEntity::getEntityName());
        $associationField = $query->getField($fieldName);
        static::assertObject($expectedFields, $associationField->getType());

        $pluralizedName = Inflector::pluralize($fieldName);
        $associationField = $query->getField($pluralizedName);
        static::assertConnectionObject($expectedFields, $associationField->getType());

        $expectedFields = [
            'association' => ObjectType::class,
        ];

        $fieldName = Inflector::camelize(ManyToManyEntity::getEntityName());
        $manyToManyField = $query->getField($fieldName);
        static::assertObject($expectedFields, $manyToManyField->getType());

        $pluralizedName = Inflector::pluralize($fieldName);
        $manyToManyField = $query->getField($pluralizedName);
        static::assertConnectionObject($expectedFields, $manyToManyField->getType());

        $expectedFields = [
            'association' => ObjectType::class,
        ];

        $fieldName = Inflector::camelize(ManyToOneEntity::getEntityName());
        $manyToOneField = $query->getField($fieldName);
        static::assertObject($expectedFields, $manyToOneField->getType());

        $pluralizedName = Inflector::pluralize($fieldName);
        $manyToOneField = $query->getField($pluralizedName);
        static::assertConnectionObject($expectedFields, $manyToOneField->getType());
    }

    public function testGetQueryIgnoresTranslationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([ProductTranslationDefinition::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetQueryIgnoresMappingEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([ProductCategoryDefinition::class]);

        $query = $this->typeRegistry->getQuery();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Query', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationForBaseEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([BaseEntity::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(3, $query->getFields());

        $create = new Mutation(Mutation::ACTION_CREATE, BaseEntity::getEntityName());
        $createField = $query->getField($create->getName());
        static::assertObject([
            'id' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $createField->getType());

        static::assertInputArgs([
            'id' => IDType::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $createField);

        $update = new Mutation(Mutation::ACTION_UPDATE, BaseEntity::getEntityName());
        $updateField = $query->getField($update->getName());
        static::assertObject([
            'id' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $updateField->getType());

        static::assertInputArgs([
            'id' => NonNull::class,
            'bool' => BooleanType::class,
            'date' => DateType::class,
            'int' => IntType::class,
            'float' => FloatType::class,
            'json' => JsonType::class,
            'string' => StringType::class
        ], $updateField);

        $delete = new Mutation(Mutation::ACTION_DELETE, BaseEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(NonNull::class, $deleteField->getArg('id')->getType());
    }

    public function testGetMutationForAssociationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([AssociationEntity::class, ManyToManyEntity::class, ManyToOneEntity::class, MappingEntity::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(9, $query->getFields());

        $association = new Mutation(Mutation::ACTION_CREATE, AssociationEntity::getEntityName());
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

        $association = new Mutation(Mutation::ACTION_UPDATE, AssociationEntity::getEntityName());
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
            'id' => NonNull::class,
            'manyToMany' => ListOfType::class,
            'manyToOneId' => IDType::class,
            'manyToOne' => InputObjectType::class
        ], $associationField);

        $delete = new Mutation(Mutation::ACTION_DELETE, AssociationEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(NonNull::class, $deleteField->getArg('id')->getType());

        $manyToMany = new Mutation(Mutation::ACTION_CREATE, ManyToManyEntity::getEntityName());
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

        $manyToMany = new Mutation(Mutation::ACTION_UPDATE, ManyToManyEntity::getEntityName());
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
            'id' => NonNull::class,
            'association' => ListOfType::class,
        ], $manyToManyField);

        $delete = new Mutation(Mutation::ACTION_DELETE, ManyToManyEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(NonNull::class, $deleteField->getArg('id')->getType());

        $manyToOne = new Mutation(Mutation::ACTION_CREATE, ManyToOneEntity::getEntityName());
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

        $manyToOne = new Mutation(Mutation::ACTION_UPDATE, ManyToOneEntity::getEntityName());
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
            'id' => NonNull::class,
            'association' => ListOfType::class,
        ], $manyToOneField);

        $delete = new Mutation(Mutation::ACTION_DELETE, ManyToOneEntity::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(NonNull::class, $deleteField->getArg('id')->getType());
    }

    public function testGetMutationIgnoresTranslationEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([ProductTranslationDefinition::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationIgnoresMappingEntity()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([ProductCategoryDefinition::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(0, $query->getFields());
    }

    public function testGetMutationWithDefault()
    {
        $this->definitionRegistry->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([BaseEntityWithDefaults::class]);

        $query = $this->typeRegistry->getMutation();
        static::assertInstanceOf(ObjectType::class, $query);
        static::assertEquals('Mutation', $query->name);
        static::assertCount(3, $query->getFields());

        $create = new Mutation(Mutation::ACTION_CREATE, BaseEntityWithDefaults::getEntityName());
        $baseField = $query->getField($create->getName());
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

        $update = new Mutation(Mutation::ACTION_UPDATE, BaseEntityWithDefaults::getEntityName());
        $baseField = $query->getField($update->getName());
        static::assertObject([
            'id' => NonNull::class,
            'string' => StringType::class
        ], $baseField->getType());

        static::assertInputArgs([
            'id' => NonNull::class,
            'string' => StringType::class
        ], $baseField);

        static::assertFalse($baseField->getArg('string')->defaultValueExists());

        $delete = new Mutation(Mutation::ACTION_DELETE, BaseEntityWithDefaults::getEntityName());
        $deleteField = $query->getField($delete->getName());
        static::assertInstanceOf(IDType::class, $deleteField->getType());
        static::assertCount(1, $deleteField->args);
        static::assertInstanceOf(NonNull::class, $deleteField->getArg('id')->getType());
    }
}
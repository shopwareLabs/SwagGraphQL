<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;

class SchemaFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCreateSchema()
    {
        $registry = $this->getContainer()->get(DefinitionRegistry::class);
        $schema = SchemaFactory::createSchema(new TypeRegistry($registry, new CustomTypes()));

        static::assertInstanceOf(Schema::class, $schema);
        static::assertEmpty($schema->validate());
    }
}
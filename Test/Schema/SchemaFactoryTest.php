<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;

class SchemaFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCreateSchema()
    {
        $schema = SchemaFactory::createSchema($this->getContainer()->get(TypeRegistry::class));

        static::assertInstanceOf(Schema::class, $schema);
        static::assertEmpty($schema->validate());
    }
}
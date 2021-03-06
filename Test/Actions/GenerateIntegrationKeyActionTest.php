<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Actions;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use SwagGraphQL\Api\ApiController;
use SwagGraphQL\Resolver\QueryResolver;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;
use SwagGraphQL\Test\Traits\GraphqlApiTest;

class GenerateIntegrationKeyActionTest extends TestCase
{
    use GraphqlApiTest;

    /** @var ApiController */
    private $apiController;

    /**
     * @var Context
     */
    private $context;

    public function setUp(): void
    {
        $registry = $this->getContainer()->get(DefinitionRegistry::class);
        $schema = SchemaFactory::createSchema($this->getContainer()->get(TypeRegistry::class));

        $this->apiController = new ApiController($schema, new QueryResolver($this->getContainer(), $registry));
        $this->context = Context::createDefaultContext();
    }

    public function testGenerateIntegrationKey()
    {
        $query = "
            query {
	            generateIntegrationKey {
	                accessKey
	                secretAccessKey
	            }
            }
        ";

        $request = $this->createGraphqlRequestRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('errors', $data, print_r($data, true));
        static::assertArrayHasKey(
            'accessKey',
            $data['data']['generateIntegrationKey'],
            print_r($data['data'], true)
        );
        static::assertArrayHasKey(
            'secretAccessKey',
            $data['data']['generateIntegrationKey'],
            print_r($data['data'], true)
        );
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Actions;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use SwagGraphQL\Api\ApiController;
use SwagGraphQL\Resolver\QueryResolver;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;
use SwagGraphQL\Test\Traits\GraphqlApiTest;

class DissolveMediaFolderActionTest extends TestCase
{
    use GraphqlApiTest;

    /** @var ApiController */
    private $apiController;

    /** @var Context */
    private $context;

    /** @var EntityRepositoryInterface */
    private $repository;

    public function setUp(): void
    {
        $registry = $this->getContainer()->get(DefinitionRegistry::class);
        $schema = SchemaFactory::createSchema($this->getContainer()->get(TypeRegistry::class));

        $this->apiController = new ApiController($schema, new QueryResolver($this->getContainer(), $registry));
        $this->context = Context::createDefaultContext();

        $this->repository = $this->getContainer()->get('media_folder.repository');
    }

    public function testDissolveMediaFolder()
    {
        $folderId = Uuid::uuid4()->getHex();

        $data = [
            [
                'id' => $folderId,
                'name' => 'test folder',
                'configuration' => [],
            ],
        ];
        $this->repository->create($data, $this->context);
        $query = "
            mutation {
	            dissolveMediaFolder(
	                mediaFolderId: \"$folderId\"
	            )
            }
        ";

        $request = $this->createGraphqlRequestRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('errors', $data, print_r($data, true));
        static::assertEquals(
            $data['data']['dissolveMediaFolder'],
            $folderId,
            print_r($data['data'], true)
        );

        $folders = $this->repository->search(new Criteria([$folderId]), $this->context);
        static::assertNull($folders->get($folderId));
    }
}
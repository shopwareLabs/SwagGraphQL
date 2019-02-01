<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Actions;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaProtectionFlags;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use SwagGraphQL\Api\ApiController;
use SwagGraphQL\Resolver\QueryResolver;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;
use SwagGraphQL\Test\Traits\GraphqlApiTest;

class ProvideFileNameActionTest extends TestCase
{
    use GraphqlApiTest, MediaFixtures;

    /** @var ApiController */
    private $apiController;

    /** @var Context */
    private $context;

    /** @var RepositoryInterface */
    private $repository;

    public function setUp()
    {
        $registry = $this->getContainer()->get(DefinitionRegistry::class);
        $schema = SchemaFactory::createSchema($this->getContainer()->get(TypeRegistry::class));

        $this->apiController = new ApiController($schema, new QueryResolver($this->getContainer(), $registry));
        $this->context = Context::createDefaultContext();
        $this->context->getWriteProtection()->allow(MediaProtectionFlags::WRITE_META_INFO);
        $this->setFixtureContext($this->context);

        $this->repository = $this->getContainer()->get('media.repository');
    }

    public function testProvideFileName()
    {
        $media = $this->getJpg();

        $query = sprintf('
            mutation {
	            provideFileName(
	                fileName: "%s",
	                fileExtension: "jpg"
	            )
            }
        ', $media->getFileName());

        $request = $this->createGraphqlRequestRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('errors', $data, print_r($data, true));
        static::assertEquals(
            $data['data']['provideFileName'],
            $media->getFileName() . '_(1)',
            print_r($data['data'], true)
        );
    }
}
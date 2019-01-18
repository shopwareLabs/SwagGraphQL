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

class RenameMediaActionTest extends TestCase
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

    public function testRenameMedia()
    {
        $media = $this->getJpg();

        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $mediaPath = $urlGenerator->getRelativeMediaUrl($media);

        $this->getPublicFilesystem()->put($mediaPath, 'test file');

        $query = sprintf('
            mutation {
	            rename_media(
	                media_id: "%s"
	                fileName: "new Name"
	            ) {
	                id
	                fileName
	            }
            }
        ', $media->getId());

        $request = $this->createGraphqlRequestRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('errors', $data, print_r($data, true));
        static::assertEquals(
            $data['data']['rename_media']['id'],
            $media->getId(),
            print_r($data['data'], true)
        );
        static::assertEquals(
            $data['data']['rename_media']['fileName'],
            'new Name',
            print_r($data['data'], true)
        );

        /** @var MediaEntity $updatedMedia */
        $updatedMedia = $this->repository
            ->read(new ReadCriteria([$media->getId()]), $this->context)
            ->get($media->getId());
        static::assertEquals('new Name', $updatedMedia->getFileName());

        static::assertFalse($this->getPublicFilesystem()->has($mediaPath));
        static::assertTrue($this->getPublicFilesystem()->has($urlGenerator->getRelativeMediaUrl($updatedMedia)));
    }
}
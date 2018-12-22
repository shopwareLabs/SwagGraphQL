<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use GraphQL\Type\Introspection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagGraphQL\Api\ApiController;
use SwagGraphQL\Api\UnsupportedContentTypeException;
use SwagGraphQL\Resolver\QueryResolver;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaFactory;
use SwagGraphQL\Schema\TypeRegistry;
use Symfony\Component\HttpFoundation\Request;

class ApiControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    /** @var ApiController */
    private $apiController;

    /** @var Context */
    private $context;

    /** @var EntityRepository */
    private $repository;

    public function setUp()
    {
        $registry = $this->getContainer()->get(DefinitionRegistry::class);
        $schema = SchemaFactory::createSchema(new TypeRegistry($registry, new CustomTypes()));

        $this->apiController = new ApiController($schema, new QueryResolver($this->getContainer(), $registry));
        $this->context = Context::createDefaultContext();
        $this->repository = $this->getContainer()->get('product.repository');
    }

    public function testGenerateSchema()
    {
        @unlink(__DIR__ . '/../../Resources/schema.graphql');

        $response = $this->apiController->generateSchema();
        static::assertEquals(200, $response->getStatusCode());
        static::assertFileExists(__DIR__ . '/../../Resources/schema.graphql');
    }

    public function testQueryIntrospectionQuery()
    {
        $request = $this->createPostJsonRequest(Introspection::getIntrospectionQuery());
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayHasKey('data', $data);
        static::assertArrayNotHasKey('error', $data);
    }

    public function testQueryProductWithoutData()
    {
        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertEmpty($data['data']['product']['edges']);
        static::assertEquals(0, $data['data']['product']['total']);
    }

    public function testQueryGET()
    {
        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $request = Request::create(
            'localhost',
            Request::METHOD_GET,
            ['query' => $query]
        );
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertEmpty($data['data']['product']['edges']);
        static::assertEquals(0, $data['data']['product']['total']);
    }

    public function testQueryWithApplicationGraphQL()
    {
        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = Request::create(
            'localhost',
            Request::METHOD_POST,
            [],
            [],
            [],
            [],
            $query
        );
        $request->headers->add(['content_type' => 'application/graphql']);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertEmpty($data['data']['product']['edges']);
        static::assertEquals(0, $data['data']['product']['total']);
    }

    public function testWithUnsupportedContentType()
    {
        $this->expectException(UnsupportedContentTypeException::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->headers->set('content_type', 'application/svg');
        $this->apiController->query($request, $this->context);
    }

    public function testQueryProductWithOneProduct()
    {
        $productId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $productResult = $data['data']['product']['edges'][0]['node'];
        static::assertCount(2, $productResult);
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($productId, $productResult['id']);
        static::assertEquals(1, $data['data']['product']['total']);
    }

    public function testQueryProductWithMultipleProduct()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstProductId = $ids[0];
        $secondProductId = $ids[1];
        $thirdProductId = $ids[2];
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $firstProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'first product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
            [
                'id' => $secondProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'second product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $thirdProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'third product',
                'tax' => ['id' => $taxId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(3, $data['data']['product']['edges']);

        $firstProduct = $data['data']['product']['edges'][0]['node'];
        static::assertCount(2, $firstProduct);
        static::assertEquals('first product', $firstProduct['name']);
        static::assertEquals($firstProductId, $firstProduct['id']);

        $secondProduct = $data['data']['product']['edges'][1]['node'];
        static::assertCount(2, $secondProduct);
        static::assertEquals('second product', $secondProduct['name']);
        static::assertEquals($secondProductId, $secondProduct['id']);

        $thirdProduct = $data['data']['product']['edges'][2]['node'];
        static::assertCount(2, $thirdProduct);
        static::assertEquals('third product', $thirdProduct['name']);
        static::assertEquals($thirdProductId, $thirdProduct['id']);
        static::assertEquals(3, $data['data']['product']['total']);
    }

    public function testQueryProductWithFilter()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstProductId = $ids[0];
        $secondProductId = $ids[1];
        $thirdProductId = $ids[2];
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $firstProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'first product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
            [
                'id' => $secondProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'second product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $thirdProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'third product',
                'tax' => ['id' => $taxId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = "
            query {
	            product (
	                query:  {
	                    type: equals
	                    field: \"id\"
	                    value: \"{$firstProductId}\"
	                }
	            ) {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ";
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $firstProduct = $data['data']['product']['edges'][0]['node'];
        static::assertCount(2, $firstProduct);
        static::assertEquals('first product', $firstProduct['name']);
        static::assertEquals($firstProductId, $firstProduct['id']);

        static::assertEquals(1, $data['data']['product']['total']);
    }

    public function testQueryProductWithNestedFilter()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstProductId = $ids[0];
        $secondProductId = $ids[1];
        $thirdProductId = $ids[2];
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $firstProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'first product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
            [
                'id' => $secondProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test 2'],
                'name' => 'second product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $thirdProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'third product',
                'tax' => ['id' => $taxId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product (
	                sortBy: "id"
	                query:  {
	                    type: equals
	                    field: "manufacturer.name"
	                    value: "test"
	                }
	            ) {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(2, $data['data']['product']['edges']);

        $firstProduct = $data['data']['product']['edges'][0]['node'];
        static::assertCount(2, $firstProduct);
        static::assertEquals('first product', $firstProduct['name']);
        static::assertEquals($firstProductId, $firstProduct['id']);

        $secondProduct = $data['data']['product']['edges'][1]['node'];
        static::assertCount(2, $secondProduct);
        static::assertEquals('third product', $secondProduct['name']);
        static::assertEquals($thirdProductId, $secondProduct['id']);

        static::assertEquals(2, $data['data']['product']['total']);
    }

    public function testQueryProductWithPagination()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstProductId = $ids[0];
        $secondProductId = $ids[1];
        $thirdProductId = $ids[2];
        $fourthProductId = $ids[3];
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $firstProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'z product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
            [
                'id' => $secondProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'b product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $thirdProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'c product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $fourthProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'a product',
                'tax' => ['id' => $taxId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product (
	                sortBy: "name"
	                sortDirection: DESC
	                first: 2
	                after: "MQ=="
	            ) {
	                edges {
	                    node {
	                        id
		                    name
	                    }
	                }
	                total
	                pageInfo {
	                    hasNextPage
	                    endCursor
	                }
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(2, $data['data']['product']['edges']);

        $firstProduct = $data['data']['product']['edges'][0]['node'];
        static::assertCount(2, $firstProduct);
        static::assertEquals('c product', $firstProduct['name']);
        static::assertEquals($thirdProductId, $firstProduct['id']);

        $secondProduct = $data['data']['product']['edges'][1]['node'];
        static::assertCount(2, $secondProduct);
        static::assertEquals('b product', $secondProduct['name']);
        static::assertEquals($secondProductId, $secondProduct['id']);

        static::assertEquals(4, $data['data']['product']['total']);

        static::assertEquals(true, $data['data']['product']['pageInfo']['hasNextPage']);
        static::assertEquals('Mw==', $data['data']['product']['pageInfo']['endCursor']);
    }

    public function testQueryProductWithAggregation()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstProductId = $ids[0];
        $secondProductId = $ids[1];
        $thirdProductId = $ids[2];
        $fourthProductId = $ids[3];
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $firstProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'z product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
            [
                'id' => $secondProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'b product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $thirdProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'c product',
                'tax' => ['id' => $taxId],
            ],
            [
                'id' => $fourthProductId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'a product',
                'tax' => ['id' => $taxId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product (
	                aggregations: [
	                    {
	                        type: sum
	                        field: "tax.taxRate"
	                        name: "tax_sum"
	                    },
	                    {
	                        type: avg
	                        field: "tax.taxRate"
	                        name: "tax_avg"
	                    }
	                ]
	            ) {
	                total
	                aggregations {
	                    name
	                    results {
	                        type
	                        result
	                    }
	                }
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);

        static::assertEquals(4, $data['data']['product']['total']);

        static::assertCount(2, $data['data']['product']['aggregations']);

        $firstAggregation = $data['data']['product']['aggregations'][0];
        static::assertEquals('tax_sum', $firstAggregation['name']);
        static::assertEquals('sum', $firstAggregation['results'][0]['type']);
        static::assertEquals(52, $firstAggregation['results'][0]['result']);

        $secondAggregation = $data['data']['product']['aggregations'][1];
        static::assertEquals('tax_avg', $secondAggregation['name']);
        static::assertEquals('avg', $secondAggregation['results'][0]['type']);
        static::assertEquals(13, $secondAggregation['results'][0]['result']);
    }

    public function testQueryProductIncludesManyToOne()
    {
        $productId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
		                    manufacturer {
		                        name
		                    }
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $productResult = $data['data']['product']['edges'][0]['node'];
        static::assertCount(3, $productResult);
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($productId, $productResult['id']);
        static::assertCount(1, $productResult['manufacturer']);
        static::assertEquals('test', $productResult['manufacturer']['name']);

        static::assertEquals(1, $data['data']['product']['total']);
    }

    public function testQueryProductIncludesOneToMany()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstId = $ids[0];
        $secondId = $ids[1];
        $productId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();
        $ruleId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
                'priceRules' => [
                    [
                        'id' => $firstId,
                        'currencyId' => Defaults::CURRENCY,
                        'quantityStart' => 1,
                        'rule' => [
                            'id' => $ruleId,
                            'name' => 'test',
                            'payload' => new AndRule(),
                            'priority' => 1,
                        ],
                        'price' => ['gross' => 15, 'net' => 10],
                    ],
                    [
                        'id' => $secondId,
                        'currencyId' => Defaults::CURRENCY,
                        'quantityStart' => 5,
                        'rule' => [
                            'id' => $ruleId,
                            'name' => 'test',
                            'payload' => new AndRule(),
                            'priority' => 1,
                        ],
                        'price' => ['gross' => 10, 'net' => 5],
                    ],
                ],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
		                    priceRules {
		                        edges {
		                            node {
		                            	quantityStart
		                            }
		                        }
		                    }
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $productResult = $data['data']['product']['edges'][0]['node'];
        static::assertCount(3, $productResult);
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($productId, $productResult['id']);
        static::assertCount(2, $productResult['priceRules']['edges']);

        $firstPriceRule =  $productResult['priceRules']['edges'][0]['node'];
        static::assertCount(1, $firstPriceRule);
        static::assertEquals(1,  $firstPriceRule['quantityStart']);

        $secondPriceRule =  $productResult['priceRules']['edges'][1]['node'];
        static::assertCount(1, $secondPriceRule);
        static::assertEquals(5,  $secondPriceRule['quantityStart']);

        static::assertEquals(1, $data['data']['product']['total']);
    }

    public function testQueryProductIncludesOneToManyNested()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstId = $ids[0];
        $secondId = $ids[1];
        $productId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();
        $firstRuleId = Uuid::uuid4()->getHex();
        $secondRuleId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
                'priceRules' => [
                    [
                        'id' => $firstId,
                        'currencyId' => Defaults::CURRENCY,
                        'quantityStart' => 1,
                        'rule' => [
                            'id' => $firstRuleId,
                            'name' => 'first Rule',
                            'payload' => new AndRule(),
                            'priority' => 1,
                        ],
                        'price' => ['gross' => 15, 'net' => 10],
                    ],
                    [
                        'id' => $secondId,
                        'currencyId' => Defaults::CURRENCY,
                        'quantityStart' => 5,
                        'rule' => [
                            'id' => $secondRuleId,
                            'name' => 'second Rule',
                            'payload' => new AndRule(),
                            'priority' => 1,
                        ],
                        'price' => ['gross' => 10, 'net' => 5],
                    ],
                ],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
		                    priceRules {
		                        edges {
		                            node {
		                            	quantityStart
		                            	rule {
		                            	    name
		                            	}
		                            }
		                        }
		                    }
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $productResult = $data['data']['product']['edges'][0]['node'];
        static::assertCount(3, $productResult);
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($productId, $productResult['id']);
        static::assertCount(2, $productResult['priceRules']['edges']);

        $firstPriceRule =  $productResult['priceRules']['edges'][0]['node'];
        static::assertCount(2, $firstPriceRule);
        static::assertEquals(1,  $firstPriceRule['quantityStart']);
        static::assertCount(1, $firstPriceRule['rule']);
        static::assertEquals('first Rule',  $firstPriceRule['rule']['name']);

        $secondPriceRule =  $productResult['priceRules']['edges'][1]['node'];
        static::assertCount(2, $secondPriceRule);
        static::assertEquals(5,  $secondPriceRule['quantityStart']);
        static::assertCount(1, $secondPriceRule['rule']);
        static::assertEquals('second Rule',  $secondPriceRule['rule']['name']);

        static::assertEquals(1, $data['data']['product']['total']);
    }

    public function testQueryProductIncludesManyToManyOnce()
    {
        $ids = [Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex()];
        sort($ids);
        $firstId = $ids[0];
        $secondId = $ids[1];
        $productId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['id' => $taxId, 'taxRate' => 13, 'name' => 'green'],
                'categories' => [
                    [
                        'id' => $firstId,
                        'name' => 'first Category'
                    ],
                    [
                        'id' => $secondId,
                        'name' => 'second Category'
                    ],
                ]
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = '
            query {
	            product {
	                edges {
	                    node {
	                        id
		                    name
		                    categories {
		                        edges {
		                            node {
		                                name
		                            }
		                        }
		                    }
	                    }
	                }
	                total
	            }
            }
        ';
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        static::assertArrayNotHasKey('error', $data);
        static::assertCount(1, $data['data']['product']['edges']);

        $productResult = $data['data']['product']['edges'][0]['node'];
        static::assertCount(3, $productResult);
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($productId, $productResult['id']);
        static::assertCount(2, $productResult['categories']['edges']);

        $firstCategory =  $productResult['categories']['edges'][0]['node'];
        static::assertCount(1, $firstCategory);
        static::assertEquals('first Category', $firstCategory['name']);

        $secondCategory =  $productResult['categories']['edges'][1]['node'];
        static::assertCount(1, $secondCategory);
        static::assertEquals('second Category', $secondCategory['name']);
    }

    public function testMutationProduct()
    {
        $manufacturerId = Uuid::uuid4()->getHex();
        $query = "
            mutation {
	            upsert_product(
	                name: \"product\" 
	                manufacturer: {
	                    id: \"{$manufacturerId}\"
	                    name: \"test\"
	                }
	                tax: {taxRate: 13, name: \"tax\"}
	                price: \"{\\\"gross\\\": 10, \\\"net\\\": 9}\"
	            ) {
		            id
		            name
		            manufacturer {
		                id
		            }
	            }
            }
        ";
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        static::assertArrayNotHasKey('error', $data);
        static::assertCount(3, $data['data']['upsert_product']);

        $productResult = $data['data']['upsert_product'];
        static::assertEquals('product', $productResult['name']);
        static::assertEquals($manufacturerId, $productResult['manufacturer']['id']);
    }

    public function testMutationDeleteProduct()
    {
        $productId = Uuid::uuid4()->getHex();

        $products = [
            [
                'id' => $productId,
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['name' => 'test'],
                'name' => 'product',
                'tax' => ['taxRate' => 13, 'name' => 'green'],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $query = "
            mutation {
	            delete_product(
	                id: \"{$productId}\"
	            )
            }
        ";
        $request = $this->createPostJsonRequest($query);
        $response = $this->apiController->query($request, $this->context);
        static::assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        static::assertArrayNotHasKey('error', $data);
        static::assertEquals($productId, $data['data']['delete_product']);

        static::assertCount(0, $this->repository->read(new ReadCriteria([$productId]), Context::createDefaultContext())->getIds());
    }

    private function createPostJsonRequest(string $query): Request
    {
        $request = Request::create(
            'localhost',
            Request::METHOD_POST,
            [],
            [],
            [],
            [],
            json_encode(['query' => $query])
        );
        $request->headers->add(['content_type' => 'application/json']);

        return $request;
    }
}
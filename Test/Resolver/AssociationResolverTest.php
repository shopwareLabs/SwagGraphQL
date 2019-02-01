<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagGraphQL\Resolver\AssociationResolver;

class AssociationResolverTest extends TestCase
{
    public function testAddsNoAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ]
        ], ProductDefinition::class);

        static::assertEmpty($criteria->getAssociations());
    }

    public function testAddsToOneAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ],
            'manufacturer' => [
                'type' => new ObjectType(['name' => 'test']),
                'args' => [],
                'fields' => [
                    'name' => [
                        'type' => Type::string(),
                        'args' => [],
                        'fields' => []
                    ]
                ]
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.manufacturer', $criteria->getAssociations());
        static::assertInstanceOf(Criteria::class, $criteria->getAssociations()['product.manufacturer']);
    }

    public function testAddsToManyAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ],
            'categories' => [
                'type' => new ObjectType(['name' => 'test']),
                'args' => [],
                'fields' => [
                    'name' => [
                        'type' => Type::string(),
                        'args' => [],
                        'fields' => []
                    ]
                ]
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.categories', $criteria->getAssociations());
        static::assertInstanceOf(Criteria::class, $criteria->getAssociations()['product.categories']);
    }

    public function testAddsNestedAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ],
            'categories' => [
                'type' => new ObjectType(['name' => 'test']),
                'args' => [],
                'fields' => [
                    'name' => [
                        'type' => Type::string(),
                        'args' => [],
                        'fields' => []
                    ],
                    'parent' => [
                        'type' => new ObjectType(['name' => 'test']),
                        'args' => [],
                        'fields' => [
                            'name' => [
                                'type' => Type::string(),
                                'args' => [],
                                'fields' => []
                            ]
                        ]
                    ]
                ]
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.categories', $criteria->getAssociations());

        /** @var Criteria $nested */
        $nested = $criteria->getAssociations()['product.categories'];
        static::assertInstanceOf(Criteria::class, $nested);

        static::assertArrayHasKey('category.parent', $nested->getAssociations());
        static::assertInstanceOf(Criteria::class, $nested->getAssociations()['category.parent']);
    }

    public function testAddsNestedAssociationIgnoresTechnicalFields()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ],
            'categories' => [
                'type' => new ObjectType(['name' => 'test']),
                'args' => [],
                'fields' => [
                    'edges' => [
                        'type' => new ObjectType(['name' => 'test']),
                        'args' => [],
                        'fields' => [
                            'node' => [
                                'type' => new ObjectType(['name' => 'test']),
                                'args' => [],
                                'fields' => [
                                    'name' => [
                                        'type' => Type::string(),
                                        'args' => [],
                                        'fields' => []
                                    ],
                                    'parent' => [
                                        'type' => new ObjectType(['name' => 'test']),
                                        'args' => [],
                                        'fields' => [
                                            'name' => [
                                                'type' => Type::string(),
                                                'args' => [],
                                                'fields' => []
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]

                ]
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.categories', $criteria->getAssociations());

        /** @var Criteria $nested */
        $nested = $criteria->getAssociations()['product.categories'];
        static::assertInstanceOf(Criteria::class, $nested);

        static::assertArrayHasKey('category.parent', $nested->getAssociations());
        static::assertInstanceOf(Criteria::class, $nested->getAssociations()['category.parent']);
    }

    public function testAddsNestedAssociationWithArgs()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => [
                'type' => Type::id(),
                'args' => [],
                'fields' => []
            ],
            'name' => [
                'type' => Type::string(),
                'args' => [],
                'fields' => []
            ],
            'categories' => [
                'type' => new ObjectType(['name' => 'test']),
                'args' => [
                    'sortBy' => 'name',
                    'sortDirection' => 'DESC'
                ],
                'fields' => [
                    'edges' => [
                        'type' => new ObjectType(['name' => 'test']),
                        'args' => [],
                        'fields' => [
                            'node' => [
                                'type' => new ObjectType(['name' => 'test']),
                                'args' => [],
                                'fields' => [
                                    'name' => [
                                        'type' => Type::string(),
                                        'args' => [],
                                        'fields' => []
                                    ],
                                    'parent' => [
                                        'type' => new ObjectType(['name' => 'test']),
                                        'args' => [],
                                        'fields' => [
                                            'name' => [
                                                'type' => Type::string(),
                                                'args' => [],
                                                'fields' => []
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]

                ]
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.categories', $criteria->getAssociations());

        /** @var Criteria $nested */
        $nested = $criteria->getAssociations()['product.categories'];
        static::assertInstanceOf(Criteria::class, $nested);
        static::assertEquals('name', $nested->getSorting()[0]->getField());
        static::assertEquals(FieldSorting::DESCENDING, $nested->getSorting()[0]->getDirection());

        static::assertArrayHasKey('category.parent', $nested->getAssociations());
        static::assertInstanceOf(Criteria::class, $nested->getAssociations()['category.parent']);
    }
}
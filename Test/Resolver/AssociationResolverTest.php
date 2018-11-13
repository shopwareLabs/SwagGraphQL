<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagGraphQL\Resolver\AssociationResolver;

class AssociationResolverTest extends TestCase
{
    public function testAddsNoAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => true,
            'name' => true
        ], ProductDefinition::class);

        static::assertEmpty($criteria->getAssociations());
    }

    public function testAddsToOneAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => true,
            'name' => true,
            'manufacturer' => [
                'name' => true
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.manufacturer', $criteria->getAssociations());
        static::assertInstanceOf(Criteria::class, $criteria->getAssociations()['product.manufacturer']);
    }

    public function testAddsToManyAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => true,
            'name' => true,
            'categories' => [
                'name' => true
            ]
        ], ProductDefinition::class);

        static::assertArrayHasKey('product.categories', $criteria->getAssociations());
        static::assertInstanceOf(Criteria::class, $criteria->getAssociations()['product.categories']);
    }

    public function testAddsNestedAssociation()
    {
        $criteria = new Criteria();
        AssociationResolver::addAssociations($criteria, [
            'id' => true,
            'name' => true,
            'categories' => [
                'name' => true,
                'parent' => [
                    'name' => true
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
            'id' => true,
            'name' => true,
            'categories' => [
                'edges' => [
                    'node' => [
                        'name' => true,
                        'parent' => [
                            'name' => true
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
}
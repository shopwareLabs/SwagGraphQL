<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MinAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\StatsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagGraphQL\Resolver\CriteriaParser;

class CriteriaParserTest extends TestCase
{
    public function testParsePaginationForward()
    {
        $criteria = CriteriaParser::buildCriteria([
            'first' => 5,
            'after' => base64_encode('10')
        ],
            ProductDefinition::class
        );

        static::assertEquals(Criteria::TOTAL_COUNT_MODE_EXACT, $criteria->getTotalCountMode());
        static::assertEquals(5, $criteria->getLimit());
        static::assertEquals(10, $criteria->getOffset());
    }

    public function testParsePaginationBackward()
    {
        $criteria = CriteriaParser::buildCriteria([
            'last' => 5,
            'before' => base64_encode('15')
        ],
            ProductDefinition::class
        );

        static::assertEquals(Criteria::TOTAL_COUNT_MODE_EXACT, $criteria->getTotalCountMode());
        static::assertEquals(5, $criteria->getLimit());
        static::assertEquals(10, $criteria->getOffset());
    }

    public function testParseSorting()
    {
        $criteria = CriteriaParser::buildCriteria([
            'sortBy' => 'id',
            'sortDirection' => FieldSorting::DESCENDING
        ],
            ProductDefinition::class
        );

        static::assertEquals('id', $criteria->getSorting()[0]->getField());
        static::assertEquals(FieldSorting::DESCENDING, $criteria->getSorting()[0]->getDirection());
    }

    public function testParseEqualsQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'equals',
                'field' => 'id',
                'value' => 'test'
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('product.id', $criteria->getFilters()[0]->getField());
        static::assertEquals('test', $criteria->getFilters()[0]->getValue());
    }

    public function testParseEqualsAnyQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'equalsAny',
                'field' => 'id',
                'value' => 'test|fancy'
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(EqualsAnyFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('product.id', $criteria->getFilters()[0]->getField());
        static::assertEquals('test', $criteria->getFilters()[0]->getValue()[0]);
        static::assertEquals('fancy', $criteria->getFilters()[0]->getValue()[1]);
    }

    public function testParseContainsQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'contains',
                'field' => 'id',
                'value' => 'test'
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(ContainsFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('product.id', $criteria->getFilters()[0]->getField());
        static::assertEquals('test', $criteria->getFilters()[0]->getValue());
    }

    public function testParseRangeQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'range',
                'field' => 'id',
                'parameters' => [
                    [
                        'operator' => 'gt',
                        'value' => 5
                    ],
                    [
                        'operator' => 'lt',
                        'value' => 10
                    ],
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(RangeFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('product.id', $criteria->getFilters()[0]->getField());
        static::assertEquals(5, $criteria->getFilters()[0]->getParameter('gt'));
        static::assertEquals(10, $criteria->getFilters()[0]->getParameter('lt'));
    }

    public function testParseNotQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'not',
                'operator' => 'AND',
                'queries' => [
                    [
                        'type' => 'equals',
                        'field' => 'id',
                        'value' => 'test'
                    ]
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(NotFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('AND', $criteria->getFilters()[0]->getOperator());

        $inner =  $criteria->getFilters()[0]->getQueries()[0];
        static::assertInstanceOf(EqualsFilter::class, $inner);
        static::assertEquals('product.id', $inner->getField());
        static::assertEquals('test', $inner->getValue());
    }

    public function testParseMultiQuery()
    {
        $criteria = CriteriaParser::buildCriteria([
            'query' => [
                'type' => 'multi',
                'operator' => 'AND',
                'queries' => [
                    [
                        'type' => 'equals',
                        'field' => 'id',
                        'value' => 'test'
                    ],
                    [
                        'type' => 'equalsAny',
                        'field' => 'id',
                        'value' => 'test|fancy'
                    ]
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(MultiFilter::class, $criteria->getFilters()[0]);
        static::assertEquals('AND', $criteria->getFilters()[0]->getOperator());

        $first =  $criteria->getFilters()[0]->getQueries()[0];
        static::assertInstanceOf(EqualsFilter::class, $first);
        static::assertEquals('product.id', $first->getField());
        static::assertEquals('test', $first->getValue());

        $second =  $criteria->getFilters()[0]->getQueries()[1];
        static::assertInstanceOf(EqualsAnyFilter::class, $second);
        static::assertEquals('product.id', $second->getField());
        static::assertEquals('test', $second->getValue()[0]);
        static::assertEquals('fancy', $second->getValue()[1]);
    }

    public function testParseMaxAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'max',
                    'field' => 'id',
                    'name' => 'max_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(MaxAggregation::class, $criteria->getAggregations()['max_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['max_id']->getField());
        static::assertEquals('max_id', $criteria->getAggregations()['max_id']->getName());
    }

    public function testParseMinAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'min',
                    'field' => 'id',
                    'name' => 'min_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(MinAggregation::class, $criteria->getAggregations()['min_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['min_id']->getField());
        static::assertEquals('min_id', $criteria->getAggregations()['min_id']->getName());
    }

    public function testParseAvgAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'avg',
                    'field' => 'id',
                    'name' => 'avg_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(AvgAggregation::class, $criteria->getAggregations()['avg_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['avg_id']->getField());
        static::assertEquals('avg_id', $criteria->getAggregations()['avg_id']->getName());
    }

    public function testParseCountAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'count',
                    'field' => 'id',
                    'name' => 'count_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(CountAggregation::class, $criteria->getAggregations()['count_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['count_id']->getField());
        static::assertEquals('count_id', $criteria->getAggregations()['count_id']->getName());
    }

    public function testParseValueCountAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'value_count',
                    'field' => 'id',
                    'name' => 'value_count_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(ValueCountAggregation::class, $criteria->getAggregations()['value_count_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['value_count_id']->getField());
        static::assertEquals('value_count_id', $criteria->getAggregations()['value_count_id']->getName());
    }

    public function testParseStatsAggregation()
    {
        $criteria = CriteriaParser::buildCriteria([
            'aggregations' => [
                [
                    'type' => 'stats',
                    'field' => 'id',
                    'name' => 'stats_id'
                ]
            ]
        ],
            ProductDefinition::class
        );

        static::assertInstanceOf(StatsAggregation::class, $criteria->getAggregations()['stats_id']);
        static::assertEquals('product.id', $criteria->getAggregations()['stats_id']->getField());
        static::assertEquals('stats_id', $criteria->getAggregations()['stats_id']->getName());
    }
}
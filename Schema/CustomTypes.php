<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagGraphQL\Types\DateType;
use SwagGraphQL\Types\JsonType;

class CustomTypes
{
    /** @var DateType */
    private static $dateType;

    /** @var JsonType */
    private static $jsonType;

    /** @var EnumType */
    private static $sortDirection;

    /** @var ObjectType */
    private static $pageInfo;

    /** @var InputObjectType */
    private static $query;

    /** @var EnumType */
    private static $queryOperator;

    /** @var EnumType */
    private static $queryTypes;

    /** @var EnumType */
    private static $rangeOperator;

    /** @var EnumType */
    private static $aggregationTypes;

    /** @var InputObjectType */
    private static $aggregation;

    /** @var ObjectType */
    private static $aggregationResult;

    // Custom Scalars
    public function date(): DateType
    {
        if (static::$dateType === null) {
            static::$dateType = new DateType();
        }

        return static::$dateType;
    }

    public function json(): JsonType
    {
        if (static::$jsonType === null) {
            static::$jsonType = new JsonType();
        }

        return static::$jsonType;
    }

    // Enums
    public function sortDirection(): EnumType
    {
        if (static::$sortDirection === null) {
            static::$sortDirection = new EnumType([
                'name' => 'SortDirection',
                'values' => [
                    'ASC' => [
                        'value' => FieldSorting::ASCENDING
                    ],
                    'DESC' => [
                        'value' => FieldSorting::DESCENDING
                    ]
                ]
            ]);
        }

        return static::$sortDirection;
    }

    public function queryOperator(): EnumType
    {
        if (static::$queryOperator === null) {
            static::$queryOperator = new EnumType([
                'name' => 'QueryOperator',
                'values' => [
                    'AND' => [
                        'value' => MultiFilter::CONNECTION_AND
                    ],
                    'OR' => [
                        'value' => MultiFilter::CONNECTION_OR
                    ]
                ]
            ]);
        }

        return static::$queryOperator;
    }

    public function rangeOperator(): EnumType
    {
        if (static::$rangeOperator === null) {
            static::$rangeOperator = new EnumType([
                'name' => 'RangeOperator',
                'values' => [
                    'GTE' => [
                        'value' => RangeFilter::GTE
                    ],
                    'GT' => [
                        'value' => RangeFilter::GT
                    ],
                    'LTE' => [
                        'value' => RangeFilter::LTE
                    ],
                    'LT' => [
                        'value' => RangeFilter::LT
                    ]
                ]
            ]);
        }

        return static::$rangeOperator;
    }

    public function queryTypes(): EnumType
    {
        if (static::$queryTypes === null) {
            static::$queryTypes = new EnumType([
                'name' => 'QueryTypes',
                'values' => ['equals', 'contains', 'equalsAny', 'multi', 'not', 'range']
            ]);
        }

        return static::$queryTypes;
    }

    public function aggregationTypes(): EnumType
    {
        if (static::$aggregationTypes === null) {
            static::$aggregationTypes = new EnumType([
                'name' => 'AggregationTypes',
                'values' => ['avg', 'cardinality', 'count', 'max', 'min', 'stats', 'sum', 'value_count']
            ]);
        }

        return static::$aggregationTypes;
    }

    // Objects
    public function pageInfo(): ObjectType
    {
        if (static::$pageInfo === null) {
            static::$pageInfo = new ObjectType([
                'name' => 'PageInfo',
                'fields' => [
                    'endCursor' => ['type' => Type::id()],
                    'startCursor' => ['type' => Type::id()],
                    'hasNextPage' => ['type' => Type::boolean()],
                    'hasPreviousPage' => ['type' => Type::boolean()]
                ]
            ]);
        }

        return static::$pageInfo;
    }

    public function aggregationResult(): ObjectType
    {
        if (static::$aggregationResult === null) {
            static::$aggregationResult = new ObjectType([
                'name' => 'AggregationResults',
                'fields' => [
                    'name' => ['type' => Type::string()],
                    'results' => ['type' => Type::listOf(new ObjectType([
                        'name' => 'AggregationResult',
                        'fields' => [
                            'type' => ['type' => Type::string()],
                            'result' => ['type' => Type::float()]
                        ]
                    ]))]

                ]
            ]);
        }

        return static::$aggregationResult;
    }

    // Inputs
    public function query(): InputObjectType
    {
        if (static::$query === null) {
            static::$query = new InputObjectType([
                'name' => 'SearchQuery',
                'fields' => function () {
                    return [
                        'type' => Type::nonNull(static::queryTypes()),
                        'operator' => static::queryOperator(),
                        'queries' => Type::listOf(static::query()),
                        'field' => Type::string(),
                        'value' => Type::string(),
                        'parameters' => Type::listOf(new InputObjectType([
                            'name' => 'Parameter',
                            'fields' => [
                                'operator' => Type::nonNull(static::rangeOperator()),
                                'value' => Type::nonNull(Type::float())
                            ]
                        ]))
                    ];
                }
            ]);
        }

        return static::$query;
    }

    public function aggregation(): InputObjectType
    {
        if (static::$aggregation === null) {
            static::$aggregation = new InputObjectType([
                'name' => 'Aggregation',
                'fields' => function () {
                    return [
                        'type' => Type::nonNull(static::aggregationTypes()),
                        'name' => Type::nonNull(Type::string()),
                        'field' => Type::nonNull(Type::string()),
                    ];
                }
            ]);
        }

        return static::$aggregation;
    }
}
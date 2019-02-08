<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagGraphQL\Schema\SchemaBuilder\EnumBuilder;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilder;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\SchemaBuilder\ObjectBuilder;
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
            static::$sortDirection = EnumBuilder::create('SortDirection')
                ->addValue(FieldSorting::ASCENDING, 'ASC', 'Ascending sort direction')
                ->addValue(FieldSorting::DESCENDING, 'DESC', 'Descending sort direction')
                ->setDescription('The possible sort directions')
                ->build();
        }

        return static::$sortDirection;
    }

    public function queryOperator(): EnumType
    {
        if (static::$queryOperator === null) {
            static::$queryOperator = EnumBuilder::create('QueryOperator')
                ->addValue(MultiFilter::CONNECTION_AND, 'AND', 'Combines the queries using logical "and"')
                ->addValue(MultiFilter::CONNECTION_OR, 'OR', 'Combines the queries using logical "or"')
                ->setDescription('The possible operators to combine queries')
                ->build();
        }

        return static::$queryOperator;
    }

    public function rangeOperator(): EnumType
    {
        if (static::$rangeOperator === null) {
            static::$rangeOperator = EnumBuilder::create('RangeOperator')
                ->addValue(RangeFilter::GTE, 'GTE', 'Greater than or equals')
                ->addValue(RangeFilter::GT, 'GT', 'Greater than')
                ->addValue(RangeFilter::LTE, 'LTE', 'Less than or equals')
                ->addValue(RangeFilter::LT, 'LT', 'Less than')
                ->setDescription('The possible operators for range queries')
                ->build();
        }

        return static::$rangeOperator;
    }

    public function queryTypes(): EnumType
    {
        if (static::$queryTypes === null) {
            static::$queryTypes = EnumBuilder::create('QueryTypes')
                ->addValue('equals', null, 'Performs an equals query')
                ->addValue('contains', null, 'Performs a contains query')
                ->addValue('equalsAny', null, 'Performs an equalsAny query')
                ->addValue('multi', null, 'Combines multiple queries')
                ->addValue('not', null, 'Inverts an query')
                ->addValue('range', null, 'Performs a range query')
                ->setDescription('The QueryTypes the DAL can perform')
                ->build();
        }

        return static::$queryTypes;
    }

    public function aggregationTypes(): EnumType
    {
        if (static::$aggregationTypes === null) {
            static::$aggregationTypes = EnumBuilder::create('AggregationTypes')
                ->addValue('avg', null, 'Performs an average aggregation')
                ->addValue('cardinality', null, 'Performs a cardinality aggregation')
                ->addValue('count', null, 'Performs a count aggregation')
                ->addValue('max', null, 'Performs a maximum aggregation')
                ->addValue('min', null, 'Performs a minimum aggregation')
                ->addValue('stats', null, 'Performs a stats aggregation')
                ->addValue('sum', null, 'Performs a sum aggregation')
                ->addValue('value_count', null, 'Performs a value count aggregation')
                ->setDescription('The AggregationTypes the DAL can perform')
                ->build();
        }

        return static::$aggregationTypes;
    }

    // Objects
    public function pageInfo(): ObjectType
    {
        if (static::$pageInfo === null) {
            static::$pageInfo = ObjectBuilder::create('PageInfo')
                ->addField(FieldBuilder::create('endCursor', Type::id())->setDescription('The cursor to the last element in the current Connection'))
                ->addField(FieldBuilder::create('startCursor', Type::id())->setDescription('The cursor to the first element in the current Connection'))
                ->addField(FieldBuilder::create('hasNextPage', Type::boolean())->setDescription('Shows if there are more Items'))
                ->addField(FieldBuilder::create('hasPreviousPage', Type::boolean())->setDescription('Shows if there are previous Items'))
                ->setDescription('Contains information about the current Page fetched from the Connection')
                ->build();
        }

        return static::$pageInfo;
    }

    public function aggregationResult(): ObjectType
    {
        if (static::$aggregationResult === null) {
            static::$aggregationResult = ObjectBuilder::create('AggregationResults')
                ->addField(FieldBuilder::create('name', Type::string())->setDescription('Name of the AggregationResults'))
                ->addField(FieldBuilder::create('results', Type::listOf(
                    ObjectBuilder::create('AggregationResult')
                    ->addField(FieldBuilder::create('type', Type::string())->setDescription('The type of the aggregation'))
                    ->addField(FieldBuilder::create('result', Type::string())->setDescription('The result of the aggregation'))
                    ->setDescription('Contains the result of a single aggregation')
                    ->build()
                ))->setDescription('Contains an aggregationResult'))
                ->setDescription('Contains the results of the aggregations')
                ->build();
        }

        return static::$aggregationResult;
    }

    // Inputs
    public function query(): InputObjectType
    {
        if (static::$query === null) {
            static::$query = ObjectBuilder::create('SearchQuery')
                ->addLazyFieldCollection(function () { return FieldBuilderCollection::create()
                    ->addFieldBuilder(FieldBuilder::create('type', Type::nonNull(static::queryTypes()))->setDescription('The query type'))
                    ->addFieldBuilder(FieldBuilder::create('operator', static::queryOperator())->setDescription('The operator used to combine the queries'))
                    ->addFieldBuilder(FieldBuilder::create('queries', Type::listOf(static::query()))->setDescription('A nested list of SearchQueries'))
                    ->addFieldBuilder(FieldBuilder::create('field', Type::string())->setDescription('The field used in the Query'))
                    ->addFieldBuilder(FieldBuilder::create('value', Type::string())->setDescription('The value with which the field will be compared'))
                    ->addFieldBuilder(FieldBuilder::create('parameters', Type::listOf(
                        ObjectBuilder::create('Parameter')
                            ->addField(FieldBuilder::create('operator', Type::nonNull(static::rangeOperator()))->setDescription('The operator used to compare the field and the value'))
                            ->addField(FieldBuilder::create('value', Type::nonNull(Type::float()))->setDescription('The value with which the field will be compared'))
                            ->buildAsInput()
                    ))->setDescription('A list of parameters with which the field will be compared in a Range Query'));
                })
                ->setDescription('The DAL query that is used to filter the Items')
                ->buildAsInput();
        }

        return static::$query;
    }

    public function aggregation(): InputObjectType
    {
        if (static::$aggregation === null) {
            static::$aggregation = ObjectBuilder::create('Aggregation')
                ->addLazyFieldCollection(function () { return FieldBuilderCollection::create()
                    ->addFieldBuilder(FieldBuilder::create('type', Type::nonNull(static::aggregationTypes()))->setDescription('The aggregation type'))
                    ->addFieldBuilder(FieldBuilder::create('name', Type::nonNull(Type::string()))->setDescription('The name of the aggregation'))
                    ->addFieldBuilder(FieldBuilder::create('field', Type::nonNull(Type::string()))->setDescription('The field used to aggregate'));
                })
                ->setDescription('A Aggregation the DAL should perform')
                ->buildAsInput();
        }

        return static::$aggregation;
    }
}
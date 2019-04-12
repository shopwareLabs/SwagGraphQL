<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\CountryDefinition;
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
    private static $taxRule;

    /** @var ObjectType */
    private static $aggregationResult;

    /** @var ObjectType */
    private static $deliveryDate;

    /** @var ObjectType */
    private static $deliveryInformation;

    /** @var ObjectType */
    private static $calculatedTax;

    /** @var ObjectType */
    private static $calculatedPrice;

    /** @var ObjectType */
    private static $lineItem;

    /** @var ObjectType */
    private static $cartPrice;

    /** @var ObjectType */
    private static $cart;

    /** @var ObjectType */
    private static $transaction;

    /** @var ObjectType */
    private static $shippingLocation;

    /** @var ObjectType */
    private static $deliveryPosition;

    /** @var ObjectType */
    private static $delivery;

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
                ->addField(FieldBuilder::create('name', Type::string())->setDescription('Name of the aggregation'))
                ->addField(FieldBuilder::create('buckets', Type::listOf(
                    ObjectBuilder::create('AggregationBucket')
                        ->addField(FieldBuilder::create('keys', Type::listOf(
                            ObjectBuilder::create('AggregationKey')
                                ->addField(FieldBuilder::create('field', Type::string())->setDescription('The field used to group the result'))
                                ->addField(FieldBuilder::create('value', Type::string())->setDescription('The value for the groupByKey'))
                                ->setDescription('A key of the Aggregation Bucket')
                                ->build()
                        ))->setDescription('The Keys of this aggregation bucket'))
                        ->addField(FieldBuilder::create('results', Type::listOf(
                            ObjectBuilder::create('AggregationResult')
                                ->addField(FieldBuilder::create('type', Type::string())->setDescription('The type of the aggregation'))
                                ->addField(FieldBuilder::create('result', Type::string())->setDescription('The result of the aggregation'))
                                ->setDescription('Contains the result of a single aggregation')
                                ->build()
                        ))->setDescription('The result of the aggregation'))
                        ->setDescription('Contains the result of a single aggregation')
                        ->build()
                ))
                    ->setDescription('Contains an aggregationResult'))
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
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
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
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('type', Type::nonNull(static::aggregationTypes()))->setDescription('The aggregation type'))
                        ->addFieldBuilder(FieldBuilder::create('name', Type::nonNull(Type::string()))->setDescription('The name of the aggregation'))
                        ->addFieldBuilder(FieldBuilder::create('field', Type::nonNull(Type::string()))->setDescription('The field used to aggregate'))
                        ->addFieldBuilder(FieldBuilder::create('groupByFields', Type::listOf(Type::string()))->setDescription('The fields used to group the result'));
                })
                ->setDescription('A Aggregation the DAL should perform')
                ->buildAsInput();
        }

        return static::$aggregation;
    }

    public function taxRule(): ObjectType
    {
        if (static::$taxRule === null) {
            static::$taxRule = ObjectBuilder::create('TaxRule')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('taxRate', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('percentage', Type::nonNull(Type::float())));
            })
                ->setDescription('A TaxRule inside a cart')
                ->build();
        }

        return static::$taxRule;
    }

    public function calculatedTax(): ObjectType
    {
        if (static::$calculatedTax === null) {
            static::$calculatedTax = ObjectBuilder::create('CalculatedTax')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('tax', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('taxRate', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('price', Type::nonNull(Type::float())));
                })
                ->setDescription('The calculated Tax for a calculated Price')
                ->build();
        }

        return static::$calculatedTax;
    }

    public function calculatedPrice(): ObjectType
    {
        if (static::$calculatedPrice === null) {
            static::$calculatedPrice = ObjectBuilder::create('CalculatedPrice')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('unitPrice', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('quantity', Type::nonNull(Type::int())))
                        ->addFieldBuilder(FieldBuilder::create('totalPrice', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('calculatedTaxes', Type::listOf(static::calculatedTax())))
                        ->addFieldBuilder(FieldBuilder::create('taxRules', Type::listOf(static::taxRule())));

                })
                ->setDescription('The calculated PRice for a LineItem')
                ->build();
        }

        return static::$calculatedPrice;
    }

    public function deliveryDate(): ObjectType
    {
        if (static::$deliveryDate === null) {
            static::$deliveryDate = ObjectBuilder::create('DeliveryDate')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('earliest', static::date()))
                        ->addFieldBuilder(FieldBuilder::create('latest', static::date()));
            })
                ->setDescription('A DeliveryDate for a LineItem')
                ->build();
        }

        return static::$deliveryDate;
    }

    public function deliveryInformation(): ObjectType
    {
        if (static::$deliveryInformation === null) {
            static::$deliveryInformation = ObjectBuilder::create('DeliveryInformation')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('stock', Type::int()))
                        ->addFieldBuilder(FieldBuilder::create('weight', Type::float()))
                        ->addFieldBuilder(FieldBuilder::create('freeDelivery', Type::boolean()))
                        ->addFieldBuilder(FieldBuilder::create('inStockDeliveryDate', static::deliveryDate()))
                        ->addFieldBuilder(FieldBuilder::create('outOfStockDeliveryDate', static::deliveryDate()));

                })
                ->setDescription('The delivery Information for a LineItem')
                ->build();
        }

        return static::$deliveryInformation;
    }

    public function lineItem(TypeRegistry $typeRegistry): ObjectType
    {
        if (static::$lineItem === null) {
            static::$lineItem = ObjectBuilder::create('LineItem')
                ->addLazyFieldCollection(function () use ($typeRegistry) {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('key', Type::nonNull(Type::string())))
                        ->addFieldBuilder(FieldBuilder::create('label', Type::string()))
                        ->addFieldBuilder(FieldBuilder::create('quantity', Type::nonNull(Type::int())))
                        ->addFieldBuilder(FieldBuilder::create('type', Type::nonNull(Type::string())))
                        ->addFieldBuilder(FieldBuilder::create('payload', static::json()))
                        ->addFieldBuilder(FieldBuilder::create('price', static::calculatedPrice()))
                        ->addFieldBuilder(FieldBuilder::create('good', Type::nonNull(Type::boolean())))
                        ->addFieldBuilder(FieldBuilder::create('priority', Type::nonNull(Type::int())))
                        ->addFieldBuilder(FieldBuilder::create('description', Type::string()))
                        ->addFieldBuilder(FieldBuilder::create('cover', $typeRegistry->getObjectForDefinition(MediaDefinition::class)))
                        ->addFieldBuilder(FieldBuilder::create('deliveryInformation', static::deliveryInformation()))
                        ->addFieldBuilder(FieldBuilder::create('children', Type::listOf(static::lineItem($typeRegistry))))
                        ->addFieldBuilder(FieldBuilder::create('removable', Type::nonNull(Type::boolean())))
                        ->addFieldBuilder(FieldBuilder::create('stackable', Type::nonNull(Type::boolean())));
                })
                ->setDescription('A LineItem in the Cart')
                ->build();
        }

        return static::$lineItem;
    }

    public function cartPrice(): ObjectType
    {
        if (static::$cartPrice === null) {
            static::$cartPrice = ObjectBuilder::create('CartPrice')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('netPrice', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('totalPrice', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('positionPrice', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('taxStatus', Type::nonNull(Type::string())))
                        ->addFieldBuilder(FieldBuilder::create('calculatedTaxes', Type::listOf(static::calculatedTax())))
                        ->addFieldBuilder(FieldBuilder::create('taxRules', Type::listOf(static::taxRule())));
                })
                ->setDescription('The Price of a cart')
                ->build();
        }

        return static::$cartPrice;
    }

    public function transaction(): ObjectType
    {
        if (static::$transaction=== null) {
            static::$transaction = ObjectBuilder::create('Transaction')
                ->addLazyFieldCollection(function () {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('amount', Type::nonNull(static::calculatedPrice())))
                        ->addFieldBuilder(FieldBuilder::create('paymentMethodId', Type::nonNull(Type::id())));
                })
                ->setDescription('A transaction inside the cart')
                ->build();
        }

        return static::$transaction;
    }

    public function shippingLocation(TypeRegistry $typeRegistry): ObjectType
    {
        if (static::$shippingLocation=== null) {
            static::$shippingLocation = ObjectBuilder::create('ShippingLocation')
                ->addLazyFieldCollection(function () use ($typeRegistry) {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('country', Type::nonNull($typeRegistry->getObjectForDefinition(CountryDefinition::class))))
                        ->addFieldBuilder(FieldBuilder::create('countryState', $typeRegistry->getObjectForDefinition(CountryStateDefinition::class)))
                        ->addFieldBuilder(FieldBuilder::create('address', $typeRegistry->getObjectForDefinition(CustomerAddressDefinition::class)));
                })
                ->setDescription('A Location for a Shipping.')
                ->build();
        }

        return static::$shippingLocation;
    }

    public function deliveryPosition(TypeRegistry $typeRegistry): ObjectType
    {
        if (static::$deliveryPosition=== null) {
            static::$deliveryPosition = ObjectBuilder::create('DeliveryPosition')
                ->addLazyFieldCollection(function () use ($typeRegistry) {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('lineItem', Type::nonNull(static::lineItem($typeRegistry))))
                        ->addFieldBuilder(FieldBuilder::create('quantity', Type::nonNull(Type::float())))
                        ->addFieldBuilder(FieldBuilder::create('price', type::nonNull(static::calculatedPrice())))
                        ->addFieldBuilder(FieldBuilder::create('identifier', type::nonNull(Type::string())))
                        ->addFieldBuilder(FieldBuilder::create('deliveryDate', type::nonNull(static::deliveryDate())));
                })
                ->setDescription('A single position inside one Delivery.')
                ->build();
        }

        return static::$deliveryPosition;
    }

    public function delivery(TypeRegistry $typeRegistry): ObjectType
    {
        if (static::$delivery=== null) {
            static::$delivery = ObjectBuilder::create('Delivery')
                ->addLazyFieldCollection(function () use ($typeRegistry) {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('positions', Type::listOf(static::deliveryPosition($typeRegistry))))
                        ->addFieldBuilder(FieldBuilder::create('location', Type::nonNull(static::shippingLocation($typeRegistry))))
                        ->addFieldBuilder(FieldBuilder::create('deliveryDate', type::nonNull(static::deliveryDate())))
                        ->addFieldBuilder(FieldBuilder::create('shippingMethod', type::nonNull($typeRegistry->getObjectForDefinition(ShippingMethodDefinition::class))))
                        ->addFieldBuilder(FieldBuilder::create('shippingCosts', type::nonNull(static::calculatedPrice())))
                        ->addFieldBuilder(FieldBuilder::create('endDeliveryDate', type::nonNull(static::deliveryDate())));
                })
                ->setDescription('A delivery inside a cart.')
                ->build();
        }

        return static::$delivery;
    }

    public function cart(TypeRegistry $typeRegistry): ObjectType
    {
        if (static::$cart === null) {
            static::$cart = ObjectBuilder::create('Cart')
                ->addLazyFieldCollection(function () use ($typeRegistry) {
                    return FieldBuilderCollection::create()
                        ->addFieldBuilder(FieldBuilder::create('name', Type::nonNull(Type::string())))
                        ->addFieldBuilder(FieldBuilder::create('token', Type::nonNull(Type::id())))
                        ->addFieldBuilder(FieldBuilder::create('price', Type::nonNull(static::cartPrice())))
                        ->addFieldBuilder(FieldBuilder::create('lineItems', Type::listOf(static::lineItem($typeRegistry))))
                        ->addFieldBuilder(FieldBuilder::create('transactions', Type::listOf(static::transaction())))
                        ->addFieldBuilder(FieldBuilder::create('deliveries', Type::listOf(static::delivery($typeRegistry))));
                })
                ->setDescription('The cart')
                ->build();
        }

        return static::$cart;
    }
}
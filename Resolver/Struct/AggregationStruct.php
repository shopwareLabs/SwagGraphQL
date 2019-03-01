<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\Struct\Struct;

class AggregationStruct extends Struct
{
    /** @var string */
    protected $name;

    /** @var AggregationBucketStruct[] */
    protected $buckets;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AggregationBucketStruct[]
     */
    public function getBuckets(): array
    {
        return $this->buckets;
    }

    /**
     * @return AggregationStruct[]
     */
    public static function fromCollection(AggregationResultCollection $collection): array
    {
        $aggregations = [];
        foreach ($collection->getElements() as $result) {
            $aggregations[] = static::fromAggregationResult($result);
        }

        return $aggregations;
    }

    public static function fromAggregationResult(AggregationResult $aggregation): AggregationStruct
    {
        $buckets = [];
        foreach ($aggregation->getResult() as $result) {
            $buckets[] = AggregationBucketStruct::fromAggregationBucket($result);
        }

        return (new AggregationStruct())->assign([
            'name' => $aggregation->getName(),
            'buckets' => $buckets
        ]);
    }
}
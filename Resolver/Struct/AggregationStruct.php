<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\Struct\Struct;

class AggregationStruct extends Struct
{
    /** @var string */
    protected $name;

    /** @var AggregationResultStruct[] */
    protected $results;

    public function getName(): string
    {
        return $this->name;
    }

    public function getResults(): array
    {
        return $this->results;
    }

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
        $results = [];
        foreach ($aggregation->getResult() as $type => $result) {
            if (is_array($result)) {
                $results[] = (new AggregationResultStruct())->assign([
                    'type' => strval($result['key']),
                    'result' => (float)$result['count']
                ]);
                continue;
            }
            $results[] = (new AggregationResultStruct())->assign([
                'type' => $type,
                'result' => $result
            ]);
        }

        return (new AggregationStruct())->assign([
            'name' => $aggregation->getName(),
            'results' => $results
        ]);
    }
}
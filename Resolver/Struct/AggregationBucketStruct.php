<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AggregationBucketStruct extends Struct
{
    /** @var AggregationResultStruct[] */
    protected $results;

    /** @var AggregationKeyStruct[] */
    protected $keys;

    /**
     * @return AggregationResultStruct[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return AggregationKeyStruct[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    public static function fromAggregationBucket(array $data): AggregationBucketStruct
    {
        $keys = static::parseKeys($data);

        unset($data['key']);

        $results = static::parseResults($data);

        return (new static())->assign([
            'keys' => $keys,
            'results' => $results
        ]);
    }

    private static function parseKeys(array $data): array
    {
        if ($data['key'] === null) {
            return [];
        }

        $keys = [];
        foreach ($data['key'] as $key => $value) {
            $keys[] = (new AggregationKeyStruct())->assign([
                'field' => $key,
                'value' => $value
            ]);
        }

        return $keys;
    }

    private static function parseResults(array $data): array
    {
        $results = [];
        foreach ($data as $type => $value) {
            if (\is_array($value)) {
                foreach ($value as $key => $count) {
                    $results[] = (new AggregationResultStruct())->assign([
                        'type' => \strval($key),
                        'result' => $count
                    ]);
                }
                continue;
            }
            $results[] = (new AggregationResultStruct())->assign([
                'type' => \strval($type),
                'result' => $value
            ]);
        }
        return $results;
    }
}
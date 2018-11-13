<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\Struct\Struct;

class AggregationResultStruct extends Struct
{
    /** @var string */
    protected $type;

    /** @var float */
    protected $result;

    public function getType(): string
    {
        return $this->type;
    }

    public function getResult(): float
    {
        return $this->result;
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AggregationResultStruct extends Struct
{
    /** @var string */
    protected $type;

    protected $result;

    public function getType(): string
    {
        return $this->type;
    }

    public function getResult()
    {
        return $this->result;
    }
}
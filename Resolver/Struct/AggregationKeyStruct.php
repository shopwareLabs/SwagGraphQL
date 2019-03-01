<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AggregationKeyStruct extends Struct
{
    /** @var string */
    protected $field;

    /** @var string */
    protected $value;

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Struct\Struct;

class EdgeStruct extends Struct
{
    /** @var Entity */
    protected $node;

    /** @var string */
    protected $cursor;

    public function getNode(): Entity
    {
        return $this->node;
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    public static function fromElements(array $elements, int $offset): array
    {
        $edges = [];
        $index = 1;
        foreach ($elements as $element) {
            $edges[] = (new EdgeStruct())->assign([
                'node' => $element,
                'cursor' => base64_encode(strval($offset + $index))
            ]);

            $index++;
        }

        return $edges;
    }
}
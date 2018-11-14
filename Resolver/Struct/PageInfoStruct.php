<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;

class PageInfoStruct extends Struct
{
    /** @var string */
    protected $endCursor;

    /** @var bool */
    protected $hasNextPage;

    /** @var string */
    protected $startCursor;

    /** @var bool */
    protected $hasPreviousPage;

    public function getEndCursor(): string
    {
        return $this->endCursor;
    }

    public function getHasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    public function getStartCursor(): string
    {
        return $this->startCursor;
    }

    public function getHasPreviousPage(): bool
    {
        return $this->hasNextPage;
    }

    public static function fromCriteria(Criteria $criteria, int $total): PageInfoStruct
    {
        $limit = ($criteria->getLimit() ?? $total);
        $offset = $criteria->getOffset() ?? 0;

        return (new PageInfoStruct())->assign([
            'endCursor' => base64_encode(strval($limit + $offset)),
            'hasNextPage' => $total >= $limit + $offset,
            'startCursor' => base64_encode(strval($offset + 1)),
            'hasPreviousPage' => $offset > 0
        ]);
    }
}
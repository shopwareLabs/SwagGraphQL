<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\Struct;

class ConnectionStruct extends Struct
{
    /** @var int */
    protected $total;

    /** @var PageInfoStruct */
    protected $pageInfo;

    /** @var EdgeStruct[] */
    protected $edges;

    /** @var AggregationResult[] */
    protected $aggregations;

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPageInfo(): PageInfoStruct
    {
        return $this->pageInfo;
    }

    /**
     * @return EdgeStruct[]
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * @return AggregationStruct[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public static function fromResult(EntitySearchResult $searchResult): ConnectionStruct
    {
        return (new ConnectionStruct())->assign([
            'total' => $searchResult->getTotal(),
            'pageInfo' => PageInfoStruct::fromCriteria($searchResult->getCriteria(), $searchResult->getTotal()),
            'edges' => EdgeStruct::fromElements($searchResult->getElements(), $searchResult->getCriteria()->getOffset() ?? 0),
            'aggregations' => AggregationStruct::fromCollection($searchResult->getAggregations())
        ]);
    }
}
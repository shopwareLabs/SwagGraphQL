<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\SearchRequestException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Parser\AggregationParser;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Parser\QueryStringParser;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CriteriaParser
{
    public static function buildCriteria(array $args, string $definition): Criteria
    {
        $criteria = new Criteria();
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        static::parsePagination($args, $criteria);
        static::parseId($args, $criteria);
        static::parseSorting($args, $criteria);
        static::parseQuery($args, $criteria, $definition);
        static::parseAggregations($args, $criteria, $definition);

        return $criteria;
    }

    private static function parsePagination(array $args, Criteria $criteria): void
    {
        if (isset($args['first'])) {
            $criteria->setLimit($args['first']);

            if (isset($args['after'])) {
                $criteria->setOffset((int)base64_decode($args['after']));
            }

            return;
        }

        if (isset($args['last']) && isset($args['before'])) {
            $criteria->setLimit($args['last']);

            $criteria->setOffset((int)base64_decode($args['before']) - $criteria->getLimit());
        }
    }

    private static function parseId(array $args, Criteria $criteria): void
    {
        if (isset($args['id'])) {
            $criteria->addFilter(new EqualsFilter('id', $args['id']));

            return;
        }
    }

    private static function parseSorting(array $args, Criteria $criteria)
    {
        if (isset($args['sortBy'])) {
            $criteria->addSorting(new FieldSorting($args['sortBy'], $args['sortDirection'] ?? FieldSorting::ASCENDING));
        }
    }

    private static function parseQuery(array $args, Criteria $criteria, string $definition): void
    {
        if (isset($args['query'])) {
            $args['query'] = static::parseRangeParameter($args['query']);
            $e = new SearchRequestException();
            $criteria->addFilter(QueryStringParser::fromArray($definition, $args['query'], $e));

            $e->tryToThrow();
        }
    }

    private static function parseRangeParameter(array $query): array
    {
        if (isset($query['parameters'])) {
            $params = [];
            foreach ($query['parameters'] as $param) {
                $params[$param['operator']] = $param['value'];
            }
            $query['parameters'] = $params;
        }

        if (isset($query['queries'])) {
            foreach ($query['queries'] as $key => $nested) {
                $query['queries'][$key] = static::parseRangeParameter($nested);
            }
        }

        return $query;
    }

    private static function parseAggregations(array $args, Criteria $criteria, string $definition): void
    {
        if (isset($args['aggregations'])) {
            $e = new SearchRequestException();
            AggregationParser::buildAggregations($definition, $args, $criteria, $e);

            $e->tryToThrow();
        }
    }
}
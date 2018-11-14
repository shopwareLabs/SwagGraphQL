<?php declare(strict_types=1);

namespace SwagGraphQL\Types;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class DateType extends ScalarType
{
    public $name = 'Date';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     * @return mixed
     * @throws Error
     */
    public function serialize($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new InvariantViolation("Could not serialize following Date: " . print_r($value, true));
        }

        return $value->format(DATE_ATOM);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * In the case of an invalid value this method must throw an Exception
     *
     * @param mixed $value
     * @return mixed
     * @throws Error
     */
    public function parseValue($value)
    {
        $date = \DateTime::createFromFormat(DATE_ATOM, $value);
        if ($date === false) {
            throw new Error("Could not parse following Date: " . $value);
        }

        return $date;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input
     *
     * In the case of an invalid node or value this method must throw an Exception
     *
     * @param Node $valueNode
     * @param mixed[]|null $variables
     * @return mixed
     * @throws \Exception
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        $date = \DateTime::createFromFormat(DATE_ATOM, $valueNode->value);
        if ($date === false) {
            throw new Error("Could not parse following Date: " . $valueNode->value);
        }

        return $date;
    }
}
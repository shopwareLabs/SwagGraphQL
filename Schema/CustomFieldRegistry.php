<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use SwagGraphQL\CustomFields\GraphQLField;

class CustomFieldRegistry
{
    private $fields;

    public function addField(string $name, GraphQLField $field)
    {
        $this->fields[$name] = $field;
    }

    public function get(string $name): ?GraphQLField
    {
        if (!array_key_exists($name, $this->fields)) {
            return null;
        }

        return $this->fields[$name];
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
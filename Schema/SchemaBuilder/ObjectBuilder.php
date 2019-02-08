<?php declare(strict_types=1);

namespace SwagGraphQL\Schema\SchemaBuilder;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;

class ObjectBuilder
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $callables = [];

    private function __construct(string $name)
    {
        $this->config['name'] = $name;
        $this->config['fields'] = function () {
            foreach ($this->callables as $callable) {
                /** @var FieldBuilderCollection $fields */
                $fields = $callable();
                $this->fields = array_merge($this->fields, $fields->build());
            }


            return $this->fields;
        };
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function addField(FieldBuilder ...$fields): self
    {
        foreach ($fields as $field) {
            $fieldConfig = $field->build();
            $this->fields[$fieldConfig['name']] = $fieldConfig;
        }

        return $this;
    }

    public function addLazyFieldCollection(callable $fields): self
    {
        $this->callables[] = $fields;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->config['description'] = $description;

        return $this;
    }

    public function setDeprecationReason(string $reason) : self
    {
        $this->config['deprecationReason'] = $reason;
        return $this;
    }

    public function build(): ObjectType
    {
        return new ObjectType($this->config);
    }

    public function buildAsInput(): InputObjectType
    {
        return new InputObjectType($this->config);
    }
}
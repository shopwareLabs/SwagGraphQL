<?php declare(strict_types=1);

namespace SwagGraphQL\Schema\SchemaBuilder;

use GraphQL\Type\Definition\Type;
use SimPod\GraphQLUtils\Tests\Builder\FieldBuilderTest;

class FieldBuilderCollection
{
    /**
     * @var array
     */
    private $config = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function addFieldBuilder(FieldBuilder $field) : self
    {
        $fieldConfig = $field->build();
        $this->config[$fieldConfig['name']] = $fieldConfig;

        return $this;
    }

    public function addField(string $name, Type $type, ?string $description = null) : self
    {
        $this->config[$name] = ['type' => $type];
        if ($description !== null) {
            $this->config[$name]['description'] = $description;
        }
        return $this;
    }

    public function build(): array
    {
        return $this->config;
    }
}
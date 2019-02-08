<?php declare(strict_types=1);

namespace SwagGraphQL\Schema\SchemaBuilder;

use GraphQL\Type\Definition\EnumType;

class EnumBuilder
{
    /**
     * @var array
     */
    private $config;

    private function __construct(string $name)
    {
        $this->config['name'] = $name;
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function setDescription(string $description): self
    {
        $this->config['description'] = $description;

        return $this;
    }

    public function addValue(string $value, ?string $name = null, string $description = ''): self
    {
        $this->config['values'][$name ?? $value] = [
            'value' => $value,
            'description' => $description
        ];

        return $this;
    }

    public function build(): EnumType
    {
        return new EnumType($this->config);
    }
}
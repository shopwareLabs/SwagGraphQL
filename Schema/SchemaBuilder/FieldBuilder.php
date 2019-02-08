<?php declare(strict_types=1);

namespace SwagGraphQL\Schema\SchemaBuilder;

use GraphQL\Type\Definition\Type;

class FieldBuilder
{
    /**
     * @var array
     */
    private $config;

    private function __construct(string $name, Type $type)
    {
        $this->config['name'] = $name;
        $this->config['type'] = $type;
    }

    public static function create(string $name, Type $type): self
    {
        return new self($name, $type);
    }

    public function setDescription(string $description): self
    {
        $this->config['description'] = $description;

        return $this;
    }

    public function setArguments(FieldBuilderCollection $arguments) : self
    {
        $this->config['args'] = $arguments->build();

        return $this;
    }

    public function setResolver(callable $callback) : self
    {
        $this->config['resolve'] = $callback;

        return $this;
    }

    public function setDeprecationReason(string $reason) : self
    {
        $this->config['deprecationReason'] = $reason;

        return $this;
    }

    public function setDefault($default) : self
    {
        $this->config['defaultValue'] = $default;

        return $this;
    }

    public function build(): array
    {
        return $this->config;
    }
}
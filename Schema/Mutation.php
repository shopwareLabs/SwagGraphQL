<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

class Mutation
{
    const ACTION_DELETE = 'delete';
    const ACTION_UPSERT = 'upsert';

    /** @var string */
    private $action;

    /** @var string */
    private $entityName;

    public static function fromName(string $name): Mutation
    {
        if (strpos($name, static::ACTION_UPSERT) === 0) {
            return new self(static::ACTION_UPSERT, substr($name, strlen(static::ACTION_UPSERT) + 1));
        }
        if (strpos($name, static::ACTION_DELETE) === 0) {
            return new self(static::ACTION_DELETE, substr($name, strlen(static::ACTION_DELETE) + 1));
        }

        throw new \Exception('Mutation without valid action prefix called, got: ' . $name);
    }

    public function __construct(string $action, string $entityName)
    {
        $this->action = $action;
        $this->entityName = $entityName;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getName(): string
    {
        return sprintf('%s_%s', $this->action, $this->entityName);
    }
}
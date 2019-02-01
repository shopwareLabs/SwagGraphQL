<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use PHPUnit\Framework\TestCase;
use SwagGraphQL\Schema\Mutation;

class MutationTest extends TestCase
{
    public function testGetNameCreate()
    {
        $mutation = new Mutation(Mutation::ACTION_CREATE, 'test');

        static::assertEquals('createTest', $mutation->getName());
    }

    public function testGetNameUpdate()
    {
        $mutation = new Mutation(Mutation::ACTION_UPDATE, 'test');

        static::assertEquals('updateTest', $mutation->getName());
    }

    public function testGetNameDelete()
    {
        $mutation = new Mutation(Mutation::ACTION_DELETE, 'test');

        static::assertEquals('deleteTest', $mutation->getName());
    }

    public function testFromNameCreate()
    {
        $mutation = Mutation::fromName('createTest');

        static::assertEquals(Mutation::ACTION_CREATE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }

    public function testFromNameUpdate()
    {
        $mutation = Mutation::fromName('updateTest');

        static::assertEquals(Mutation::ACTION_UPDATE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }

    public function testFromNameDelete()
    {
        $mutation = Mutation::fromName('deleteTest');

        static::assertEquals(Mutation::ACTION_DELETE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }
}
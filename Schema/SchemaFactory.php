<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Schema;

class SchemaFactory
{
    public static function createSchema(TypeRegistry $typeRegistry): Schema
    {
        return new Schema([
            'query' => $typeRegistry->getQuery(),
            'mutation' => $typeRegistry->getMutation(),
        ]);
    }
}
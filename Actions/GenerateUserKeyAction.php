<?php declare(strict_types=1);

namespace SwagGraphQL\Actions;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;

class GenerateUserKeyAction implements GraphQLField
{
    public function returnType(): Type
    {
        return new ObjectType([
            'name' => 'KeyPair',
            'fields' => [
                'accessKey' => [
                    'type' => Type::nonNull(Type::id())
                ],
                'secretAccessKey' => [
                    'type' => Type::nonNull(Type::id())
                ]
            ]
        ]);
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create();
    }

    public function description(): string
    {
        return 'Generates the access keys for a user.';
    }

    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        return [
            'accessKey' => AccessKeyHelper::generateAccessKey('user'),
            'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
        ];
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\Actions;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use SwagGraphQL\CustomFields\GraphQLField;

class GenerateIntegrationKeyAction implements GraphQLField
{
    public function returnType(): Type
    {
        return new ObjectType([
            'name' => 'IntegrationAccessKey',
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

    public function defineArgs(): array
    {
        return [];
    }

    public function description(): string
    {
        return 'Generates access keys for integrations.';
    }

    public function resolve($rootValue, $args, Context $context, ResolveInfo $info)
    {
        return [
            'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
            'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
        ];
    }
}
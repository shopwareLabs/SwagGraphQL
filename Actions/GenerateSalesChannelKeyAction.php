<?php declare(strict_types=1);

namespace SwagGraphQL\Actions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;

class GenerateSalesChannelKeyAction implements GraphQLField
{
    public function returnType(): Type
    {
        return Type::nonNull(Type::id());
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create();
    }

    public function description(): string
    {
        return 'Generates the access key for a sales channel.';
    }

    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        return AccessKeyHelper::generateAccessKey('sales-channel');
    }
}
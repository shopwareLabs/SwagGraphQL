<?php declare(strict_types=1);

namespace SwagGraphQL\CustomFields;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\Context;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;

/**
 * Mörder wäre es wenn wir hier das meiste über Annotations abfrühstücken könnten
 * sprich name über ne annotation an der Klasse
 * und Args über Annotations an properties die wir dann direkt setzen in dem resolve wrapper,
 * weswegen args gar nicht mehr an die resolve function übergeben werden müssten
 */
interface GraphQLField
{
    /**
     * @return Type the Type the field returns
     */
    public function returnType(): Type;

    /**
     * @return FieldBuilderCollection the arguments this field accepts
     */
    public function defineArgs(): FieldBuilderCollection;

    /**
     * @return string description of the field
     */
    public function description(): string;

    /**
     * @param mixed $rootValue
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $resolveInfo
     * @return mixed return value must be possible to be casted to return type
     */
    public function resolve($rootValue, array $args, Context $context, ResolveInfo $resolveInfo);
}
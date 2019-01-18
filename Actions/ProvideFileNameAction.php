<?php declare(strict_types=1);

namespace SwagGraphQL\Actions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Content\Media\File\FileNameProvider;
use Shopware\Core\Framework\Context;
use SwagGraphQL\CustomFields\GraphQLField;

class ProvideFileNameAction implements GraphQLField
{
    CONST FILE_NAME_ARGUMENT = 'fileName';
    CONST FILE_EXTENSION_ARGUMENT = 'fileExtension';
    CONST MEDIA_ID_ARGUMENT = 'media_id';

    /**
     * @var FileNameProvider
     */
    private $nameProvider;

    public function __construct(FileNameProvider $nameProvider)
    {
        $this->nameProvider = $nameProvider;
    }

    public function returnType(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function defineArgs(): array
    {
        return [
            self::FILE_NAME_ARGUMENT => Type::nonNull(Type::string()),
            self::FILE_EXTENSION_ARGUMENT => Type::nonNull(Type::string()),
            self::MEDIA_ID_ARGUMENT => Type::id()
        ];
    }

    public function description(): string
    {
        return 'Provides a unique filename based on the given one.';
    }

    public function resolve($rootValue, $args, Context $context, ResolveInfo $info)
    {
        $fileName = $args[self::FILE_NAME_ARGUMENT];
        $fileExtension = $args[self::FILE_EXTENSION_ARGUMENT];
        $mediaId = array_key_exists(self::MEDIA_ID_ARGUMENT, $args) ?
            $args[self::FILE_NAME_ARGUMENT] :
            null;

        return $this->nameProvider->provide($fileName, $fileExtension, $mediaId, $context);
    }
}
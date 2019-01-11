<?php declare(strict_types=1);

namespace SwagGraphQL\Actions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Content\Media\MediaFolderService;
use Shopware\Core\Framework\Context;
use SwagGraphQL\CustomFields\GraphQLField;

class DissolveMediaFolderAction implements GraphQLField
{
    CONST FOLDER_ID_ARGUMENT = 'media_folder_id';
    /**
     * @var MediaFolderService
     */
    private $mediaFolderService;

    public function __construct(MediaFolderService $mediaFolderService)
    {
        $this->mediaFolderService = $mediaFolderService;
    }

    public function returnType(): Type
    {
        return Type::id();
    }

    public function defineArgs(): array
    {
        return [
            self::FOLDER_ID_ARGUMENT => Type::id()
        ];
    }

    public function resolve($rootValue, $args, Context $context, ResolveInfo $info)
    {
        $folderId = $args[self::FOLDER_ID_ARGUMENT];
        $this->mediaFolderService->dissolve($folderId, $context);

        return $folderId;
    }
}
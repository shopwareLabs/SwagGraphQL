<?php declare(strict_types=1);

namespace SwagGraphQL\SalesChannelActions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\LineItemCoverNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotFoundException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Storefront\CartService;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\TypeRegistry;

class UpdateLineItemAction implements GraphQLField
{
    const KEY_ARGUMENT = 'key';
    const QUANTIY_ARGUMENT = 'quantity';
    const PAYLOAD_ARGUMENT = 'payload';
    const STACKABLE_ARGUMENT = 'stackable';
    const REMOVABLE_ARGUMENT = 'removable';
    const PRIORITY_ARGUMENT = 'priority';
    const LABEL_ARGUMENT = 'label';
    const DESCRIPTION_ARGUMENT = 'description';
    const COVER_ARGUMENT = 'coverId';

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var TypeRegistry
     */
    private $typeRegistry;

    /**
     * @var CustomTypes
     */
    private $customTypes;
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    public function __construct(CartService $cartService, TypeRegistry $typeRegistry, CustomTypes $customTypes, EntityRepositoryInterface $mediaRepository)
    {
        $this->cartService = $cartService;
        $this->typeRegistry = $typeRegistry;
        $this->customTypes = $customTypes;
        $this->mediaRepository = $mediaRepository;
    }

    public function returnType(): Type
    {
        return $this->customTypes->cart($this->typeRegistry);
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addField(self::KEY_ARGUMENT, Type::nonNull(Type::id()))
            ->addField(self::QUANTIY_ARGUMENT, Type::int())
            ->addField(self::PAYLOAD_ARGUMENT, $this->customTypes->json())
            ->addField(self::STACKABLE_ARGUMENT, Type::boolean())
            ->addField(self::REMOVABLE_ARGUMENT, Type::boolean())
            ->addField(self::PRIORITY_ARGUMENT, Type::int())
            ->addField(self::LABEL_ARGUMENT, Type::string())
            ->addField(self::DESCRIPTION_ARGUMENT, Type::string())
            ->addField(self::COVER_ARGUMENT, Type::id());
    }

    public function description(): string
    {
        return 'Update a LineItem from the Cart.';
    }

    /**
     * @param SalesChannelContext $context
     */
    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);
        $id = $args[self::KEY_ARGUMENT];

        if (!$cart->has($id)) {
            throw new LineItemNotFoundException($id);
        }

        $lineItem = $this->cartService->getCart($context->getToken(), $context)->getLineItems()->get($id);
        $this->updateLineItem($lineItem, $args, $context->getContext());

        $cart = $this->cartService->recalculate($cart, $context);

        return $cart;
    }

    private function updateLineItem(LineItem $lineItem, array $args, Context $context)
    {
        if (isset($args[self::QUANTIY_ARGUMENT])) {
            $lineItem->setQuantity($args[self::QUANTIY_ARGUMENT]);
        }

        if (isset($args[self::STACKABLE_ARGUMENT])) {
            $lineItem->setStackable($args[self::STACKABLE_ARGUMENT]);
        }

        if (isset($args[self::REMOVABLE_ARGUMENT])) {
            $lineItem->setRemovable($args[self::REMOVABLE_ARGUMENT]);
        }

        if (isset($args[self::PRIORITY_ARGUMENT])) {
            $lineItem->setPriority($args[self::PRIORITY_ARGUMENT]);
        }

        if (isset($args[self::LABEL_ARGUMENT])) {
            $lineItem->setLabel($args[self::LABEL_ARGUMENT]);
        }

        if (isset($args[self::DESCRIPTION_ARGUMENT])) {
            $lineItem->setDescription($args[self::DESCRIPTION_ARGUMENT]);
        }

        if (isset($args[self::COVER_ARGUMENT])) {
            $cover = $this->mediaRepository->search(new Criteria([$args[self::COVER_ARGUMENT]]), $context)->get($args[self::COVER_ARGUMENT]);

            if (!$cover) {
                throw new LineItemCoverNotFoundException($args[self::COVER_ARGUMENT], $lineItem->getKey());
            }

            $lineItem->setCover($cover);
        }
    }
}
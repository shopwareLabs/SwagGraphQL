<?php declare(strict_types=1);

namespace SwagGraphQL\SalesChannelActions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Storefront\CartService;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\TypeRegistry;

class AddToCartAction implements GraphQLField
{
    const PRODUCT_ID_ARGUMENT = 'productId';
    const QUANTITY_ARGUMENT = 'quantity';
    const PAYLOAD_ARGUMENT = 'payload';

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

    public function __construct(CartService $cartService, TypeRegistry $typeRegistry, CustomTypes $customTypes)
    {
        $this->cartService = $cartService;
        $this->typeRegistry = $typeRegistry;
        $this->customTypes = $customTypes;
    }

    public function returnType(): Type
    {
        return $this->customTypes->cart($this->typeRegistry);
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addField(self::PRODUCT_ID_ARGUMENT, Type::nonNull(Type::id()))
            ->addField(self::QUANTITY_ARGUMENT, Type::nonNull(Type::int()))
            ->addField(self::PAYLOAD_ARGUMENT, $this->customTypes->json());
    }

    public function description(): string
    {
        return 'Add a product to the Cart.';
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
        $id = $args[self::PRODUCT_ID_ARGUMENT];
        $payload = array_replace_recursive(['id' => $id], $args[self::PAYLOAD_ARGUMENT] ?? []);

        $lineItem = (new LineItem($id, ProductCollector::LINE_ITEM_TYPE, $args[self::QUANTITY_ARGUMENT]))
            ->setPayload($payload)
            ->setRemovable(true)
            ->setStackable(true);

        $cart = $this->cartService->add($cart, $lineItem, $context);

        return $cart;
    }
}
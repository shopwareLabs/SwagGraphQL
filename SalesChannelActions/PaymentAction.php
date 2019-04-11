<?php declare(strict_types=1);

namespace SwagGraphQL\SalesChannelActions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Storefront\CartService;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilder;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\TypeRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentAction implements GraphQLField
{
    const ORDER_ID_ARGUMENT = 'orderId';
    const FINISH_URL_ARGUMENT = 'finishUrl';

    /**
     * @var PaymentService
     */
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function returnType(): Type
    {
        return Type::string();
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addFieldBuilder(FieldBuilder::create(self::ORDER_ID_ARGUMENT, Type::nonNull(Type::id())))
            ->addFieldBuilder(FieldBuilder::create(self::FINISH_URL_ARGUMENT, Type::string()));
    }

    public function description(): string
    {
        return 'Pay the order.';
    }

    /**
     * @param SalesChannelContext $context
     */
    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $response = $this->paymentService->handlePaymentByOrder($args[self::ORDER_ID_ARGUMENT], $context, $args[self::FINISH_URL_ARGUMENT] ?? null);

        if ($response) {
            return $response->getTargetUrl();
        }
    }
}
<?php declare(strict_types=1);

namespace SwagGraphQL\SalesChannelActions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\LineItemCoverNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotFoundException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Storefront\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Shipping\Exception\ShippingMethodNotFoundException;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\TypeRegistry;

class UpdateContextAction implements GraphQLField
{
    const SHIPPING_METHOD_ARGUMENT = 'shippingMethodId';
    const PAYMENT_METHOD_ARGUMENT = 'paymentMethodId';
    const SHIPPING_ADDRESS_ARGUMENT = 'shippingAddressId';
    const BILLING_ADDRESS_ARGUMENT = 'billingAddressId';

    /**
     * @var SalesChannelContextPersister
     */
    private $contextPersister;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $shippingMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $addressRepository;

    public function __construct(
        SalesChannelContextPersister $contextPersister,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $shippingMethodRepository,
        EntityRepositoryInterface $addressRepository
    ) {

        $this->contextPersister = $contextPersister;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->addressRepository = $addressRepository;
    }

    public function returnType(): Type
    {
        return Type::nonNull(Type::id());
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addField(self::SHIPPING_METHOD_ARGUMENT, Type::id())
            ->addField(self::PAYMENT_METHOD_ARGUMENT, Type::id())
            ->addField(self::SHIPPING_ADDRESS_ARGUMENT, Type::id())
            ->addField(self::BILLING_ADDRESS_ARGUMENT, Type::id());
    }

    public function description(): string
    {
        return 'Update the context of the currently logged in Customer.';
    }

    /**
     * @param SalesChannelContext $context
     */
    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        $update = [];
        if (array_key_exists(self::SHIPPING_METHOD_ARGUMENT, $args)) {
            $update[self::SHIPPING_METHOD_ARGUMENT] = $this->validateShippingMethodId($args[self::SHIPPING_METHOD_ARGUMENT], $context);
        }
        if (array_key_exists(self::PAYMENT_METHOD_ARGUMENT, $args)) {
            $update[self::PAYMENT_METHOD_ARGUMENT] = $this->validatePaymentMethodId($args[self::PAYMENT_METHOD_ARGUMENT], $context);
        }
        if (array_key_exists(self::BILLING_ADDRESS_ARGUMENT, $args)) {
            $update[self::BILLING_ADDRESS_ARGUMENT] = $this->validateAddressId($args[self::BILLING_ADDRESS_ARGUMENT], $context);
        }
        if (array_key_exists(self::SHIPPING_ADDRESS_ARGUMENT, $args)) {
            $update[self::SHIPPING_ADDRESS_ARGUMENT] = $this->validateAddressId($args{self::SHIPPING_ADDRESS_ARGUMENT}, $context);
        }

        $this->contextPersister->save($context->getToken(), $update);

        return $context->getToken();
    }

    private function validatePaymentMethodId(string $paymentMethodId, SalesChannelContext $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('payment_method.id', $paymentMethodId));

        $valid = $this->paymentMethodRepository->searchIds($criteria, $context->getContext());
        if (!\in_array($paymentMethodId, $valid->getIds(), true)) {
            throw new UnknownPaymentMethodException($paymentMethodId);
        }

        return $paymentMethodId;
    }

    private function validateShippingMethodId(string $shippingMethodId, SalesChannelContext $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shipping_method.id', $shippingMethodId));

        $valid = $this->shippingMethodRepository->searchIds($criteria, $context->getContext());
        if (!\in_array($shippingMethodId, $valid->getIds(), true)) {
            throw new ShippingMethodNotFoundException($shippingMethodId);
        }

        return $shippingMethodId;
    }

    private function validateAddressId(string $addressId, SalesChannelContext $context): string
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $addresses = $this->addressRepository->search(new Criteria([$addressId]), $context->getContext());
        /** @var CustomerAddressEntity|null $address */
        $address = $addresses->get($addressId);

        if (!$address) {
            throw new AddressNotFoundException($addressId);
        }

        if ($address->getCustomerId() !== $context->getCustomer()->getId()) {
            throw new AddressNotFoundException($address->getCustomerId() . '/' . $context->getCustomer()->getId());
        }

        return $addressId;
    }
}
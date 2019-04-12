<?php declare(strict_types=1);

namespace SwagGraphQL\SalesChannelActions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Checkout\Customer\Storefront\AccountService;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;

class LoginAction implements GraphQLField
{
    const EMAIL_ARGUMENT = 'email';
    const PASSWORD_ARGUMENT = 'password';

    /**
     * @var AccountService
     */
    private $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function returnType(): Type
    {
        return Type::nonNull(Type::id());
    }

    public function defineArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addField(self::EMAIL_ARGUMENT, Type::nonNull(Type::string()))
            ->addField(self::PASSWORD_ARGUMENT, Type::nonNull(Type::string()));
    }

    public function description(): string
    {
        return 'Login with a email and password.';
    }

    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        $email = $args[self::EMAIL_ARGUMENT];
        $password = $args[self::PASSWORD_ARGUMENT];
        $data = new DataBag(['username' => $email, 'password' => $password]);

        return $this->accountService->loginWithPassword($data, $context);
    }
}
# SwagGraphQL

A simple plugin that exposes an GraphQL-API for the Shopware Platform Core-API.

## Installation

Clone this repo in your `custom/plugins` folder of your Shopware Platform Template.

run:
```
cd custom/plugins/SwagGraphQL
composer install
cd ../../..
bin/console plugin:install SwagGraphQL
bin/console plugin:activate SwagGraphQL
```

After installation the GraphQL endpoint is available under `{baseUrl}/graphql/query`.

## Getting started

Getting started with [GraphQL](https://graphql.org/learn/).

The easiest way to fiddle around with the Shopware GraphQL-API is to use
[GraphiQL](https://github.com/graphql/graphiql), for example as a [Chrome-Extension](https://chrome.google.com/webstore/detail/chromeiql)

## Custom Fields

You can define your custom fields, by implementing the `GraphQLField` Interface and tagging your Field
either with the `swag_graphql.queries` or `swag_graphql.mutations` tag.
In either case you have to specify the name under which the field will be queryable inside the service tag, 
either as `mutation` or `query`

####Example:

in `services.xml`:
```xml
<service id="SwagGraphQL\Actions\GenerateUserKeyAction">
    <tag name="swag_graphql.queries" query="generate_user_key"></tag>
</service>
```
your class:
```php
class GenerateUserKeyAction implements GraphQLField
{
    public function returnType(): Type
    {
        return new ObjectType([
            'name' => 'UserAccessKey',
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
        return 'Generates the access keys for a user.';
    }

    public function resolve($rootValue, $args, Context $context, ResolveInfo $info)
    {
        return [
            'accessKey' => AccessKeyHelper::generateAccessKey('user'),
            'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
        ];
    }
}
```

## Dependencies

It uses [webonyx/graphql-php](https://github.com/webonyx/graphql-php) for the GraphQL part 
and the Shopware 6 Framework-Bundle for schema generation and query resolving.

The Tests also depend on the Shopware 6 Content-Bundle.

## Known Problems

Nested connections don't really work. The connection information (total, pageInfo and aggregation) aren't returned.

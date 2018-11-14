# SwagGraphQL
A Simple Plugin that exposes an GraphQL-API for the Shopware 6 Core-API.

## Installation
Clone this repo in your `custom/plugins` folder of your Shopware 6 Template.

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

## Dependencies
It uses [webonyx/graphql-php](https://github.com/webonyx/graphql-php) for the GraphQL part 
and the Shopware 6 Framework-Bundle for schema generation and query resolving.
The Tests also depend on the Shopware 6 Content-Bundle.

## Known Problems
Nested connections don't really work. The connection information (total, pageInfo and aggregation) aren't returned.
Also the input arguments for nested connections are ignored for now.

Actions that are exposed over custom controllers are currently not supported.
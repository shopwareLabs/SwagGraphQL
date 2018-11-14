<!-- .slide: class="splashscreen" -->
# GraphQL + 
# Shopware 6 
<img src="https://graphql.org/img/logo.svg" height="180" class="noborder">
<i class="fas fa-plus fa-2x" style="position:relative; bottom:75px"></i>
<img src="/img/sw-logo.svg" alt="Shopware Logo" height="180" class="noborder">

---

## What is GraphQL

<img src="https://graphql.org/img/logo.svg" width="200" class="noborder">

* query language for APIs
* developed by facebook
* open sourced in 2015 (under MIT since 2017)

---

## Benefits

| GraphQL | REST |
|---------|------|
| query exactly the information you need <!-- .element: class="fragment" data-fragment-index="1" --> | getting everything an REST-Endpoint returns <!-- .element: class="fragment" data-fragment-index="1" -->|
| get predictable results that mirror your query <!-- .element: class="fragment" data-fragment-index="2" --> | arbitrary data defined by the backend <!-- .element: class="fragment" data-fragment-index="2" --> |

---

## How it works

Queries for reading data


```
query {                "data": {
  product {              "product": {
    name                   "name": "Fantastic Paper Qleen Tooth"
    id                     "id": "000b57c2bbdb4ac385b846641178aaf7"
    manufacturer {         "manufacturer": {
      name                   "name": "Kuhn"
    }                      }
  }                      }
}                      }
```

---

## How it works

Mutations for writing data

```
mutation {
  upsert_product(
    name: "my new Product", 
    manufacturer: {
      name: "fancy manufacturer"
    }
  ) {
    name
  }
}
```

---

## GraphQL Schema

* defines which queries and mutations exist
* defines all Types that exist in that schema

---

## GraphQL Schema - Types

* **type** = scalar | object | input object
* **object** = name + fields 
* **field** = name + args + field type 
* **arg** = scalar | input object 
* **field type** = scalar | object 

---

## Best Practices - Pagination

Cursor-based pagination over connection model
> Ultimately designing APIs with feature-rich pagination led to a best practice pattern called "Connections".

[GraphQL Website](https://graphql.org/learn/best-practices/#pagination)

---

## Best Practices - Pagination

Cursor-based pagination over connection model
``` 
query {
  productConnection(first: 5, after: "lastCursor") {
    total
    pageInfo {
      hasNextPage
      endCursor
    }
    edges {
      // contains the actual product
      node {
        name
        id
      }
      cursor
    }
  }
}
```

---

## Best Practices - Versioning

Deprecate stuff in advance, so you don't need Versioning
> GraphQL takes a strong opinion on avoiding versioning by providing the tools for the continuous evolution of a GraphQL schema.

[GraphQL Website](https://graphql.org/learn/best-practices/#versioning)

---

## Fiddling around with the Shopware 6 GraphQL-API!

---

## Who wants to see the actual code?!

---

## Known Limitations

* Arguments in nested association are ignored
    * library doesn't provide lookAhead() functionality
* Connection Information in nested associations is empty
    * entity searcher doesn't return these infos (atm)

---

## Useful Links

* [Learn GraphQL](https://graphql.org/learn/)
* [GraphQL Best Practices](https://graphql.org/learn/best-practices/)
* [GraphQL reference implementation for PHP](https://github.com/webonyx/graphql-php) (used for the GraphQL stuff)
* [Article about cursor based Pagination](https://hackernoon.com/guys-were-doing-pagination-wrong-f6c18a91b232) by Yan Cui (Lead Developer @ DAZN)

---

<!-- .slide: class="splashscreen" -->
## Shopware 6 <i class="far fa-heart"></i> GraphQL

sources @ https://stash.shopware.com/users/j.elfering/repos/swaggraphql

Any Questions?

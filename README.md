## Last Viewed Products for Sylius CMS Plugin

It's plugin show customers last viewed products. 

This simple plugins helps your customers compare and looking the best product for themself


### How to install?

```bash

composer require vldmr-k/sylius-viewed-products-plugin

```

Import config file into `_sylius.yml`

```yaml
imports:
    ....
    - { resource: "@VldmrkSyliusViewedProductsPlugin/Resources/config/config.yml" }
```

By default, this plugins render last viewed products on product show page, event block name `sylius.shop.product.show.content`.

If you want to add this block to another page, add this code to block events which you want

```yaml
sylius_ui:
    events:
        sylius.shop.product.show.content: # <--- default
            blocks:
                viewed_products:
                    template: "@VldmrkSyliusViewedProductsPlugin/_viewed_products.html.twig"
                    priority: 20
                    
```

### Storage

The ids of viewed products (the last viewed first) are kept in one of two storages:

- `session` (default): in the session, under `_viewed_product`. A visitor who views a product gets a session.
- `cookie`: in a cookie of its own (`viewed_products=812.77.4031`, HttpOnly, SameSite=Lax). Viewing products
  does not start a session, and every view extends the cookie lifetime.

```yaml
# config/packages/vldmrk_sylius_viewed_products.yaml
vldmrk_sylius_viewed_products:
    storage:
        type: cookie          # session | cookie
        limit: 20             # how many products are remembered
        cookie:
            name: viewed_products
            lifetime: 2592000 # seconds (30 days)
```

The chosen storage is the service `vldmrk.viewed_product.storage.product_viewed_storage`, also autowired as
`Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface`.
To keep the list somewhere else, implement that interface and point the alias at your service.

That's all :)

### Development

See [FORDEVREADME.md](./FORDEVREADME.md)


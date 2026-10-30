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

Sylius 2 is required (the 1.x branch is for Sylius 1).

By default, this plugin renders the last viewed products on the product show page, as the Twig hook
`viewed_products` of `sylius_shop.product.show.content` (priority 50: after the product info, before the associations).
The products are shown as Sylius shop product cards (`sylius_shop:product:card`).

To move or replace the block, configure the hook in your app:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.product.show.content':
            viewed_products:
                template: "@VldmrkSyliusViewedProductsPlugin/_viewed_products.html.twig" # or your own template
                priority: 50
                # enabled: false  # to hide it
```

To show the list on another page, add a hook with that template to one of that page's hooks.

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

The plugin has no vendor of its own: its tests borrow the autoloader of a Sylius 2 app.

```bash
HOST_AUTOLOAD=/path/to/app/vendor/autoload.php /path/to/app/vendor/bin/phpunit -c phpunit.xml.dist
```


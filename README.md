## Last Viewed Products for Sylius CMS Plugin

It's plugin show customers last viewed products. 

This simple plugins helps your customers compare and looking the best product for themself


### How to install?

```bash

compose require vldmrk/vldmr-k/sylius-viewed-products-plugin

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
        sylius.shop.product.show.content: <--- default
            blocks:
                viewed_products:
                    template: "@VldmrkSyliusViewedProductsPlugin/_viewed_products.html.twig"
                    priority: 20
                    
```

### Storage

By default, this plugins storage ids of viewed products in `sylius.storage.session`.

If you want to implement your own storage, you need to implement interface  `Sylius\Component\Resource\Storage\StorageInterface`,
and override default storage, that to pass to `vldmrk.viewed_product.storage.product_viewed_storage`  arguments custom storage.

That's all :)

### Development

See [FORDEVREADME.md](./FORDEVREADME.md)


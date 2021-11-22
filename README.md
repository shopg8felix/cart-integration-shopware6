# Shopgate Shopware6 Integration

## Install

### Packagist install (recommended)
This plugin is available on `packagist.org`. To install simply add the composer package to the shopware's root composer:
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6
```
Afterwards just increment the plugin version root/composer.json, and run `composer update` to get the latest version.

### Folder install

It can be installed manually by copying the plugin folder to `custom/plugins` folder.

#### Composer symlink (development)

After placing it in the `plugins` folder you can now link it to composer by running this command in the root
directory:

```shell
cd [shopware6 root folder]

# this step is required only in case you do not already have this in the root composer.json specified
composer config repositories.sym '{"type": "path", "url": "custom/plugins/*", "options": {"symlink": true}}'

# make sure to use the exact version (e.g. `1.6.4`) that is provided in the composer.json of this plugin.
composer require shopgate/cart-integration-shopware6:1.6.4
```

#### Update SDK separately

Alternatively you could place this plugin's folder in the `custom/plguins`. Afterwards make sure the run
the `composer update` inside this plugin's folder so that the Shopgate SDK is installed. Not recommended as it will also download the shopware core package.
```shell
composer update --no-dev -d custom/plugins/SgateShopgatePluginSW6 
```
**Or** you can install SDK separately in the root composer. Not recommended as you will have to manually update SDK version & keep track of it.
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-sdk:^2.9.81
```

## Enable & Activate

Install and activate the module:

```shell
cd [shopware6 root folder]
php bin/console plugin:refresh
php bin/console plugin:install SgateShopgatePluginSW6
php bin/console plugin:activate SgateShopgatePluginSW6
php bin/console cache:clear
```

You may install and activate via the Shopware administration panel instead, if you prefer.

## Compile frontend

This shopware 6 command will compile the JavaScript of frontend and backend:

```shell
cd [shopware6 root folder]
./bin/build-js.sh
```

# Known errors

* `No SaleChannel domain exists corresponding to the SaleChannel default language` - indicates an issue when there is a
  default language set for a domain, but no domain URL exists that has that language. In short:
  1. go to `SalesChannels`
  1. select SaleChannel that is being queried by Shopgate API
  1. Check `General Settings` default language (e.g., English)
  1. Check `Domains` list, see that there is no domain URL with default language (e.g., English)

# Configuration

### Email template variable usage (supported as of Shopware 6.4.4.0)

For create order emails:

```html
{% set shopgateOrder = order.extensions.shopgateOrder %}

Selected shipping type:
{% if shopgateOrder %}
{{ shopgateOrder.receivedData.shipping_infos.display_name }}
{% else %}
{{ delivery.shippingMethod.translated.name }}
{% endif %}

Payment Type:
{% if shopgateOrder %}
{{ shopgateOrder.receivedData.payment_infos.shopgate_payment_name }}
{% endif %}
```

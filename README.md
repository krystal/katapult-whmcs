# Katapult WHMCS Module

You can set the Katapult API key in a products settings under the 'Other' tab, after selecting the product as 'Katapult'. This key will be used for all products.

Alternatively, you set your Katapult API key in your WHMCS configuration file, or other secure file which is included at WHMCS runtime:

```php
define('KATAPULT_API_V1_KEY', 'M8X34xE........57Jr7f');
```

When using the constant method, the GUI to update the API key will be hidden from the products configuration page in WHMCS.



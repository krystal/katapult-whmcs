# Katapult WHMCS Module

Access the full documentation for this module at [Katapult Developer Docs](https://developers.katapult.io/docs/category/whmcs).

## Requirements

* **PHP:** Version 8.1 or higher
* **WHMCS:** Version 8.0 or higher
* **Katapult account:** Must have access to managed organizations
* **API Key:** Katapult organization API key

## Versioning

This module uses [semantic versioning](https://semver.org/).

## Security

If you find any security related issues, please email [contact@krystal.io](mailto:contact@krystal.io) instead of using the issue tracker.

## Installing and upgrading

1. Download the [latest `katapult.zip`](https://github.com/krystal/katapult-whmcs/releases).
2. Extract it to `/whmcs/modules/servers/katapult`. Your file structure should look like this:

```plain
├── /whmcs/modules/servers/katapult
│   ├── assets
│   ├── helpers.php
│   ├── hooks.php
│   ├── katapult.php
│   ├── lib
│   ├── overrides
│   ├── README.md
│   ├── vendor
│   └── views
```

You can now use the Katapult module in WHMCS.

## Development

You can clone this repository directly into your development WHMCS installation, for example using [this Vagrant box](https://github.com/grizzlyware/whmcs-dev/):

```shell
# Assuming you're WHMCS installation is at `/var/www/html/whmcs`
# This would normally be run on your host machine, not in the Vagrant VM
cd /var/www/html/whmcs/modules/servers
git clone git@github.com:krystal/katapult-whmcs.git katapult
cd katapult
composer install
```

### Building the module for distribution

After making changes to the module, you need to create a new ZIP file to attach to the release for distribution.

There are a few dev dependencies, which are not required for releases.

Steps to package the module:

* Install Composer dependencies (with `--no-dev`)
* Remove untracked files
* Deny access to `vendor` with `.htaccess`

The `build:server-module` command does all this for you.

```shell
./bin/katapult build:server-module
```

This creates a `katapult.zip` file in your `build` directory and outputs the full path.

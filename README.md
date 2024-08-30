<p align="center"><a href="https://github.com/krystal/katapult-whmcs"><img src="./build/katapult-logo.png" alt="Katapult logo" /></a></p>

# Katapult WHMCS Module

Full documentation for this module can be found at [Katapult Developer Docs](https://developers.katapult.io/docs/category/whmcs).
    
## Requirements
* PHP >= 8.1
* WHMCS >= 8.0
* A Katapult account with access to managed organizations
* A Katapult organization API key

## Versioning
This module follows [semantic versioning](https://semver.org/).

## Security
If you discover any security related issues, please email contact@krystal.uk instead of using the issue tracker.

## Installing and upgrading
Download the [latest katapult.zip](https://github.com/krystal/katapult-whmcs/releases) and extract it to `/whmcs/modules/servers/katapult`. The resulting file structure should look similar to this:

```
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

The Katapult module will then be available for you to use inside WHMCS.

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
When changes have been made to the module, and a new release is being published, a new ZIP file will need to be created to attach to the release. These ZIP files are used to distribute and install the module into WHMCS.

The module has a few require-dev dependencies which aren't required for use within WHMCS. Guzzle being one which has been known to cause conflicts with WHMCS, and is required by [krystal/katapult-php](https://github.com/krystal/katapult-php). Because WHMCS has Guzzle installed, we don't need to include it the distribution of this module.

Things to consider when packaging the module up:

* Installing the required Composer dependencies
* Removing development and untracked files
* Adding special files such as `.htaccess` in `/vendor` (WHMCS restriction with it being in the docroot)
* Zipping it up consistently between versions.

To build the module automatically:

```shell
$ ./bin/katapult build:server-module
```

This will result in a `katapult.zip` file in your `build` directory, the full path will be outputted by the command.

# Aruba SwitchAPI (ArubaOS)

Aruba SwitchAPI is a PHP library for requesting Aruba switches (ArubaOS). This library can retrieve, create, update and delete configuration on the switch. It wan be used to :

* Configure switch from a PHP designed web interface.
* Backup switch configuration with a PHP script.
* So much more, it is up to you !

**Warning** : This library is incomplete and mainly oriented towards POE, VLAN, port and LED locator. Contributions are welcome !

You can find all supported methods on [Aruba website](https://h10145.www1.hpe.com/Downloads/ProductsList.aspx), choose your equipment and download the API documentation.

## Table of contents

<!--ts-->
   * [Getting started](#getting-started)
   * [Documentation](#documentation)
      * [Config class](#config-class)
      * [SwitchAPI class](#switchapi-class)
         * [cli()](#cli)
<!--te-->

## Getting started

1. Get [Composer](http://getcomposer.org/).
2. Install the library using composer `composer require benclerc/aruba-switchapi`.
3. Add the following to your application's main PHP file `require 'vendor/autoload.php';`.
4. Instanciate the Config class with the switch's hostname, username and password `$configSwitch = new \Aruba\Config('123.123.123.123', 'admin', 'password');`.
5. Use the Config object previously created to instanciate the SwitchAPI object `$switch = new \Aruba\SwitchAPI($configSwitch);`.
6. Start using the library `$runningConf = $switch->getRunningConfig();`.

## Documentation

You can find a full documentation [here](https://benclerc.github.io/Aruba-SwitchAPI/).

### Config class

This Config class is used to prepare the mandatory configuration information to instanciate and use the SwitchAPI class. In the constructor you must pass :

1. The switch's hostname (FQDN) or IP address
2. A valid user's username
3. The valid user's password

Optional parameters :

* CURL timeout : 5000ms. Use `setTimeout()` to change.
* CURL SSL verify peer option : TRUE. Use `setSSLVerifyPeer()` to change.
* CURL SSL verify host option : 2. Use `setSSLVerifyHost()` to change.
* API version : 'v7'. Use `setAPIVersion()` to change (only >= v7 are supported).

Example :

```php
// Basic configuration
$configSwitch = new \Aruba\Config('123.123.123.123', 'admin', 'password');

// Configuration for very slow switchs/long requests
$configSwitch = new \Aruba\Config('123.123.123.123', 'admin', 'password');
$configSwitch->setTimeout(20000);

// Unsecure configuration
$configSwitch = new \Aruba\Config('123.123.123.123', 'admin', 'password');
$configSwitch->setSSLVerifyPeer(FALSE)->setSSLVerifyHost(FALSE);

// Special API version
$configSwitch = new \Aruba\Config('123.123.123.123', 'admin', 'password');
$configSwitch->setAPIVersion('v8');

$switch = new \Aruba\SwitchAPI($configSwitch);
```

### SwitchAPI class

#### cli()

This method allows to execute a basic CLI command and get the result.

Examples :

```php
// Get startup-config
try {
	$startupConf = $switch->cli('show startup-config');
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}
```



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
         * [Available methods](#available-methods)
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

#### Available methods

* [blinkLedLocator()](classes/Aruba-SwitchAPI.html#method_blinkLedLocator) : Turn on or off the locator LED. If no duration is set, default to 30 minutes.
* [cli()](classes/Aruba-SwitchAPI.html#method_cli) : Execute a CLI command.
* [createVlan()](classes/Aruba-SwitchAPI.html#method_createVlan) : Create a VLAN on the switch.
* [deleteVlan()](classes/Aruba-SwitchAPI.html#method_deleteVlan) : Delete a VLAN on the switch.
* [disablePoePort()](classes/Aruba-SwitchAPI.html#method_disablePoePort) : Disable POE on a port.
* [disablePort()](classes/Aruba-SwitchAPI.html#method_disablePort) : Disable a port.
* [enablePoePort()](classes/Aruba-SwitchAPI.html#method_enablePoePort) : Enable POE on a port.
* [enablePort()](classes/Aruba-SwitchAPI.html#method_enablePort) : Enable a port.
* [getMacAddressInfo()](classes/Aruba-SwitchAPI.html#method_getMacAddressInfo) : Get infos about a MAC address.
* [getMacTable()](classes/Aruba-SwitchAPI.html#method_getMacTable) : Get MAC table of the switch.
* [getMacTablePort()](classes/Aruba-SwitchAPI.html#method_getMacTablePort) : Get MAC table of a port.
* [getPortsPOEStatus()](classes/Aruba-SwitchAPI.html#method_getPortsPOEStatus) : Get all ports POE status.
* [getPortsStatus()](classes/Aruba-SwitchAPI.html#method_getPortsStatus) : Get all ports status.
* [getRunningConfig()](classes/Aruba-SwitchAPI.html#method_getRunningConfig) : Get runnning configuration.
* [getTVlanPort()](classes/Aruba-SwitchAPI.html#method_getTVlanPort) : Get the tagged vlan for one port.
* [getUVlanPort()](classes/Aruba-SwitchAPI.html#method_getUVlanPort) : Get the untagged vlan for one port.
* [getVlanPorts()](classes/Aruba-SwitchAPI.html#method_getVlanPorts) : Get list of ports for one vlan.
* [getVlans()](classes/Aruba-SwitchAPI.html#method_getVlans) : Get all VLANs on the switch.
* [getVlansPort()](classes/Aruba-SwitchAPI.html#method_getVlansPort) : Get list of vlans affected to one port.
* [getVlansPorts()](classes/Aruba-SwitchAPI.html#method_getVlansPorts) : Get list of vlans/ports association.
* [isPortEnabled()](classes/Aruba-SwitchAPI.html#method_isPortEnabled) : Check if a port is enabled.
* [isPortUp()](classes/Aruba-SwitchAPI.html#method_isPortUp) : Check if a port is up.
* [portPoeStatus()](classes/Aruba-SwitchAPI.html#method_portPoeStatus) : Check if a port is POE enabled.
* [restartPoePort()](classes/Aruba-SwitchAPI.html#method_restartPoePort) : Restart POE on a port.
* [restartPort()](classes/Aruba-SwitchAPI.html#method_restartPort) : Disable a port 5sec and re-enable it.
* [setTVlanPort()](classes/Aruba-SwitchAPI.html#method_setTVlanPort) : Set tagged VLAN(s) on port.
* [setUVlanPort()](classes/Aruba-SwitchAPI.html#method_setUVlanPort) : Set untagged VLAN on port.
* [updateVlan()](classes/Aruba-SwitchAPI.html#method_updateVlan) : Update a VLAN on the switch.

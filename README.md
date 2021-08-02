# Aruba SwitchAPI (ArubaOS)

Aruba SwitchAPI is a PHP library for requesting Aruba switches (ArubaOS). This library can retrieve, create, update and delete configuration on the switch. It wan be used to :

* Configure switch from a PHP designed web interface.
* Backup switch configuration with a PHP script.
* So much more, it is up to you !

**Warning** : This library is incomplete and mainly oriented towards POE, VLAN, port and LED locator. Contributions are welcome !

You can find all supported methods on [Aruba's website](https://h10145.www1.hpe.com/Downloads/ProductsList.aspx), choose your equipment and download the API documentation.

## Table of contents

<!--ts-->
   * [Getting started](#getting-started)
   * [Documentation](#documentation)
      * [Config class](#config-class)
      * [SwitchAPI class](#switchapi-class)
         * [Usage](#usage)
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

* Timeout : 5000ms. Use `setTimeout()` to change.
* SSL verify peer option : TRUE. Use `setSSLVerifyPeer()` to change.
* SSL verify host option : 2. Use `setSSLVerifyHost()` to change.
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

// The class logins to the switch when being instanciated hence the try/catch statement. 
try {
	$switch = new \Aruba\SwitchAPI($configSwitch);
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}
```

### SwitchAPI class

#### Usage

This class uses Exception to handle errors, for nominal execution you should instanciate and request methods inside try/catch statements.

Examples :

```php
// Blink for 1 min LED locator
try {
	$res = $switch->blinkLedLocator(2, 1);
	if ($res) {
		echo('Blink succeeded');
	} else {
		echo('Blink failed');
	}
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}

// Create a VLAN
try {
	$res = $switch->createVlan(666, 'HELL');
	if ($res) {
		echo('The VLAN has been created.');
	} else {
		echo('Error : the VLAN was not created.');
	}
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}

// Get status of all ports
try {
	$res = $switch->getPortsStatus();
	if ($res != FALSE) {
		foreach ($res as $key => $value) {
			$status = ($value->is_port_enabled) ? 'up' : 'down';
			echo('Port '.$value->id.' is '.$status.'<br>');
		}
	} else {
		echo('Error : status could not be retrieved.');
	}
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}

// Set untagged VLAN 666 on port 42
try {
	$res = $switch->setUVlanPort(666, '42');
	if ($res) {
		echo('The VLAN 666 has been affected to the port 42.');
	} else {
		echo('Error : the VLAN was not affected.');
	}
} catch (Exception $e) {
	echo('Handle error : '.$e->getMessage());
}
```

#### Available methods

You can browse all available methods [here](https://benclerc.github.io/Aruba-SwitchAPI/classes/Aruba-SwitchAPI.html).

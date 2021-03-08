<?php

/**
* 	Library used for interacting with Aruba switch (ArubaOS) API.
*	@author Benjamin Clerc <contact@benjamin-clerc.com>
*	@copyright Copyright (c) 2020, Benjamin Clerc.
*	@license MIT
*	@link https://github.com/benclerc/ArubaOSAPI
*/

namespace Aruba;

use Exception;

/**
* 	SwitchOSAPI's configuration class
*/
class Config {
	private string $hostname;
	private string $username;
	private string $password;
	private int $timeout = 5000;
	private bool $SSLVerifyPeer = TRUE;
	private int $SSLVerifyHost = 2;
	private string $apiVersion = 'v7';


	/**
	*	@param string $hostname switch's FQDN or IP address
	*	@param string $username API autorized user
	*	@param string $password API autorized user's password
	*	@return Config Config object to be passed on a new instance of SwitchOSAPI object.
	*/
	public function __construct(string $hostname, string $username, string $password) {
		// Check and register firewall's hostname
		if (filter_var($hostname, FILTER_VALIDATE_DOMAIN)) {
			$this->hostname = $hostname;
		} else {
			throw new Exception('__construct() : Invalid hostname provided.');
		}
		// Register username and password
		$this->username = $username;
		$this->password = $password;
	}


	/**
	*	Getter for firewall's FQDN.
	*	@return string Firewall's FQDN.
	*/
	public function getHostname() {
		return $this->hostname;
	}


	/**
	*	Getter for API autorized user.
	*	@return string API autorized user.
	*/
	public function getUsername() {
		return $this->username;
	}


	/**
	*	Getter for API autorized user's password.
	*	@return string API autorized user's password.
	*/
	public function getPassword() {
		return $this->password;
	}


	/**
	*	Setter for curl's timeout in ms.
	*	@param int $timeout Curl's timeout in ms.
	*	@return Config Config object to be passed on a new instance of SwitchOSAPI object.
	*/
	public function setTimeout(int $timeout) {
		$this->timeout = $timeout;
		return $this;
	}


	/**
	*	Getter for curl's timeout in ms.
	*	@return int Curl's timeout in ms.
	*/
	public function getTimeout() {
		return $this->timeout;
	}


	/**
	*	Setter for curl's option to verify SSL peer.
	*	@param int $verifySSLPeer Curl's option to verify SSL peer.
	*	@return Config Config object to be passed on a new instance of SwitchOSAPI object.
	*/
	public function setSSLVerifyPeer(bool $verifySSLPeer) {
		$this->SSLVerifyPeer = $verifySSLPeer;
		return $this;
	}


	/**
	*	Getter for curl's option to verify SSL peer.
	*	@return bool Curl's option to verify SSL peer.
	*/
	public function getSSLVerifyPeer() {
		return $this->SSLVerifyPeer;
	}


	/**
	*	Setter for curl's option to verify SSL peer.
	*	@param bool $verifySSLHost Curl's option to verify SSL host.
	*	@return Config Config object to be passed on a new instance of SwitchOSAPI object.
	*/
	public function setSSLVerifyHost(bool $verifySSLHost) {
		switch ($verifySSLHost) {
			case TRUE:
				$this->SSLVerifyHost = 2;
				break;
			case FALSE:
				$this->SSLVerifyHost = 0;
				break;
		}
		return $this;
	}


	/**
	*	Getter for curl's option to verify SSL peer.
	*	@return int Curl's option to verify SSL host.
	*/
	public function getSSLVerifyHost() {
		return $this->SSLVerifyHost;
	}


	/**
	*	Setter for API version to use.
	*	@param string $version API version to use e.g. 'v7'.
	*	@return Config Config object to be passed on a new instance of SwitchOSAPI object.
	*/
	public function setAPIVersion(string $version) {
		$this->apiVersion = $version;
		return $this;
	}


	/**
	*	Getter for API version to use.
	*	@return string API version to use.
	*/
	public function getAPIVersion() {
		return $this->apiVersion;
	}

}
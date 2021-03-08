<?php

/**
* 	Library used for interacting with Aruba switch (ArubaOS) API.
*	@author Benjamin Clerc <contact@benjamin-clerc.com>
*	@copyright Copyright (c) 2020, Benjamin Clerc.
*	@license MIT
*	@link https://github.com/benclerc/Aruba-SwitchAPI
*/

namespace Aruba;

use Exception;
use stdClass;

/**
* 	Switch ArubaOS API
*	@property Config $config Config object with all needed information.
*	@property string $token Authentication token kept in cache.
*	@property array $cache Variable use to cache information.
*	@link https://h10145.www1.hpe.com/Downloads/ProductsList.aspx Aruba ressource downloader, you can find API documentation in the product page.
*/
class SwitchAPI {
	private Config $config;
	private $token = '';
	private $cache = [];


	/**
	*	Constructor takes care of checking and registering switch's data and login to the API
	*	@param Config $config Object containing all necessary configuration.
	*/
	public function __construct(Config $config) {
		$this->config = $config;
		// Login
		$this->login();
	}


	/**
	*	Method to request the switch's API
	*	@param string $method HTTP method (e.g. 'GET', 'POST', 'PUT', 'DELETE' ...).
	*	@param string $endpoint API endpoint without the 2 first element ('rest' and API version '/rest/vX'), e.g. /login-sessions.
	*	@param string $data Data to be passed in the request body as a JSON document (e.g. '{"userName":"api","password":"api"}').
	*	@return mixed Return switch's response as a PHP array if any or TRUE on success without response.
	*/
	private function curlRequest(string $method, string $endpoint, string $data = NULL, int $timeout = NULL) {
		// Init CURL
		$ch = curl_init();

		// Set CURL options (URL, method used, return response in variable, data if exists and timeout)
		curl_setopt($ch, CURLOPT_URL, 'https://'.$this->config->getHostname().'/rest/'.$this->config->getAPIVersion().$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if (isset($data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->getSSLVerifyPeer());
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config->getSSLVerifyHost());
		$curlTimeout = (empty($timeout)) ? $this->config->getTimeout() : $timeout;
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curlTimeout);

		// Set headers for authentication
		$headers = array();
		$headers[] = 'Content-Type: text/plain';
		// If token exist, add it in the headers
		if (!empty($this->token)) {
			$headers[] = 'Cookie: '.$this->token;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// Execute CURL
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
		    throw new Exception('curlRequest() : Curl error : '.curl_error($ch));
		}
		// Close CURL
		curl_close ($ch);

		// Decode response
		if (!empty($result)) {
			$resultJSON = json_decode($result);
			if (json_last_error() === JSON_ERROR_NONE) {
				if (!empty($resultJSON->message)) {
					throw new Exception('curlRequest() called by '.debug_backtrace()[1]['function'].'() : API returned error : '.$resultJSON->message);
				} else {
					// Return decoded JSON response
					return $resultJSON;
				}
			} else {
				throw new Exception('curlRequest() called by '.debug_backtrace()[1]['function'].'() : Curl response is not JSON as expected.');
			}
		} else {
			return TRUE;
		}
	}


	/**
	*	Login in the switch.
	*	@return bool Return TRUE if successful or throw Exception if fails.
	*/
	private function login() : bool {
		// Create and fill data object
		$data = new stdClass();
		$data->userName = $this->config->getUsername();
		$data->password = $this->config->getPassword();
		// Login
		$res = $this->curlRequest('POST', '/login-sessions', json_encode($data));

		if (!empty($res->cookie)) {
			$this->token = $res->cookie;
			return TRUE;
		} else {
			throw new Exception('login() : Login failed');
		}
	}


	/**
	*	Execute a CLI command.
	*	@param string $command A Aruba OS valid CLI command.
	*	@return mixed Return decoded response if success or FALSE on fail.
	*/
	public function cli(string $command) {
		// Create and fill data object
		$data = new stdClass();
		$data->cmd = $command;

		// Send request (add 10sec to classic timeout because CLI commands might take more time to process especially if it is configuration generation related)
		$res = $this->curlRequest('POST', '/cli', json_encode($data), $this->config->getTimeout()+10000);

		// Check if the command was successful
		if ($res->status == 'CCS_SUCCESS') {
			return base64_decode($res->result_base64_encoded);
		} else {
			return FALSE;
		}
	}


	/**
	*	Get runnning configuration.
	*	@return mixed Return the configuration if successful, FALSE if it failed.
	*/
	public function getRunningConfig() {
		// Execute CLI command
		return $this->cli('show running-config');
	}


	/**
	*	Turn on or off the locator LED. If no duration is set, default to 30 minutes.
	*	@param int $mode 1 : blink mode, 2 : on, 3 : off.
	*	@param int $duration Duration of the mode wanted in minutes.
	*	@return Return the configuration if successful, FALSE if it failed.
	*/
	public function blinkLedLocator(int $mode, int $duration = 30) {
		// Check if duration is valid. Must be between 1 and 1440.
		if ($duration < 0 || $duration > 1440) {
			throw new Exception('blinkLedLocator() called by '.debug_backtrace()[1]['function'].'() : duration is invalid. Must be between 1 and 1440.');
		}

		// Create and fill data object
		$data = new stdClass();
		// 3 modes : LS_BLINK, LS_ON and LS_OFF
		switch ($mode) {
			case 0:
				$data->led_blink_status = 'LS_OFF';
				break;
			case 1:
				$data->led_blink_status = 'LS_ON';
				break;
			case 2:
				$data->led_blink_status = 'LS_BLINK';
				break;
			
			default:
				throw new Exception('blinkLedLocator() called by '.debug_backtrace()[1]['function'].'() : LED mode is invalid. Must be between 1 and 3.');
				break;
		}
		$data->when = 'LBT_NOW';
		$data->duration_in_minutes = $duration;

		// Send request
		return $this->curlRequest('POST', '/locator-led-blink', json_encode($data));
	}


	/**
	*	VLAN MANAGEMENT
	*/


	/**
	*	Get list of vlans/ports association.
	*	@return mixed Return the list as an array of objects if successful, FALSE if it failed.
	*/
	public function getVlansPorts() {
		// Check if the information is not already stored in cache, if it is return it else request it
		if (isset($this->cache['getVlansPorts'])) {
			return $this->cache['getVlansPorts'];
		} else {
			// Request info
			$res = $this->curlRequest('GET', '/vlans-ports');

			// Check waited parameters are here, save result in cache and return result
			if (isset($res->vlan_port_element)) {
				$this->cache['getVlansPorts'] = $res->vlan_port_element;
				return $res->vlan_port_element;
			} else {
				return FALSE;
			}
		}
	}


	/**
	*	Get list of vlans affected to one port.
	*	@param string $port Port id.
	*	@return mixed Return the list as an array of objects if successful, FALSE if it failed.
	*/
	public function getVlansPort(string $port) {
		// Request info
		$res = $this->getVlansPorts();

		// If result is valid, iterate through it and keep only the wanted port
		if ($res !== FALSE) {
			$return = [];
			foreach ($res as $key => $value) {
				if ($value->port_id == "$port") { $return[] = $value; }
			}
			// If we did not find the port then return FALSE
			if (!isset($return)) { $return = FALSE; }
		} else {
			$return = FALSE;
		}
		return $return;
	}


	/**
	*	Get the untagged vlan for one port.
	*	@param string $port Port id.
	*	@return mixed Return the port association object info if successful, FALSE if it failed.
	*/
	public function getUVlanPort(string $port) {
		// Request info
		$res = $this->getVlansPorts();

		// If result is valid, iterate through it and keep only the wanted VLAN association
		if ($res !== FALSE) {
			foreach ($res as $key => $value) {
				if ($value->port_id == $port && $value->port_mode == "POM_UNTAGGED") { $return = $value; break; }
			}
			// If we did not find the port then return FALSE
			if (!isset($return)) { $return = FALSE; }
		} else {
			$return = FALSE;
		}
		return $return;
	}


	/**
	*	Get the tagged vlan for one port.
	*	@param string $port Port id.
	*	@return mixed Return the port association object info list if successful, FALSE if it failed.
	*/
	public function getTVlanPort(string $port) {
		// Request info
		$res = $this->getVlansPorts();

		// If result is valid, iterate through it and keep only the wanted VLAN association
		if ($res !== FALSE) {
			$return = [];
			foreach ($res as $key => $value) {
				if ($value->port_id == $port && $value->port_mode == "POM_TAGGED_STATIC") { $return[] = $value; }
			}
		} else {
			$return = FALSE;
		}
		return $return;
	}


	/**
	*	Get list of ports for one vlan.
	*	@param int $vlan VLAN id.
	*	@return mixed Return the list as an array of objects if successful, FALSE if it failed.
	*/
	public function getVlanPorts(int $vlan) {
		// Request info
		$res = $this->getVlansPorts();

		// If result is valid, iterate through it and keep only the wanted vlan
		if ($res !== FALSE) {
			$return = [];
			foreach ($res as $key => $value) {
				if ($value->vlan_id == $vlan) { $return[] = $value; }
			}
			// If we did not find the vlan then return FALSE
			if (empty($return)) { $return = FALSE; }
		} else {
			$return = FALSE;
		}
		return $return;
	}


	/**
	*	Get all VLANs on the switch.
	*	@return mixed Return the list as an array of objects if successful, FALSE if it failed.
	*/
	public function getVlans() {
		// Check if the information is not already stored in cache, if it is return it else request it
		if (isset($this->cache['getVlans'])) {
			return $this->cache['getVlans'];
		} else {
			// Send request
			$res = $this->curlRequest('GET', '/vlans');
			
			if ($res->collection_result->total_elements_count > 0 && !empty($res->vlan_element)) {
				$this->cache['getVlans'] = $res->vlan_element;
				return $res->vlan_element;
			} else {
				return FALSE;
			}
		}
	}


	/**
	*	Create a VLAN on the switch.
	*	@param int $vlan VLAN id.
	*	@param string $name VLAN name.
	*	@return bool Return TRUE if successful, FALSE if it failed.
	*/
	public function createVlan(int $vlan, string $name) : bool {
		// Check if the VLAN is already created
		$currentConfiguration = $this->getVlans();
		$found = FALSE;
		foreach ($currentConfiguration as $key => $value) {
			if ($value->vlan_id == $vlan) {
				$found = TRUE;
				break;
			}
		}

		// If VLAN was not found, create it
		if (!$found) {
			// Create and fill data object
			$data = new stdClass();
			$data->vlan_id = $vlan;
			$data->name = $name;

			// Send request
			$res = $this->curlRequest('POST', '/vlans', json_encode($data));

			if ($res->vlan_id == $vlan && $res->name == $name) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return TRUE;
		}
	}


	/**
	*	Update a VLAN on the switch.
	*	@param int $vlan VLAN id.
	*	@param string $name VLAN name.
	*	@return bool Return TRUE if successful, FALSE if it failed.
	*/
	public function updateVlan(int $vlan, string $name) {
		// Check if the VLAN already exists
		$currentConfiguration = $this->getVlans();
		$found = FALSE;
		foreach ($currentConfiguration as $key => $value) {
			if ($value->vlan_id == $vlan) {
				$found = TRUE;
				break;
			}
		}

		// If VLAN was found, update it
		if ($found) {
			// Create and fill data object
			$data = new stdClass();
			$data->name = $name;

			// Send request
			return $this->curlRequest('POST', '/vlans/'.$vlan, json_encode($data));
		} else {
			return FALSE;
		}
	}


	/**
	*	Delete a VLAN on the switch.
	*	@param int $vlan VLAN id.
	*	@return bool Return TRUE if successful, FALSE if it failed.
	*/
	public function deleteVlan(int $vlan) : bool {
		// Check if the VLAN exists
		$currentConfiguration = $this->getVlans();
		$found = FALSE;
		foreach ($currentConfiguration as $key => $value) {
			if ($value->vlan_id == $vlan) {
				$found = TRUE;
				break;
			}
		}

		// If VLAN was found, delete it
		if ($found) {
			// Send request
			return $this->curlRequest('DELETE', '/vlans/'.$vlan);
		} else {
			return TRUE;
		}
	}


	/**
	*	Set untagged VLAN on port.
	*	@param int $vlan VLAN id.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function setUVlanPort(int $vlan, string $port) : bool {
		// Get the current configuration
		$currentUVlan = $this->getUVlanPort($port)->vlan_id;

		// If the wanted configuration is already set, do nothing, else change it
		if ($currentUVlan == $vlan) {
			return TRUE;
		} else {
			// Check if the wanted untagged VLAN is not already a tagged one. If it is then remove it.
			$currentTVlan = $this->getTVlanPort($port);
			foreach ($currentTVlan as $key => $value) {
				if ($value->vlan_id == $vlan) {
					// Send request to delete association in a try catch to catch error coming from curlRequest()
					try {
						$res = $this->curlRequest('DELETE', '/vlans-ports/'.$value->vlan_id.'-'.$port);
						if ($res !== TRUE) {
							throw new Exception('setUVlanPort() : Wanted untagged VLAN '.$vlan.' is already a tagged VLAN and were not able to be removed before set as untagged on port '.$port.'.');
						}
					} catch (Exception $e) {
						throw new Exception('setUVlanPort() : Wanted untagged VLAN '.$vlan.' is already a tagged VLAN and were not able to be removed before set as untagged on port '.$port.'. Previous exception : '.$e->getMessage());
					}
					// Break the loop because the VLAN cannot be present more than once.
					break;
				}
			}

			// Create and fill data object
			$data = new stdClass();
			$data->vlan_id = $vlan;
			$data->port_id = $port;
			$data->port_mode = 'POM_UNTAGGED';

			// Send request
			$res = $this->curlRequest('POST', '/vlans-ports', json_encode($data));

			// Remove VlansPorts cache
			unset($this->cache['getVlansPorts']);

			// Check if the request was correctly applied
			if ($res->vlan_id === $vlan || $res->port_id === "$port") {
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}


	/**
	*	Set tagged VLAN(s) on port.
	*	@param array $vlans [VLAN ids].
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*	@link Working but be careful ongoing forum post about not being able to remove tagged vlan if it's the default vlan (1). More info : https://community.arubanetworks.com/t5/Wired-Intelligent-Edge-Campus/Removing-default-VLAN-from-REST-not-working/td-p/674880
	*/
	public function setTVlanPort(array $vlans, string $port) : bool {
		// Get the current configuration
		$resCurrentVlans = $this->getVlansPort($port);

		// Get all current tagged vlans, if the untagged vlan is in $vlans array throw exception
		$currentVlans = [];
		foreach ($resCurrentVlans as $key => $value) {
			if ($value->port_mode == 'POM_TAGGED_STATIC') {
				$currentVlans[] = $value->vlan_id;
			} elseif ($value->port_mode == 'POM_UNTAGGED' && in_array($value->vlan_id, $vlans)) {
				throw new Exception('setTVlanPort() : Cannot set tagged VLAN '.$value->vlan_id.' because already set as untagged on port '.$port.'.');
			}
		}

		// Create and fill data object
		$data = new stdClass();
		$data->port_id = "$port";
		$data->port_mode = 'POM_TAGGED_STATIC';

		// Add tagged vlans wanted which are not already tagged on this port
		foreach ($vlans as $key => $value) {
			if (!in_array($value, $currentVlans)) {
				$data->vlan_id = $value;
				// Send request in a try catch to catch error coming from curlRequest()
				try {
					$res = $this->curlRequest('POST', '/vlans-ports', json_encode($data));
					if ($res->vlan_id != $value || $res->port_id != $port || $res->port_mode != 'POM_TAGGED_STATIC') {
						throw new Exception('setTVlanPort() : Cannot set tagged VLAN '.$value.' on port '.$port.'. Initial configuration : '.implode(', ', $currentVlans).'. Wanted configuration : '.implode(', ', $vlans).'.');
					}
				} catch (Exception $e) {
					throw new Exception('setTVlanPort() : Cannot set tagged VLAN '.$value.' on port '.$port.'. Initial configuration : '.implode(', ', $currentVlans).'. Wanted configuration : '.implode(', ', $vlans).'. Previous exception : '.$e->getMessage());
				}
			}
		}

		// Remove vlan associations not wanted anymore on this port
		foreach ($currentVlans as $key => $value) {
			if (!in_array($value, $vlans)) {
				// Send request in a try catch to catch error coming from curlRequest()
				try {
					$res = $this->curlRequest('DELETE', '/vlans-ports/'.$value.'-'.$port);
					if ($res !== TRUE) {
						throw new Exception('setTVlanPort() : Cannot remove tagged VLAN '.$value.' on port '.$port.'. Initial configuration : '.implode(', ', $currentVlans).'. Wanted configuration : '.implode(', ', $vlans).'.');
					}
				} catch (Exception $e) {
					throw new Exception('setTVlanPort() : Cannot remove tagged VLAN '.$value.' on port '.$port.'. Initial configuration : '.implode(', ', $currentVlans).'. Wanted configuration : '.implode(', ', $vlans).'. Previous exception : '.$e->getMessage());
				}
			}
		}

		// Remove VlansPorts cache
		unset($this->cache['getVlansPorts']);
		// Get the new configuration
		$resNewVlans = $this->getVlansPort($port);

		// Check if made changes are OK
		$newVlans = [];
		foreach ($resNewVlans as $key => $value) {
			if ($value->port_mode == 'POM_TAGGED_STATIC') {
				$newVlans[] = $value->vlan_id;
			}
		}
		// Sort both arrays
		sort($vlans); // Wanted
		sort($newVlans); // Real
		// Compare and return
		if ($vlans == $newVlans) {
			return TRUE;
		} else {
			throw new Exception('setTVlanPort() : The applied changes did not return any error but do not correspond to the wanted configuration. Details : wanted tagged VLANs : '.implode(', ', $vlans).' VS real tagged VLANs : '.implode(', ', $newVlans).' on port '.$port.'.');
			return FALSE;
		}
	}


	/**
	*	PORT MANAGEMENT
	*/


	/**
	*	Get all ports status.
	*	@return array Return an array like [1=>['up'=>TRUE, 'enable'=>TRUE], 2=>['up'=>FALSE, 'enable'=>TRUE], ...].
	*/
	public function getPortsStatus() : array {
		// Check if port info exists in cache, else request the information
		if (!empty($this->cache['getPortsStatus'])) {
			$res = $this->cache['getPortsStatus'];
		} else {
			// Send request
			$res = $this->curlRequest('GET', '/ports');
			$this->cache['getPortsStatus'] = $res;
		}

		// Return result
		return $res->port_element;
	}


	/**
	*	Check if a port is enabled.
	*	@param string $port Port id.
	*	@return bool Return TRUE if the port is enabled, FALSE if not.
	*/
	public function isPortEnabled(string $port) : bool {
		// Check if port info exists in cache, else request the information
		if (!empty($this->cache['isPort'][$port])) {
			$res = $this->cache['isPort'][$port];
		} else {
			// Send request
			$res = $this->curlRequest('GET', '/ports/'.$port);
			$this->cache['isPort'][$port] = $res;
		}

		// Check if enabled
		if ($res->is_port_enabled === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Enable a port.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function enablePort(string $port) : bool {
		// Create and fill data object
		$data = new stdClass();
		$data->id = "$port";
		$data->is_port_enabled = TRUE;

		// Send request
		$res = $this->curlRequest('PUT', '/ports/'.$port, json_encode($data));
		
		// Update or create cached info
		$this->cache['isPort'][$port] = $res;

		// Check if the request was correctly applied
		if ($res->is_port_enabled === TRUE || $res->port_id === "$port") {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Disable a port.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function disablePort(string $port) : bool {
		// Create and fill data object
		$data = new stdClass();
		$data->id = "$port";
		$data->is_port_enabled = FALSE;

		// Send request
		$res = $this->curlRequest('PUT', '/ports/'.$port, json_encode($data));
		
		// Update or create cached info
		$this->cache['isPort'][$port] = $res;

		// Check if the request was correctly applied
		if ($res->is_port_enabled === FALSE || $res->port_id === "$port") {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Disable a port 5sec and re-enable it. Be careful when disabling links between switchs or firewalls ...
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function restartPort(string $port) : bool {
		// Disable
		$resDisable = $this->disablePort($port);
		if ($resDisable === TRUE) {
			// Sleep 5 seconds
			sleep(5);
			// Re-enable
			$resEnable = $this->enablePort($port);
		}

		// Check if the everything went well and return
		if ($resDisable === TRUE && $resEnable === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Check if a port is up.
	*	@param string $port Port id.
	*	@return bool Return TRUE if the port is up, FALSE if not.
	*/
	public function isPortUp(string $port) : bool {
		// Check if port info exists in cache, else request the information
		if (!empty($this->cache['isPort'][$port])) {
			$res = $this->cache['isPort'][$port];
		} else {
			// Send request
			$res = $this->curlRequest('GET', '/ports/'.$port);
			$this->cache['isPort'][$port] = $res;
		}
		
		// Check if up
		if ($res->is_port_up === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Get infos about a MAC address.
	*	@param $mac = MAC address format 123456-789abc.
	*	@return mixed Return the object if successful, FALSE if it failed.
	*/
	public function getMacAddressInfo($mac) {
		// Send request in try catch to catch curlrequest's possible 404 error if MAC is unknown
		try {
			return $this->curlRequest('GET', '/mac-table/'.$mac);
		} catch (Exception $e) {
			return FALSE;
		}
	}


	/**
	*	Get MAC table of the switch.
	*	@return Return an array of objects if successful, FALSE if it failed.
	*/
	public function getMacTable() {
		// Send request
		$res = $this->curlRequest('GET', '/mac-table');

		// Check if the request went well and return
		if (isset($res->mac_table_entry_element)) {
			return $res->mac_table_entry_element;
		} else {
			return FALSE;
		}
	}


	/**
	*	Get MAC table of a port.
	*	@param string $port Port id.
	*	@return Return an array of objects if successful, FALSE if it failed.
	*/
	public function getMacTablePort(string $port) {
		// Send request
		$res = $this->curlRequest('GET', '/ports/'.$port.'/mac-table');

		// Check if the request went well and return
		if (isset($res->mac_table_entry_element)) {
			return $res->mac_table_entry_element;
		} else {
			return FALSE;
		}
	}


	/**
	*	POE PORT MANAGEMENT
	*/


	/**
	*	Get all ports POE status.
	*	@return array Return an array of objects. Values of port_poe_stats can be :
	*	- If POE is disable : PPDS_DISABLE.
	*	- If POE is enable but not delivering : PPDS_SEARCHING.
	*	- If POE is enable and delivering : PPDS_DELIVERING.
	*	- If POE has problem or is failing : PPDS_FAULT, PPDS_TEST, PPDS_OTHER_FAULT.
	*/
	public function getPortsPOEStatus() : array {
		// Check if port info exists in cache, else request the information
		if (!empty($this->cache['getPortsPOEStatus'])) {
			$res = $this->cache['getPortsPOEStatus'];
		} else {
			// Send request
			$res = $this->curlRequest('GET', '/poe/ports/stats');
			$this->cache['getPortsPOEStatus'] = $res;
		}

		// Return result
		return $res->port_poe_stats;
	}


	/**
	*	Check if a port is POE enabled.
	*	@param string $port Port id.
	*	@return Return the object. Values of port_poe_stats can be :
	*	- If POE is disable : PPDS_DISABLE.
	*	- If POE is enable but not delivering : PPDS_SEARCHING.
	*	- If POE is enable and delivering : PPDS_DELIVERING.
	*	- If POE has problem or is failing : PPDS_FAULT, PPDS_TEST, PPDS_OTHER_FAULT.
	*/
	public function portPoeStatus(string $port) {
		// Send request
		return $this->curlRequest('GET', '/ports/'.$port.'/poe/stats');		
	}


	/**
	*	Enable POE on a port.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function enablePoePort(string $port) : bool {
		// Create and fill data object
		$data = new stdClass();
		$data->port_id = "$port";
		$data->is_poe_enabled = TRUE;

		// Send request
		$res = $this->curlRequest('PUT', '/ports/'.$port.'/poe', json_encode($data));

		// Check if the request was correctly applied
		if ($res->is_poe_enabled === TRUE || $res->port_id === "$port") {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Disable POE on a port.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function disablePoePort(string $port) : bool {
		// Create and fill data object
		$data = new stdClass();
		$data->port_id = "$port";
		$data->is_poe_enabled = FALSE;

		// Send request
		$res = $this->curlRequest('PUT', '/ports/'.$port.'/poe', json_encode($data));

		// Check if the request was correctly applied
		if ($res->is_poe_enabled === FALSE || $res->port_id === "$port") {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Restart POE on a port.
	*	@param string $port Port id.
	*	@return bool Return the TRUE if successful, FALSE if it failed.
	*/
	public function restartPoePort(string $port) : bool {
		// Disable
		$resDisable = $this->disablePoePort($port);
		if ($resDisable === TRUE) {
			// Sleep 5 seconds
			sleep(5);
			// Re-enable
			$resEnable = $this->enablePoePort($port);
		}

		// Check if the everything went well and return
		if ($resDisable === TRUE && $resEnable === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	*	Logout
	*/
	private function logout() {
		// Logout
		$this->curlRequest('DELETE', '/login-sessions');
	}


	/**
	*	Destructor takes care of logout
	*/
	public function __destruct() {
		$this->logout();
	}
}

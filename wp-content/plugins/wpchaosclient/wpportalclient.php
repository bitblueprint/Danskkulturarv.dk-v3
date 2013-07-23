<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */

use CHAOS\Portal\Client\PortalClient;

class CHAOSException extends \RuntimeException {}

/**
 * WordPress Portal Client that automatically
 * retrieves and sets the accessPointGUID
 * stored in the database
 */
class WPPortalClient extends PortalClient {

	public function CallService($path, $method, array $parameters = null, $requiresSession = true) {
		if(!isset($parameters['accessPointGUID']) || $parameters['accessPointGUID'] == null) {
			$parameters['accessPointGUID'] = get_option('wpchaos-accesspoint-guid');
		}
		$response = parent::CallService($path, $method, $parameters, $requiresSession);
		if(!$response->WasSuccess()) {
			throw new \CHAOSException($response->Error()->Message());
		} elseif($response->Portal() != null && !$response->Portal()->WasSuccess()) {
			throw new \CHAOSException($response->Portal()->Error()->Message());
		} elseif($response->Statistics() != null && !$response->Statistics()->WasSuccess()) {
			throw new \CHAOSException($response->Statistics()->Error()->Message());
		} elseif($response->EmailPassword() != null && !$response->EmailPassword()->WasSuccess()) {
			throw new \CHAOSException($response->EmailPassword()->Error()->Message());
		} elseif($response->MCM() != null && !$response->MCM()->WasSuccess()) {
			throw new \CHAOSException($response->MCM()->Error()->Message());
		} else {
			return $response;
		}
	}

}

//eol
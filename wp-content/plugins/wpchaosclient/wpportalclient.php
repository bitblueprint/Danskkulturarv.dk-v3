<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */

use CHAOS\Portal\Client\PortalClient;
class WPPortalClient extends PortalClient {

	public function CallService($path, $method, array $parameters = null, $requiresSession = true) {
		if(!isset($parameters['accessPointGUID']) || $parameters['accessPointGUID'] == null) {
			$parameters['accessPointGUID'] = get_option('wpchaos-accesspoint-guid');
		}
		return parent::CallService($path, $method, $parameters, $requiresSession);
	}

}

//eol
<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
return array(
	array(
		/*Sections*/
		'name'		=> 'default',
		'title'		=> 'General Settings',
		'fields'	=> array(
			/*Section fields*/
			array(
				'name' => 'wpchaos-servicepath',
				'title' => 'Service Path',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-clientguid',
				'title' => 'Client GUID',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-accesspoint-guid',
				'title' => 'Access Point GUID',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-email',
				'title' => 'E-mail used for authentication',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-password',
				'title' => 'Password',
				'type' => 'password'
			)
		)
	)
);
//eol
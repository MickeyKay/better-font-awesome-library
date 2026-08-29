<?php

return array(
	'version'       => '5.15.4',
	'icons'         => array(
		array(
			'id'         => 'flag',
			'label'      => 'Flag',
			'membership' => array(
				'free' => array( 'solid' ),
			),
			'styles'     => array( 'solid', 'regular', 'light', 'duotone' ),
		),
		array(
			'id'         => 'address-book',
			'label'      => 'Address Book',
			'membership' => array(
				'free' => array( 'solid', 'regular' ),
			),
			'styles'     => array( 'solid', 'regular', 'light', 'duotone' ),
		),
		array(
			'id'         => 'pro-only',
			'label'      => 'Pro Only',
			'membership' => array(
				'free' => array(),
			),
			'styles'     => array( 'solid', 'regular', 'light', 'duotone' ),
		),
	),
	'srisByLicense' => array(
		'free' => array(
			array(
				'path'  => 'css/all.css',
				'value' => 'sha384-valid-all',
			),
			array(
				'path'  => 'css/v4-shims.css',
				'value' => 'sha384-valid-shim',
			),
		),
	),
);

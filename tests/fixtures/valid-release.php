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
				'value' => 'sha384-M7J8exYiWg22+2feRQ+QQBysM+ot+2l18dT00XfMccTe+f/NuDR0NNct9G9iyPYm',
			),
			array(
				'path'  => 'css/v4-shims.css',
				'value' => 'sha384-m8iXtR+kTECgkwmpCPpF8TAtjcjdHARnvoLvFQtCXfW0dPFYRgk7HFHoROIkphaN',
			),
		),
	),
);

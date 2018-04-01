<?php

function bfa_get_updated_icon_slug( $slug ) {
	$bfa_icon_name_change_list = array(
		'address-book-o' => array(
			'old_slug' => 'address-book-o',
			'new_slug' => 'address-book',
		),
		'address-card-o' => array(
			'old_slug' => 'address-card-o',
			'new_slug' => 'address-card',
		),
		'area-chart' => array(
			'old_slug' => 'area-chart',
			'new_slug' => 'chart-area',
		),
		'arrow-circle-o-down' => array(
			'old_slug' => 'arrow-circle-o-down',
			'new_slug' => 'arrow-alt-circle-down',
		),
		'arrow-circle-o-left' => array(
			'old_slug' => 'arrow-circle-o-left',
			'new_slug' => 'arrow-alt-circle-left',
		),
		'arrow-circle-o-right' => array(
			'old_slug' => 'arrow-circle-o-right',
			'new_slug' => 'arrow-alt-circle-right',
		),
		'arrow-circle-o-up' => array(
			'old_slug' => 'arrow-circle-o-up',
			'new_slug' => 'arrow-alt-circle-up',
		),
		'arrows-alt' => array(
			'old_slug' => 'arrows-alt',
			'new_slug' => 'expand-arrows-alt',
		),
		'arrows-h' => array(
			'old_slug' => 'arrows-h',
			'new_slug' => 'arrows-alt-h',
		),
		'arrows-v' => array(
			'old_slug' => 'arrows-v',
			'new_slug' => 'arrows-alt-v',
		),
		'arrows' => array(
			'old_slug' => 'arrows',
			'new_slug' => 'arrows-alt',
		),
		'asl-interpreting' => array(
			'old_slug' => 'asl-interpreting',
			'new_slug' => 'american-sign-language-interpreting',
		),
		'automobile' => array(
			'old_slug' => 'automobile',
			'new_slug' => 'car',
		),
		'bank' => array(
			'old_slug' => 'bank',
			'new_slug' => 'university',
		),
		'bar-chart-o' => array(
			'old_slug' => 'bar-chart-o',
			'new_slug' => 'chart-bar',
		),
		'bar-chart' => array(
			'old_slug' => 'bar-chart',
			'new_slug' => 'chart-bar',
		),
		'bathtub' => array(
			'old_slug' => 'bathtub',
			'new_slug' => 'bath',
		),
		'battery-0' => array(
			'old_slug' => 'battery-0',
			'new_slug' => 'battery-empty',
		),
		'battery-1' => array(
			'old_slug' => 'battery-1',
			'new_slug' => 'battery-quarter',
		),
		'battery-2' => array(
			'old_slug' => 'battery-2',
			'new_slug' => 'battery-half',
		),
		'battery-3' => array(
			'old_slug' => 'battery-3',
			'new_slug' => 'battery-three-quarters',
		),
		'battery-4' => array(
			'old_slug' => 'battery-4',
			'new_slug' => 'battery-full',
		),
		'battery' => array(
			'old_slug' => 'battery',
			'new_slug' => 'battery-full',
		),
		'bell-o' => array(
			'old_slug' => 'bell-o',
			'new_slug' => 'bell',
		),
		'bell-slash-o' => array(
			'old_slug' => 'bell-slash-o',
			'new_slug' => 'bell-slash',
		),
		'bitbucket-square' => array(
			'old_slug' => 'bitbucket-square',
			'new_slug' => 'bitbucket',
		),
		'bitcoin' => array(
			'old_slug' => 'bitcoin',
			'new_slug' => 'btc',
		),
		'bookmark-o' => array(
			'old_slug' => 'bookmark-o',
			'new_slug' => 'bookmark',
		),
		'building-o' => array(
			'old_slug' => 'building-o',
			'new_slug' => 'building',
		),
		'cab' => array(
			'old_slug' => 'cab',
			'new_slug' => 'taxi',
		),
		'calendar-check-o' => array(
			'old_slug' => 'calendar-check-o',
			'new_slug' => 'calendar-check',
		),
		'calendar-minus-o' => array(
			'old_slug' => 'calendar-minus-o',
			'new_slug' => 'calendar-minus',
		),
		'calendar-o' => array(
			'old_slug' => 'calendar-o',
			'new_slug' => 'calendar',
		),
		'calendar-plus-o' => array(
			'old_slug' => 'calendar-plus-o',
			'new_slug' => 'calendar-plus',
		),
		'calendar-times-o' => array(
			'old_slug' => 'calendar-times-o',
			'new_slug' => 'calendar-times',
		),
		'calendar' => array(
			'old_slug' => 'calendar',
			'new_slug' => 'calendar-alt',
		),
		'caret-square-o-down' => array(
			'old_slug' => 'caret-square-o-down',
			'new_slug' => 'caret-square-down',
		),
		'caret-square-o-left' => array(
			'old_slug' => 'caret-square-o-left',
			'new_slug' => 'caret-square-left',
		),
		'caret-square-o-right' => array(
			'old_slug' => 'caret-square-o-right',
			'new_slug' => 'caret-square-right',
		),
		'caret-square-o-up' => array(
			'old_slug' => 'caret-square-o-up',
			'new_slug' => 'caret-square-up',
		),
		'cc' => array(
			'old_slug' => 'cc',
			'new_slug' => 'closed-captioning',
		),
		'chain-broken' => array(
			'old_slug' => 'chain-broken',
			'new_slug' => 'unlink',
		),
		'chain' => array(
			'old_slug' => 'chain',
			'new_slug' => 'link',
		),
		'check-circle-o' => array(
			'old_slug' => 'check-circle-o',
			'new_slug' => 'check-circle',
		),
		'check-square-o' => array(
			'old_slug' => 'check-square-o',
			'new_slug' => 'check-square',
		),
		'circle-o-notch' => array(
			'old_slug' => 'circle-o-notch',
			'new_slug' => 'circle-notch',
		),
		'circle-o' => array(
			'old_slug' => 'circle-o',
			'new_slug' => 'circle',
		),
		'circle-thin' => array(
			'old_slug' => 'circle-thin',
			'new_slug' => 'circle',
		),
		'clock-o' => array(
			'old_slug' => 'clock-o',
			'new_slug' => 'clock',
		),
		'close' => array(
			'old_slug' => 'close',
			'new_slug' => 'times',
		),
		'cloud-download' => array(
			'old_slug' => 'cloud-download',
			'new_slug' => 'cloud-download-alt',
		),
		'cloud-upload' => array(
			'old_slug' => 'cloud-upload',
			'new_slug' => 'cloud-upload-alt',
		),
		'cny' => array(
			'old_slug' => 'cny',
			'new_slug' => 'yen-sign',
		),
		'code-fork' => array(
			'old_slug' => 'code-fork',
			'new_slug' => 'code-branch',
		),
		'comment-o' => array(
			'old_slug' => 'comment-o',
			'new_slug' => 'comment',
		),
		'commenting-o' => array(
			'old_slug' => 'commenting-o',
			'new_slug' => 'comment-alt',
		),
		'commenting' => array(
			'old_slug' => 'commenting',
			'new_slug' => 'comment-alt',
		),
		'comments-o' => array(
			'old_slug' => 'comments-o',
			'new_slug' => 'comments',
		),
		'credit-card-alt' => array(
			'old_slug' => 'credit-card-alt',
			'new_slug' => 'credit-card',
		),
		'cutlery' => array(
			'old_slug' => 'cutlery',
			'new_slug' => 'utensils',
		),
		'dashboard' => array(
			'old_slug' => 'dashboard',
			'new_slug' => 'tachometer-alt',
		),
		'deafness' => array(
			'old_slug' => 'deafness',
			'new_slug' => 'deaf',
		),
		'dedent' => array(
			'old_slug' => 'dedent',
			'new_slug' => 'outdent',
		),
		'diamond' => array(
			'old_slug' => 'diamond',
			'new_slug' => 'gem',
		),
		'dollar' => array(
			'old_slug' => 'dollar',
			'new_slug' => 'dollar-sign',
		),
		'dot-circle-o' => array(
			'old_slug' => 'dot-circle-o',
			'new_slug' => 'dot-circle',
		),
		'drivers-license-o' => array(
			'old_slug' => 'drivers-license-o',
			'new_slug' => 'id-card',
		),
		'drivers-license' => array(
			'old_slug' => 'drivers-license',
			'new_slug' => 'id-card',
		),
		'eercast' => array(
			'old_slug' => 'eercast',
			'new_slug' => 'sellcast',
		),
		'envelope-o' => array(
			'old_slug' => 'envelope-o',
			'new_slug' => 'envelope',
		),
		'envelope-open-o' => array(
			'old_slug' => 'envelope-open-o',
			'new_slug' => 'envelope-open',
		),
		'eur' => array(
			'old_slug' => 'eur',
			'new_slug' => 'euro-sign',
		),
		'euro' => array(
			'old_slug' => 'euro',
			'new_slug' => 'euro-sign',
		),
		'exchange' => array(
			'old_slug' => 'exchange',
			'new_slug' => 'exchange-alt',
		),
		'external-link-square' => array(
			'old_slug' => 'external-link-square',
			'new_slug' => 'external-link-square-alt',
		),
		'external-link' => array(
			'old_slug' => 'external-link',
			'new_slug' => 'external-link-alt',
		),
		'eyedropper' => array(
			'old_slug' => 'eyedropper',
			'new_slug' => 'eye-dropper',
		),
		'fa' => array(
			'old_slug' => 'fa',
			'new_slug' => 'font-awesome',
		),
		'facebook-f' => array(
			'old_slug' => 'facebook-f',
			'new_slug' => 'facebook-f',
		),
		'facebook-official' => array(
			'old_slug' => 'facebook-official',
			'new_slug' => 'facebook',
		),
		'facebook' => array(
			'old_slug' => 'facebook',
			'new_slug' => 'facebook-f',
		),
		'feed' => array(
			'old_slug' => 'feed',
			'new_slug' => 'rss',
		),
		'file-archive-o' => array(
			'old_slug' => 'file-archive-o',
			'new_slug' => 'file-archive',
		),
		'file-audio-o' => array(
			'old_slug' => 'file-audio-o',
			'new_slug' => 'file-audio',
		),
		'file-code-o' => array(
			'old_slug' => 'file-code-o',
			'new_slug' => 'file-code',
		),
		'file-excel-o' => array(
			'old_slug' => 'file-excel-o',
			'new_slug' => 'file-excel',
		),
		'file-image-o' => array(
			'old_slug' => 'file-image-o',
			'new_slug' => 'file-image',
		),
		'file-movie-o' => array(
			'old_slug' => 'file-movie-o',
			'new_slug' => 'file-video',
		),
		'file-o' => array(
			'old_slug' => 'file-o',
			'new_slug' => 'file',
		),
		'file-pdf-o' => array(
			'old_slug' => 'file-pdf-o',
			'new_slug' => 'file-pdf',
		),
		'file-photo-o' => array(
			'old_slug' => 'file-photo-o',
			'new_slug' => 'file-image',
		),
		'file-picture-o' => array(
			'old_slug' => 'file-picture-o',
			'new_slug' => 'file-image',
		),
		'file-powerpoint-o' => array(
			'old_slug' => 'file-powerpoint-o',
			'new_slug' => 'file-powerpoint',
		),
		'file-sound-o' => array(
			'old_slug' => 'file-sound-o',
			'new_slug' => 'file-audio',
		),
		'file-text-o' => array(
			'old_slug' => 'file-text-o',
			'new_slug' => 'file-alt',
		),
		'file-text' => array(
			'old_slug' => 'file-text',
			'new_slug' => 'file-alt',
		),
		'file-video-o' => array(
			'old_slug' => 'file-video-o',
			'new_slug' => 'file-video',
		),
		'file-word-o' => array(
			'old_slug' => 'file-word-o',
			'new_slug' => 'file-word',
		),
		'file-zip-o' => array(
			'old_slug' => 'file-zip-o',
			'new_slug' => 'file-archive',
		),
		'files-o' => array(
			'old_slug' => 'files-o',
			'new_slug' => 'copy',
		),
		'flag-o' => array(
			'old_slug' => 'flag-o',
			'new_slug' => 'flag',
		),
		'flash' => array(
			'old_slug' => 'flash',
			'new_slug' => 'bolt',
		),
		'floppy-o' => array(
			'old_slug' => 'floppy-o',
			'new_slug' => 'save',
		),
		'folder-o' => array(
			'old_slug' => 'folder-o',
			'new_slug' => 'folder',
		),
		'folder-open-o' => array(
			'old_slug' => 'folder-open-o',
			'new_slug' => 'folder-open',
		),
		'frown-o' => array(
			'old_slug' => 'frown-o',
			'new_slug' => 'frown',
		),
		'futbol-o' => array(
			'old_slug' => 'futbol-o',
			'new_slug' => 'futbol',
		),
		'gbp' => array(
			'old_slug' => 'gbp',
			'new_slug' => 'pound-sign',
		),
		'ge' => array(
			'old_slug' => 'ge',
			'new_slug' => 'empire',
		),
		'gear' => array(
			'old_slug' => 'gear',
			'new_slug' => 'cog',
		),
		'gears' => array(
			'old_slug' => 'gears',
			'new_slug' => 'cogs',
		),
		'gittip' => array(
			'old_slug' => 'gittip',
			'new_slug' => 'gratipay',
		),
		'glass' => array(
			'old_slug' => 'glass',
			'new_slug' => 'glass-martini',
		),
		'google-plus-circle' => array(
			'old_slug' => 'google-plus-circle',
			'new_slug' => 'google-plus',
		),
		'google-plus-official' => array(
			'old_slug' => 'google-plus-official',
			'new_slug' => 'google-plus',
		),
		'google-plus' => array(
			'old_slug' => 'google-plus',
			'new_slug' => 'google-plus-g',
		),
		'group' => array(
			'old_slug' => 'group',
			'new_slug' => 'users',
		),
		'hand-grab-o' => array(
			'old_slug' => 'hand-grab-o',
			'new_slug' => 'hand-rock',
		),
		'hand-lizard-o' => array(
			'old_slug' => 'hand-lizard-o',
			'new_slug' => 'hand-lizard',
		),
		'hand-o-down' => array(
			'old_slug' => 'hand-o-down',
			'new_slug' => 'hand-point-down',
		),
		'hand-o-left' => array(
			'old_slug' => 'hand-o-left',
			'new_slug' => 'hand-point-left',
		),
		'hand-o-right' => array(
			'old_slug' => 'hand-o-right',
			'new_slug' => 'hand-point-right',
		),
		'hand-o-up' => array(
			'old_slug' => 'hand-o-up',
			'new_slug' => 'hand-point-up',
		),
		'hand-paper-o' => array(
			'old_slug' => 'hand-paper-o',
			'new_slug' => 'hand-paper',
		),
		'hand-peace-o' => array(
			'old_slug' => 'hand-peace-o',
			'new_slug' => 'hand-peace',
		),
		'hand-pointer-o' => array(
			'old_slug' => 'hand-pointer-o',
			'new_slug' => 'hand-pointer',
		),
		'hand-rock-o' => array(
			'old_slug' => 'hand-rock-o',
			'new_slug' => 'hand-rock',
		),
		'hand-scissors-o' => array(
			'old_slug' => 'hand-scissors-o',
			'new_slug' => 'hand-scissors',
		),
		'hand-spock-o' => array(
			'old_slug' => 'hand-spock-o',
			'new_slug' => 'hand-spock',
		),
		'hand-stop-o' => array(
			'old_slug' => 'hand-stop-o',
			'new_slug' => 'hand-paper',
		),
		'handshake-o' => array(
			'old_slug' => 'handshake-o',
			'new_slug' => 'handshake',
		),
		'hard-of-hearing' => array(
			'old_slug' => 'hard-of-hearing',
			'new_slug' => 'deaf',
		),
		'hdd-o' => array(
			'old_slug' => 'hdd-o',
			'new_slug' => 'hdd',
		),
		'header' => array(
			'old_slug' => 'header',
			'new_slug' => 'heading',
		),
		'heart-o' => array(
			'old_slug' => 'heart-o',
			'new_slug' => 'heart',
		),
		'hospital-o' => array(
			'old_slug' => 'hospital-o',
			'new_slug' => 'hospital',
		),
		'hotel' => array(
			'old_slug' => 'hotel',
			'new_slug' => 'bed',
		),
		'hourglass-1' => array(
			'old_slug' => 'hourglass-1',
			'new_slug' => 'hourglass-start',
		),
		'hourglass-2' => array(
			'old_slug' => 'hourglass-2',
			'new_slug' => 'hourglass-half',
		),
		'hourglass-3' => array(
			'old_slug' => 'hourglass-3',
			'new_slug' => 'hourglass-end',
		),
		'hourglass-o' => array(
			'old_slug' => 'hourglass-o',
			'new_slug' => 'hourglass',
		),
		'id-card-o' => array(
			'old_slug' => 'id-card-o',
			'new_slug' => 'id-card',
		),
		'ils' => array(
			'old_slug' => 'ils',
			'new_slug' => 'shekel-sign',
		),
		'image' => array(
			'old_slug' => 'image',
			'new_slug' => 'image',
		),
		'inr' => array(
			'old_slug' => 'inr',
			'new_slug' => 'rupee-sign',
		),
		'institution' => array(
			'old_slug' => 'institution',
			'new_slug' => 'university',
		),
		'intersex' => array(
			'old_slug' => 'intersex',
			'new_slug' => 'transgender',
		),
		'jpy' => array(
			'old_slug' => 'jpy',
			'new_slug' => 'yen-sign',
		),
		'keyboard-o' => array(
			'old_slug' => 'keyboard-o',
			'new_slug' => 'keyboard',
		),
		'krw' => array(
			'old_slug' => 'krw',
			'new_slug' => 'won-sign',
		),
		'legal' => array(
			'old_slug' => 'legal',
			'new_slug' => 'gavel',
		),
		'lemon-o' => array(
			'old_slug' => 'lemon-o',
			'new_slug' => 'lemon',
		),
		'level-down' => array(
			'old_slug' => 'level-down',
			'new_slug' => 'level-down-alt',
		),
		'level-up' => array(
			'old_slug' => 'level-up',
			'new_slug' => 'level-up-alt',
		),
		'life-bouy' => array(
			'old_slug' => 'life-bouy',
			'new_slug' => 'life-ring',
		),
		'life-buoy' => array(
			'old_slug' => 'life-buoy',
			'new_slug' => 'life-ring',
		),
		'life-saver' => array(
			'old_slug' => 'life-saver',
			'new_slug' => 'life-ring',
		),
		'lightbulb-o' => array(
			'old_slug' => 'lightbulb-o',
			'new_slug' => 'lightbulb',
		),
		'line-chart' => array(
			'old_slug' => 'line-chart',
			'new_slug' => 'chart-line',
		),
		'linkedin-square' => array(
			'old_slug' => 'linkedin-square',
			'new_slug' => 'linkedin',
		),
		'linkedin' => array(
			'old_slug' => 'linkedin',
			'new_slug' => 'linkedin-in',
		),
		'long-arrow-down' => array(
			'old_slug' => 'long-arrow-down',
			'new_slug' => 'long-arrow-alt-down',
		),
		'long-arrow-left' => array(
			'old_slug' => 'long-arrow-left',
			'new_slug' => 'long-arrow-alt-left',
		),
		'long-arrow-right' => array(
			'old_slug' => 'long-arrow-right',
			'new_slug' => 'long-arrow-alt-right',
		),
		'long-arrow-up' => array(
			'old_slug' => 'long-arrow-up',
			'new_slug' => 'long-arrow-alt-up',
		),
		'mail-forward' => array(
			'old_slug' => 'mail-forward',
			'new_slug' => 'share',
		),
		'mail-reply-all' => array(
			'old_slug' => 'mail-reply-all',
			'new_slug' => 'reply-all',
		),
		'mail-reply' => array(
			'old_slug' => 'mail-reply',
			'new_slug' => 'reply',
		),
		'map-marker' => array(
			'old_slug' => 'map-marker',
			'new_slug' => 'map-marker-alt',
		),
		'map-o' => array(
			'old_slug' => 'map-o',
			'new_slug' => 'map',
		),
		'meanpath' => array(
			'old_slug' => 'meanpath',
			'new_slug' => 'font-awesome',
		),
		'meh-o' => array(
			'old_slug' => 'meh-o',
			'new_slug' => 'meh',
		),
		'minus-square-o' => array(
			'old_slug' => 'minus-square-o',
			'new_slug' => 'minus-square',
		),
		'mobile-phone' => array(
			'old_slug' => 'mobile-phone',
			'new_slug' => 'mobile-alt',
		),
		'mobile' => array(
			'old_slug' => 'mobile',
			'new_slug' => 'mobile-alt',
		),
		'money' => array(
			'old_slug' => 'money',
			'new_slug' => 'money-bill-alt',
		),
		'moon-o' => array(
			'old_slug' => 'moon-o',
			'new_slug' => 'moon',
		),
		'mortar-board' => array(
			'old_slug' => 'mortar-board',
			'new_slug' => 'graduation-cap',
		),
		'navicon' => array(
			'old_slug' => 'navicon',
			'new_slug' => 'bars',
		),
		'newspaper-o' => array(
			'old_slug' => 'newspaper-o',
			'new_slug' => 'newspaper',
		),
		'paper-plane-o' => array(
			'old_slug' => 'paper-plane-o',
			'new_slug' => 'paper-plane',
		),
		'paste' => array(
			'old_slug' => 'paste',
			'new_slug' => 'clipboard',
		),
		'pause-circle-o' => array(
			'old_slug' => 'pause-circle-o',
			'new_slug' => 'pause-circle',
		),
		'pencil-square-o' => array(
			'old_slug' => 'pencil-square-o',
			'new_slug' => 'edit',
		),
		'pencil-square' => array(
			'old_slug' => 'pencil-square',
			'new_slug' => 'pen-square',
		),
		'pencil' => array(
			'old_slug' => 'pencil',
			'new_slug' => 'pencil-alt',
		),
		'photo' => array(
			'old_slug' => 'photo',
			'new_slug' => 'image',
		),
		'picture-o' => array(
			'old_slug' => 'picture-o',
			'new_slug' => 'image',
		),
		'pie-chart' => array(
			'old_slug' => 'pie-chart',
			'new_slug' => 'chart-pie',
		),
		'play-circle-o' => array(
			'old_slug' => 'play-circle-o',
			'new_slug' => 'play-circle',
		),
		'plus-square-o' => array(
			'old_slug' => 'plus-square-o',
			'new_slug' => 'plus-square',
		),
		'question-circle-o' => array(
			'old_slug' => 'question-circle-o',
			'new_slug' => 'question-circle',
		),
		'ra' => array(
			'old_slug' => 'ra',
			'new_slug' => 'rebel',
		),
		'refresh' => array(
			'old_slug' => 'refresh',
			'new_slug' => 'sync',
		),
		'remove' => array(
			'old_slug' => 'remove',
			'new_slug' => 'times',
		),
		'reorder' => array(
			'old_slug' => 'reorder',
			'new_slug' => 'bars',
		),
		'repeat' => array(
			'old_slug' => 'repeat',
			'new_slug' => 'redo',
		),
		'resistance' => array(
			'old_slug' => 'resistance',
			'new_slug' => 'rebel',
		),
		'rmb' => array(
			'old_slug' => 'rmb',
			'new_slug' => 'yen-sign',
		),
		'rotate-left' => array(
			'old_slug' => 'rotate-left',
			'new_slug' => 'undo',
		),
		'rotate-right' => array(
			'old_slug' => 'rotate-right',
			'new_slug' => 'redo',
		),
		'rouble' => array(
			'old_slug' => 'rouble',
			'new_slug' => 'ruble-sign',
		),
		'rub' => array(
			'old_slug' => 'rub',
			'new_slug' => 'ruble-sign',
		),
		'ruble' => array(
			'old_slug' => 'ruble',
			'new_slug' => 'ruble-sign',
		),
		'rupee' => array(
			'old_slug' => 'rupee',
			'new_slug' => 'rupee-sign',
		),
		's15' => array(
			'old_slug' => 's15',
			'new_slug' => 'bath',
		),
		'scissors' => array(
			'old_slug' => 'scissors',
			'new_slug' => 'cut',
		),
		'send-o' => array(
			'old_slug' => 'send-o',
			'new_slug' => 'paper-plane',
		),
		'send' => array(
			'old_slug' => 'send',
			'new_slug' => 'paper-plane',
		),
		'share-square-o' => array(
			'old_slug' => 'share-square-o',
			'new_slug' => 'share-square',
		),
		'shekel' => array(
			'old_slug' => 'shekel',
			'new_slug' => 'shekel-sign',
		),
		'sheqel' => array(
			'old_slug' => 'sheqel',
			'new_slug' => 'shekel-sign',
		),
		'shield' => array(
			'old_slug' => 'shield',
			'new_slug' => 'shield-alt',
		),
		'sign-in' => array(
			'old_slug' => 'sign-in',
			'new_slug' => 'sign-in-alt',
		),
		'sign-out' => array(
			'old_slug' => 'sign-out',
			'new_slug' => 'sign-out-alt',
		),
		'signing' => array(
			'old_slug' => 'signing',
			'new_slug' => 'sign-language',
		),
		'sliders' => array(
			'old_slug' => 'sliders',
			'new_slug' => 'sliders-h',
		),
		'smile-o' => array(
			'old_slug' => 'smile-o',
			'new_slug' => 'smile',
		),
		'snowflake-o' => array(
			'old_slug' => 'snowflake-o',
			'new_slug' => 'snowflake',
		),
		'soccer-ball-o' => array(
			'old_slug' => 'soccer-ball-o',
			'new_slug' => 'futbol',
		),
		'sort-alpha-asc' => array(
			'old_slug' => 'sort-alpha-asc',
			'new_slug' => 'sort-alpha-down',
		),
		'sort-alpha-desc' => array(
			'old_slug' => 'sort-alpha-desc',
			'new_slug' => 'sort-alpha-up',
		),
		'sort-amount-asc' => array(
			'old_slug' => 'sort-amount-asc',
			'new_slug' => 'sort-amount-down',
		),
		'sort-amount-desc' => array(
			'old_slug' => 'sort-amount-desc',
			'new_slug' => 'sort-amount-up',
		),
		'sort-asc' => array(
			'old_slug' => 'sort-asc',
			'new_slug' => 'sort-up',
		),
		'sort-desc' => array(
			'old_slug' => 'sort-desc',
			'new_slug' => 'sort-down',
		),
		'sort-numeric-asc' => array(
			'old_slug' => 'sort-numeric-asc',
			'new_slug' => 'sort-numeric-down',
		),
		'sort-numeric-desc' => array(
			'old_slug' => 'sort-numeric-desc',
			'new_slug' => 'sort-numeric-up',
		),
		'spoon' => array(
			'old_slug' => 'spoon',
			'new_slug' => 'utensil-spoon',
		),
		'square-o' => array(
			'old_slug' => 'square-o',
			'new_slug' => 'square',
		),
		'star-half-empty' => array(
			'old_slug' => 'star-half-empty',
			'new_slug' => 'star-half',
		),
		'star-half-full' => array(
			'old_slug' => 'star-half-full',
			'new_slug' => 'star-half',
		),
		'star-half-o' => array(
			'old_slug' => 'star-half-o',
			'new_slug' => 'star-half',
		),
		'star-o' => array(
			'old_slug' => 'star-o',
			'new_slug' => 'star',
		),
		'sticky-note-o' => array(
			'old_slug' => 'sticky-note-o',
			'new_slug' => 'sticky-note',
		),
		'stop-circle-o' => array(
			'old_slug' => 'stop-circle-o',
			'new_slug' => 'stop-circle',
		),
		'sun-o' => array(
			'old_slug' => 'sun-o',
			'new_slug' => 'sun',
		),
		'support' => array(
			'old_slug' => 'support',
			'new_slug' => 'life-ring',
		),
		'tablet' => array(
			'old_slug' => 'tablet',
			'new_slug' => 'tablet-alt',
		),
		'tachometer' => array(
			'old_slug' => 'tachometer',
			'new_slug' => 'tachometer-alt',
		),
		'television' => array(
			'old_slug' => 'television',
			'new_slug' => 'tv',
		),
		'thermometer-0' => array(
			'old_slug' => 'thermometer-0',
			'new_slug' => 'thermometer-empty',
		),
		'thermometer-1' => array(
			'old_slug' => 'thermometer-1',
			'new_slug' => 'thermometer-quarter',
		),
		'thermometer-2' => array(
			'old_slug' => 'thermometer-2',
			'new_slug' => 'thermometer-half',
		),
		'thermometer-3' => array(
			'old_slug' => 'thermometer-3',
			'new_slug' => 'thermometer-three-quarters',
		),
		'thermometer-4' => array(
			'old_slug' => 'thermometer-4',
			'new_slug' => 'thermometer-full',
		),
		'thermometer' => array(
			'old_slug' => 'thermometer',
			'new_slug' => 'thermometer-full',
		),
		'thumb-tack' => array(
			'old_slug' => 'thumb-tack',
			'new_slug' => 'thumbtack',
		),
		'thumbs-o-down' => array(
			'old_slug' => 'thumbs-o-down',
			'new_slug' => 'thumbs-down',
		),
		'thumbs-o-up' => array(
			'old_slug' => 'thumbs-o-up',
			'new_slug' => 'thumbs-up',
		),
		'ticket' => array(
			'old_slug' => 'ticket',
			'new_slug' => 'ticket-alt',
		),
		'times-circle-o' => array(
			'old_slug' => 'times-circle-o',
			'new_slug' => 'times-circle',
		),
		'times-rectangle-o' => array(
			'old_slug' => 'times-rectangle-o',
			'new_slug' => 'window-close',
		),
		'times-rectangle' => array(
			'old_slug' => 'times-rectangle',
			'new_slug' => 'window-close',
		),
		'toggle-down' => array(
			'old_slug' => 'toggle-down',
			'new_slug' => 'caret-square-down',
		),
		'toggle-left' => array(
			'old_slug' => 'toggle-left',
			'new_slug' => 'caret-square-left',
		),
		'toggle-right' => array(
			'old_slug' => 'toggle-right',
			'new_slug' => 'caret-square-right',
		),
		'toggle-up' => array(
			'old_slug' => 'toggle-up',
			'new_slug' => 'caret-square-up',
		),
		'trash-o' => array(
			'old_slug' => 'trash-o',
			'new_slug' => 'trash-alt',
		),
		'trash' => array(
			'old_slug' => 'trash',
			'new_slug' => 'trash-alt',
		),
		'try' => array(
			'old_slug' => 'try',
			'new_slug' => 'lira-sign',
		),
		'turkish-lira' => array(
			'old_slug' => 'turkish-lira',
			'new_slug' => 'lira-sign',
		),
		'unsorted' => array(
			'old_slug' => 'unsorted',
			'new_slug' => 'sort',
		),
		'usd' => array(
			'old_slug' => 'usd',
			'new_slug' => 'dollar-sign',
		),
		'user-circle-o' => array(
			'old_slug' => 'user-circle-o',
			'new_slug' => 'user-circle',
		),
		'user-o' => array(
			'old_slug' => 'user-o',
			'new_slug' => 'user',
		),
		'vcard-o' => array(
			'old_slug' => 'vcard-o',
			'new_slug' => 'address-card',
		),
		'vcard' => array(
			'old_slug' => 'vcard',
			'new_slug' => 'address-card',
		),
		'video-camera' => array(
			'old_slug' => 'video-camera',
			'new_slug' => 'video',
		),
		'vimeo' => array(
			'old_slug' => 'vimeo',
			'new_slug' => 'vimeo-v',
		),
		'volume-control-phone' => array(
			'old_slug' => 'volume-control-phone',
			'new_slug' => 'phone-volume',
		),
		'warning' => array(
			'old_slug' => 'warning',
			'new_slug' => 'exclamation-triangle',
		),
		'wechat' => array(
			'old_slug' => 'wechat',
			'new_slug' => 'weixin',
		),
		'wheelchair-alt' => array(
			'old_slug' => 'wheelchair-alt',
			'new_slug' => 'accessible-icon',
		),
		'window-close-o' => array(
			'old_slug' => 'window-close-o',
			'new_slug' => 'window-close',
		),
		'won' => array(
			'old_slug' => 'won',
			'new_slug' => 'won-sign',
		),
		'y-combinator-square' => array(
			'old_slug' => 'y-combinator-square',
			'new_slug' => 'hacker-news',
		),
		'yc-square' => array(
			'old_slug' => 'yc-square',
			'new_slug' => 'hacker-news',
		),
		'yc' => array(
			'old_slug' => 'yc',
			'new_slug' => 'y-combinator',
		),
		'yen' => array(
			'old_slug' => 'yen',
			'new_slug' => 'yen-sign',
		),
		'youtube-play' => array(
			'old_slug' => 'youtube-play',
			'new_slug' => 'youtube',
		),
		'youtube-square' => array(
			'old_slug' => 'youtube-square',
			'new_slug' => 'youtube',
		),
	);

	return ! empty( $bfa_icon_name_change_list[ $slug ] ) ? $bfa_icon_name_change_list[ $slug ]['new_slug'] : null;
}
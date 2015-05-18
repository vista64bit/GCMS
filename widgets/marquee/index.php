<?php
	// widgets/marquee/index.php
	if (defined('MAIN_INIT')) {
		$widget = array();
		$widget[] = '<div id=marquee_containner><div id=marquee_scroller>'.$config['marquee_text'].'</div></div>';
		$widget[] = '<script>';
		$widget[] = 'new GScroll("marquee_containner","marquee_scroller").play({"to":"left","speed":"'.$config['marquee_speed'].'"});';
		$widget[] = '</script>';
		$widget = implode("\n", $widget);
	}

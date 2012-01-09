<?php

function backpress_get_option( $option ) {
	switch ($option) {
		case 'charset':
			return 'utf8';
		default:
			return false;
	}
}
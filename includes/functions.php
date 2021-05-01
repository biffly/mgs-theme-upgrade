<?php
if( !function_exists('mgs_get_referer') ){
	function mgs_get_referer(){
		$ref = wp_get_referer();
		return ( !$ref ) ? wp_get_raw_referer() : $ref;
	}
}
<?php

/**
 * Security stuff 
 */

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

/**
 * Prevent SQL injection and other dangerous values
 * 
 * @param array $attr
 * @return boolean - true if attr given are ok
 */
function sqb_is_attr_safe($attr) 
{
  $prohibited_values = [';', 'SELECT ', 'UPDATE ', 'INSERT ', 'DELETE ', 'CREATE ', 'ALTER ', 'DROP ', 'TRUNCATE ', 'SHOW ', 'USE ', 'GRANT '];
  foreach($prohibited_values as $pv) {
    if (stripos($attr, $pv) !== false) {
      return false;
    }
  }
  return true;
}

/**
 * Remove shortcodes in user comments (could be used by hackers to inject db queries
 * 
 * @return string
 */
function sqb_remove_query_builder_shortcodes( $text ) 
{
  $regex = get_shortcode_regex( ['shortcode-query'] );
  return preg_replace( "/$regex/", '', $text );
}

//add_filter( 'the_content', 'sqb_remove_query_builder_shortcodes' );
add_filter( 'comment_text', 'sqb_remove_query_builder_shortcodes' );

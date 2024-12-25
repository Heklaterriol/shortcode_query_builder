<?php
/**
 * Plugin Name: Shortcode Query Builder
 * Plugin URI: https://github.com/sternenvogel/shortcode_query_builder
 * Description: WordPress Plugin to build database queries using shortcodes and display results as list or table
 * Version: 1.0.1
 * Text Domain: sternenvogel-wordpress-plugin-shortcode-query-builder
 * Author: Benno Flory
 * Author URI: https://www.web-und-wandel.net/
 */

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/renderer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/security.php' );

/**
 * Define array of available attributes with default value and description
 * 
 * @return array
 */
function sqb_define_avaliable_attrs() 
{
  return [
    'table'       => ['default' => '', 'description' => 'Tablename in database. You can use "#_" as placeholder for table prefixes (e.g. #_posts)'],
    'cols'        => ['default' => '*', 'description' => 'Table columns to select'],
    'where'       => ['default' => '', 'description' => 'Conditions. If you use table names, you can use "#_" as placeholder for table prefixes'],
    'order-by'    => ['default' => '', 'description' => 'Sorting column(s)'],
    'limit'       => ['default' => '', 'description' => 'Limit rows (e.g. 10 or 5,10)'],
    'wrapper'     => ['default' => 'div', 'description' => 'HTML Container: "ul", "ol", "table" or "div" - div renders rows in p-Tags, separated by comma'],
    'element-wrapper' => ['default' => 'p', 'description' => 'HTML Container for list elements: "p", "li" or "div"'],
    'class'       => ['default' => 'shortcode-query-builder', 'Description' => 'class name to be added to wrapper element'],
    'format'      => ['default' => '', 'description' => 'String to format column values, see php sprintf() for syntax. For tables, definitions are separated by | (pipe)'],
    'link'        => ['default' => '', 'description' => 'URL pattern with {placeholders}. Allowed placeholders are all fields available in attr "cols"'],
    'table-head'  => ['default' => '', 'description' => 'For tables only: Labels for table heading, separated by | (pipe)'],
    'no-data-msg' => ['default' => '', 'description' => 'Message to display, if query did not return any results'],
  ];
}

/**
 * Verify all attributes from shortcode are allowed
 * 
 * @param array $attrs
 * @param array $attrs_available
 * @return boolean - true if all attrs given are ok
 */
function sqb_verify_attrs(&$attrs, $attrs_available, &$errors = []) 
{
  $attrs_ok = false;
  if ($attrs) {
    
    // Check if all given attributes are available in list
    $unknown_attrs = array_diff_key($attrs, $attrs_available);
    if (count($unknown_attrs) === 0) {
      $attrs_ok = true;
      
      // Validate all $attrs are defined and SQL safe, set default if not defined
      foreach(array_keys($attrs_available) as $available_key) {
        $attrs[$available_key] = (array_key_exists($available_key, $attrs) && $attrs[$available_key])?(str_replace(['&gt;', '&lt;'], ['>', '<'], $attrs[$available_key])):$attrs_available[$available_key]['default'];
        if (!sqb_is_attr_safe($attrs[$available_key])) {
          $errors[] = 'The attribute ' . htmlentities($available_key) . ' is not safe for SQL.';
          $attrs_ok = false;
          break;
        }
        // Replace table prefixes
        $attrs[$available_key] = sqb_prefix_tables($attrs[$available_key]);
      }
      
      // verify attr 'table' is defined
      if ($attrs['table'] === '') {
        $errors[] = htmlentities('Attribute "table" is not defined.');
        $attrs_ok = false;
      }
    }
    else {
      $errors[] = 'Some attributes are unknown (' . htmlentities(implode(', ', $unknown_attrs)) . ').';
    }
  }
  return $attrs_ok;
}

/**
 * Replace #_ with current table prefix.
 * 
 * @param string $value
 * @return string
 */
function sqb_prefix_tables($value)
{
  global $wpdb;
  return str_replace('#_', $wpdb->prefix, $value);
}

/**
 * 
 * @global object $wpdb
 * @param array $attrs
 * @return array - Query result
 */
function sqb_query_database($attrs) 
{
  global $wpdb;

  // Build database query
  $statement = "SELECT DISTINCT " . $attrs['cols'] . " FROM " . $attrs['table'];
  
  if ($attrs['where'])    { $statement .= ' WHERE '    . $attrs['where'];    }
  if ($attrs['order-by']) { $statement .= ' ORDER BY ' . $attrs['order-by']; }
  if ($attrs['limit'])    { $statement .= ' LIMIT '    . $attrs['limit'];    }
  
  // Execute query
  $result = $wpdb->get_results($statement, ARRAY_A);
  return $result;
}

/**
 * 
 * @global object $wpdb
 * @param array $attrs
 * @return string
 */
function shortcode_query_builder($attrs) 
{
  // Define available attributes with default value and description
  $attrs_available = sqb_define_avaliable_attrs();
  $errors = [];
  
  if (sqb_verify_attrs($attrs, $attrs_available, $errors)) {
    $result = sqb_query_database($attrs);
    $content = sqb_render_html($result, $attrs);
  } else {
    $content = sqb_render_help($attrs_available, $errors);
  }

  return $content;
}

add_shortcode('shortcode-query', 'shortcode_query_builder');

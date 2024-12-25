<?php

/**
 * Render functions for query results
 */

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

/**
 * Returns array of sprintf format definition for each table column
 * 
 * @param array $col_keys - column keys
 * @param array $format_defs - sprintf format definitions for all columns, separated by | (pipe)
 * @return array
 */
function sqb_get_table_col_formats($col_keys, $format_defs)
{
  // Make format elements array same length as given cols in row
  $format_elements = ($format_defs)?array_map("trim", explode('|', $format_defs)):[];
  // crop format elements, if too many
  $elements_diff = count($format_elements) - count($col_keys);
  if ($elements_diff > 0) {
    $format_elements = array_slice($format_elements, 0, count($col_keys));
  }
  // or add format elements, if too few
  elseif ($elements_diff < 0) {
    $format_elements = array_merge($format_elements, array_fill(0, abs($elements_diff), '%s'));
  }

  // Return associative array
  return array_combine($col_keys, $format_elements);
}

/**
 * Render HTML table head, matching number of columns
 * @param array $col_keys
 * @param string $table_headings
 * @return string
 */
function sqb_get_table_head($col_keys, $table_headings)
{
  // Make format elements array same length as given cols in row (crop or 
  $headings = ($table_headings)?array_map("trim", explode('|', $table_headings)):[];
  // crop format elements, if too many
  $headings_diff = count($headings) - count($col_keys);
  
  if ($headings_diff > 0) {
    $headings = array_slice($headings, 0, count($col_keys));
  }
  // or add format elements, if too few
  elseif ($headings_diff < 0) {
    $headings = array_merge($headings, array_slice($col_keys, abs($headings_diff) + 1));
  }
  // Render HTML table head
  return '<tr>' . implode('', array_map(function($heading) { 
    return '<th>' . htmlentities($heading) . '</th>';
  }, $headings)) . '</tr>';
}

/**
 * Replace link placeholders and return URL
 * 
 * @param array $row
 * @param array $attrs
 * @return string - URL
 */
function sqb_get_link($row, $attrs)
{
  if ($attrs['link']) {
    $link = $attrs['link'];
    // tbd: Replace placeholders in $link
    $link_start_tag = '<a href="' . $link . '">';
    $link_end_tag = '</a>';
  }
  else {
    // What?
  }
}


/**
 * Render help with syntax and attributes
 * 
 * @param array $attrs
 * @return string - Rendered HTML help
 */
function sqb_render_help($attrs_available, $errors)
{
  $html = '';
  if (is_array($errors) && count($errors) > 0) {
    $html .= '<h3>Errors from Plugin Shortcode Query Builder</h3>'."\n";
    $html .= '<div class="errors"><p>' . implode('</p><p>', $errors) . '</p></div>';
  }
  $html .= '<h3>Help for Plugin Shortcode Query Builder</h3>'."\n";
  $html .= '<p><strong>Some attributes are not correct.</strong></p>'."\n";
  $html .= '<h4>Syntax:</h4>'."\n";
//  $html .= '<p>test</p>'."\n";
  $html .= '<p>&lbrack;shortcode-query &lt;attributes&gt;=...&rbrack;</p>'."\n";
  $html .= '<h4>Available attributes</h4>'."\n";
  $html .= '<ul>'."\n";
  foreach ($attrs_available as $attr => $definition) {
    $html .= '<li><strong>' . htmlentities($attr) . '</strong>: ' . htmlentities($definition['description']) . '</li>'."\n";
  }
  $html .= '</ul>'."\n";
  $html .= '<h4>Examples</h4>'."\n";
  $html .= '<p>&lbrack;shortcode-query table="em_events" cols="DATE_FORMAT(event_start_date, \'%e.\') AS start, DATE_FORMAT(event_end_date, \'%e.%m.%Y\') AS end, event_name" where="event_start_date > DATE(NOW())" order-by="event_start_date"Â limit="5" wrapper="ul" format="%s bis %s: %s" no-data-msg="Keine Seminare gefunden."&rbrack;</p>'."\n";
  $html .= '<p>&lbrack;shortcode-query table="em_events" cols="DATE_FORMAT(event_start_date, \'%e.%m.%Y\') AS start, DATE_FORMAT(event_end_date, \'%e.%m.%Y\') AS end, event_name" order-by="event_start_date" limit="5" wrapper="table" table-head="Von|Bis|Veranstaltung" no-data-msg="Keine Seminare gefunden."&rbrack;</p>'."\n";
  return $html;
}
/**
 * Render HTML code for given table data
 * 
 * @param array $rows
 * @param array $attrs
 * @return string - Rendered HTML code
 */
function sqb_render_html($rows, $attrs)
{
  if ($rows && is_array($rows) && count($rows) > 0) {
    
    // Render HTML output
    $html = '<' . $attrs['wrapper'] . ' class="' . $attrs['class'] . '">';
    
    // Add table head?
    if ($attrs['wrapper'] === 'table' && $attrs['table-head']) {
      $html .= sqb_get_table_head(array_keys($rows[0]), $attrs['table-head']);
    }

    // Add rows
    foreach ($rows as $row) {
      $html .= sqb_render_row($row, $attrs);
    }
    
    $html .= '</' . $attrs['wrapper'] . '>';
    
  } else {
    
    // Render no data message, if result is empty
    $html = '<div class="' . $attrs['class'] . ' no-data-msg">' . $attrs['no-data-msg'] . '</div>';
    
  }
  return $html;
}

/**
 * Render HTML code for given row data
 * 
 * @param array $row
 * @param array $attrs
 * @return string - Rendered HTML code
 */
function sqb_render_row($row, $attrs)
{
  if ($attrs['wrapper'] === 'table') {
    $html = sqb_render_table_row($row, $attrs);
  }
  else {
    $row_wrapper = (in_array($attrs['wrapper'], ['ul', 'ol']))?'li':$attrs['element-wrapper'];
    
    
    $link_start_tag = ($attrs['link'])?sqb_get_link($row, $attrs):'';
    $link_end_tag = ($attrs['link'])?'</a>':'';
    
    if ($attrs['format']) {

      // Render all fields formatted
      if ($attrs['format'] === 'json') {
        $html = json_encode($row);
      }
      else {
        $rows_diff = count($row) - substr_count($attrs['format'], '%');
        // crop values, if too many
        if ($rows_diff > 0) {
          $row = array_slice($row, 0, substr_count($attrs['format'], '%'));
        }
        // or add values, if too few
        elseif ($rows_diff < 0) {
          $row = array_merge($row, array_fill(0, abs($rows_diff), '?'));
        }

        $html = vsprintf($attrs['format'], array_values($row));
        if ($rows_diff != 0) {
          $html .= ' - ERROR: Format string (' . $attrs['format'] . ') has not correct number of placeholders.</td>';
        }
      }
    }
    else {
      // Render fields separated by comma
      $html = implode(', ', $row);
    }
    return '<' . $row_wrapper . '>' . $link_start_tag . $html . $link_end_tag . '</' . $row_wrapper . '>';
  }
  
  return $html;
}

function sqb_render_table_row($row, $attrs)
{
  $col_formats = sqb_get_table_col_formats(array_keys($row), $attrs['format']);
  $html = '';
  $link_start_tag = ($attrs['link'])?sqb_get_link($row, $attrs):'';
  $link_end_tag = ($attrs['link'])?'</a>':'';

  foreach ($row as $col_key => $col_value) {
    try {
      $html .= '<td>' . sprintf($col_formats[$col_key], $col_value?:'') . '</td>';
    } catch (ArgumentCountError $e) {
      $html .= '<td>ERROR: Format string for field <i>' . $col_key . '</i> has more than 1 placeholders.</td>';
    }
  }
  return '<tr>' . $html . '</tr>';
}


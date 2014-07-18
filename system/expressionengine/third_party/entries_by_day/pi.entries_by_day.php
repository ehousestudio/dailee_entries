<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Entries By Day Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Samuel J. King
 * @link		http://samueljking.net
 */

$plugin_info = array(
	'pi_name'		=> 'Entries By Day',
	'pi_version'	=> '1.0',
	'pi_author'		=> 'Samuel J. King',
	'pi_author_url'	=> 'http://samueljking.net',
	'pi_description'=> 'Returns a list of entries categorized by day, month, and year.',
	'pi_usage'		=> Entries_by_day::usage()
);

// ------------------------------------------------------------------------
/*
		TODOS:
		1. ADD A START AND END DATE PARAMETER
		2. ADD A WAY TO MODIFY OUTPUT DISPLAY (NESTED/LINEAR, OR SOMETHING LIKE THAT)
		3. ADD THE ABILITY TO CONTROL HTML WRAPPING ELEMENTS AROUND THE HEADER DISPLAY
*/

class Entries_by_day {

	public $return_data;

	public $entries = array();

	private function _debug_array($array){
	  echo '<pre>';
	  print_r($array);
	  echo '</pre>';
	  die();
	}

	// thanks Stack Overflow!
	// http://stackoverflow.com/questions/3654295/remove-empty-array-elements
	private function _array_remove_empty($haystack){
	  foreach ($haystack as $key => $value) {
	    if (is_array($value)) {
	      $haystack[$key] = $this->_array_remove_empty($haystack[$key]);
	    }
	    if (empty($haystack[$key])) {
	      unset($haystack[$key]);
	    }
	  }
	  return $haystack;
	}

	/**
	 * Constructor
	 */

	public function __construct()
	{
		$this->EE =& get_instance();
		if ( ! class_exists('Channel')){
			require_once(APPPATH.'modules/channel/mod.channel.php');
		}
		// get channel name 
		// * REQUIRED *
		$channel_name = $this->EE->TMPL->fetch_param('channel');

		// get limit - defaults to everything... (?)
		$limit = ee()->TMPL->fetch_param('limit', 50);

		// are we showing the year header ?
		$show_year_header = ee()->TMPL->fetch_param('show_year_header', "true");
		$year_header_format = ee()->TMPL->fetch_param('year_header_format', 'Y');

		// are we showing the month header ?
		$show_month_header = ee()->TMPL->fetch_param('show_month_header', "true");
		$month_header_format = ee()->TMPL->fetch_param('month_header_format', 'F');

		// are we showing the month header ?
		$show_day_header = ee()->TMPL->fetch_param('show_day_header', "true");
		$day_header_format = ee()->TMPL->fetch_param('day_header_format', 'j');

		// initial query to retrive the number of entries by day
		$get_channel = ee()->db->query("SELECT channel_id, field_group FROM exp_channels WHERE channel_name = '$channel_name'");
		$channel_id = $get_channel->row('channel_id');
		$field_group = $get_channel->row('field_group');

		$get_entries_by_day = ee()->db->query("
		  SELECT 
		    DATE(FROM_UNIXTIME(entry_date)) AS entry_date, 
		    COUNT(*) AS num_entries 
		  FROM exp_channel_titles 
		  GROUP BY DATE(FROM_UNIXTIME(entry_date)) 
		  ORDER BY entry_date DESC
		");

		// loop through the results from above and grab the entry_ids of the entries for each day
		// stuff it in the main entries array
		// pay attention to the limit variable - if we hit the limit, stop adding to the array
		// breaking the loop seems to cause a clusterfuck
		$i = 0;

		foreach ($get_entries_by_day->result_array() as $day){
		  if ($day['num_entries']>0){
		    $num_entries = $day['num_entries'];
		    $floor = strtotime($day['entry_date']);
		    $ceiling = strtotime($day['entry_date']) + (60*60*24) - 1;
		    $entry_date = strtotime($day['entry_date']);
		    $year = date('Y', $entry_date);
		    $month = date('F', $entry_date);
		    $day = date('j', $entry_date);
		    $todays_entries = array();
		    $query = ee()->db->query("SELECT entry_id FROM exp_channel_titles WHERE entry_date BETWEEN $floor AND $ceiling");
		    foreach ($query->result_array() as $row){
		      if ($i < $limit){
		        $todays_entries[] = $row['entry_id'];
		      }
		      $i++;
		    }
		    $entries[$year][$month][$day] = $todays_entries;
		  }
		}

		$final_entries = array();

		// remove any empty array nodes
		$entries = $this->_array_remove_empty($entries);

		// build final output
		$output = '<ul class="entries_by_date">';
		  foreach ($entries as $year => $months){
		    if ($show_year_header=="true"){
		      $output .= '<li class="years">'.date($year_header_format, strtotime($month.' '.$day.', '.$year));
		      $output .= '<ul>';
		    }
		    foreach ($months as $month => $days){
		      if ($show_month_header=="true"){
		        $output .= '<li class="months">'.date($month_header_format, strtotime($month.' '.$day.', '.$year));
		          $output .= '<ul>';
		      }
		      foreach ($days as $day => $todays_entries){
		        $output .= '<li class="days">'. date($day_header_format, strtotime($month.' '.$day.', '.$year));
		          $output .= '<ul>';

		          // the final query to pull the entry data
		          // grab each entry id for the given day and implode them to use in the IN query below
							if (empty($todays_entries)){
							    return $this->EE->TMPL->no_results;
							}
		          $todays_entries = implode('|',$todays_entries);
		          $this->EE->TMPL->tagparams['fixed_order'] = $todays_entries;
		          $channel = new Channel();
		          $output .= $channel->entries();
		          $output .= '</ul>';
		        $output .= '</li>';
		      } 
		      if ($show_month_header=="true"){
		          $output .= '</ul>';
		        $output .= '</li>';
		      }
		    }
		    if ($show_year_header=="true"){
		        $output .= '</ul>';
		      $output .= '</li>';
		    }
		  }
		$output .= '</ul>';
		$this->return_data = $output;

		//$this->_debug_array($entries);
	}
	
	// ----------------------------------------------------------------

	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

This plugin lets you display entries by date headers. For example, if you wanted to do something like:

- 2014
	- July
		- 16
			- entry 3
			- entry 2
			- entry 1
		- 15
			- entry 1
		- 10
			- entry 1
	- May
		- 5
			- entry 2
			- entry 1
- 2013
	-	December
		- 12
			- entry 1
...

The following parameters are currently accepted (with the defaults):

{exp:entries_by_day 
channel="" 
limit="50" 
show_year_header="true" 
year_header_format="Y" 
show_month_header="true" 
month_header_format="F" 
show_day_header="true" 
day_header_format="j" 
}
...
{/exp:entries_by_day}

To only show a day header, with the entry title, like this:

- July 16, 2014
	- entry 3

You would format your tag like so:

{exp:entries_by_day 
	channel="channel_short_name" 
	limit="50" 
	show_year_header="false" 
	show_month_header="false" 
	show_day_header="true" 
	day_header_format="F j, Y" 
}
	<li>{title}</li>
{/exp:entries_by_day}

Note that:
	1. each "entry" is dropped into an unordered list, so the content inside the tag should start and end with a li tag. (See below for remaining todos)
	2. the format string should be the PHP date formatting equivalent. 
	3. Standard channel entry tags should all work inside the tag pair, but this is not fully tested (see below)

TODOS:
	1. ADD A START AND END DATE PARAMETER
	2. ADD A WAY TO MODIFY OUTPUT DISPLAY (NESTED/LINEAR, OR SOMETHING LIKE THAT)
	3. ADD THE ABILITY TO CONTROL HTML WRAPPING ELEMENTS AROUND THE HEADER AND ENTRY DISPLAY

Please Note:
--------------------
This plugin was developed for a specific purpose and will not fit every situation I'm sure there are a number of bugs and inefficient methodology. It was an interesting problem to solve, however, and was something I've looked for a number of times. Feedback and/or forking is welcome and appreciated. 

<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}

/* End of file pi.entries_by_day.php */
/* Location: /system/expressionengine/third_party/entries_by_day/pi.entries_by_day.php */
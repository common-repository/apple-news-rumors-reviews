<?php

/*
Plugin Name: Apple - News, rumors & reviews
Plugin URI: http://www.applelunch.com/
Description: Enables an RSS widget which can be configured to show a specified number of RSS items of Apple news, rumors and/or reviews from AppleLunch.com
Author: Rune Jensen
Version: 0.8.2
Author URI: http://www.applelunch.com/
*/

function AppleLunch_RSS_init() {
	function AppleLunch_RSS() {
		include_once(ABSPATH.WPINC.'/rss.php');  
		$options = get_option('AppleLunch_RSS_Widget');
		$options = AppleLunch_RSS_LoadDefaults($options);

		$feed['all'] = 'http://feeds.feedburner.com/Applelunch?format=xml';
		$feed['reviews'] = 'http://feeds.feedburner.com/AppleLunchReviews?format=xml';
		$feed['applelunch'] = 'http://feeds.feedburner.com/AppleLunchAbout?format=xml';

		$i = 0;
		if($options['reviews'] AND !$options['newsrumors']) { $mode = 'ReviewsOnly'; }
		else if(!$options['reviews'] AND $options['newsrumors']) { $mode = 'ReviewsExcluded'; }
		else { $mode = 'All'; }
		$return = FALSE;
		$return .= $options['list_start'];
		if($mode == 'ReviewsOnly') {
			$feed['review'] = fetch_rss($feed['reviews']);
			foreach($feed['review']->items AS $k => $v) {
				$i++;
				if($i > $options['count']) { break; }
				$return .= AppleLunch_RSS_PrintItem($v, $options['formatting']);
			}
		} else if($mode == 'ReviewsExcluded') {
			$feed['all'] = fetch_rss($feed['all']);
			$feed['review'] = fetch_rss($feed['reviews']);
			$feed['applelunch'] = fetch_rss($feed['applelunch']);
			$exclude = FALSE;
			foreach($feed['review']->items AS $k => $v) { $i++; if($i > $options['count']) { break; } $exclude[$v['guid']] = TRUE; }
			$i = 0;
			foreach($feed['applelunch']->items AS $k => $v) { $i++; if($i > $options['count']) { break; } $exclude[$v['guid']] = TRUE; }
			$i = 0;
			foreach($feed['all']->items AS $k => $v) {
				if(!$exclude[$v['guid']]) {
					$i++;
					if($i > $options['count']) { break; }
					$return .= AppleLunch_RSS_PrintItem($v, $options['formatting']);
				}
			}
		} else if($mode == 'All') {
			$feed['all'] = fetch_rss($feed['all']);
			$feed['applelunch'] = fetch_rss($feed['applelunch']);
			$exclude = FALSE;
			foreach($feed['applelunch']->items AS $k => $v) { $i++; if($i > $options['count']) { break; } $exclude[$v['guid']] = TRUE; }
			$i = 0;
			foreach($feed['all']->items AS $k => $v) {
				if(!$exclude[$v['guid']]) {
					$i++;
					if($i > $options['count']) { break; }
					$return .= AppleLunch_RSS_PrintItem($v, $options['formatting']);
				}
			}
		}
		$return .= $options['list_end'];

		return $return;
	}
	function AppleLunch_RSS_DataFormatting($date) {
		$months_str = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$months_int = array('01', '02', '03', '04', '05', '06', '07', '08', '09', 10, 11, 12);
		$day = substr($date, strpos($date, ', ')+2, 2);
		$month = substr($date, strpos($date, ', ')+5, 3);
		$year = substr($date, strpos($date, $month)+4, 4);
		$month = str_replace($months_str, $months_int, $month);
		$hour = substr($date, strpos($date, $year)+5, 2);
		$date = mktime($hour+2, 0, 0, $month, $day, $year);

		return date('m/d', $date);
	}
	function AppleLunch_RSS_PrintItem($v, $item_formatting) {
		return str_replace(array('[link]', '[title]', '[description]', '[date]'), array($v['link'], $v['title'], $v['description'], AppleLunch_RSS_DataFormatting($v['pubdate'])), $item_formatting);
	}
	function AppleLunch_RSS_Widget($args) {
		$options = get_option('AppleLunch_RSS_Widget');
		$options = AppleLunch_RSS_LoadDefaults($options);

		extract($args);
		echo $before_widget.$before_title.$options['title'].$after_title.AppleLunch_RSS().$after_widget;
	}
	function AppleLunch_RSS_LoadDefaults($options) {
		$options['title'] = empty($options['title']) ? __('Apple rumors & reviews') : $options['title'];
		$options['list_start'] = empty($options['list_start']) ? __('<ul>') : $options['list_start'];
		$options['list_end'] = empty($options['list_end']) ? __('</ul>') : $options['list_end'];
		$options['formatting'] = empty($options['formatting']) ? __('<li><a href="[link]">[date] - [title]</a></li>') : $options['formatting'];
		$options['count'] = empty($options['count']) ? __(5) : $options['count'];
		$options['reviews'] = empty($options['count']) ? __(TRUE) : $options['reviews'];
		$options['newsrumors'] = empty($options['count']) ? __(TRUE) : $options['newsrumors'];

		return $options;
	}
	function AppleLunch_RSS_WidgetControl() {
		$options = $newoptions = get_option('AppleLunch_RSS_Widget');
		if($_POST['AppleLunch_RSS_WidgetSubmit']) {
			$newoptions['title'] = strip_tags(stripslashes($_POST['AppleLunch_RSS_WidgetTitle']));
			$newoptions['count'] = $_POST['AppleLunch_RSS_ItemCount'];
			$newoptions['list_start'] = stripslashes($_POST['AppleLunch_RSS_ListStart']);
			$newoptions['list_end'] = stripslashes($_POST['AppleLunch_RSS_ListEnd']);
			$newoptions['formatting'] = stripslashes($_POST['AppleLunch_RSS_ItemFormatting']);
			$newoptions['reviews'] = $_POST['AppleLunch_RSS_ShowReviews'];
			$newoptions['newsrumors'] = $_POST['AppleLunch_RSS_ShowNewsRumors'];
		}
		if($options != $newoptions) {
			$options = $newoptions;
			update_option('AppleLunch_RSS_Widget', $options);
		}
		$options = AppleLunch_RSS_LoadDefaults($options);

		if($options['reviews']) { $ShowReviewsStatus = ' checked'; } else { $ShowReviewsStatus = FALSE; }
		if($options['newsrumors']) { $ShowNewsRumorsStatus = ' checked'; } else { $ShowNewsRumorsStatus = FALSE; }

		echo '
<h3>List</h3>
<p><label for="AppleLunch_RSS_WidgetTitle">Title: <input id="AppleLunch_RSS_WidgetTitle" name="AppleLunch_RSS_WidgetTitle" type="text" value="'.attribute_escape($options['title']).'" /></label><br />
<label for="AppleLunch_RSS_ListStart">Start: <input id="AppleLunch_RSS_ListStart" name="AppleLunch_RSS_ListStart" type="text" value="'.attribute_escape($options['list_start']).'" /></label><br />
<label for="AppleLunch_RSS_ListEnd">End: <input id="AppleLunch_RSS_ListEnd" name="AppleLunch_RSS_ListEnd" type="text" value="'.attribute_escape($options['list_end']).'" /></label></p>

<h3>Items</h3>
<p><label for="AppleLunch_RSS_ItemCount">Item count: <select id="AppleLunch_RSS_ItemCount" name="AppleLunch_RSS_ItemCount">';
		for($i=1; $i <= 10; $i++) {
			if(attribute_escape($options['count']) == $i OR (attribute_escape($options['count']) <= 0 AND $i == 5)) { $selected = ' selected'; } else { $selected = FALSE; }
			echo '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
		}
		echo '</select></label><br />
<label for="AppleLunch_RSS_ItemFormatting">Item formatting:<br /><i>([link], [title], [date], [description])</i><br /><textarea style="font-size: 10px;" id="AppleLunch_RSS_ItemFormatting" name="AppleLunch_RSS_ItemFormatting">'.attribute_escape($options['formatting']).'</textarea /></label><br />
<label for="AppleLunch_RSS_ShowReviews">Show reviews: <input type="checkbox" id="AppleLunch_RSS_ShowReviews" name="AppleLunch_RSS_ShowReviews"'.$ShowReviewsStatus.' /><br />
<label for="AppleLunch_RSS_ShowNewsRumors">Show news & rumors: <input type="checkbox" id="AppleLunch_RSS_ShowNewsRumors" name="AppleLunch_RSS_ShowNewsRumors"'.$ShowNewsRumorsStatus.' /></p>
<input type="hidden" id="AppleLunch_RSS_WidgetSubmit" name="AppleLunch_RSS_WidgetSubmit" value="true" />';
	}

	register_sidebar_widget('Apple - News, rumors & reviews', 'AppleLunch_RSS_Widget');
	register_widget_control('Apple - News, rumors & reviews', 'AppleLunch_RSS_WidgetControl');
}
add_action('plugins_loaded', 'AppleLunch_RSS_init');

?>

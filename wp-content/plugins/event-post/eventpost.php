<?php
/*
  Plugin Name: Event Post
  Plugin URI: https://event-post.com?mtm_campaign=wp-plugin&mtm_kwd=event-post&mtm_medium=dashboard
  Description: Add calendar and/or geolocation metadata on any posts.
  Version: 5.5
  Author: N.O.U.S. Open Useful and Simple
  Contributors: bastho, sabrinaleroy, unecologeek, agencenous
  Author URI: https://apps.avecnous.eu/?mtm_campaign=wp-plugin&mtm_kwd=event-post&mtm_medium=dashboard
  License: GPLv2
  Text Domain: event-post
  Domain Path: /languages/
  Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap,EELV,calendar,agenda,blocks
 */

/**
 *
 * @package event-post
 */
global $EventPost;
$EventPost = new EventPost();

$EventPost_cache=array();

function EventPost(){
	global $EventPost;
	return $EventPost;
}

/**
 * The main class where everything begins.
 *
 * Add calendar and/or geolocation metadata on posts
 */
class EventPost {
	const META_START = 'event_begin';
	const META_END = 'event_end';
	const META_COLOR = 'event_color';
	// http://codex.wordpress.org/Geodata
	const META_ADD = 'geo_address';
	const META_LAT = 'geo_latitude';
	const META_LONG = 'geo_longitude';
	// https://schema.org/eventStatus
	const META_STATUS = 'event_status';
	// https://schema.org/location
	const META_VIRTUAL_LOCATION = 'event_virtual_location';
	// https://pending.schema.org/eventAttendanceMode
	const META_ATTENDANCE_MODE = 'event_attendance_mode';

	public $list_id;
	public $NomDuMois;
	public $Week;
	public $settings;
	public $dateformat;

	private $pagination;
	public $version = '5.3';
	public $plugin_path ;
	private $script_sufix;

	public $map_interactions;
	public $quick_edit_fields;

	public $Shortcodes;

	public $attendance_modes;
	public $statuses;
	private $is_schema_output=false;

	public function __construct() {
		add_action('init', array(&$this,'init'), 1);
		add_action('widgets_init', array(&$this,'widgets_init'), 1);
		add_action('save_post', array(&$this, 'save_postdata'));
		add_filter('dashboard_glance_items', array(&$this, 'dashboard_right_now'));

		// Scripts
		add_action( 'admin_init', array(&$this, 'editor_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_head'));
		add_action('admin_print_scripts', array(&$this, 'admin_scripts'));
		add_action('wp_enqueue_scripts', array(&$this, 'load_styles'));

		// Single
		add_filter('the_content', array(&$this, 'display_single'), 9999);
		add_filter('the_title', array(&$this, 'the_title'), 9999, 2);
		add_action('the_event', array(&$this, 'print_single'));
		add_action('wp_head', array(&$this, 'single_header'));
		add_action('wpseo_schema_webpage', array(&$this, 'wpseo_schema_webpage'));

		// Ajax
		add_action('wp_ajax_EventPostGetLatLong', array(&$this, 'GetLatLong'));
		add_action('wp_ajax_EventPostHumanDate', array(&$this, 'HumanDate'));
		add_action('wp_ajax_EventPostList', array(&$this, 'ajaxlist'));
		add_action('wp_ajax_EventPostMap', array(&$this, 'ajaxmap'));
		add_action('wp_ajax_EventPostCalendar', array(&$this, 'ajaxcal'));
		add_action('wp_ajax_nopriv_EventPostCalendar', array(&$this, 'ajaxcal'));
		add_action('wp_ajax_EventPostCalendarDate', array(&$this, 'ajaxdate'));
		add_action('wp_ajax_nopriv_EventPostCalendarDate', array(&$this, 'ajaxdate'));

		// Calendar publishing
		add_action('parse_request', array(&$this, 'parse_request'), 100);
		add_action('wp_ajax_EventPostExport', array(&$this, 'export'));
		add_action('wp_ajax_nopriv_EventPostExport', array(&$this, 'export'));
		add_action('wp_ajax_EventPostFeed', array(&$this, 'feed'));
		add_action('wp_ajax_nopriv_EventPostFeed', array(&$this, 'feed'));

		//
		add_filter('eventpost_list_shema',array(&$this, 'custom_shema'),10,1);


		add_action('widgets_init', array(&$this, 'register_widgets'),1,1);

		// Quick edit
		add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit' ), 10, 2 );
		add_action( 'admin_print_scripts-edit.php', array(&$this, 'scripts_edit') );
		add_action( 'wp_ajax_inline-save', array(&$this, 'inline_save'), 1 );
		add_action( 'wp_ajax_eventpost_save_bulk', array(&$this, 'save_bulkdatas') );
		add_filter( 'eventpost_inline_field', array(&$this, 'inline_field_color'), 10, 3);

		$inc_path = plugin_dir_path(__FILE__).'inc/';
		$this->plugin_path = plugin_dir_path(__FILE__);
		include_once ($inc_path . 'settings.php');
		include_once ($inc_path . 'wrappers.php');
		include_once ($inc_path . 'widget.php');
		include_once ($inc_path . 'widget.cal.php');
		include_once ($inc_path . 'widget.map.php');
		include_once ($inc_path . 'multisite.php');
		include_once ($inc_path . 'shortcodes.php');
		include_once ($inc_path . 'openweathermap.php');
		include_once ($inc_path . 'children.php');

		$this->Settings = new EventPost\Settings;
	}

	/**
	 * PHP4 constructor
	 */
	public function EventPost(){
		$this->__construct();
	}

	public function init(){
		add_rewrite_rule('event-feed/?', admin_url('admin-ajax.php?action=EventPostFeed'), 'top');
		add_rewrite_rule('eventpost/([0-9]*)\.(ics|vcs)?', plugins_url('export/ics.php', __FILE__), 'top');
	}

	/**
	 * Init all variables when WP is ready
	 *
	 * @action evenpost_init
	 * @filter eventpost_default_list_shema
	 * @filter eventpost_list_shema
	 */
	public function widgets_init(){
		$this->META_START = 'event_begin';
		$this->META_END = 'event_end';
		$this->META_COLOR = 'event_color';
		// http://codex.wordpress.org/Geodata
		$this->META_ADD = 'geo_address';
		$this->META_LAT = 'geo_latitude';
		$this->META_LONG = 'geo_longitude';
		// https://schema.org/eventStatus
		$this->META_STATUS = 'event_status';
		// https://schema.org/location
		$this->META_VIRTUAL_LOCATION = 'event_virtual_location';
		// https://pending.schema.org/eventAttendanceMode
		$this->META_ATTENDANCE_MODE = 'event_attendance_mode';
		$this->list_id = 0;
		$this->NomDuMois = array('', __('Jan', 'event-post'), __('Feb', 'event-post'), __('Mar', 'event-post'), __('Apr', 'event-post'), __('May', 'event-post'), __('Jun', 'event-post'), __('Jul', 'event-post'), __('Aug', 'event-post'), __('Sept', 'event-post'), __('Oct', 'event-post'), __('Nov', 'event-post'), __('Dec', 'event-post'));
		$this->Week = array(__('Sunday', 'event-post'), __('Monday', 'event-post'), __('Tuesday', 'event-post'), __('Wednesday', 'event-post'), __('Thursday', 'event-post'), __('Friday', 'event-post'), __('Saturday', 'event-post'));
		$this->attendance_modes = array(
			'OfflineEventAttendanceMode' => _x('Physical', 'Attendance Mode', 'event-post'),
			'MixedEventAttendanceMode' => _x('Mixed', 'Attendance Mode', 'event-post'),
			'OnlineEventAttendanceMode' => _x('Online', 'Attendance Mode', 'event-post'),
		);
		$this->statuses = array(
			'EventScheduled' =>_x('Scheduled', 'Event Status', 'event-post'),
			'EventCancelled'=>_x('Cancelled', 'Event Status', 'event-post'),
			'EventMovedOnline'=>_x('Moved Online', 'Event Status', 'event-post'),
			'EventPostponed'=>_x('Postoned', 'Event Status', 'event-post'),
			'EventRescheduled'=>_x('Rescheduled', 'Event Status', 'event-post'),
		);

		$this->maps = $this->get_maps();
		$this->settings = $this->get_settings();

		do_action('evenpost_init', $this);

		$this->Shortcodes = new EventPost_Shortcodes();

		if(function_exists('register_block_type')){
			$block_path = plugin_dir_path(__FILE__).'inc/blocks/';
			include_once ($block_path . 'eventslist.php');
			include_once ($block_path . 'eventsmap.php');
			include_once ($block_path . 'eventscalendar.php');
		}

		// Edit
		add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
		foreach($this->settings['posttypes'] as $posttype){
			add_filter('manage_'.$posttype.'_posts_columns', array(&$this, 'columns_head'), 2);
			add_action('manage_'.$posttype.'_posts_custom_column', array(&$this, 'columns_content'), 10, 2);
		}


		if (!empty($this->settings['markpath']) && !empty($this->settings['markurl'])) {
			$this->markpath = ABSPATH.'/'.$this->settings['markpath'];
			$this->markurl = $this->settings['markurl'];
		} else {
			$this->markpath = plugin_dir_path(__FILE__) . 'markers/';
			$this->markurl = plugins_url('/markers/', __FILE__);
		}

		$this->dateformat = str_replace(array('yy', 'mm', 'dd'), array('Y', 'm', 'd'), __('yy-mm-dd', 'event-post'));

		$this->default_list_shema = apply_filters('eventpost_default_list_shema', array(
			'container' => '
			<%type% class="event_loop %id% %class%" id="%listid%" style="%style%" %attributes%>
			%list%
			%pagination%
			</%type%><!-- .event_loop -->',
			'item' => '
				<%child% class="event_item %class%" data-color="%color%">
					<a href="%event_link%">
						%event_thumbnail%
						<h5>%event_title%</h5>
					</a>
					%event_date%
					%event_cat%
					%event_location%
					%event_excerpt%
				</%child%><!-- .event_item -->'
		));
		$this->list_shema = apply_filters('eventpost_list_shema',$this->default_list_shema);

		$this->map_interactions=array(
			'DragRotate'=>__('Drag Rotate', 'event-post'),
			'DoubleClickZoom'=>__('Double Click Zoom', 'event-post'),
			'DragPan'=>__('Drag Pan', 'event-post'),
			'PinchRotate'=>__('Pinch Rotate', 'event-post'),
			'PinchZoom'=>__('Pinch Zoom', 'event-post'),
			'KeyboardPan'=>__('Keyboard Pan', 'event-post'),
			'KeyboardZoom'=>__('Keyboard Zoom', 'event-post'),
			'MouseWheelZoom'=>__('Mouse Wheel Zoom', 'event-post'),
			'DragZoom'=>__('Drag Zoom', 'event-post'),
		);

		$this->quick_edit_fields = apply_filters('eventpost_quick_edit_fields', array(
				'event'=>array(
						$this->META_START=>__('Begin:', 'event-post'),
						$this->META_END=>__('End:', 'event-post'),
						$this->META_COLOR=>__('Color:', 'event-post'),
					),
				'location'=>array(
						$this->META_ADD=>__('Address:', 'event-post'),
						$this->META_LAT=>__('Latitude:', 'event-post'),
						$this->META_LONG=>__('Longitude:', 'event-post'),
					),
				)
		);
		$this->bulk_edit_fields = apply_filters('eventpost_bulk_edit_fields', array(
				'event'=>array(
						$this->META_COLOR=>__('Color:', 'event-post'),
					),
				)
		);

	}

	public function register_widgets(){
		register_widget('EventPost_List');
		register_widget('EventPost_Map');
		register_widget('EventPost_Cal');
	}

	/**
	 * Usefull hexadecimal to decimal converter. Returns an array of RGB from a given hexadecimal color.
	 * @param string $color
	 * @return array $color($R, $G, $B)
	 */
	public function hex2dec($color = '000000') {
		$tbl_color = array();
		if (!strstr('#', $color)){
			$color = '#' . $color;
	}
		$tbl_color['R'] = hexdec(substr($color, 1, 2));
		$tbl_color['G'] = hexdec(substr($color, 3, 2));
		$tbl_color['B'] = hexdec(substr($color, 5, 2));
		return $tbl_color;
	}
	/**
	 * Fetch all registered image sizes
	 * @global array $_wp_additional_image_sizes
	 * @return array
	 */
	function get_thumbnail_sizes(){
		global $_wp_additional_image_sizes;
		$sizes = array('thumbnail', 'medium', 'large', 'full');
		foreach(array_keys($_wp_additional_image_sizes) as $size){
			$sizes[]=$size;
		}
		return $sizes;
	}

	/**
	 * Get blog settings, load and saves default settings if needed. Can be filterred using
	 *
	 * `<?php add_filter('eventpost_getsettings', 'some_function'); ?>`
	 *
	 * @action eventpost_getsettings_action
	 * @filter eventpost_getsettings
	 * @return array
	 */
	public function get_settings() {
		$ep_settings = $this->Settings->get_settings();
		return apply_filters('eventpost_getsettings', $ep_settings);
	}

	/**
	 * Checks if HTML schemas are not empty
	 * @param array $shema
	 * @return array
	 */
	public function custom_shema($shema){
	if(!empty($this->settings['container_shema'])){
		$shema['container']=$this->settings['container_shema'];
	}
	if(!empty($this->settings['item_shema'])){
		$shema['item']=$this->settings['item_shema'];
	}
	return $shema;
	}

	/**
	 * Parse the maps.json file. Custom maps can be added by using the `eventpost_getsettings` filter like the following example:
	 *
	 * ```
	 * <?php
	 *  add_filter('eventpost_getsettings', 'map_function');
	 *  function map_function($maps){
	 *	array_push($maps, array(
	 *		'name'=>'Myt custom map',
	 *		'id'=>'custom_map',
	 *		'urls'=>array(
	 *		  'http://a.customurl.org/{z}/{x}/{y}.png',
	 *		  'http://b.customurl.org/{z}/{x}/{y}.png',
	 *		  'http://c.customurl.org/{z}/{x}/{y}.png',
	 *		)
	 *	));
	 *	return $maps;
	 *  }
	 *  ?>
	 * ```
	 *
	 * @filter eventpost_maps
	 * @return array of map arrays ['name', 'id', 'urls']
	 */
	public function get_maps() {
		$maps = array();
		$filename = plugin_dir_path(__FILE__) . 'maps.json';
		if (is_file($filename) && (false !== $json = json_decode(file_get_contents($filename)))) {
			// Convert objects to array to ensure retrocompatibility
			$arrays = array();
			foreach($json as $map){
				$arrays[$map->id] = (array) $map;
			}
			$maps = apply_filters('eventpost_maps', $arrays);
		}
		return $maps;
	}

	/**
	 *
	 * @return array
	 */
	public function get_colors() {
		$colors = array();
		if (is_dir($this->markpath)) {
			$files = scandir($this->markpath);
			foreach ($files as $file) {
				if (substr($file, -4) == '.png') {
					$colors[substr($file, 0, -4)] = $this->markurl . $file;
				}
			}
		}
		return $colors;
	}

	/**
	 *
	 * @param string $color
	 * @return sring
	 */
	public function get_marker($color) {
		if (is_file($this->markpath . $color . '.png')) {
			return $this->markurl . $color . '.png';
		}
		return plugins_url('/markers/ffffff.png', __FILE__);
	}

	/**
	 * Enqueue CSS files
	 */
	public function load_styles() {
		//CSS
		if(!empty($this->settings['customcss'])){
			wp_enqueue_style('event-post-custom', $this->settings['customcss']);
		}
		elseif(is_file(get_stylesheet_directory().'/event-post.css') || is_file(get_template_directory().'/event-post.css')){
			wp_enqueue_style('event-post-custom', get_theme_file_uri('event-post.css'));
		}
		else{
			wp_register_style('event-post', plugins_url('/css/event-post.css', __FILE__), false,  filemtime( "{$this->plugin_path}/css/event-post.css" ));
			wp_enqueue_style('event-post');
		}

		// Lib scripts
		wp_enqueue_style('openlayers', plugins_url('/css/ol.css', __FILE__), false,  filemtime( "{$this->plugin_path}/css/ol.css" ));
		wp_enqueue_style('dashicons', includes_url('/css/dashicons.min.css'));
	}
	/**
	 * Enqueue Editor style
	 */
	public function editor_styles() {
		add_editor_style( plugins_url('/css/event-post.css', __FILE__) );
	}

	/**
	 * Enqueue JS files
	 */
	public function load_scripts($deps = array('jquery')) {
		// JS
		wp_enqueue_script('jquery', false, false, false, true);
		wp_enqueue_script('event-post', plugins_url('/js/event-post.min.js', __FILE__), $deps, filemtime( "{$this->plugin_path}/js/event-post.min.js" ), true);
		$maps = $this->maps;
		foreach($maps as $m=>$map){
			if(isset($map['api_param']) && $this->settings['tile_api_key']){
				foreach($map['urls'] as $i=>$url){
					$maps[$m]['urls'][$i] = add_query_arg($map['api_param'], $this->settings['tile_api_key'], $url);
				}
			}
		}
		wp_localize_script('event-post', 'eventpost_params', array(
			'imgpath' => plugins_url('/img/', __FILE__),
			'maptiles' => $maps,
			'defaulttile' => $this->settings['tile'],
			'zoom' => $this->settings['zoom'],
			'ajaxurl' => admin_url() . 'admin-ajax.php',
			'map_interactions'=>$this->map_interactions,
		));
	}
	/**
	 * Enqueue JS files for maps
	 */
	public function load_map_scripts() {
		// JS
		wp_enqueue_script('openlayers', plugins_url('/js/ol.js', __FILE__), false,  filemtime( "{$this->plugin_path}/js/ol.js" ), true);
		$this->load_scripts(array('jquery', 'openlayers'));
		if(is_admin()){
			$this->admin_scripts(array('jquery', 'openlayers'));
		}
	}

	/**
	 * Enqueue CSS files in admin
	 */
	public function admin_head() {
		$page = basename($_SERVER['SCRIPT_NAME']);
		if( $page!='post-new.php' && !($page=='post.php' && filter_input(INPUT_GET, 'action')=='edit') && !($page=='options-general.php' && filter_input(INPUT_GET, 'page')=='event-settings') ){
			return;
		}
		wp_enqueue_style('openlayers', plugins_url('/css/ol.css', __FILE__), false,  filemtime( "{$this->plugin_path}/css/ol.css" ));
		wp_enqueue_style('jquery-ui', plugins_url('/css/jquery-ui.css', __FILE__), false,  filemtime( "{$this->plugin_path}/css/jquery-ui.css" ));
		wp_enqueue_style('event-post-admin', plugins_url('/css/event-post-admin.css', __FILE__), false,  filemtime( "{$this->plugin_path}/css/event-post-admin.css" ));
	}

	/**
	 * Enqueue JS files in admin
	 */
	public function admin_scripts($deps = array('jquery'), $force=false) {
		$page = basename($_SERVER['SCRIPT_NAME']);
		if(!$force && $page!='post-new.php' && !($page=='post.php' && filter_input(INPUT_GET, 'action')=='edit') && !($page=='options-general.php' && filter_input(INPUT_GET, 'page')=='event-settings')){
			return;
		}
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-effects-core');
		wp_enqueue_script('jquery-effects-shake');
		if(!is_array($deps)){
			$deps = array('jquery');
		}
		if($this->settings['datepicker']=='simple' || !is_admin() || (isset($_GET['page']) && $_GET['page']=='event-settings')){
			wp_enqueue_script('jquery-ui-datepicker');
			$deps[] = 'jquery-ui-datepicker';
		}
		wp_enqueue_script('event-post-admin', plugins_url('/js/event-post-admin.min.js', __FILE__), $deps,  filemtime( "{$this->plugin_path}/js/event-post-admin.min.js" ), true);
		$language = get_bloginfo('language');
		if (strpos($language, '-') > -1) {
			$language = strtolower(substr($language, 0, 2));
		}
		wp_localize_script('event-post-admin', 'eventpost', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'imgpath' => plugins_url('/img/', __FILE__),
			'date_choose' => '<span class="screen-reader-text">'.__('Choose', 'event-post').'</span><i class="dashicons dashicons-calendar-alt"></i>',
			'date_format' => __('yy-mm-dd', 'event-post'),
			'more_icons' => __('More icons', 'event-post'),
			'pick_a_date'=>__('Pick a date','event-post'),
			'use_current_location'=>__('Use my current location','event-post'),
			'start_drag'=>__('Click to<br>drag the map<br>and change location','event-post'),
			'empty_address'=>__('Be kind to fill a non empty address:)', 'event-post'),
			'search'=>__('Type an address', 'event-post'),
			'stop_drag'=>_x('Done','Stop allowing to drag the map', 'event-post'),
			'datepickeri18n'=>array(
				'order'=>__( '%1$s %2$s, %3$s @ %4$s:%5$s', 'event-post'),
				'day'=>__('Day', 'event-post'),
				'month'=>__('Month', 'event-post'),
				'year'=>__('Day', 'event-post'),
				'hour'=>__('Hour', 'event-post'),
				'minute'=>__('Minute', 'event-post'),
				'ok'=>__('OK', 'event-post'),
				'cancel'=>__('Cancel', 'event-post'),
				'remove'=>__('Remove', 'event-post'),
				'edit'=>__('Edit', 'event-post'),
				'months'=>$this->NomDuMois,
			),
			'META_START' => $this->META_START,
			'META_END' => $this->META_END,
			'META_ADD' => $this->META_ADD,
			'META_LAT' => $this->META_LAT,
			'META_LONG' => $this->META_LONG,
			'META_STATUS' => $this->META_STATUS,
			'META_ATTENDANCE_MODE' => $this->META_ATTENDANCE_MODE,
			'lang'=>$language,
			'maptiles' => $this->maps,
			'defaulttile' => $this->settings['tile']
		));
	}
	function scripts_edit() {
		// load only when editing a supported post type
		$current_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
		if ( in_array( $current_post_type, $this->settings['posttypes'] ) ) {
			wp_enqueue_script( 'eventpost-inline-edit', plugins_url( 'js/inline-edit.min.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), '', true );
			wp_localize_script('eventpost-inline-edit', 'eventpost_inline_edit', array(
				'quick'=>$this->quick_edit_fields,
				'bulk'=>$this->bulk_edit_fields
			));
		}
	}

	function get_rich_result($event){
		/**
		 * https://search.google.com/test/rich-results
			{
				"@context": "https://schema.org",
				"@type": "Event",
				"name": "The Adventures of Kira and Morrison",
				"startDate": "2025-07-21T19:00",
				"endDate": "2025-07-21T23:00",
				"eventStatus": "https://schema.org/EventScheduled",
				"eventAttendanceMode": "https://schema.org/OnlineEventAttendanceMode",
				"location": {
					"@type": "VirtualLocation",
					"url": "https://operaonline.stream5.com/"
					},
				"image": [
					"https://example.com/photos/1x1/photo.jpg",
					"https://example.com/photos/4x3/photo.jpg",
					"https://example.com/photos/16x9/photo.jpg"
				 ],
				"description": "The Adventures of Kira and Morrison is coming to Snickertown in a can’t miss performance.",
				"offers": {
					"@type": "Offer",
					"url": "https://www.example.com/event_offer/12345_201803180430",
					"price": "30",
					"priceCurrency": "USD",
					"availability": "https://schema.org/InStock",
					"validFrom": "2024-05-21T12:00"
				},
				"performer": {
					"@type": "PerformingGroup",
					"name": "Kira and Morrison"
				}
			}
		 */
		$location_virtual = array(
			'@type'=>'VirtualLocation',
			'url'=>$event->virtual_location,
		);
		$physical_location = array(
			'@type'=>'place',
			'name'=>$event->address,
			'address'=>$event->address,
			'geo'=>array(
				'@type'=>'GeoCoordinates',
				'latitude'=>$event->lat,
				'longitude'=>$event->long,
			),
		);
		$min	= 60 * get_option('gmt_offset');
		$sign   = $min < 0 ? "-" : "+";
		$absmin = abs($min);
		$gmt_offset = sprintf("%s%02d:%02d", $sign, $absmin/60, $absmin%60);
		$time_format = (is_numeric($event->time_start) && is_numeric($event->time_end) && date('H:i', $event->time_start) != date('H:i', $event->time_end) && date('H:i', $event->time_start) != '00:00' && date('H:i', $event->time_end) != '00:00') ? 'Y-m-d\Th:i:00'.$gmt_offset : 'Y-m-d';

		return apply_filters('event-post-rich-result', array(
			'@context'=>'https://schema.org',
			'@type'=>'event',
			'name'=>$event->post_title,
			'datePublished'=>str_replace(' ', 'T', $event->post_date_gmt).$gmt_offset,
			'dateModified'=>str_replace(' ', 'T', $event->post_modified_gmt).$gmt_offset,
			'startDate'=>$event->time_start ? date($time_format, $event->time_start) : null,
			'endDate'=>$event->time_end ? date($time_format, $event->time_end) : null,
			'eventStatus'=>$event->status,
			'eventAttendanceMode'=>$event->attendance_mode,
			'location'=> ($event->attendance_mode == 'MixedEventAttendanceMode') ? array($location_virtual,$physical_location) : ($event->attendance_mode == 'OnlineEventAttendanceMode' ? $location_virtual : $physical_location),
			'image'=> has_post_thumbnail($event->ID) ? array(
				get_the_post_thumbnail_url($event->ID, 'post-thumbnail'),
				get_the_post_thumbnail_url($event->ID, 'medium'),
				get_the_post_thumbnail_url($event->ID, 'large'),
				get_the_post_thumbnail_url($event->ID, 'full'),
			) : null,
			'description'=>$event->post_excerpt,
		), $event);
	}

	function wpseo_schema_webpage($rich_result){
		$event = $this->retreive();
		if ($event->time_start != '' && $event->time_end != '') {
			$rich_result = array_merge($rich_result, $this->get_rich_result($event));
		}
		$this->is_schema_output = true;
		return $rich_result;
	}

	/**
	 * Add custom header meta for single events
	 */
	public function single_header() {
		if (is_single()) {
			$twitter_label_id=0;
			$event = $this->retreive();
			$has_location = $has_time = false;
			if ($event->address != '' || ($event->lat != '' && $event->long != '')) {
				$twitter_label_id++;
				$has_location = true;
				?>
<meta name="geo.placename" content="<?php echo  esc_attr($event->address) ?>" />
<meta name="geo.position" content="<?php echo  esc_attr($event->lat) ?>;<?php echo  esc_attr($event->long) ?>" />
<meta name="ICBM" content="<?php echo $event->lat ?>;<?php echo  esc_attr($event->long) ?>" />
<meta property="place:location:latitude"  content="<?php echo  esc_attr($event->lat) ?>" />
<meta property="place:location:longitude" content="<?php echo  esc_attr($event->long) ?>" />
<meta name="twitter:label<?php echo $twitter_label_id; ?>" content="<?php _e('Location', 'event-post'); ?>"/>
<meta name="twitter:data<?php echo $twitter_label_id; ?>" content="<?php echo esc_attr($event->address) ?>"/>
<?php
			}
			if ($event->start != '' && $event->end != '') {
				$has_time = true;
				$twitter_label_id++;
				?>
<meta name="datetime-coverage-start" content="<?php echo date('c', $event->time_start) ?>" />
<meta name="datetime-coverage-end" content="<?php echo date('c', $event->time_end) ?>" />
<meta name="twitter:label<?php echo $twitter_label_id; ?>" content="<?php _e('Date', 'event-post'); ?>"/>
<meta name="twitter:data<?php echo $twitter_label_id; ?>" content="<?php echo esc_attr($this->human_date($event->time_start)) ?>"/>
<?php
			}
			if(($has_location || $has_time) && !$this->is_schema_output){
				$rich_result = $this->get_rich_result($event);
				$this->is_schema_output = true;
				?>
				<script type="application/ld+json"><?php echo json_encode($rich_result); ?></script>
				<?php
			}

		}
	}

	/**
	 *
	 * @param type $str
	 * @return string
	 * @since 5.0.1
	 */
	public function date_cleanup($str){
		return trim(str_replace(array('0', ' ', ':', '-'), '', $str));
	}

	/**
	 *
	 * @param string $str
	 * @return boolean
	 */
	public function dateisvalid($str) {
		return is_string($str) && $this->date_cleanup($str) != '';
	}

	/**
	 *
	 * @param string $date
	 * @param string $sep
	 * @return string
	 */
	public function parsedate($date, $sep = '') {
		if (!empty($date)) {
			return substr($date, 0, 10) . $sep . substr($date, 11, 8);
		} else {
			return '';
		}
	}

	/**
	 *
	 * @param mixed $date
	 * @param string $format
	 * @return type
	 */
	public function human_date($date, $format = 'l j F Y') {
		if($this->settings['dateforhumans']){
			if (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y')) {
				return __('today', 'event-post');
			} elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('+1 day'))) {
				return __('tomorrow', 'event-post');
			} elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('-1 day'))) {
				return __('yesterday', 'event-post');
			}
		}
		return date_i18n($format, $date);
	}

	/**
	 *
	 * @param timestamp $time_start
	 * @param timestamp $time_end
	 * @return string
	 */
	public function delta_date($time_start, $time_end){
		if(!$time_start || !$time_end){
			return;
		}

		$from_to_days = _x('%1$sfrom%2$s %3$s %4$sto%5$s %6$s', 'Days', 'event-post');
		$single_day_at = _x('%1$s%2$s,%3$s %4$s', 'From/To single day at time', 'event-post');
		$from_to_hours = _x('%1$sfrom%2$s %3$s %4$sto%5$s %6$s', 'Hours', 'event-post');
		$at_time = _x('%1$sat%2$s %3$s', 'Time', 'event-post');

		//Display dates
		$dates="\t\t\t\t".'<div class="event_date" data-start="' . $this->human_date($time_start) . '" data-end="' . $this->human_date($time_end) . '">';
		// Same day
		if (date('Ymd', $time_start) == date('Ymd', $time_end)) {
			$dates.= "\n\t\t\t\t\t\t\t".'<time itemprop="dtstart" datetime="' . date_i18n('c', $time_start) . '">'
					. '<span class="date date-single">' . $this->human_date($time_end, $this->settings['dateformat']) . "</span>";
			if (date('H:i', $time_start) != date('H:i', $time_end) && date('H:i', $time_start) != '00:00' && date('H:i', $time_end) != '00:00') {
				$dates.= ' '.sprintf(
					$from_to_hours,
					'<span class="linking_word linking_word-from">',
					'</span>',
					'<span class="time time-start">' . date_i18n($this->settings['timeformat'], $time_start) . '</span>',
					'<span class="linking_word linking_word-to">',
					'</span>',
					'<span class="time time-end">' . date_i18n($this->settings['timeformat'], $time_end) . '</span>'
				);
			}
			elseif (date('H:i', $time_start) != '00:00') {
				$dates.= ' '.sprintf(
					$at_time,
					'<span class="linking_word">',
					'</span>',
					'<span class="time time-single">' . date_i18n($this->settings['timeformat'], $time_start) . '</span>'
				);
			}
			$dates.="\n\t\t\t\t\t\t\t".'</time>';
		}
		// Not same day
		else {
				$dates.= ' '.sprintf(
					$from_to_days,
					'<span class="linking_word linking_word-from">',
					'</span>',
					'<time class="date date-start" itemprop="dtstart" datetime="' . date('c', $time_start) . '">'
							. ((date('H:i:s', $time_start) != '00:00:00' || date('H:i:s', $time_end) != '00:00:00')
									? sprintf($single_day_at, '<span class="date">'.$this->human_date($time_start, $this->settings['dateformat']).'</span>', '<span class="linking_word">', '</span>', '<span class="time">'.date_i18n($this->settings['timeformat'], $time_start).'</span>')
									: $this->human_date($time_start, $this->settings['dateformat'])
							)
							. '</time>',
					'<span class="linking_word linking_word-to">',
					'</span>',
					'<time class="date date-to" itemprop="dtend" datetime="' . date('c', $time_end) . '">'
							. ((date('H:i:s', $time_start) != '00:00:00' || date('H:i:s', $time_end) != '00:00:00')
									? sprintf($single_day_at, '<span class="date">'.$this->human_date($time_end, $this->settings['dateformat']).'</span>', '<span class="linking_word">', '</span>', '<span class="time">'.date_i18n($this->settings['timeformat'], $time_end).'</span>')
									: $this->human_date($time_end, $this->settings['dateformat'])
							)
							. '</time>'
				);
		}
		$dates.="\n\t\t\t\t\t\t".'</div><!-- .event_date -->';
		return $dates;
	}

	/**
	 *
	 * @param WP_Post object $post
	 * @param mixed $links
	 * @return string
	 */
	public function print_date($post = null, $links = 'deprecated', $context='') {
		$dates = '';
		$event = $this->retreive($post);
		if ($event->start != '' && $event->end != '') {

			$dates.=$this->delta_date($event->time_start, $event->time_end);
			if( // Status setting
				$this->settings['displaystatus'] == 'both' ||
				($this->settings['displaystatus'] == 'single' && is_single() ) ||
				($this->settings['displaystatus'] == 'list' && !is_single() )
			){
				$dates.='<span class="eventpost-status">'.$this->statuses[$event->status].'</span>';
			}
			$timezone_string = get_option('timezone_string');
			$gmt_offset = $gmt = $this->get_gmt_offset();

			if (
				!is_admin()
				&& ( // Export when setting
					$this->settings['export_when'] == 'both' ||
					( $this->settings['export_when'] == 'future' && $this->is_future($event) ) ||
					( $this->settings['export_when'] == 'past' && $this->is_past($event) )
				)
				&& ( // Export setting
					$this->settings['export'] == 'both' ||
					($this->settings['export'] == 'single' && is_single() ) ||
					($this->settings['export'] == 'list' && !is_single() )
				)
			) {
				// Export event
				$title = urlencode($post->post_title);
				$address = urlencode($post->address);
				$desc = urlencode($event->post_excerpt."\\n\\n".$post->permalink);
				$allday = ($post->time_start && $post->time_end && date('H:i:s', $post->time_start) == '00:00:00' && date('H:i:s', $post->time_end) == '00:00:00');
				$d_s = date("Ymd", $event->time_start) . ($allday ? ''  : 'T' . date("His", $event->time_start));
				$d_e = date("Ymd", $event->time_end) . ($allday ? ''  : 'T' . date("His", $event->time_end));
				$uid = $post->ID . '-' . $post->blog_id;
				$url = $event->permalink;

				// format de date ICS
				$permalink_structure = get_option( 'permalink_structure' );
				if($permalink_structure != ""){
					$ics_url = site_url('eventpost/'.$event->ID.'.ics');
					$vcs_url = site_url('eventpost/'.$event->ID.'.vcs');
				}
				else{
					$ics_url = add_query_arg(array('action'=>'EventPostExport', 'event_id'=>$event->ID, 'format'=>'ics'), admin_url('admin-ajax.php'));
					$vcs_url = add_query_arg(array('action'=>'EventPostExport', 'event_id'=>$event->ID, 'format'=>'vcs'), admin_url('admin-ajax.php'));

				}

				// format de date Google cal
				//$google_url = 'https://www.google.com/calendar/event?action=TEMPLATE&amp;text=' . $title . '&amp;dates=' . $d_s . 'Z/' . $d_e . 'Z&amp;details=' . $url . '&amp;ctz='.$timezone_string.'&amp;location=' . $address . '&amp;trp=false&amp;sprop=&amp;sprop=name';
				$google_url = add_query_arg(array(
					'action'=>'TEMPLATE',
					'trp'=>'false',
					'sprop'=>'name',
					'text'=>$title,
					'dates'=>$d_s.'/'.$d_e.'', // Removed Z to fix TZ issue
					'location'=>$address,
					'details'=>$desc,
					'gmt'=> urlencode($gmt_offset)
				), 'https://www.google.com/calendar/event');
				if(!empty($timezone_string)){
				  $google_url = add_query_arg(array(
					  'ctz'=>$timezone_string,
				  ), $google_url);
				}

				$dates.='
						<span class="eventpost-date-export">
							<a href="' . $ics_url . '" class="event_link event-export ics" target="_blank" title="' . __('Download ICS file', 'event-post') . '">ical</a>
							<a href="' . $google_url . '" class="event_link event-export gcal" target="_blank" title="' . __('Add to Google calendar', 'event-post') . '">Google</a>
							<a href="' . $vcs_url . '" class="event_link event-export vcs" target="_blank" title="' . __('Add to Outlook', 'event-post') . '">outlook</a>
							<i class="dashicons-before dashicons-calendar"></i>
						</span>';
			}
		}
		return apply_filters('eventpost_printdate', $dates);
	}

	/**
	 *
	 * @param WP_Post object $post
	 * @return string
	 */
	public function print_location($post=null, $context='') {
		$location = '';
		if ($post == null)
			$post = get_post();
		elseif (is_numeric($post)) {
			$post = get_post($post);
		}
		if (!isset($post->start)) {
			$post = $this->retreive($post);
		}
		$address = $post->address;
		$lat = $post->lat;
		$long = $post->long;
		$color = $post->color;
		$virtual_location = $post->virtual_location;
		$attendance_mode = $post->attendance_mode;

		if ($this->is_online($post) && $virtual_location) {
				$location.="\t\t\t\t".'<div><a href="'.esc_url($virtual_location).'" class="eventpost-virtual-location-link" target="_blank" rel="noopener">'
				.__('Join link', 'event-post')
				.'</a></div>'
				."\n";
		}
		if ($this->is_offline($post) && ($address != '' || ($lat != '' && $long != ''))) {
			$location.="\t\t\t\t".'<address';
			if ($lat != '' && $long != '') {
				$location.=' data-id="' . $post->ID . '" data-latitude="' . $lat . '" data-longitude="' . $long . '" data-marker="' . $this->get_marker($color) . '" ';
			}
			$location.=' itemprop="adr" class="eventpost-address">'
					. "\n\t\t\t\t\t\t\t".'<span>'
					. "\n".$address
					. "\n\t\t\t\t\t\t\t". '</span>';
			if ($context=='single' && $lat != '' && $long != '') {
				$location.="\n\t\t\t\t\t\t\t".'<a class="event_link gps dashicons-before dashicons-location-alt" href="https://www.openstreetmap.org/?lat=' . $lat .'&amp;lon=' . $long . '&amp;zoom=13" target="_blank"  itemprop="geo">' . __('Map', 'event-post') . '</a>';
			}
			$location.="\n\t\t\t\t\t\t".'</address>';
			if (wp_is_mobile() && $lat != '' && $long != '') {
				$location.="\n\t\t\t\t\t\t".'<a class="event_link gps-geo-link" href="geo:' . $lat . ',' . $long . '" target="_blank"  itemprop="geo"><i class="dashicons-before dashicons-location"></i> ' . __('Open in app', 'event-post') . '</a>';
			}
		}

		return apply_filters('eventpost_printlocation', $location);
	}

	/**
	 *
	 * @param WP_Post object $post
	 * @return string
	 */
	public function print_categories($post=null, $context='') {
		if ($post == null)
			$post = get_post();
		elseif (is_numeric($post)) {
			$post = get_post($post);
		}
		if (!isset($post->start)) {
			$post = $this->retreive($post);
		}
		$cats = '';
		$categories = $post->categories;
		if ($categories) {
			$cats.="\t\t\t\t".'<span class="event_category"';
			$color = $post->color;
			if ($color != '') {
				$cats.=' style="color:#' . $color . '"';
			}
			$cats.='>';
			foreach ($categories as $category) {
				$cats .= $category->name . ' ';
			}
			$cats.='</span>';
		}
		return $cats;
	}

	/**
	 * Generate, return or output date event datas
	 * @param WP_Post object $post
	 * @param string $class
	 * @filter eventpost_get_single
	 * @return string
	 */
	public function get_single($post = null, $class = '', $context='') {
		if ($post == null) {
			$post = $this->retreive();
		}
		$datas_date = $this->print_date($post, null, $context);
		$datas_cat = $this->print_categories($post, $context);
		$datas_loc = $this->print_location($post, $context);
		$classes = array(
			'event_data',
			'status-'.$post->status,
			'location-type-'.$post->attendance_mode,
			$class
		);
		if ($datas_date != '' || $datas_loc != '') {
			$rgb = $this->hex2dec($post->color);
			return '<div class="' . implode(' ', $classes) . '" style="border-left-color:#' . $post->color . ';background:rgba(' . $rgb['R'] . ',' . $rgb['G'] . ',' . $rgb['B'] . ',0.1)" itemscope itemtype="http://microformats.org/profile/hcard">'
					. apply_filters('eventpost_get_single', $datas_date . $datas_cat . $datas_loc, $post)
					. '</div>';
		}
		return '';
	}

	/**
	 * Displays dates of a gieven post
	 * @param WP_Post object $post
	 * @param string $class
	 * @return string
	 */
	public function get_singledate($post = null, $class = '', $context='') {
		return '<div class="event_data event_date ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . "\n\t\t".$this->print_date($post, null, $context) . "\n\t\t\t\t\t".'</div><!-- .event_date -->';
	}

	/**
	 * Displays coloured terms of a given post
	 * @param WP_Post object $post
	 * @param string $class
	 * @return string
	 */
	public function get_singlecat($post = null, $class = '', $context='') {
		return '<div class="event_data event_category ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . "\n\t\t".$this->print_categories($post, $context) . "\n\t\t\t\t\t".'</div><!-- .event_category -->';
	}

	/**
	 * Displays location of a given post
	 * @param WP_Post object $post
	 * @param string $class
	 * @return string
	 */
	public function get_singleloc($post = null, $class = '', $context='') {
		return '<div class="event_data event_location ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . "\n\t\t".$this->print_location($post, $context) . "\n\t\t\t\t\t".'</div><!-- .event_location -->';
	}

	/**
	 * Uses `the_content` filter to add event details before or after the content of the current post
	 * @param string $content
	 * @return string
	 */
	public function display_single($content) {
		if (is_page() || !is_single() || is_home() || !in_the_loop() || !is_main_query()){
			return $content;
		}

		$post = $this->retreive();
		$eventbar = apply_filters('eventpost_contentbar', $this->get_single($post, 'event_single', 'single'), $post);
		if($this->settings['singlepos']=='before'){
			$content=$eventbar.$content;
		}
		elseif($this->settings['singlepos']=='after'){
			$content.=$eventbar;
		}
		$this->load_map_scripts();
		return $content;
	}

	/**
	 * Outputs events details (dates, geoloc, terms) of given post
	 * @param WP_Post object $post
	 * @echoes string
	 * @return void
	 */
	public function print_single($post = null) {
		echo $this->get_single($post);
	}

	/**
	 * Alter the post title in order to add icons if needed
	 * @param string $title
	 * @return string
	 */
	public function the_title($title, $post_id = null){
	if(!$post_id || !in_the_loop() || !$this->settings['loopicons']){
		return $title;
	}
		$icons_ = array(
			// Emojis
			1=>array('🗓', '🗺'),
			// Dashicons
			2=>array('<span class="dashicons dashicons-calendar"></span>', '<span class="dashicons dashicons-location"></span>'),
		);

	$event = $this->retreive($post_id);
	if(!empty($event->start)){
	   $title .= ' '.$icons_[$this->settings['loopicons']][0];
	}
	if(!empty($event->lat) && !empty($event->long)){
	   $title .= ' '.$icons_[$this->settings['loopicons']][1];
	}
	return $title;
	}


	/**
	 * Return an HTML list of events
	 *
	 * @filter eventpost_params($defaults, 'list_events')
	 * @filter eventpost_listevents
	 * @filter eventpost_item_scheme_entities
	 * @filter eventpost_item_scheme_values
	 *
	 * @param array $atts
	 * @param string $id
	 * @param string $context
	 * @return string
	 */
	public function list_events($atts, $id = 'event_list', $context='') {

	$ep_settings = $this->settings;
		$defaults = array(
			'nb' => 0,
			'type' => 'div',
			'future' => true,
			'past' => false,
			'geo' => 0,
			'width' => '',
			'height' => '',
			'list' => 0,
			'zoom' => '',
			'map_position' => 'false',
			'latitude' => '',
			'longitude' => '',
			'tile' => $ep_settings['tile'],
			'pop_element_schema' => 'false',
			'htmlPop_element_schema' => '',
			'title' => '',
			'before_title' => '<h3>',
			'after_title' => '</h3>',
			'cat' => '',
			'tag' => '',
			'tax_name' => '',
			'tax_term' => '',
			'events' => '',
			'style' => '',
			'thumbnail' => '',
			'thumbnail_size' => '',
			'excerpt' => '',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'class' => '',
			'className' => '',
			'container_schema' => $this->list_shema['container'],
			'item_schema' => $this->list_shema['item'],
			'pages' => false,
		);
		// Map UI options
		foreach($this->map_interactions as $int_key=>$int_name){
			$defaults[$int_key]=true;
		}
		$atts = shortcode_atts(apply_filters('eventpost_params', $defaults, 'list_events'), $atts);

		extract($atts);
		if (!is_array($events)) {
			$events = $this->get_events($atts);
		}
		$ret = '';
		$this->list_id++;
		if (sizeof($events) > 0) {
			if (!empty($title)) {
				$ret.= html_entity_decode($before_title) . $title . html_entity_decode($after_title);
			}

			$child = ($type == 'ol' || $type == 'ul') ? 'li' : 'div';

			$html = '';

		if($id=='event_geolist'){
			$this->load_map_scripts();
			$html.=sprintf('<%1$s class="event_geolist_icon_loader"><p><span class="dashicons dashicons-location-alt"></span></p><p class="screen-reader-text">'.__('An events map', 'event-post').'</p></%1$s>', $type);
		}

			foreach ($events as $event) {
				$class_item = array(
					$this->is_future($event) ? 'event_future' : 'event_past',
					'status-'.$event->status,
					'location-type-'.$event->attendance_mode,
				);
				if ($ep_settings['emptylink'] == 0 && empty($event->post_content)) {
					$event->permalink = '#' . $id . $this->list_id;
				}
				elseif(empty($event->permalink)){
					$event->permalink=$event->guid;
				}
				$html.=str_replace(
						apply_filters('eventpost_item_scheme_entities', array(
					'%child%',
					'%class%',
					'%color%',
					'%event_link%',
					'%event_thumbnail%',
					'%event_title%',
					'%event_date%',
					'%event_cat%',
					'%event_location%',
					'%event_excerpt%'
						)), apply_filters('eventpost_item_scheme_values', array(
					$child,
					implode(' ', $class_item),
					$event->color,
					$event->permalink,
					$thumbnail == true ? '<span class="event_thumbnail_wrap">' . get_the_post_thumbnail($event->root_ID, !empty($thumbnail_size) ? $thumbnail_size : 'thumbnail', array('class' => 'attachment-thumbnail wp-post-image event_thumbnail')) . '</span>' : '',
					$event->post_title,
					$this->get_singledate($event, '', $context),
					$this->get_singlecat($event, '', $context),
					$this->get_singleloc($event, '', $context),
					$excerpt == true && $event->post_excerpt!='' ? '<span class="event_exerpt">'.$event->post_excerpt.'</span>' : '',
					), $event), $item_schema
				);
			}
			$attributes = '';
			if($id == 'event_geolist'){
				if($height==''){
					$height = '300px';
				}
				if($width==''){
					$width = '100%';
				}
				$attributes = 'data-tile="'.$tile.'" data-width="'.$width.'" data-height="'.$height.'" data-zoom="'.$zoom.'" data-map_position="'.$map_position.'" data-latitude="'.$latitude.'" data-longitude="'.$longitude.'" data-pop_element_schema="'.$pop_element_schema.'" data-htmlPop_element_schema="'.esc_attr($htmlPop_element_schema).'"  data-list="'.$list.'" data-disabled-interactions="';
				// add data-position avec ma variables
				foreach($this->map_interactions as $int_key=>$int_name){
					$attributes.=$atts[$int_key]==false ? $int_key.', ' : '';
				}
				$attributes.='"';
			}
			$pagination = '';
			if($pages && $this->pagination){
				global $wp_rewrite;
				$paged = ( get_query_var( 'page' ) ) ? absint( get_query_var( 'page' ) ) : 1;
				$pagination = paginate_links( array(
						'prev_text' => __('« Previous Events', 'event-post'),
						'next_text' => __('Next Events »', 'event-post'),
						'current' => $paged,
						'total' => $this->pagination['max_num_pages'],
					)
				);
			}
			$ret.=str_replace(
					array(
				'%type%',
				'%id%',
				'%class%',
				'%listid%',
				'%style%',
				'%attributes%',
				'%list%',
				'%pagination%',
					), array(
				$type,
				$id,
				$class.($className ? ' '.$className : '').($id == 'event_geolist' && $list ? ' has-list list-'.$list : ' no-list'),
				$id . $this->list_id,
				(!empty($width) ? 'width:' . $width . ';' : '') . (!empty($height) ? 'height:' . $height . ';' : '') . $style,
				$attributes,
				$html,
				$pagination,
					), $container_schema
			);
		}
		elseif(filter_input(INPUT_POST, 'action')=='bulk_do_shortcode'){
			return '<div class="event_geolist_icon_loader"><p><span class="dashicons dashicons-calendar"></span></p><p class="screen-reader-text">'.__('An empty list of events', 'event-post').'</p></div>';
		}
		return apply_filters('eventpost_listevents', $ret, $id.$this->list_id, $atts, $events, $context);
	}

	/**
	 * get_events
	 * @param array $atts
	 * @filter eventpost_params
	 * @filter eventpost_get_items
	 * @return array of post_ids wich are events
	 */
	public function get_events($atts) {
		$paged = ( get_query_var( 'page' ) ) ? absint( get_query_var( 'page' ) ) : 1;
		$requete = (shortcode_atts(apply_filters('eventpost_params', array(
					'nb' => 5,
					'future' => true,
					'past' => false,
					'geo' => 0,
					'cat' => '',
					'tag' => '',
					'date' => '',
					'orderby' => 'meta_value',
					'orderbykey' => $this->META_START,
					'order' => 'ASC',
					'tax_name' => '',
					'tax_term' => '',
					'paged' => $paged,
					'post_type'=> $this->settings['posttypes']
					), 'get_events'), $atts));
		extract($requete);
		wp_reset_query();


		$arg = array(
			'post_status' => 'publish',
			'post_type' => $post_type,
			'posts_per_page' => $nb,
			'paged' => $paged,
			'meta_key' => $orderbykey,
			'orderby' => $orderby,
			'order' => $order
		);

		if($tax_name=='category'){
			$tax_name='';
			$cat=$tax_term;
		}
		elseif($tax_name=='post-tag'){
			$tax_name='';
			$tag=$tax_term;
		}

		// CUSTOM TAXONOMY
		if ($tax_name != '' && $tax_term != '') {
			$arg['tax_query'] = array(
				array(
					'taxonomy' => $tax_name,
					'field'	=> 'slug',
					'terms'	=> $tax_term,
				),
			);
		}
		// CAT
		if ($cat != '') {
			if (preg_match('/[a-zA-Z]/i', $cat)) {
				$arg['category_name'] = $cat;
			} else {
				$arg['cat'] = $cat;
			}
		}
		// TAG
		if ($tag != '') {
			$arg['tag'] = $tag;
		}
		// DATES
		$meta_query = array(
			array(
				'key' => $this->META_END,
				'value' => '',
				'compare' => '!='
			),
			array(
				'key' => $this->META_END,
				'value' => '0:0:00 0:',
				'compare' => '!='
			),
			array(
				'key' => $this->META_END,
				'value' => ':00',
				'compare' => '!='
			),
			array(
				'key' => $this->META_START,
				'value' => '',
				'compare' => '!='
			),
			array(
				'key' => $this->META_START,
				'value' => '0:0:00 0:',
				'compare' => '!='
			)
		);
		if ($future == 0 && $past == 0) {
			$meta_query = array();
			$arg['meta_key'] = null;
			$arg['orderby'] = null;
			$arg['order'] = null;
		}
		elseif ($future == 1 && $past == 0) {
			$meta_query[] = array(
				'key' => $this->META_END,
				'value' => current_time('mysql'),
				'compare' => '>=',
					//'type'=>'DATETIME'
			);
		}
		elseif ($future == 0 && $past == 1) {
			$meta_query[] = array(
				'key' => $this->META_END,
				'value' => current_time('mysql'),
				'compare' => '<=',
					//'type'=>'DATETIME'
			);
		}
		if ($date != '') {
			$date = date('Y-m-d', $date);

			$meta_query = array(
				array(
					'key' => $this->META_END,
					'value' => $date . ' 00:00:00',
					'compare' => '>=',
					'type' => 'DATETIME'
				),
				array(
					'key' => $this->META_START,
					'value' => $date . ' 23:59:59',
					'compare' => '<=',
					'type' => 'DATETIME'
				)
			);
		}
		// GEO
		if ($geo == 1) {
			$meta_query[] = array(
				'key' => $this->META_LAT,
				'value' => '',
				'compare' => '!='
			);
			$meta_query[] = array(
				'key' => $this->META_LONG,
				'value' => '',
				'compare' => '!='
			);
			$arg['meta_key'] = $this->META_LAT;
			$arg['orderby'] = 'meta_value';
			$arg['order'] = 'DESC';
		}

		$arg['meta_query'] = $meta_query;

		$query_md5 = 'eventpost_' . md5(var_export($requete, true));
		// Check if cache is activated
		if ($this->settings['cache'] == 1 && false !== ( $cached_events = get_transient($query_md5) )) {
			return apply_filters('eventpost_get_items', is_array($cached_events) ? $cached_events : array(), $requete, $arg);
		}

		$events = apply_filters('eventpost_get', '', $requete, $arg);
		if ('' === $events) {
			global $wpdb;
			$query = new WP_Query($arg);
			$events = $wpdb->get_col($query->request);
			$this->pagination = array(
				'found_posts' => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
			foreach ($events as $k => $post) {
				$event = $this->retreive($post);
				$events[$k] = $event;
			}
		}
		if ($this->settings['cache'] == 1){
			set_transient($query_md5, $events, 5 * MINUTE_IN_SECONDS);
		}
		return apply_filters('eventpost_get_items', $events, $requete, $arg);
	}

	/**
	 * Checks if the given event is in the future or not
	 *
	 * @param  object  $event
	 * @param  boolean $exact Future status has to be calculated against time or entire day
	 * @return boolean
	 */
	function is_future($event, $exact=false){
		$match = current_time('timestamp');
		// if EXACT is false, end date is set to begining of the current day
		if(!$exact){
			$match = mktime(0, 0, 0, date('m', $match), date('d', $match), date('Y', $match));
		}
		return ($event->time_end >= $match);
	}

	/**
	 * Checks if the given event is completed or not
	 *
	 * @param  object  $event
	 * @param  boolean $exact Past status has to be calculated against time or entire day
	 * @return boolean
	 */
	function is_past($event, $exact=false){
		$match = current_time('timestamp');
		// if EXACT is false or full day event, end date is set to end of the current day
		if(!$exact || ( date('H:i:s', $event->time_start) == '00:00:00' && date('H:i:s', $event->time_end) == '00:00:00' )){
			$match = mktime(23, 59, 59, date('m', $match), date('d', $match), date('Y', $match));
		}
		return ($event->time_end < $match);
	}

	function is_online($event){
		return (in_array($event->attendance_mode, array('MixedEventAttendanceMode', 'OnlineEventAttendanceMode')));
	}

	function is_offline($event){
		return (in_array($event->attendance_mode, array('MixedEventAttendanceMode', 'OfflineEventAttendanceMode')));
	}


	/**
	 *
	 * @param object $event
	 * @return object
	 */
	public function retreive($event = null) {
		global $EventPost_cache;
		$ob = get_post($event);
		if(is_object($ob) && $ob->start){
			return $ob;
		}
		if(is_object($ob) && isset($EventPost_cache[$ob->ID])){
			return $EventPost_cache[$ob->ID];
		}
		$ob->start = get_post_meta($ob->ID, $this->META_START, true);
		$ob->end = get_post_meta($ob->ID, $this->META_END, true);
		if (!$this->dateisvalid($ob->start)){
			$ob->start = '';
		}
		if (!$this->dateisvalid($ob->end)){
			$ob->end = '';
		}
		$ob->root_ID = $ob->ID;
		$ob->time_start = !empty($ob->start) ? strtotime($ob->start) : '';
		$ob->time_end = !empty($ob->end) ? strtotime($ob->end) : '';
		$ob->virtual_location = get_post_meta($ob->ID, $this->META_VIRTUAL_LOCATION, true);
		$ob->address = get_post_meta($ob->ID, $this->META_ADD, true);
		$ob->lat = get_post_meta($ob->ID, $this->META_LAT, true);
		$ob->long = get_post_meta($ob->ID, $this->META_LONG, true);
		$ob->attendance_mode = (null != $att_mod = get_post_meta($ob->ID, $this->META_ATTENDANCE_MODE, true)) ? $att_mod : array_keys($this->attendance_modes)[0];
		$ob->status = (null != $status = get_post_meta($ob->ID, $this->META_STATUS, true)) ? $status : array_keys($this->statuses)[0];
		$ob->color = get_post_meta($ob->ID, $this->META_COLOR, true);
		$ob->categories = get_the_category($ob->ID);
		$ob->permalink = get_permalink($ob->ID);
		$ob->blog_id = get_current_blog_id();
		if ($ob->color == ''){
			$ob->color = '000000';
		}

		$EventPost_cache[$ob->ID] = apply_filters('eventpost_retreive', $ob);
		return $EventPost_cache[$ob->ID];
	}

	/**
	 *
	 * @param mixte $_term
	 * @param string $taxonomy
	 * @param string $post_type
	 */
	public function retreive_term($_term=null, $taxonomy='category', $post_type='post') {
		$term = get_term($_term, $taxonomy);

		if(!$term){
			return $term;
		}

		$term->start = $term->end = $term->time_start = $term->time_end = Null;

		$request = array(
			'post_type'=>$post_type,
			'tax_name'=>$term->taxonomy,
			'tax_term'=>$term->slug,
			'future'=>true,
			'past'=>true,
			'nb'=>-1,
			'order'=>'ASC'
		);

		$events = $this->get_events($request);

		$term->events_count = count($events);
		if($term->events_count){
			$term->start = $events[0]->start;
			$term->time_start = $events[0]->time_start;
			$term->end = $events[$term->events_count-1]->end;
			$term->time_end = $events[$term->events_count-1]->time_end;
		}

		$request['order']='DESC';
		$request['nb']=1;
		$request['orderbykey']=$this->META_END;
		$events = $this->get_events($request);
		if(count($events)){
			$term->end = $events[0]->end;
			$term->time_end = $events[0]->time_end;
		}

		return $term;

	}

	/** ADMIN ISSUES * */

	/**
	 * add custom boxes in posts edit page
	 */
	public function add_custom_box() {
		foreach($this->settings['posttypes'] as $posttype){
			add_meta_box(
				'event_post_date',
				__('Event date', 'event-post'),
				array(&$this, 'inner_custom_box_date'),
				$posttype,
				apply_filters('eventpost_add_custom_box_position', $this->settings['adminpos'], $posttype),
				'core',
				array(
					'__block_editor_compatible_meta_box' => true,
				)
			);
			add_meta_box(
				'event_post_loc',
				__('Location', 'event-post'),
				array(&$this, 'inner_custom_box_loc'),
				$posttype,
				apply_filters('eventpost_add_custom_box_position', $this->settings['adminpos'], $posttype),
				'core',
				array(
					'__block_editor_compatible_meta_box' => true,
				)
			);
			do_action('eventpost_add_custom_box', $posttype);
		}
	}
	/**
	 * display the date custom box
	 */
	public function inner_custom_box_date() {
		wp_nonce_field(plugin_basename(__FILE__), 'eventpost_nonce');
		$post_id = get_the_ID();
		$event = $this->retreive($post_id);
		$start_date = $event->start;
		$end_date = $event->end;
		$eventcolor = $event->color;

		$language = get_bloginfo('language');
		if (strpos($language, '-') > -1) {
			$language = strtolower(substr($language, 0, 2));
		}
		$colors = $this->get_colors();
		include (plugin_dir_path(__FILE__) . 'views/admin/custombox-date.php');
		do_action ('eventpost_custom_box_date', $event);
	}
	/**
	 * displays the location custom box
	 */
	public function inner_custom_box_loc($post) {
		$event = $this->retreive($post);
		include (plugin_dir_path(__FILE__) . 'views/admin/custombox-location.php');
		do_action ('eventpost_custom_box_loc', $event);
		$this->load_map_scripts();
	}

	/**
	*
	* @param string $column_name
	* @param boolean $bulk
	*/
   function quick_edit( $column_name, $post_type, $bulk=false) {

	if ($bulk) {
			static $eventpostprintNonceBulk = TRUE;
			if ($eventpostprintNonceBulk) {
				$eventpostprintNonceBulk = FALSE;
			}
			$fields = $this->bulk_edit_fields;
			echo '<input type="hidden" name="eventpost-bulk-editor" id="eventpost-bulk-editor" value="eventpost-bulk-editor">';
		}
		else {
			static $eventpostprintNonce = TRUE;
			if ($eventpostprintNonce) {
				$eventpostprintNonce = FALSE;
			}
			$fields = $this->quick_edit_fields;
		}
		wp_nonce_field(plugin_basename(__FILE__), 'eventpost_nonce');
		if(isset($fields[$column_name])): ?>
		<fieldset class="inline-edit-col-left inline-edit-<?php echo $column_name; ?>">
			<div class="inline-edit-group">
			<?php foreach ($fields[$column_name] as $fieldname=>$fieldlabel): ?>
				<fieldset class="inline-edit-col inline-edit-<?php echo $fieldname; ?>">
				  <div class="inline-edit-col column-<?php echo $fieldname; ?>">
					<label class="inline-edit-group">
						<span class="title"><?php echo $fieldlabel; ?></span>
						<span class="input-text-wrap">
							<?php echo $this->inline_field($fieldname, $bulk); ?>
						</span>
					</label>
				  </div>
				</fieldset>
		<?php endforeach; ?>
			</div>
		</fieldset>
	<?php endif;
	}

	/**
	 *
	 * @param type $fieldname
	 * @return type
	 */
	function inline_field($fieldname, $bulk){
		return apply_filters('eventpost_inline_field', '<input name="'.$fieldname.'" class="eventpost-inline-'.$fieldname.'" value="" type="text">', $fieldname, $bulk);
	}

	function inline_field_color($html, $fieldname, $bulk){
		if($fieldname==$this->META_COLOR){
			$colors = $this->get_colors();
			$html='';
			if($bulk){
				$html.= '<span class="eventpost-bulk-colorpicker-button link">'.__('No Change', 'event-post').'</span>';
			}
			$html .= '<select name="'.$fieldname.'" class="eventpost-inline-colorpicker eventpost-inline-'.$fieldname.' '.($bulk?'is-bulk':'no-bulk').'" >';
			if($bulk){
				$html.='<option value="false">— '.__('No Change', 'event-post').' —</option>';
			}
			foreach ($colors as $color=>$image_path){
				$html.='<option value="'.$color.'" data-path="'.$image_path.'">'.$color.'</option>';
			}
			$html.= '</select>';
		}
		return $html;
	}

	/**
	 *
	 * @param type $column_name
	 * @param type $post_type
	 */
	function bulk_edit($column_name, $post_type){
			$this->quick_edit($column_name, $post_type, true);
	}

	/**
	 *
	 */
	function inline_save(){
		$post_id = filter_input(INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT);
		$this->save_postdata($post_id);
	}
	/**
	 * When the post is saved, saves our custom data
	 * @param int $post_id
	 * @return void
	 */
	public function save_postdata($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return;
	}

		if (!wp_verify_nonce(filter_input(INPUT_POST, 'eventpost_nonce', FILTER_SANITIZE_STRING), plugin_basename(__FILE__))){
			return;
	}

		// Clean color or no color
		if (false !== $color = filter_input(INPUT_POST, $this->META_COLOR, FILTER_SANITIZE_STRING)) {
			update_post_meta($post_id, $this->META_COLOR, $color);
		}
		if (false !== $attendance_mode = filter_input(INPUT_POST, $this->META_ATTENDANCE_MODE, FILTER_SANITIZE_STRING)) {
			update_post_meta($post_id, $this->META_ATTENDANCE_MODE, $attendance_mode);
		}
		if (false !== $status = filter_input(INPUT_POST, $this->META_STATUS, FILTER_SANITIZE_STRING)) {
			update_post_meta($post_id, $this->META_STATUS, $status);
		}
		if (false !== $virtual_location = filter_input(INPUT_POST, $this->META_VIRTUAL_LOCATION, FILTER_SANITIZE_URL)) {
			update_post_meta($post_id, $this->META_VIRTUAL_LOCATION, $virtual_location);
		}
		// Clean date or no date
	if ((false !== $start = filter_input(INPUT_POST, $this->META_START, FILTER_SANITIZE_STRING)) &&
		(false !== $end = filter_input(INPUT_POST, $this->META_END, FILTER_SANITIZE_STRING)) &&
		'' != $start &&
		'' != $end) {
		update_post_meta($post_id, $this->META_START, substr($start,0,16).':00');
		update_post_meta($post_id, $this->META_END, substr($end,0,16).':00');
		}
	else {
		delete_post_meta($post_id, $this->META_START);
		delete_post_meta($post_id, $this->META_END);
	}

		// Clean location or no location
	if ((false !== $lat = filter_input(INPUT_POST, $this->META_LAT, FILTER_SANITIZE_STRING)) &&
		(false !== $long = filter_input(INPUT_POST, $this->META_LONG, FILTER_SANITIZE_STRING)) &&
		'' != $lat &&
		'' != $long) {
		update_post_meta($post_id, $this->META_ADD, filter_input(INPUT_POST, $this->META_ADD, FILTER_SANITIZE_STRING));
		update_post_meta($post_id, $this->META_LAT, $lat);
		update_post_meta($post_id, $this->META_LONG, $long);
	}
	else {
		delete_post_meta($post_id, $this->META_ADD);
		delete_post_meta($post_id, $this->META_LAT);
		delete_post_meta($post_id, $this->META_LONG);
	}

		$post_ids = (!empty($_POST['post_ids']) ) ? $_POST['post_ids'] : array();
	}

	function save_bulkdatas() {
		$current_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
		if (in_array($current_post_type, $this->settings['posttypes'])) {
			$post_ids = (!empty($_POST['post_ids']) ) ? $_POST['post_ids'] : array();
			if (!empty($post_ids) && is_array($post_ids)) {
				foreach ($this->bulk_edit_fields as $sets) {
					foreach ($sets as $fieldname => $fieldlabel) {
						if ((false != $value = filter_input(INPUT_POST, $fieldname))) {
							foreach ($post_ids as $post_id) {
								update_post_meta($post_id, $fieldname, $value);
							}
						}
					}
				}
			}
			wp_send_json(true);
			exit;
		}
		wp_send_json(false);
	}

	/**
	 *
	 * @param string $date
	 * @param string $cat
	 * @param boolean $display
	 * @return boolean
	 */
	public function display_caldate($date, $cat = '', $display = false, $colored=true, $thumbnail='', $title='',$tax_name='' ,$tax_term='') {
		$events = $this->get_events(array('nb' => -1, 'date' => $date, 'cat' => $cat, 'retreive' => true,'tax_name' => $tax_name ,'tax_term' => $tax_term));
		$nb = count($events);
		$ret = "";
		if(!$display && !$nb){
			$ret = date('j', $date);
		}
		if($nb){
			if ($display || $title) {
				$ret='<ul>';
				foreach ($events as $event) {
					if ($this->settings['emptylink'] == 0 && empty($event->post_content)) {
						$event->guid = '#';
					}
					$ret.='<li>'
						. '<a href="' . $event->permalink . '" title="'.esc_attr(sprintf(__('View event: %s', 'event-post'), $event->post_title)).'">'
						. '<h4>' . $event->post_title . '</h4>'
						.$this->get_single($event)
						. (!empty($thumbnail) ? '<span class="event_thumbnail_wrap">' . get_the_post_thumbnail($event->ID, $thumbnail) . '</span>' : '')
						.'</a>'
						. '</li>';
				}
				$ret.='</ul>';
			}
			if ($display) {
				// return $ret;
			}
			elseif($title) {
				$ret =  '<span '.($colored?' style="color:#'.$events[0]->color.'"':'').'>'.date('j', $date).'</span>'.$ret;
			}
			else {
				$ret = '<button data-event="'. $tax_term .'" data-date="' . date('Y-m-d', $date).'"'
					.' class="'.apply_filters( 'event_post_class_calendar_link', 'eventpost_cal_link' ).'"'.($colored?' style="background-color:#'.$events[0]->color.'"':'')
					.' title="'.esc_attr(sprintf(_n('View %1$d event at date %2$s', 'View %1$d events at date %2$s', $nb, 'event-post'), $nb, $this->human_date($date, $this->settings['dateformat']))).'"'
					.'>'
					. date('j', $date)
					. '</button>';
			}
		}

		return apply_filters('eventpost_display_caldate', $ret, $events, $date, $cat ,$display ,$colored ,$thumbnail ,$title ,$tax_name ,$tax_term );
	}


	/**
	 * @param array $atts
	 * @filter eventpost_params
	 * @return string
	 */
	public function calendar($atts) {
		extract(shortcode_atts(apply_filters('eventpost_params', array(
			'date' => date('Y-n'),
			'cat' => '',
			'mondayfirst' => 0, //1 : weeks starts on monday
			'datepicker' => 1,
			'colored' => 1,
			'display_title'=>0,
			'tax_name' => '',
			'tax_term' => '',
			'thumbnail'=>'',
			), 'calendar'), $atts));

		if($date && !preg_match('#[0-9][0-9][0-9][0-9]-[0-9][0-9]?#i', $date)){
			$date = date('Y-n', strtotime($date));
		}
		if(!$date){
			$date = date('Y-n');
		}

		$annee = substr($date, 0, 4);
		$mois = substr($date, 5);

		$time = mktime(0, 0, 0, $mois, 1, $annee);

		$prev_year = strtotime('-1 Year', $time);
		$next_year = strtotime('+1 Year', $time);
		$prev_month = strtotime('-1 Month', $time);
		$next_month = strtotime('+1 Month', $time);

		$JourMax = date("t", $time);
		$NoJour = -date("w", $time);
		if ($mondayfirst == 0) {
			$NoJour +=1;
		} else {
			$NoJour +=2;
			$this->Week[] = array_shift($this->Week);
		}
		if ($NoJour > 0 && $mondayfirst == 1) {
			$NoJour -=7;
		}
		$ret = '<table class="event-post-calendar-table">'
				. '<caption class="screen-reader-text">'
				. __('A calendar of events', 'event-post')
				. '</caption>';
		$ret.='<thead><tr><th colspan="7">';
		if ($datepicker == 1) {
			$ret.='<div class="eventpost-calendar-header">';
			$ret.='<span class="eventpost-cal-year"><button data-date="' . date('Y-n', $prev_year) . '" tabindex="0" title="'.sprintf(__('Switch to %s', 'event-post'), date('Y', $prev_year)).'" class="eventpost_cal_bt eventpost-cal-bt-prev">&laquo;</button><span class="eventpost-cal-header-text">';
			$ret.=$annee;
			$ret.='</span><button data-date="' . date('Y-n', $next_year) . '" title="'.sprintf(__('Switch to %s', 'event-post'), date('Y', $next_year)).'" class="eventpost_cal_bt eventpost-cal-bt-next">&raquo;</button></span>';
			$ret.='<span class="eventpost-cal-month"><button data-date="' . date('Y-n', $prev_month) . '" title="'.sprintf(__('Switch to %s', 'event-post'), date_i18n('F Y', $prev_month)).'" class="eventpost_cal_bt eventpost-cal-bt-prev">&laquo;</button><span class="eventpost-cal-header-text">';
			$ret.=$this->NomDuMois[abs($mois)];
			$ret.='</span><button data-date="' . date('Y-n', $next_month) . '" title="'.sprintf(__('Switch to %s', 'event-post'), date_i18n('F Y', $next_month)).'" class="eventpost_cal_bt eventpost-cal-bt-next">&raquo;</button> </span>';
			$ret.='<span class="eventpost-cal-today"><button data-date="' . date('Y-n') . '" class="eventpost_cal_bt">' . __('Today', 'event-post') . '</button></span>';
			$ret.='</div>';
		}
		$ret.='</th></tr><tr class="event_post_cal_days">';
		for ($w = 0; $w < 7; $w++) {
			$ret.='<th scope="col">' . strtoupper(substr($this->Week[$w], 0, 1)) . '</th>';
		}
		$ret.='</tr>';
		$ret.='</thead>';

		$ret.='<tbody>';
		$sqldate = date('Y-m', $time);
		$cejour = date('Y-m-d');
		for ($semaine = 0; $semaine <= 5; $semaine++) {   // 6 semaines par mois
			$tr_row_content ='';
			for ($journee = 0; $journee <= 6; $journee++) { // 7 jours par semaine
				if ($NoJour > 0 && $NoJour <= $JourMax) { // si le jour est valide a afficher
					$td = '<td class="event_post_day">';
					if ($sqldate . '-' . ($NoJour<10?'0':'').$NoJour == $cejour) {
						$td = '<td class="event_post_day_now">';
					}
					if ($sqldate . '-' . ($NoJour<10?'0':'').$NoJour < $cejour){
						$td = '<td class="event_post_day_over">'; // Patch ahf
					}
					$tr_row_content.=$td;
					$tr_row_content.= $this->display_caldate(mktime(0, 0, 0, $mois, $NoJour, $annee), $cat, false, $colored, $thumbnail, $display_title ,$tax_name ,$tax_term);
					$tr_row_content.='</td>';
				} else {
					$tr_row_content.='<td></td>';
				}
				$NoJour ++;
			}
			if($tr_row_content){
				$ret.='<tr>'.$tr_row_content.'</tr>';
			}

		}
		$ret.='</tbody></table>';
		return $ret;
	}

	public function ajaxlist(){
		echo $this->list_events(array(
			'nb' => esc_attr(FILTER_INPUT(INPUT_POST, 'nb')),
			'future' => esc_attr(FILTER_INPUT(INPUT_POST, 'future')),
			'past' => esc_attr(FILTER_INPUT(INPUT_POST, 'past')),
			'geo' => esc_attr(FILTER_INPUT(INPUT_POST, 'geo')),
			'width' => esc_attr(FILTER_INPUT(INPUT_POST, 'width')),
			'height' => esc_attr(FILTER_INPUT(INPUT_POST, 'height')),
			'zoom' => esc_attr(FILTER_INPUT(INPUT_POST, 'zoom')),
			'tile' => esc_attr(FILTER_INPUT(INPUT_POST, 'tile')),
			'title' => esc_attr(FILTER_INPUT(INPUT_POST, 'title')),
			'before_title' => esc_attr(FILTER_INPUT(INPUT_POST, 'before_title')),
			'after_title' => esc_attr(FILTER_INPUT(INPUT_POST, 'after_title')),
			'cat' => esc_attr(FILTER_INPUT(INPUT_POST, 'cat')),
			'tag' => esc_attr(FILTER_INPUT(INPUT_POST, 'tag')),
			'events' => esc_attr(FILTER_INPUT(INPUT_POST, 'events')),
			'style' => esc_attr(FILTER_INPUT(INPUT_POST, 'style')),
			'thumbnail' => esc_attr(FILTER_INPUT(INPUT_POST, 'thumbnail')),
			'thumbnail_size' => esc_attr(FILTER_INPUT(INPUT_POST, 'thumbnail_size')),
			'excerpt' => esc_attr(FILTER_INPUT(INPUT_POST, 'excerpt')),
			'orderby' => esc_attr(FILTER_INPUT(INPUT_POST, 'orderby')),
			'order' => esc_attr(FILTER_INPUT(INPUT_POST, 'order')),
			'class' => esc_attr(FILTER_INPUT(INPUT_POST, 'class')),
			'pages' => esc_attr(FILTER_INPUT(INPUT_POST, 'pages')),
		), esc_attr(FILTER_INPUT(INPUT_POST, 'list_type')));
		exit;
	}

	/**
	 * echoes the content of the calendar in ajax context
	 */
	public function ajaxcal() {
		$method = isset($_GET['action']) ? INPUT_GET : INPUT_POST;
		echo $this->calendar(array(
			'date' => esc_attr(FILTER_INPUT($method, 'date')),
			'cat' => esc_attr(FILTER_INPUT($method, 'cat')),
			'mondayfirst' => esc_attr(FILTER_INPUT($method, 'mf')),
			'datepicker' => esc_attr(FILTER_INPUT($method, 'dp')),
			'colored' => esc_attr(FILTER_INPUT($method, 'color')),
			'display_title' => esc_attr(FILTER_INPUT($method, 'display_title')),
			'thumbnail' => esc_attr(FILTER_INPUT($method, 'thumbnail')),
			'tax_name' => esc_attr(FILTER_INPUT($method, 'tax_name')),
			'tax_term' => esc_attr(FILTER_INPUT($method, 'tax_term')),
		));
		exit();
	}

	/**
	 * echoes the date of the calendar in ajax context
	 */
	public function ajaxdate() {
		echo $this->display_caldate(
			strtotime(esc_attr(FILTER_INPUT(INPUT_GET, 'date'))),
			esc_attr(FILTER_INPUT(INPUT_GET, 'cat')),
			true,
			esc_attr(FILTER_INPUT(INPUT_GET, 'color')),
			esc_attr(FILTER_INPUT(INPUT_GET, 'thumbnail')),
			esc_attr(FILTER_INPUT(INPUT_GET, 'display_title')),
			esc_attr(FILTER_INPUT(INPUT_GET, 'tax_name')),
			esc_attr(FILTER_INPUT(INPUT_GET, 'tax_term'))
		);
		exit();
	}

	/**
	 * echoes a date in ajax context
	 */
	public function HumanDate() {
		if (isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
			$date = strtotime($_REQUEST['date']);
			echo $this->human_date($date, $this->settings['dateformat']).(date('H:i', $date)=='00:00' ? '' : ' '. date($this->settings['timeformat'], $date));
			exit();
		}
	}

	/**
	 * Displays a search form
	 *
	 * @param type $atts
	 * @return type
	 */
	public function search($atts) {
		$params = shortcode_atts(apply_filters('eventpost_params', array(
			'dates' => true,
			'q' => true,
			'tax' => false,
						), 'search'), $atts);
		$this->list_id++;

		$list_id = $this->list_id;
		$q = (false !== $q = filter_input(INPUT_GET, 'q')) ? $q : '';
		$from = (false !== $from = filter_input(INPUT_GET, 'from')) ? $from : '';
		$to = (false !== $to = filter_input(INPUT_GET, 'to')) ? $to : '';
		$tax = (false !== $tax = filter_input(INPUT_GET, 'tax')) ? $tax : '';

		$cleaned_from = $this->date_cleanup($from);
		$cleaned_to = $this->date_cleanup($to);
		if(empty($cleaned_from)){
			$from=false;
		}
		if(empty($cleaned_to)){
			$to=false;
		}

		// Search form
		$this->admin_scripts(null, true);
		wp_enqueue_style('jquery-ui', plugins_url('/css/jquery-ui.css', __FILE__), false,  filemtime( "/$block_js" ));
		include (plugin_dir_path(__FILE__) . 'views/search-form.php');

		// Results
		if ($list_id == filter_input(INPUT_GET, 'evenpost_search')) {
			$arg = array(
				'post_type' => $this->settings['posttypes'],
				'meta_key' => $this->META_START,
				'orderby' => 'meta_value',
				'order' => 'ASC',
				's' => $q
			);
			if ($tax) {
				$arg['cat'] = $tax;
			}

			if ($from || $to) {

				$arg['meta_query'] = array();
				if ($from) {
					$arg['meta_query'][] = array(
						'key' => $this->META_START,
						'value' => $from,
						'compare' => '>=',
						'type' => 'DATETIME'
					);
				}
				if ($to) {
					$arg['meta_query'][] = $meta_query = array(
						array(
							'key' => $this->META_END,
							'value' => $to,
							'compare' => '<=',
							'type' => 'DATETIME'
						),
					);
				}
			}
			$events = new WP_Query($arg);
			include (plugin_dir_path(__FILE__) . 'views/search-results.php');
			wp_reset_query();
		}
	}

	/**
	 * AJAX Get lat long from address
	 */
	public function GetLatLong() {
		if (isset($_REQUEST['q']) && !empty($_REQUEST['q'])) {
			// verifier le cache
			$q = $_REQUEST['q'];
			header('Content-Type: application/json');
			$transient_name = 'eventpost_osquery_' . $q;
			$val = get_transient($transient_name);
			if (false === $val || empty($val) || !is_string($val)) {
				$language = get_bloginfo('language');
				if (strpos($language, '-') > -1) {
					$language = strtolower(substr($language, 0, 2));
				}
				$remote_val = wp_safe_remote_request('http://nominatim.openstreetmap.org/search?q=' . urlencode($q) . '&format=json&accept-language=' . $language);
				if(json_decode($remote_val['body'])){
				  $val = $remote_val['body'];
				}
				set_transient($transient_name, $val, 30 * DAY_IN_SECONDS);
			}
			echo $val;
			exit();
		}
	}

	/**
	 * alters columns
	 * @param array $defaults
	 * @return array
	 * @filter eventpost_columns_head
	 */
	public function columns_head($defaults) {
		$defaults['event'] = __('Event', 'event-post');
		$defaults['location'] = __('Location', 'event-post');
		return apply_filters('eventpost_columns_head', $defaults);
	}

	/**
	 * echoes content of a row in a given column
	 * @param string $column_name
	 * @param int $post_id
	 * @action eventpost_columns_content
	 */
	public function columns_content($column_name, $post_id) {
		if ($column_name == 'location') {
			$lat = get_post_meta($post_id, $this->META_LAT, true);
			$lon = get_post_meta($post_id, $this->META_LONG, true);

			if (!empty($lat) && !empty($lon)) {
				add_thickbox();
				$color = get_post_meta($post_id, $this->META_COLOR, true);
				if ($color == ''){
					$color = '777777';
		}
				echo	'<a href="https://www.openstreetmap.org/export/embed.html?bbox='.($lon-0.005).'%2C'.($lat-0.005).'%2C'.($lon+0.005).'%2C'.($lat+0.005).'&TB_iframe=true&width=600&height=550" class="thickbox" target="_blank">'
						. '<i class="dashicons dashicons-location" style="color:#'.$color.';"></i>'
						. '<span class="screen-reader-text">'.__('View on a map', 'event-post').'</span>'
						. get_post_meta($post_id, $this->META_ADD, true)
						. '</a> ';
			}
			$this->column_edit_hidden_fields($post_id, 'location');
		}
		if ($column_name == 'event') {
			echo $this->print_date($post_id, false);
			$this->column_edit_hidden_fields($post_id, 'event');
		}
		do_action('eventpost_columns_content', $column_name, $post_id);
	}

	function column_edit_hidden_fields($post_id, $set){
		$event = $this->retreive($post_id);
		echo '<div class="hidden">';
		foreach($this->quick_edit_fields[$set] as $fieldname=>$fieldlabel){
			echo'<span class="inline-edit-value '.$fieldname.'">'.esc_attr($event->$fieldname).'</span>';
		}
		echo '</div>';
	}

	/** ADMIN PAGES **/

	/**
	 * adds items to the native "right now" dashboard widget
	 * @param array $elements
	 * @return array
	 */
	public function dashboard_right_now($elements){
		$nb_date = count($this->get_events(array('future'=>1, 'past'=>1, 'nb'=>-1)));
		$nb_geo = count($this->get_events(array('future'=>1, 'past'=>1, 'geo'=>1, 'nb'=>-1)));
		if($nb_date){
			array_push($elements, '<i class="dashicons dashicons-calendar"></i> <i href="edit.php?post_type=post">'.sprintf(__('%d Events','event-post'), $nb_date)."</i>");
		}
		if($nb_geo){
			array_push($elements, '<i class="dashicons dashicons-location"></i> <i href="edit.php?post_type=post">'.sprintf(__('%d Geolocalized events','event-post'), $nb_geo)."</i>");
		}
		return $elements;
	}

	/*
	 * feed
	 * generate ICS or VCS files from a category
	 */

	/**
	 *
	 * @param timestamp $timestamp
	 * @return string
	 */
	public function ics_date($timestamp){
	return date("Ymd",$timestamp).'T'.date("His",$timestamp);
	}

	public function get_gmt_offset(){
		$gmt_offset = get_option('gmt_offset ');
		$codegmt = 0;
		if ($gmt_offset != 0 && substr($gmt_offset, 0, 1) != '-' && substr($gmt_offset, 0, 1) != '+') {
			$codegmt = $gmt_offset * -1;
			$gmt_offset = '+' . $gmt_offset;
		}
		if(abs($gmt_offset < 10)){
			$gmt_offset = substr($gmt_offset, 0, 1).'0'.substr($gmt_offset, 1);
		}
		return $gmt_offset;
	}

	private function generate_ics($event_id, $format){
		$export_file = plugin_dir_path(__FILE__).'inc/export/'.$format.'.php';
		if(is_numeric($event_id) && file_exists($export_file)){
			$event = $this->retreive($event_id);
			include $export_file;
			exit;
		}
	}

	public function parse_request(){
		global $wp;
		if (preg_match('#^event-feed#i', $wp->request, $match)) {
			$this->feed();
			exit;
		}
		if (preg_match('#^eventpost/([0-9]*)\.(ics|vcs)#i', $wp->request, $match)) {
			$this->generate_ics($match[1], $match[2]);
		}
	}

	public function export(){
		if(false !== $event_id=\filter_input(INPUT_GET, 'event_id',FILTER_SANITIZE_NUMBER_INT)){
			$format = \filter_input(INPUT_GET, 'format',FILTER_SANITIZE_STRING);
			$this->generate_ics($event_id, $format);
		}
	}


	/**
	 * outputs an ICS document
	 */
	public function feed(){
		if(false !== $cat=\filter_input(INPUT_GET, 'cat',FILTER_SANITIZE_STRING)){
			$vtz = get_option('timezone_string');
				$gmt = $this->get_gmt_offset();
			date_default_timezone_set($vtz);
				$separator = "\n";

			header("content-type:text/calendar");
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Disposition: attachment; filename=". str_replace('+','-',urlencode(get_option('blogname').'-'.$cat)).".ics;" );

				$props = array();

				// General
				$props[] =  'BEGIN:VCALENDAR';
				$props[] =  'PRODID://WordPress//Event-Post-V'. file_get_contents(('VERSION')).'//EN';
				$props[] =  'VERSION:2.0';

				// Timezone
				if(!empty($vtz)){
					array_push($props,
						'BEGIN:VTIMEZONE',
						'TZID:'.$vtz,
						'BEGIN:DAYLIGHT',
						'TZOFFSETFROM:+0100',
						'TZOFFSETTO:'.($gmt).'00',
						'DTSTART:19700329T020000',
						'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3',
						'END:DAYLIGHT',
						'BEGIN:STANDARD',
						'TZOFFSETFROM:'.($gmt).'00',
						'TZOFFSETTO:+0100',
						'TZNAME:CET',
						'DTSTART:19701025T030000',
						'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10',
						'END:STANDARD',
						'END:VTIMEZONE'
					);
				}

				// Events
				$events=$this->get_events(array('cat'=>$cat,'nb'=>-1));
			foreach ($events as $event) {
				if($event->time_start && $event->time_end){
					array_push($props,
						'BEGIN:VEVENT',
						'CREATED:'.$this->ics_date(strtotime($event->post_date)).'Z',
						'LAST-MODIFIED:'.$this->ics_date(strtotime($event->post_modified)).'Z',
						'SUMMARY:'.$event->post_title,
						'UID:'.md5(site_url()."_eventpost_".$event->ID),
						'LOCATION:'.str_replace(',','\,',$event->address),
						'DTSTAMP:'.$this->ics_date($event->time_start).(!empty($vtz)?'':'Z'),
						'DTSTART'.(!empty($vtz)?';TZID='.$vtz:'').':'.$this->ics_date($event->time_start).(!empty($vtz)?'':'Z'),
						'DTEND'.(!empty($vtz)?';TZID='.$vtz:'').':'.$this->ics_date($event->time_end).(!empty($vtz)?'':'Z'),
						'DESCRIPTION:'.trim(chunk_split($event->post_excerpt."\\n\\n".$event->permalink, 60, "\n")),
						'END:VEVENT'
					);
				}
			}

			// End
			$props[] =  'END:VCALENDAR';

			echo implode($separator, $props);
			exit;
		}
	}
}

<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Heyou
 * @subpackage Heyou/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Heyou
 * @subpackage Heyou/public
 * @author     2DIGIT d.o.o. <florjan@2digit.eu>
 */
class Heyou_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $twodigit_license_options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('wp_footer', [$this, 'video_bubble_display']);

		add_action('wp_ajax_digit_bubble_offset', [$this, 'digit_bubble_offset']);
		add_action('wp_ajax_nopriv_digit_bubble_offset', [$this, 'digit_bubble_offset']);

		add_action('wp_ajax_heyou_update_analytics', [$this, 'heyou_update_analytics']);
		add_action('wp_ajax_nopriv_heyou_update_analytics', [$this, 'heyou_update_analytics']);

		add_action( 'woocommerce_checkout_order_processed', array($this, 'heyou_calculate_revenue'), 10, 3 );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Heyou_Pro_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Heyou_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */


		if(!(is_cart()) && !(is_checkout())) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/video-bubble-public.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Heyou_Pro_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Heyou_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if(!(is_cart()) && !(is_checkout())) {
			global $post;
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/video-bubble-public.js', array( 'jquery' ), $this->version, true );

			if(isset($post->ID) || isset(get_queried_object()->term_id)) {
				if(is_product()) {
					$product = wc_get_product( $post->ID );
	
					wp_localize_script($this->plugin_name, 'custom_vars', array(
						'post_ID' => $post->ID,
						'post_title' => sanitize_text_field(get_the_title($post->ID)),
						'tmp' => sanitize_text_field($product->get_price())
						)
					);
				} else {
					wp_localize_script($this->plugin_name, 'custom_vars', array(
						'post_ID' => $post->ID,
						'post_title' => sanitize_text_field(get_the_title($post->ID))
						)
					);
				}
			}
			
		}
	}

	function digit_bubble_offset() {
	  
		$timer = $this->value('twodigit_video-bubble-timer', sanitize_key($_POST['post_id']))*1000;

		if(!($timer) || $timer <= 0) $timer = 3000;

		echo json_encode(array('timer' => $timer));
	  
		die();
	}

	function heyou_update_analytics() {
		$type = sanitize_key($_POST['heyou_analytics_type']);

		$post_id = sanitize_key($_POST['post_id']);

		if(isset($post_id)) {
			if($type == 'video_loaded'):
				$statsLoaded = get_option('heyou_video_loaded', 0)+1;
				update_option( 'heyou_video_loaded', $statsLoaded );
				$this->heyou_insert_analytic($type, 1, $post_id);
			elseif($type == 'video_clicked'):
				$statsLoaded = get_option('heyou_video_clicked', 0)+1;
				update_option( 'heyou_video_clicked', $statsLoaded );
				$this->heyou_insert_analytic($type, 1, $post_id);
			elseif($type == 'video_addtocart_clicked'):
				$statsLoaded = get_option('heyou_addedcart_clicked', 0)+1;
				update_option( 'heyou_addedcart_clicked', $statsLoaded );
				$this->heyou_insert_analytic($type, 1, $post_id);
			endif;
		}

		die();
	}

	public function heyou_insert_analytic($key, $value, $post_id) {
		global $wpdb;

		//$wpdb->show_errors();

		return $wpdb->insert($wpdb->prefix."heyou_stats", array(
			"id" => 'NULL',
			"key" => $key,
			"value" => $value,
			"date" => date('Y-m-d H:i:s'),
			'post_ids' => $post_id
		));
	}

	public function video_bubble_display() {
		wp_reset_postdata();
		global $post;

		$this->twodigit_options = get_option( 'twodigit_option_name' );
		if( isset( $this->twodigit_options['global_video_1'] ) && sanitize_key($this->twodigit_options['global_video_1']) === 'global_video_1' ) {
			$globalVid = isset( $this->twodigit_options['global_file'] ) ? esc_attr( $this->twodigit_options['global_file']) : null;
			$globalEnabled = true;
		}

		if($this->value('twodigit_video-bubble-file', get_the_ID()) && is_product()) {
			$vid = wp_get_attachment_url($this->value('twodigit_video-bubble-file', get_the_ID()));
		?> 
		<div class="digit-video-bubble" id="video-bubble-minimized">
			<div class="video-bubble-wrap">
				<video
					id="my-video"
					class="video-js"
					muted 
					autoplay 
					loop 
					playsinline 
					preload="auto"
					width="640"
					height="264"
					data-setup="{}"
				>
					<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
					<p class="vjs-no-js">
					<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
					</p>
				</video>

				<div class="video-bubble-content">
					<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
				</div>
			</div>
		</div>
		<div class="digit-video-bubble" id="video-bubble-full">
			<div class="video-bubble-wrap">
				<video
					id="my-video-full"
					class="video-js"
					muted 
					autoplay 
					loop 
					playsinline 
					preload="auto"
					width="640"
					height="264"
					data-setup="{}"
				>
					<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
					<p class="vjs-no-js">
					<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
					</p>
				</video>

				<div class="video-bubble-close">
					<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
				</div>

				<div class="video-bubble-play">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 58 58" style="enable-background:new 0 0 58 58;" xml:space="preserve">
					<circle style="fill: black;" cx="29" cy="29" r="29"/>
					<g>
						<polygon style="fill:#FFFFFF;" points="44,29 22,44 22,29.273 22,14  "/>
						<path style="fill:#FFFFFF;" d="M22,45c-0.16,0-0.321-0.038-0.467-0.116C21.205,44.711,21,44.371,21,44V14   c0-0.371,0.205-0.711,0.533-0.884c0.328-0.174,0.724-0.15,1.031,0.058l22,15C44.836,28.36,45,28.669,45,29s-0.164,0.64-0.437,0.826   l-22,15C22.394,44.941,22.197,45,22,45z M23,15.893v26.215L42.225,29L23,15.893z"/>
					</g>
					</svg>
				</div>

				<div class="video-bubble-sound">
					<svg class="sound-on" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Layer_1" style="enable-background:new 0 0 128 128;" version="1.1" viewBox="0 0 128 128" xml:space="preserve"><style type="text/css">
					.st0{fill:#FFFFFF;}
					</style><g><circle cx="64" cy="64" r="64"/></g><path class="st0" d="M60.5,38L38,52H28c-2.2,0-4,1.8-4,4v16c0,2.2,1.8,4,4,4h10l22.5,14c0.6,0.5,1.5,0,1.5-0.8V38.8  C62,38.1,61.2,37.6,60.5,38z"/><g><path class="st0" d="M87.7,96.8l-1.3-1.5c-0.4-0.4-0.3-1,0.1-1.4C95.1,86.3,100,75.5,100,64c0-11.8-5.1-22.9-14.1-30.5   c-0.2-0.1-0.5-0.4-0.9-0.7s-0.5-0.9-0.2-1.3l1.1-1.7c0.3-0.5,1-0.6,1.4-0.2c0.5,0.4,1,0.8,1,0.8C98.3,38.8,104,51,104,64   c0,12.6-5.4,24.6-14.8,33C88.7,97.3,88.1,97.3,87.7,96.8z"/></g><g><path class="st0" d="M79.1,88.3l-1.2-1.6c-0.3-0.4-0.3-1,0.2-1.4C84.4,80,88,72.3,88,64s-3.6-16-10-21.3c-0.4-0.3-0.5-1-0.2-1.4   l1.2-1.6c0.3-0.4,1-0.5,1.4-0.2C87.8,45.7,92,54.5,92,64s-4.2,18.3-11.5,24.4C80.1,88.8,79.5,88.7,79.1,88.3z"/></g><g><path class="st0" d="M69.6,78.2c-0.4-0.4-0.3-1.1,0.1-1.4c4-3.1,6.3-7.8,6.3-12.7c0-5.1-2.3-9.8-6.3-12.7c-0.4-0.3-0.5-1-0.2-1.4   l1.3-1.5c0.3-0.4,0.9-0.5,1.4-0.2C77.1,51.8,80,57.7,80,64c0,6.1-2.9,11.9-7.7,15.8c-0.4,0.3-1,0.3-1.4-0.1L69.6,78.2z"/></g></svg>
				
					<svg class="sound-off" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
						viewBox="0 0 2000 2000" enable-background="new 0 0 2000 2000" xml:space="preserve">
					<g>
						<circle cx="1000" cy="1000" r="1000"/>
					</g>
					<path fill="#FFFFFF" d="M945.3,593.8L593.8,812.5H437.5c-34.4,0-62.5,28.1-62.5,62.5v250c0,34.4,28.1,62.5,62.5,62.5h156.3
						l351.6,218.8c9.4,7.8,23.4,0,23.4-12.5V606.3C968.8,595.3,956.3,587.5,945.3,593.8z"/>
					<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1064.4,1176.3l10.4,10.4c4.6,4.6,12.2,4.6,16.8,0l346.5-346.5
						c4.6-4.6,4.6-12.2,0-16.8l-10.4-10.4c-4.6-4.6-12.2-4.6-16.8,0l-346.5,346.5C1059.8,1164.1,1059.8,1171.7,1064.4,1176.3z"/>
					<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1412.4,1192.5l16.5-16.5c4.7-4.7,4.7-12.3,0-17l-339.7-339.7
						c-4.7-4.7-12.3-4.7-17,0l-16.5,16.5c-4.7,4.7-4.7,12.3,0,17l339.7,339.7C1400.1,1197.2,1407.7,1197.2,1412.4,1192.5z"/>
					</svg>
				</div>

				<div class="video-bubble-return">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 496.166 496.166" style="enable-background:new 0 0 496.166 496.166;" xml:space="preserve">
					<path style="fill: #000000;" d="M0.005,248.087C0.005,111.063,111.073,0,248.079,0c137.014,0,248.082,111.062,248.082,248.087  c0,137.002-111.068,248.079-248.082,248.079C111.073,496.166,0.005,385.089,0.005,248.087z"/>
					<path style="fill:#F7F7F7;" d="M400.813,169.581c-2.502-4.865-14.695-16.012-35.262-5.891  c-20.564,10.122-10.625,32.351-10.625,32.351c7.666,15.722,11.98,33.371,11.98,52.046c0,65.622-53.201,118.824-118.828,118.824  c-65.619,0-118.82-53.202-118.82-118.824c0-61.422,46.6-111.946,106.357-118.173v30.793c0,0-0.084,1.836,1.828,2.999  c1.906,1.163,3.818,0,3.818,0l98.576-58.083c0,0,2.211-1.162,2.211-3.436c0-1.873-2.211-3.205-2.211-3.205l-98.248-57.754  c0,0-2.24-1.605-4.23-0.826c-1.988,0.773-1.744,3.481-1.744,3.481v32.993c-88.998,6.392-159.23,80.563-159.23,171.21  c0,94.824,76.873,171.696,171.693,171.696c94.828,0,171.707-76.872,171.707-171.696  C419.786,219.788,412.933,193.106,400.813,169.581z"/>
					</svg>
				</div>

				<div class="video-bubble-cta">
					<!--<h5><?php //the_title(); ?></h5>-->
					<?php do_action('woocommerce_simple_add_to_cart'); ?>
				</div>

				<progress id="bubble-progress" max="100" value="0"><?php esc_html_e('Progress', 'heyou'); ?></progress>
			</div>
		</div>
		<?php
		} elseif($this->value('twodigit_video-bubble-file', get_the_ID())) {
			$vid = wp_get_attachment_url($this->value('twodigit_video-bubble-file', get_the_ID()));
			?> 
			<div class="digit-video-bubble" id="video-bubble-minimized">
				<div class="video-bubble-wrap">
					<video
						id="my-video"
						class="video-js"
						muted 
						autoplay 
						loop 
						playsinline 
						preload="auto"
						width="640"
						height="264"
						data-setup="{}"
					>
						<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
						<p class="vjs-no-js">
						<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
						</p>
					</video>
	
					<div class="video-bubble-content">
						<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
					</div>
				</div>
			</div>
			<div class="digit-video-bubble" id="video-bubble-full">
				<div class="video-bubble-wrap">
					<video
						id="my-video-full"
						class="video-js"
						muted 
						autoplay 
						loop 
						playsinline 
						preload="auto"
						width="640"
						height="264"
						data-setup="{}"
					>
						<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
						<p class="vjs-no-js">
						<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
						</p>
					</video>
	
					<div class="video-bubble-close">
						<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
					</div>
	
					<div class="video-bubble-play">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 58 58" style="enable-background:new 0 0 58 58;" xml:space="preserve">
						<circle style="fill: black;" cx="29" cy="29" r="29"/>
						<g>
							<polygon style="fill:#FFFFFF;" points="44,29 22,44 22,29.273 22,14  "/>
							<path style="fill:#FFFFFF;" d="M22,45c-0.16,0-0.321-0.038-0.467-0.116C21.205,44.711,21,44.371,21,44V14   c0-0.371,0.205-0.711,0.533-0.884c0.328-0.174,0.724-0.15,1.031,0.058l22,15C44.836,28.36,45,28.669,45,29s-0.164,0.64-0.437,0.826   l-22,15C22.394,44.941,22.197,45,22,45z M23,15.893v26.215L42.225,29L23,15.893z"/>
						</g>
						</svg>
					</div>
	
					<div class="video-bubble-sound">
						<svg class="sound-on" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Layer_1" style="enable-background:new 0 0 128 128;" version="1.1" viewBox="0 0 128 128" xml:space="preserve"><style type="text/css">
						.st0{fill:#FFFFFF;}
						</style><g><circle cx="64" cy="64" r="64"/></g><path class="st0" d="M60.5,38L38,52H28c-2.2,0-4,1.8-4,4v16c0,2.2,1.8,4,4,4h10l22.5,14c0.6,0.5,1.5,0,1.5-0.8V38.8  C62,38.1,61.2,37.6,60.5,38z"/><g><path class="st0" d="M87.7,96.8l-1.3-1.5c-0.4-0.4-0.3-1,0.1-1.4C95.1,86.3,100,75.5,100,64c0-11.8-5.1-22.9-14.1-30.5   c-0.2-0.1-0.5-0.4-0.9-0.7s-0.5-0.9-0.2-1.3l1.1-1.7c0.3-0.5,1-0.6,1.4-0.2c0.5,0.4,1,0.8,1,0.8C98.3,38.8,104,51,104,64   c0,12.6-5.4,24.6-14.8,33C88.7,97.3,88.1,97.3,87.7,96.8z"/></g><g><path class="st0" d="M79.1,88.3l-1.2-1.6c-0.3-0.4-0.3-1,0.2-1.4C84.4,80,88,72.3,88,64s-3.6-16-10-21.3c-0.4-0.3-0.5-1-0.2-1.4   l1.2-1.6c0.3-0.4,1-0.5,1.4-0.2C87.8,45.7,92,54.5,92,64s-4.2,18.3-11.5,24.4C80.1,88.8,79.5,88.7,79.1,88.3z"/></g><g><path class="st0" d="M69.6,78.2c-0.4-0.4-0.3-1.1,0.1-1.4c4-3.1,6.3-7.8,6.3-12.7c0-5.1-2.3-9.8-6.3-12.7c-0.4-0.3-0.5-1-0.2-1.4   l1.3-1.5c0.3-0.4,0.9-0.5,1.4-0.2C77.1,51.8,80,57.7,80,64c0,6.1-2.9,11.9-7.7,15.8c-0.4,0.3-1,0.3-1.4-0.1L69.6,78.2z"/></g></svg>
					
						<svg class="sound-off" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							viewBox="0 0 2000 2000" enable-background="new 0 0 2000 2000" xml:space="preserve">
						<g>
							<circle cx="1000" cy="1000" r="1000"/>
						</g>
						<path fill="#FFFFFF" d="M945.3,593.8L593.8,812.5H437.5c-34.4,0-62.5,28.1-62.5,62.5v250c0,34.4,28.1,62.5,62.5,62.5h156.3
							l351.6,218.8c9.4,7.8,23.4,0,23.4-12.5V606.3C968.8,595.3,956.3,587.5,945.3,593.8z"/>
						<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1064.4,1176.3l10.4,10.4c4.6,4.6,12.2,4.6,16.8,0l346.5-346.5
							c4.6-4.6,4.6-12.2,0-16.8l-10.4-10.4c-4.6-4.6-12.2-4.6-16.8,0l-346.5,346.5C1059.8,1164.1,1059.8,1171.7,1064.4,1176.3z"/>
						<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1412.4,1192.5l16.5-16.5c4.7-4.7,4.7-12.3,0-17l-339.7-339.7
							c-4.7-4.7-12.3-4.7-17,0l-16.5,16.5c-4.7,4.7-4.7,12.3,0,17l339.7,339.7C1400.1,1197.2,1407.7,1197.2,1412.4,1192.5z"/>
						</svg>
					</div>
	
					<div class="video-bubble-return">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 496.166 496.166" style="enable-background:new 0 0 496.166 496.166;" xml:space="preserve">
						<path style="fill: #000000;" d="M0.005,248.087C0.005,111.063,111.073,0,248.079,0c137.014,0,248.082,111.062,248.082,248.087  c0,137.002-111.068,248.079-248.082,248.079C111.073,496.166,0.005,385.089,0.005,248.087z"/>
						<path style="fill:#F7F7F7;" d="M400.813,169.581c-2.502-4.865-14.695-16.012-35.262-5.891  c-20.564,10.122-10.625,32.351-10.625,32.351c7.666,15.722,11.98,33.371,11.98,52.046c0,65.622-53.201,118.824-118.828,118.824  c-65.619,0-118.82-53.202-118.82-118.824c0-61.422,46.6-111.946,106.357-118.173v30.793c0,0-0.084,1.836,1.828,2.999  c1.906,1.163,3.818,0,3.818,0l98.576-58.083c0,0,2.211-1.162,2.211-3.436c0-1.873-2.211-3.205-2.211-3.205l-98.248-57.754  c0,0-2.24-1.605-4.23-0.826c-1.988,0.773-1.744,3.481-1.744,3.481v32.993c-88.998,6.392-159.23,80.563-159.23,171.21  c0,94.824,76.873,171.696,171.693,171.696c94.828,0,171.707-76.872,171.707-171.696  C419.786,219.788,412.933,193.106,400.813,169.581z"/>
						</svg>
					</div>
	
					<div class="video-bubble-cta">
						<?php 
						if(is_product()) { 
							//echo "<h5>".sanitize_text_field(get_the_title())."</h5>";
							echo "<form>";
							do_action('woocommerce_simple_add_to_cart');
							echo "</form>";
						} else {
							$btn_text = ($this->value('twodigit_video-bubble-text', get_the_ID())) ? $this->value('twodigit_video-bubble-text', get_the_ID()) : 'Shop';
							if(function_exists( 'wc_get_page_id' )) {
								$btn_link = ($this->value('twodigit_video-bubble-link', get_the_ID())) ? $this->value('twodigit_video-bubble-link', get_the_ID()) : esc_url(get_permalink( wc_get_page_id( 'shop' ) ));
							} else {
								$btn_link = ($this->value('twodigit_video-bubble-link', get_the_ID())) ? $this->value('twodigit_video-bubble-link', get_the_ID()) : '/';
							}
							?>
							<a href="<?php echo esc_attr($btn_link); ?>"><button class="button non-product-button"><?php esc_html_e($btn_text); ?></button></a>
							<?php
						} ?>
					</div>
	
					<progress id="bubble-progress" max="100" value="0"><?php esc_html_e('Progress', 'heyou'); ?></progress>
				</div>
			</div>
			<?php
		} elseif(isset($globalEnabled) && isset($globalVid) && !(is_cart()) && !(is_checkout())) {
			$vid = wp_get_attachment_url($globalVid);
			?> 
			<div class="digit-video-bubble" id="video-bubble-minimized">
				<div class="video-bubble-wrap">
					<video
						id="my-video"
						class="video-js"
						muted 
						autoplay 
						loop 
						playsinline 
						preload="auto"
						width="640"
						height="264"
						data-setup="{}"
					>
						<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
						<p class="vjs-no-js">
						<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
						</p>
					</video>
	
					<div class="video-bubble-content">
						<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
					</div>
				</div>
			</div>
			<div class="digit-video-bubble" id="video-bubble-full">
				<div class="video-bubble-wrap">
					<video
						id="my-video-full"
						class="video-js"
						muted 
						autoplay 
						loop 
						playsinline 
						preload="auto"
						width="640"
						height="264"
						data-setup="{}"
					>
						<source src="<?php echo esc_url($vid.'?v='.time()); ?>" type="video/mp4" />
						<p class="vjs-no-js">
						<?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video.'); ?>
						</p>
					</video>
	
					<div class="video-bubble-close">
						<svg style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="close"><g><circle cx="256" cy="256" r="253.44"/><path d="M350.019,144.066l17.521,17.522c6.047,6.047,6.047,15.852,0,21.9L183.607,367.419    c-6.047,6.048-15.852,6.047-21.9,0l-17.521-17.522c-6.047-6.047-6.047-15.852,0-21.9l183.932-183.933    C334.166,138.018,343.971,138.018,350.019,144.066z" style="fill:#FFFFFF;"/><path d="M367.54,349.899l-17.522,17.522c-6.047,6.047-15.852,6.047-21.9,0L144.186,183.488    c-6.047-6.047-6.047-15.852,0-21.9l17.522-17.522c6.047-6.047,15.852-6.047,21.9,0L367.54,327.999    C373.588,334.047,373.588,343.852,367.54,349.899z" style="fill:#FFFFFF;"/></g></g><g id="Layer_1"/></svg>
					</div>
	
					<div class="video-bubble-play">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 58 58" style="enable-background:new 0 0 58 58;" xml:space="preserve">
						<circle style="fill: black;" cx="29" cy="29" r="29"/>
						<g>
							<polygon style="fill:#FFFFFF;" points="44,29 22,44 22,29.273 22,14  "/>
							<path style="fill:#FFFFFF;" d="M22,45c-0.16,0-0.321-0.038-0.467-0.116C21.205,44.711,21,44.371,21,44V14   c0-0.371,0.205-0.711,0.533-0.884c0.328-0.174,0.724-0.15,1.031,0.058l22,15C44.836,28.36,45,28.669,45,29s-0.164,0.64-0.437,0.826   l-22,15C22.394,44.941,22.197,45,22,45z M23,15.893v26.215L42.225,29L23,15.893z"/>
						</g>
						</svg>
					</div>
	
					<div class="video-bubble-sound">
						<svg class="sound-on" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Layer_1" style="enable-background:new 0 0 128 128;" version="1.1" viewBox="0 0 128 128" xml:space="preserve"><style type="text/css">
						.st0{fill:#FFFFFF;}
						</style><g><circle cx="64" cy="64" r="64"/></g><path class="st0" d="M60.5,38L38,52H28c-2.2,0-4,1.8-4,4v16c0,2.2,1.8,4,4,4h10l22.5,14c0.6,0.5,1.5,0,1.5-0.8V38.8  C62,38.1,61.2,37.6,60.5,38z"/><g><path class="st0" d="M87.7,96.8l-1.3-1.5c-0.4-0.4-0.3-1,0.1-1.4C95.1,86.3,100,75.5,100,64c0-11.8-5.1-22.9-14.1-30.5   c-0.2-0.1-0.5-0.4-0.9-0.7s-0.5-0.9-0.2-1.3l1.1-1.7c0.3-0.5,1-0.6,1.4-0.2c0.5,0.4,1,0.8,1,0.8C98.3,38.8,104,51,104,64   c0,12.6-5.4,24.6-14.8,33C88.7,97.3,88.1,97.3,87.7,96.8z"/></g><g><path class="st0" d="M79.1,88.3l-1.2-1.6c-0.3-0.4-0.3-1,0.2-1.4C84.4,80,88,72.3,88,64s-3.6-16-10-21.3c-0.4-0.3-0.5-1-0.2-1.4   l1.2-1.6c0.3-0.4,1-0.5,1.4-0.2C87.8,45.7,92,54.5,92,64s-4.2,18.3-11.5,24.4C80.1,88.8,79.5,88.7,79.1,88.3z"/></g><g><path class="st0" d="M69.6,78.2c-0.4-0.4-0.3-1.1,0.1-1.4c4-3.1,6.3-7.8,6.3-12.7c0-5.1-2.3-9.8-6.3-12.7c-0.4-0.3-0.5-1-0.2-1.4   l1.3-1.5c0.3-0.4,0.9-0.5,1.4-0.2C77.1,51.8,80,57.7,80,64c0,6.1-2.9,11.9-7.7,15.8c-0.4,0.3-1,0.3-1.4-0.1L69.6,78.2z"/></g></svg>
					
						<svg class="sound-off" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							viewBox="0 0 2000 2000" enable-background="new 0 0 2000 2000" xml:space="preserve">
						<g>
							<circle cx="1000" cy="1000" r="1000"/>
						</g>
						<path fill="#FFFFFF" d="M945.3,593.8L593.8,812.5H437.5c-34.4,0-62.5,28.1-62.5,62.5v250c0,34.4,28.1,62.5,62.5,62.5h156.3
							l351.6,218.8c9.4,7.8,23.4,0,23.4-12.5V606.3C968.8,595.3,956.3,587.5,945.3,593.8z"/>
						<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1064.4,1176.3l10.4,10.4c4.6,4.6,12.2,4.6,16.8,0l346.5-346.5
							c4.6-4.6,4.6-12.2,0-16.8l-10.4-10.4c-4.6-4.6-12.2-4.6-16.8,0l-346.5,346.5C1059.8,1164.1,1059.8,1171.7,1064.4,1176.3z"/>
						<path fill="#D4000C" stroke="#000000" stroke-miterlimit="10" d="M1412.4,1192.5l16.5-16.5c4.7-4.7,4.7-12.3,0-17l-339.7-339.7
							c-4.7-4.7-12.3-4.7-17,0l-16.5,16.5c-4.7,4.7-4.7,12.3,0,17l339.7,339.7C1400.1,1197.2,1407.7,1197.2,1412.4,1192.5z"/>
						</svg>
					</div>
	
					<div class="video-bubble-return">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 496.166 496.166" style="enable-background:new 0 0 496.166 496.166;" xml:space="preserve">
						<path style="fill: #000000;" d="M0.005,248.087C0.005,111.063,111.073,0,248.079,0c137.014,0,248.082,111.062,248.082,248.087  c0,137.002-111.068,248.079-248.082,248.079C111.073,496.166,0.005,385.089,0.005,248.087z"/>
						<path style="fill:#F7F7F7;" d="M400.813,169.581c-2.502-4.865-14.695-16.012-35.262-5.891  c-20.564,10.122-10.625,32.351-10.625,32.351c7.666,15.722,11.98,33.371,11.98,52.046c0,65.622-53.201,118.824-118.828,118.824  c-65.619,0-118.82-53.202-118.82-118.824c0-61.422,46.6-111.946,106.357-118.173v30.793c0,0-0.084,1.836,1.828,2.999  c1.906,1.163,3.818,0,3.818,0l98.576-58.083c0,0,2.211-1.162,2.211-3.436c0-1.873-2.211-3.205-2.211-3.205l-98.248-57.754  c0,0-2.24-1.605-4.23-0.826c-1.988,0.773-1.744,3.481-1.744,3.481v32.993c-88.998,6.392-159.23,80.563-159.23,171.21  c0,94.824,76.873,171.696,171.693,171.696c94.828,0,171.707-76.872,171.707-171.696  C419.786,219.788,412.933,193.106,400.813,169.581z"/>
						</svg>
					</div>
	
					<div class="video-bubble-cta">
						<?php 
						if(is_product()) { 
							//echo "<h5>".sanitize_text_field(get_the_title())."</h5>";
							echo "<form>";
							do_action('woocommerce_simple_add_to_cart');
							echo "</form>";
						} else { 
							$btn_text = (isset($this->twodigit_options['global_video_button_text'])) ? $this->twodigit_options['global_video_button_text'] : 'Shop';
							if(get_permalink( wc_get_page_id( 'shop' ) )) {
								$btn_link = (isset($this->twodigit_options['global_video_button_link'])) ? $this->twodigit_options['global_video_button_link'] : esc_url(get_permalink( wc_get_page_id( 'shop' ) ));
							} else {
								$btn_link = (isset($this->twodigit_options['global_video_button_link'])) ? $this->twodigit_options['global_video_button_link'] : '/';
							}
							?>
							<a href="<?php echo esc_attr($btn_link); ?>"><button class="button non-product-button"><?php esc_html_e($btn_text); ?></button></a>
							<?php
						} ?>
					</div>
	
					<progress id="bubble-progress" max="100" value="0"><?php esc_html_e('Progress', 'heyou'); ?></progress>
				</div>
			</div>
			<?php
		}
	}

	private function value( $id, $post_id ) {
		global $post;
		if ( metadata_exists( 'post', $post_id, $id ) ) {
			$value = sanitize_text_field(get_post_meta( $post_id, $id, true ));
		} else {
			return '';
		}
		return str_replace( '\u0027', "'", $value );
	}

	public function heyou_calculate_revenue($order_id, $posted_data, $order) {

		if ( ! $order_id ) return;

		$order = wc_get_order( $order_id );

		// Allow code execution only once 
		if( ! get_post_meta( $order_id, '_heyou_thankyou_action_done', true ) ) {

			if(isset($_COOKIE['heyourev'])) {
				$statsRevenue = get_option('heyou_video_revenue', 0);

				if ( count( $order->get_items() ) > 0 ) {
					foreach ( $order->get_items() as $item_id => $item ) {
						$product_id = $item->get_product_id();
						$this->heyou_insert_analytic('heyou_video_revenue', $order->get_total(), $product_id);
					}
				}

				$statsRevenue += $order->get_total();
				update_option( 'heyou_video_revenue', $statsRevenue );
				/*$data = stripslashes($_COOKIE['heyourev']);
				$prods = json_decode($data, true);
				
				if ( count( $order->get_items() ) > 0 ) {
                    foreach ( $order->get_items() as $item_id => $item ) {
						$product = $item->get_product();
						$product_id = $item->get_product_id();
						$product_qty = $item->get_quantity();

                        // Add order pay to available pay
						foreach ($prods as $key => $value) {
							if($key == $product_id) {
								$generatedRevenue = $product->get_price()*$product_qty;
								$statsRevenue += $generatedRevenue;
								update_option( 'heyou_video_revenue', $statsRevenue );

								$order->update_meta_data( 'heyou_generated_revenue', $generatedRevenue );
							}
						}
                    }
                }*/
				unset($_COOKIE['heyourev']);
				unset($_COOKIE['heyouadd']);
				setcookie('heyourev', '', time() - 3600, '/'); // empty value and old timestamp
				setcookie('heyouadd', '', time() - 3600, '/');
			}
			

			// Flag the action as done (to avoid repetitions on reload for example)
			$order->update_meta_data( '_heyou_thankyou_action_done', true );
			$order->save();

		}
	}

}

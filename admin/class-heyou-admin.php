<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Heyou
 * @subpackage Heyou/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Heyou
 * @subpackage Heyou/admin
 * @author     2DIGIT d.o.o. <florjan@2digit.eu>
 */
class Heyou_Admin {

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

	/**
	 * The configuration options object.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $twodigit_options    The configuration options object.
	 */
	private $twodigit_options;

	private $config = '{"title":"Heyou - Video bubble",
		"description":"Select the video and timer in order to display the video bubble.",
		"prefix":"twodigit_",
		"domain":"heyou",
		"class_name":"Video_Bubble",
		"context":"normal",
		"priority":"default",
		"fields":[
			{"type":"media","label":"Video bubble file","id":"twodigit_video-bubble-file"},
			{"type":"number","label":"Display the bubble after (value) seconds.","default":"3","max":"60","step":"1","id":"twodigit_video-bubble-timer"},
			{"type":"text","label":"Button text","default":"Shop","id":"twodigit_video-bubble-text"},
			{"type":"text","label":"Button link","default":"/shop","id":"twodigit_video-bubble-link"}
			]
		}';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'admin_menu', array( $this, 'twodigit_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'twodigit_page_init' ) );

		// meta boxes
		$this->config = json_decode( $this->config, true );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ] );

		add_action('wp_ajax_heyou_get_type_posts', [$this, 'heyou_get_type_posts']);
		add_action('wp_ajax_heyou_get_type_categories', [$this, 'heyou_get_type_categories']);
		add_action('wp_ajax_heyou_get_type_taxonomies', [$this, 'heyou_get_type_taxonomies']);

		add_action( 'admin_footer', [$this, 'heyou_select_media_js'] );

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name.'select2', plugin_dir_url( __FILE__ ) . 'css/heyou-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name.'datatable', plugin_dir_url( __FILE__ ) . 'css/datatables.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name.'datatable', plugin_dir_url( __FILE__ ) . 'js/datatables.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/heyou-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function twodigit_add_plugin_page() {
		add_menu_page(
			'Heyou', // page_title
			'Heyou', // menu_title
			'manage_options', // capability
			'heyou', // menu_slug
			array( $this, 'twodigit_create_admin_page' ), // function
			'dashicons-admin-generic', // icon_url
			2 // position
		);
	}

	public function get_sortby_value($sortby) {
		if($sortby == 'today') return " = '".date('Y-m-d')."'";
		elseif($sortby == 'custom-date') return " = '".sanitize_text_field($_GET['heyou-custom-date'])."'";
		elseif($sortby == 'custom-period') return " BETWEEN '".sanitize_text_field($_GET['heyou-custom-period-from'])."' AND '".sanitize_text_field($_GET['heyou-custom-period-to'])."'";
	}

	public function get_sorted_analytic($key, $sortby) {
		$sort = $this->get_sortby_value($sortby);

		global $wpdb;

		$sql = stripslashes($wpdb->prepare("
		SELECT SUM(value) AS st 
		FROM {$wpdb->prefix}heyou_stats s   
		WHERE s.key = '%s' AND DATE(s.date) %1s 
		", $key, $sort));

		$row = $wpdb->get_row($sql);

		if (!$row->st) {
			return 0;
		} else {
			return $row->st;
		}
	}

	public function twodigit_create_admin_page() {
		$this->twodigit_options = get_option( 'twodigit_option_name' ); 

		/* Check for filters */
		if(! isset($_GET['sort-by']) || sanitize_key($_GET['sort-by']) == 'all') {
			$aLoaded = get_option('heyou_video_loaded', 0);
			$aClicked = get_option('heyou_video_clicked', 0);
			$aCart = get_option('heyou_addedcart_clicked', 0);
			$aRevenue = get_option('heyou_video_revenue', 0);
		} else {
			$sortby_val = sanitize_key($_GET['sort-by']);
			$aLoaded = $this->get_sorted_analytic('video_loaded', $sortby_val);
			$aClicked = $this->get_sorted_analytic('video_clicked', $sortby_val);
			$aCart = $this->get_sorted_analytic('video_addtocart_clicked', $sortby_val);
			$aRevenue = $this->get_sorted_analytic('heyou_video_revenue', $sortby_val);

			//echo "load: ".$aLoaded." click: ".$aClicked." cart: ".$aCart." rev: ".$aRevenue;
		}


		$default_tab = null;
		$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;
		?>

		<div class="wrap">
			<h2><?php esc_html_e('Heyou', 'heyou'); ?></h2>
			<p><?php esc_html_e('Heyou Video bubble admin page', 'heyou'); ?></p>
			<nav class="nav-tab-wrapper">
				<a href="?page=heyou" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Analytics', 'heyou'); ?></a>
				<a href="?page=heyou&tab=product-config" class="nav-tab <?php if($tab==='product-config'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Product Video Bubble', 'heyou'); ?></a>
				<a href="?page=heyou&tab=global-config" class="nav-tab <?php if($tab==='global-config'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Global Video Bubble', 'heyou'); ?></a>
				<a href="?page=heyou&tab=license" class="nav-tab <?php if($tab==='license'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('License', 'heyou'); ?></a>
			</nav>

			<div class="tab-content heyou-tab-content">
				<?php switch($tab) :
					case 'product-config':
						$this->heyou_get_current_product_videos();
						break;
					case 'global-config':
						settings_errors(); ?>
						<form method="post" action="options.php">
							<?php
								settings_fields( 'twodigit_option_group' );
								do_settings_sections( 'twodigit-admin' );
								submit_button(esc_html(__('Save global video configuration', 'heyou')));
							?>
						</form>
						
						<?php
						break;
					case 'license':
							?>
							<div class="heyou-notice heyou-warning">
								<p><?php 
								/* translators: %s is replaced with the URL to heyou's website */
								echo sprintf( esc_html(__('You are using the FREE version of Heyou, to get PRO, go to %s.', 'heyou')),
												'<a href="'.esc_url('https://www.heyou.io').'">'.esc_html('heyou.io','heyou').'</a>'); ?>
								</p>
							</div>
							<?php
						break;
					default:
						?>
						<form method="GET" class="heyou-sortby">
							<input type="hidden" name="page" value="heyou">
							<select id="heyou-stats-filter" name="sort-by">
								<option value="all" <?php echo (isset($sortby_val) && $sortby_val == "all") ? 'selected' : '' ?>><?php _e('All-time', 'heyou'); ?>
								<option value="today" <?php echo (isset($sortby_val) && $sortby_val == "today") ? 'selected' : '' ?>><?php _e('Today', 'heyou'); ?>
								<option value="custom-date" <?php echo (isset($sortby_val) && $sortby_val == "custom-date") ? 'selected' : '' ?>><?php _e('Custom date', 'heyou'); ?>
								<option value="custom-period" <?php echo (isset($sortby_val) && $sortby_val == "custom-period") ? 'selected' : '' ?>><?php _e('Custom period', 'heyou'); ?>
							</select>

							<span class="custom-date-wrap" style="<?php echo (! isset($sortby_val) || $sortby_val != 'custom-date') ? esc_attr('display: none') : ''; ?>">
								<input type="date" id="heyou-custom-date" name="heyou-custom-date" value="<?php echo (isset($_GET['heyou-custom-date'])) ? $_GET['heyou-custom-date'] : '' ?>">
							</span>

							<span class="custom-period-wrap" style="<?php echo (! isset($sortby_val) || $sortby_val != 'custom-period') ? esc_attr('display: none') : ''; ?>">
								<label for="heyou-custom-period-from"><?php esc_html_e('From', 'heyou'); ?> 
									<input type="date" id="heyou-custom-period-from" name="heyou-custom-period-from" value="<?php echo (isset($_GET['heyou-custom-period-from'])) ? esc_attr($_GET['heyou-custom-period-from']) : '' ?>"></label>
								<label for="heyou-custom-period-to"><?php esc_html_e('To', 'heyou'); ?> 
									<input type="date" id="heyou-custom-period-to" name="heyou-custom-period-to" value="<?php echo (isset($_GET['heyou-custom-period-to'])) ? esc_attr($_GET['heyou-custom-period-to']) : '' ?>"></label>
							</span>

							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Filter', 'heyou'); ?>">
						</form>

						<?php if(isset($aLoaded) && isset($aClicked) && isset($aCart) && isset($aRevenue)): ?>
						<div class="heyou-analytics-grid">
							<div class="heyou-analytics-card">
								<div claass="heyou-video-loaded">
									<h3 class="heyou-good"><?php esc_html_e($aLoaded) ?></h3>
									<span><?php esc_html_e('Number of people who saw the thumbnail', 'heyou') ?></span>
								</div>
							</div>

							<div class="heyou-analytics-card">
								<div claass="heyou-video-clicked">
									<?php $valClicked = ($aClicked) ? round($aClicked/$aLoaded, 2)*100 : 0; ?>
									<h3 class="heyou-good"><?php esc_html_e($valClicked.' %'); ?> <sup><?php esc_html_e('('.$aClicked.')'); ?></sup></h3>
									<span><?php esc_html_e('Number of people who watched the video', 'heyou') ?></span>

									<div class="progress" style="height: 7px;">
										<div class="progress-bar bg-success" role="progressbar" style="width: <?php esc_attr_e($valClicked); ?>%;"></div>
									</div>
								</div>
							</div>

							<div class="heyou-analytics-card">
								<div claass="heyou-addedcart-clicked">
									<?php $valCart = ($aCart) ? round($aCart/$aLoaded, 2)*100 : 0; ?>
									<h3 class="heyou-good"><?php esc_html_e($valCart.' %'); ?> <sup><?php esc_html_e('('.$aCart.')'); ?></sup></h3>
									<span><?php esc_html_e('Number of people who added to cart via video', 'heyou') ?></span>

									<div class="progress" style="height: 7px;">
										<div class="progress-bar bg-success" role="progressbar" style="width: <?php esc_attr_e($valCart); ?>%;"></div>
									</div>
								</div>
							</div>

							<div class="heyou-analytics-card">
								<div claass="heyou-video-revenue">
									<h3 class="heyou-good"><?php echo wc_price($aRevenue); ?></h3>
									<span><?php esc_html_e('Extra revenue generated with Heyou', 'heyou'); ?></span>
								</div>
							</div>
						</div>
						<?php else: esc_html_e('No statistics available for the selected filter(s).', 'heyou'); endif; ?>
						<?php
						break;
				endswitch; ?>
			</div>
		</div>
		<?php
	}
		

	public function twodigit_page_init() {
		register_setting(
			'twodigit_option_group', // option_group
			'twodigit_option_name', // option_name
			array( $this, 'twodigit_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'twodigit_setting_section', // id
			'Settings', // title
			array( $this, 'twodigit_section_info' ), // callback
			'twodigit-admin' // page
		);

		add_settings_field(
			'global_video_1', // id
			'Global video', // title
			array( $this, 'global_video_1_callback' ), // callback
			'twodigit-admin', // page
			'twodigit_setting_section' // section
		);

		add_settings_field(
			'global_file', // id
			'Global video file', // title
			array( $this, 'global_video_file_2_callback' ), // callback
			'twodigit-admin', // page
			'twodigit_setting_section' // section
		);

		add_settings_field(
			'global_video_button_text', // id
			'Global video button text', // title
			array( $this, 'global_video_button_text_callback' ), // callback
			'twodigit-admin', // page
			'twodigit_setting_section' // section
		);

		add_settings_field(
			'global_video_button_link', // id
			'Global video button link', // title
			array( $this, 'global_video_button_link_callback' ), // callback
			'twodigit-admin', // page
			'twodigit_setting_section' // section
		);
	}

	function heyou_remove_video() {
		if(isset($_POST['heyou-delete-video']) && isset($_POST['heyou-delete-video-id'])) {
			global $wpdb;
			$post_id = sanitize_key($_POST['heyou-delete-video-id']);

			delete_post_meta($post_id, 'twodigit_video-bubble-file');
			$table = "{$wpdb->prefix}heyou_stats";
			$wpdb->delete( $table, array( 'post_ids' => $post_id ) );
		}
	}

	function post_has_video() {
		if(isset(get_post_meta(get_the_ID(), 'twodigit_video-bubble-file')[0]) && sanitize_key(get_post_meta(get_the_ID(), 'twodigit_video-bubble-file')[0])) {
			return true;
		} else {
			return false;
		}
	}

	function count_product_videos() {
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);
		$st = 0;

		$posts = get_posts( $args );
		if($posts) {
			foreach($posts as $post_id) {
				if(isset(get_post_meta($post_id, 'twodigit_video-bubble-file')[0]) && sanitize_key(get_post_meta($post_id, 'twodigit_video-bubble-file')[0])) {
					$st++;
				}
			}
		}
		return $st;
	}

	public function heyou_get_current_product_videos() {
		$this->heyou_remove_video();
		?>
		<h2><?php esc_html_e('Currently active product video bubbles', 'heyou') ?> (<?php echo sanitize_key($this->count_product_videos()); ?>)</h2>
		<?php
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);

		$posts = get_posts( $args );
		if($posts) {
			?>
			<table id='heyou-active-videos' class="cell-border compact stripe">
				<thead>
					<tr class='heyou-active-videos-row'>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Attachment', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Product name & SKU', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Revenue generated', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('CTR to video', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Impressions', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Timer', 'heyou') ?></h3>
						</th>
						<th class='heyou-active-col'>
							<h3><?php esc_html_e('Actions', 'heyou') ?></h3>
						</th>
					</tr>
				</thead>
			<tbody>
			<?php
			foreach($posts as $post_id) {
				if(isset(get_post_meta($post_id, 'twodigit_video-bubble-file')[0]) && sanitize_key(get_post_meta($post_id, 'twodigit_video-bubble-file')[0])) {
					$attachment_id = sanitize_key(get_post_meta($post_id, 'twodigit_video-bubble-file')[0]);
					$timer = sanitize_key(get_post_meta($post_id, 'twodigit_video-bubble-timer')[0]);
					$product = wc_get_product( $post_id );
					$sku = ($product->get_sku()) ? sanitize_key($product->get_sku()) : '/';

					$videoLoaded = $this->get_product_revenue('video_loaded', $post_id);
					$videoClicked = $this->get_product_revenue('video_clicked', $post_id);
					
					?>
					<tr class='heyou-active-videos-row'>
						<td class='heyou-active-col image-preview-wrapper'>
							<video width="80" height="auto" controls>
								<source src="<?php echo esc_url(wp_get_attachment_url($attachment_id)); ?>" type="video/mp4">
								<?php esc_html_e('Your browser does not support the video tag.', 'heyou'); ?>
							</video>
						</td>
						<td class='heyou-active-col'>
							<p><a href="<?php echo esc_url(get_permalink($post_id)) ?>"><?php esc_html_e(get_the_title($post_id). '(SKU: '.$sku.')'); ?></a></p>
						</td>
						<td class='heyou-active-col'>
							<p><?php echo $this->get_product_revenue('heyou_video_revenue', $post_id) ?></p>
						</td>
						<td class='heyou-active-col'>
							<p><?php echo ($videoLoaded && $videoClicked) ? esc_html(round($videoClicked/$videoLoaded, 2)*100) : 0 ?> %</p>
						</td>
						<td class='heyou-active-col'>
							<p><?php esc_html_e($videoLoaded) ?></p>
						</td>
						<td class='heyou-active-col'>
							<p><?php esc_html_e($timer.' '.__('seconds', 'heyou')) ?></p>
						</td>
						<td class='heyou-active-col'>
							<form method="POST" style="text-align: center">
								<input type="hidden" name="heyou-delete-video-id" value="<?php echo esc_attr($post_id) ?>">
								<input type="submit" name="heyou-delete-video" class="button button-primary" value="<?php echo esc_attr('Remove', 'heyou') ?>">
							</form>
						</td>
					</tr>
					<?php
				}
			}
			echo "</tbody></table>";
		}
	}

	function get_product_revenue($key, $post_id) {
		global $wpdb;

		$sql = stripslashes($wpdb->prepare("
		SELECT SUM(value) AS st 
		FROM {$wpdb->prefix}heyou_stats s   
		WHERE s.key = '%s' AND s.post_ids IN(%s) 
		", sanitize_key($key), sanitize_key($post_id)));

		$row = $wpdb->get_row($sql);

		if (!$row->st) {
			return 0;
		} else {
			if($key == 'heyou_video_revenue') return wc_price(sanitize_key($row->st));

			return sanitize_key($row->st);
		}
	}

	public function twodigit_sanitize($input) {
		$sanitary_values = array();

		if ( isset( $input['global_video_1'] ) ) {
			$sanitary_values['global_video_1'] = $input['global_video_1'];
		}

		if ( isset( $input['global_file'] ) ) {
			$sanitary_values['global_file'] = $input['global_file'];
		}

		if ( isset( $input['global_video_button_text'] ) ) {
			$sanitary_values['global_video_button_text'] = $input['global_video_button_text'];
		}

		if ( isset( $input['global_video_button_link'] ) ) {
			$sanitary_values['global_video_button_link'] = $input['global_video_button_link'];
		}

		return $sanitary_values;
	}

	public function twodigit_section_info() {
		
	}

	public function global_video_1_callback() {
		printf(
			'<input type="checkbox" name="twodigit_option_name[global_video_1]" id="global_video_1" value="global_video_1" %s> <label for="global_video_1">Enable the global video?</label>',
			( isset( $this->twodigit_options['global_video_1'] ) && $this->twodigit_options['global_video_1'] === 'global_video_1' ) ? 'checked' : ''
		);
	}

	public function global_video_button_text_callback() {
		printf(
			'<input class="regular-text" type="text" name="twodigit_option_name[global_video_button_text]" id="global_video_button_text" value="%s">',
			isset( $this->twodigit_options['global_video_button_text'] ) ? esc_attr( $this->twodigit_options['global_video_button_text']) : 'Shop'
		);
	}

	public function global_video_button_link_callback() {
		printf(
			'<input class="regular-text" type="text" name="twodigit_option_name[global_video_button_link]" id="global_video_button_link" value="%s">',
			isset( $this->twodigit_options['global_video_button_link'] ) ? esc_attr( $this->twodigit_options['global_video_button_link']) : '/shop'
		);
	}

	public function global_video_file_2_callback() {
		$this->heyou_select_global_media();
	}

	// META BOXES BELOW

	public function add_meta_boxes() {
		add_meta_box(
			sanitize_title( $this->config['title'] ),
			$this->config['title'],
			[ $this, 'add_meta_box_callback' ],
			'',
			$this->config['context'],
			$this->config['priority']
		);
	}

	public function save_post( $post_id ) {
		foreach ( $this->config['fields'] as $field ) {
			switch ( $field['type'] ) {
				default:
					if ( isset( $_POST[ $field['id'] ] ) ) {
						$sanitized = sanitize_text_field( $_POST[ $field['id'] ] );
						update_post_meta( $post_id, $field['id'], $sanitized );
					}
			}
		}
	}

	public function add_meta_box_callback() {
		if(($this->post_has_video()) ||($this->count_product_videos() < 1 && (get_post_type() == 'product')) || ! (get_post_type() == 'product')):
			echo '<div class="heyou-meta-description">' . esc_html($this->config['description']) . '</div>';
			$this->fields_table();
		else:
			echo '<div class="heyou-meta-description heyou-notice heyou-warning">'.sprintf( esc_html(__('You are using the FREE version of Heyou which only allows 1 product video! To get PRO, go to %s.', 'heyou')),
												'<a href="'.esc_url('https://www.heyou.io').'">'.esc_html('heyou.io','heyou').'</a>').'</div>';
		endif;
	}

	private function fields_table() {
		$current_screen = get_current_screen();
		?><table class="form-table" role="presentation">
			<tbody><?php
				foreach ( $this->config['fields'] as $field ) {
					if( ($current_screen ->id === "product" && $field['id'] != 'twodigit_video-bubble-text' && $field['id'] != 'twodigit_video-bubble-link') ||  $current_screen ->id != "product") {
					?><tr>
						<th scope="row"><?php $this->label( $field ); ?></th>
						<td><?php $this->field( $field ); ?></td>
					</tr><?php
					}
				}
			?></tbody>
		</table><?php
	}

	private function label( $field ) {
		$field['id'] = sanitize_key($field['id']);
		switch ( $field['id'] ) {
			case 'twodigit_video-bubble-file':
				printf(
					'<label class="" for="%s">%s</label>',
					$field['id'], esc_html(__('Video bubble file', 'heyou'))
				);
				break;
			case 'twodigit_video-bubble-timer':
				printf(
					'<label class="" for="%s">%s</label>',
					$field['id'], esc_html(__('Display the bubble after (value) seconds.', 'heyou'))
				);
				break;
			case 'twodigit_video-bubble-text':
				printf(
					'<label class="" for="%s">%s</label>',
					$field['id'], esc_html(__('Button text', 'heyou'))
				);
				break;
			case 'twodigit_video-bubble-link':
				printf(
					'<label class="" for="%s">%s</label>',
					$field['id'], esc_html(__('Button link', 'heyou'))
				);
				break;
			default:
				printf(
					'<label class="" for="%s">%s</label>',
					$field['id'], $field['label']
				);
		}
	}

	private function field( $field ) {
		switch ( $field['type'] ) {
			case 'number':
				$this->input_minmax( $field );
				break;
			case 'media':
				$this->heyou_select_media( $field );
				break;
			default:
				$this->input( $field );
				break;
		}
	}

	private function input( $field ) {
		printf(
			'<input class="regular-text %s" id="%s" name="%s" %s type="%s" value="%s">',
			isset( $field['class'] ) ? $field['class'] : '',
			$field['id'], $field['id'],
			isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
			$field['type'],
			$this->value( $field )
		);
	}

	private function input_minmax( $field ) {
		printf(
			'<input class="regular-text" id="%s" %s %s name="%s" %s type="%s" value="%s">',
			$field['id'],
			isset( $field['max'] ) ? "max='{$field['max']}'" : '',
			isset( $field['min'] ) ? "min='{$field['min']}'" : '',
			$field['id'],
			isset( $field['step'] ) ? "step='{$field['step']}'" : '',
			$field['type'],
			$this->value( $field )
		);
	}

	private function heyou_select_media( $field ) {
		wp_enqueue_media();
		$current = $this->value( $field );

		?>
		<div class='image-preview-wrapper'>
			<video width="320" height="240" controls>
				<source src="<?php echo esc_url(wp_get_attachment_url($current)); ?>" type="video/mp4">
				<?php esc_html_e('Your browser does not support the video tag.', 'heyou'); ?>
			</video>
			<button type="button" id="heyou-remove-selected"><?php esc_html_e('X') ?></button>
		</div>
		<input id="upload_image_button" type="button" class="button" value="<?php esc_html_e( 'Select the video', 'heyou' ); ?>" />
		<input type='hidden' name='<?php echo $field['id']; ?>' id='<?php echo $field['id']; ?>' value='<?php echo esc_attr($current); ?>'>
		<?php
	}

	private function heyou_select_global_media() {

		if( isset( $this->twodigit_options['global_video_1'] ) && esc_attr($this->twodigit_options['global_video_1']) === 'global_video_1' ) {

			wp_enqueue_media();
			
			$current = isset( $this->twodigit_options['global_file'] ) ? esc_attr( $this->twodigit_options['global_file']) : '';

			?>
			<div class='image-preview-wrapper'>
				<video width="320" height="240" controls>
					<source src="<?php echo esc_url(wp_get_attachment_url($current)); ?>" type="video/mp4">
					<?php esc_html_e('Your browser does not support the video tag.', 'heyou'); ?>
				</video>
				<button type="button" id="heyou-remove-selected"><?php esc_html_e('X') ?></button>
			</div>
			<input id="upload_image_button" type="button" class="button" value="<?php echo esc_attr( 'Select the video', 'heyou' ); ?>" />
			<input type='hidden' name='twodigit_option_name[global_file]' id='global_file' value='<?php echo esc_attr($current); ?>'>
			<?php

		} else {
			echo "<p>".esc_html('Global video is currently disabled. Enable it above in order to configure it.','heyou')."</p>";
		}
	}

	function heyou_select_media_js() {

		$my_saved_attachment_post_id = sanitize_key(get_option( 'media_selector_attachment_id', 0 ));
	
		?><script type='text/javascript'>
	
			jQuery( document ).ready( function( $ ) {
	
				// Uploading files
				var file_frame;
				var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
				var set_to_post_id = <?php echo esc_attr($my_saved_attachment_post_id); ?>; // Set this

				jQuery('body #heyou-remove-selected').on('click', function (event) {
					event.preventDefault();

					console.log("click");
					$(".image-preview-wrapper video").attr('src', '');
					$('#twodigit_video-bubble-file').val('');
				});
	
				jQuery('#upload_image_button').on('click', function(event){
	
					event.preventDefault();
	
					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						// Set the post ID to what we want
						file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						// Open frame
						file_frame.open();
						return;
					} else {
						// Set the wp.media post id so the uploader grabs the ID we want when initialised
						wp.media.model.settings.post.id = set_to_post_id;
					}
	
					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: '<?php esc_html_e('Heyou - select the video, to be displayed', 'heyou'); ?>',
						button: {
							text: '<?php esc_html_e('Select', 'heyou'); ?>',
						},
						multiple: false	// Set to true to allow multiple files to be selected
					});
	
					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						// We set multiple to false so only get one image from the uploader
						attachment = file_frame.state().get('selection').first().toJSON();
	
						// Do something with attachment.id and/or attachment.url here
						$( '.image-preview-wrapper video' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
						$( '#image_attachment_id' ).val( attachment.id );
						if($ ('#twodigit_video-bubble-file').length) {
							$ ('#twodigit_video-bubble-file').val( attachment.id );
						}
						if($ ('#global_file').length) {
							$ ('#global_file').val( attachment.id );
						}
	
						// Restore the main post ID
						wp.media.model.settings.post.id = wp_media_post_id;
					});
	
						// Finally, open the modal
						file_frame.open();
				});
	
				// Restore the main ID when the add media button is pressed
				jQuery( 'a.add_media' ).on( 'click', function() {
					wp.media.model.settings.post.id = wp_media_post_id;
				});
			});
	
		</script><?php
	
	}

	private function value( $field ) {
		global $post;
		if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
			$value = get_post_meta( $post->ID, $field['id'], true );
		} else if ( isset( $field['default'] ) ) {
			$value = $field['default'];
		} else {
			return '';
		}
		return str_replace( '\u0027', "'", $value );
	}

}

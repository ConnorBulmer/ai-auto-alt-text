<?php
/**
 * Plugin Name: AI Auto Alt Text Generator
 * Plugin URI:  https://github.com/ConnorBulmer/ai-auto-alt-text/
 * Description: Automatically generates alt text and image titles for uploaded images using OpenAI’s GPT‑4o mini vision model, improving accessibility and SEO.
 * Version:     1.19
 * Requires at least: 5.5
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author:      Connor Bulmer
 * Author URI:  https://connorbulmer.co.uk
 * Text Domain: ai-auto-alt-text-generator
 * License: GPL v3 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** -----------------------------------------------------------------
 *  Lightweight file logger for the bulk updater
 * ------------------------------------------------------------------*/
if ( ! function_exists( 'aatg_write_log' ) ) {
	function aatg_write_log( $data ) {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'aatg-logs';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file = trailingslashit( $dir ) . 'bulk-debug.log';
		$time = date_i18n( 'Y-m-d H:i:s' );

		$line = '[' . $time . '] ' . print_r( $data, true ) . PHP_EOL;
		file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
	}
}


/** -----------------------------------------------------------------
 *  Toggle bulk‑update debugging
 *  (set to false or comment‑out on production sites)
 *  ---------------------------------------------------------------- */
if ( ! defined( 'AATG_BULK_DEBUG' ) ) {
	define( 'AATG_BULK_DEBUG', true );
}


/* =============================================================================
   ADMIN SETTINGS & BULK PAGE
============================================================================= */

/**
 * Add the settings page under ‘Settings’.
 */
function aatg_register_settings() {
	add_options_page(
		__( 'Alt Text Generator Settings', 'ai-auto-alt-text-generator' ),
		__( 'Alt Text Generator', 'ai-auto-alt-text-generator' ),
		'manage_options',
		'aatg-settings',
		'aatg_render_settings_page'
	);
}
add_action( 'admin_menu', 'aatg_register_settings' );

/**
 * Add the bulk-update page under ‘Tools’.
 */
function aatg_register_bulk_page() {
	add_management_page(
		__( 'Bulk Alt Text Update', 'ai-auto-alt-text-generator' ),
		__( 'Bulk Alt Text Update', 'ai-auto-alt-text-generator' ),
		'manage_options',
		'aatg-bulk-update',
		'aatg_render_bulk_page'
	);
}
add_action( 'admin_menu', 'aatg_register_bulk_page' );

/**
 * Register plugin settings.
 */
function aatg_register_settings_init() {

	register_setting( 'aatg_options_group', 'aatg_openai_api_key', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
		'autoload'          => false,
	) );

	register_setting( 'aatg_options_group', 'aatg_openai_model', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'gpt-4o-mini',
	) );

	register_setting( 'aatg_options_group', 'aatg_image_size', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'large',
	) );

	register_setting( 'aatg_options_group', 'aatg_image_detail', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'low',
	) );

	register_setting( 'aatg_options_group', 'aatg_site_context', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );

	register_setting( 'aatg_options_group', 'aatg_auto_title', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'on',
	) );

	/* ---------- NEW: “send file name” option ---------- */
	register_setting( 'aatg_options_group', 'aatg_send_filename', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'off',
	) );

	/* ---------- NEW: full context for titles ---------- */
register_setting( 'aatg_options_group', 'aatg_title_full_context', array(
	'type'              => 'string',
	'sanitize_callback' => 'sanitize_text_field',
	'default'           => 'off',
	) );

	/* delay (seconds) between bulk batches -------------------------- */
register_setting( 'aatg_options_group', 'aatg_bulk_delay', array(
	'type'              => 'integer',
	'sanitize_callback' => 'absint',
	'default'           => 3,
) );

register_setting( 'aatg_options_group', 'aatg_request_timeout', array(
	'type'              => 'integer',
	'sanitize_callback' => 'absint',
	'default'           => 30,
) );

	register_setting( 'aatg_options_group', 'aatg_bulk_batch_size', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 4,
	) );

	register_setting( 'aatg_options_group', 'aatg_bulk_batch_size', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 4,
	) );

/* language selection ------------------------------------------------------- */
register_setting( 'aatg_options_group', 'aatg_language', array(
	'type'              => 'string',
	'sanitize_callback' => 'sanitize_text_field',
	'default'           => 'en_US', // default: English (US)
) );




	add_settings_section(
		'aatg_settings_section',
		__( 'Alt Text Generator Settings', 'ai-auto-alt-text-generator' ),
		'aatg_section_callback',
		'aatg-settings'
	);

	add_settings_section(
		'aatg_rate_limit_section',
		__( 'Performance & Rate Limits', 'ai-auto-alt-text-generator' ),
		'aatg_rate_limit_section_callback',
		'aatg-settings'
	);

	add_settings_section(
		'aatg_rate_limit_section',
		__( 'Performance & Rate Limits', 'ai-auto-alt-text-generator' ),
		'aatg_rate_limit_section_callback',
		'aatg-settings'
	);

	add_settings_field(
		'aatg_openai_api_key',
		__( 'OpenAI API Key', 'ai-auto-alt-text-generator' ),
		'aatg_api_key_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	add_settings_field(
		'aatg_openai_model',
		__( 'OpenAI Model', 'ai-auto-alt-text-generator' ),
		'aatg_openai_model_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	add_settings_field(
		'aatg_image_size',
		__( 'Image Size to Send', 'ai-auto-alt-text-generator' ),
		'aatg_image_size_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	add_settings_field(
		'aatg_image_detail',
		__( 'Image Detail Quality', 'ai-auto-alt-text-generator' ),
		'aatg_image_detail_render',
		'aatg-settings',
		'aatg_rate_limit_section'
	);

	add_settings_field(
		'aatg_site_context',
		__( 'Site Context', 'ai-auto-alt-text-generator' ),
		'aatg_site_context_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	add_settings_field(
		'aatg_auto_title',
		__( 'Automatically Generate Image Title', 'ai-auto-alt-text-generator' ),
		'aatg_auto_title_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	/* ---------- NEW field ---------- */
	add_settings_field(
		'aatg_send_filename',
		__( 'Send Image File Name to OpenAI', 'ai-auto-alt-text-generator' ),
		'aatg_send_filename_render',
		'aatg-settings',
		'aatg_settings_section'
	);

	add_settings_field(
	'aatg_title_full_context',
	__( 'Use full context for image titles', 'ai-auto-alt-text-generator' ),
	'aatg_title_full_context_render',
	'aatg-settings',
	'aatg_settings_section'
	);

	add_settings_field(
		'aatg_bulk_delay',
		__( 'Bulk optimiser delay (seconds)', 'ai-auto-alt-text-generator' ),
		'aatg_bulk_delay_render',
		'aatg-settings',
		'aatg_rate_limit_section'
	);

	add_settings_field(
		'aatg_bulk_batch_size',
		__( 'Bulk batch size', 'ai-auto-alt-text-generator' ),
		'aatg_bulk_batch_size_render',
		'aatg-settings',
		'aatg_rate_limit_section'
	);

	add_settings_field(
		'aatg_request_timeout',
		__( 'OpenAI request timeout (seconds)', 'ai-auto-alt-text-generator' ),
		'aatg_request_timeout_render',
		'aatg-settings',
		'aatg_rate_limit_section'
	);

add_settings_field(
	'aatg_language',
	__( 'Output Language', 'ai-auto-alt-text-generator' ),
	'aatg_language_render',
	'aatg-settings',
	'aatg_settings_section'
);

	

}
add_action( 'admin_init', 'aatg_register_settings_init' );

/**
 * Settings–section description.
 */
function aatg_section_callback() {
	echo '<p>' . esc_html__( 'Enter your API key, choose the OpenAI model, select the image size, decide whether to send the image file name for extra context, provide optional site context, and choose whether to automatically generate an image title.', 'ai-auto-alt-text-generator' ) . '</p>';
}

/**
 * Rate limit section description.
 */
function aatg_rate_limit_section_callback() {
	echo '<p>' . esc_html__( 'Tune these settings to reduce token usage and avoid rate limits during bulk runs.', 'ai-auto-alt-text-generator' ) . '</p>';
}

/* ---------- individual field render callbacks ---------- */

function aatg_api_key_render() {
	$api_key = get_option( 'aatg_openai_api_key', '' );
	printf(
		'<input type="text" id="aatg_api_key" name="aatg_openai_api_key" value="%s" class="regular-text ltr" />',
		esc_attr( $api_key )
	);
}

function aatg_openai_model_render() {
	$current = aatg_get_selected_model();
	$models  = aatg_get_model_options();

	echo '<select id="aatg_openai_model" name="aatg_openai_model">';
	foreach ( $models as $key => $label ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function aatg_image_size_render() {
	$current = get_option( 'aatg_image_size', 'large' );
	$sizes   = array(
		'thumbnail' => __( 'Thumbnail', 'ai-auto-alt-text-generator' ),
		'medium'    => __( 'Medium', 'ai-auto-alt-text-generator' ),
		'large'     => __( 'Large', 'ai-auto-alt-text-generator' ),
		'full'      => __( 'Full', 'ai-auto-alt-text-generator' ),
	);
	echo '<select id="aatg_image_size" name="aatg_image_size">';
	foreach ( $sizes as $key => $label ) {
		printf( '<option value="%s" %s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function aatg_image_detail_render() {
	$current = get_option( 'aatg_image_detail', 'low' );
	$opts    = array(
		'high' => __( 'High', 'ai-auto-alt-text-generator' ),
		'low'  => __( 'Low', 'ai-auto-alt-text-generator' ),
	);
	echo '<select id="aatg_image_detail" name="aatg_image_detail">';
	foreach ( $opts as $key => $label ) {
		printf( '<option value="%s" %s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
	echo ' <span class="description">' .
	     esc_html__( 'Low uses fewer tokens and is usually sufficient; switch to High for detailed images at higher cost.', 'ai-auto-alt-text-generator' ) .
	     '</span>';
}

function aatg_site_context_render() {
	$context = get_option( 'aatg_site_context', '' );
	printf(
		'<textarea id="aatg_site_context" name="aatg_site_context" class="large-text" rows="3">%s</textarea>',
		esc_textarea( $context )
	);
}

function aatg_auto_title_render() {
	$auto = get_option( 'aatg_auto_title', 'on' );
	echo '<input type="checkbox" id="aatg_auto_title" name="aatg_auto_title" value="on" ' . checked( $auto, 'on', false ) . ' /> ' .
		 esc_html__( 'Enable automatic image title generation.', 'ai-auto-alt-text-generator' );
}

/* ---------- NEW render callback ---------- */
function aatg_send_filename_render() {
	$send = get_option( 'aatg_send_filename', 'off' );
	echo '<input type="checkbox" id="aatg_send_filename" name="aatg_send_filename" value="on" ' .
	     checked( $send, 'on', false ) . ' /> ' .
	     esc_html__( 'Pass the image’s file name (e.g. “man-on-a-horse.jpg”) to OpenAI for extra context.', 'ai-auto-alt-text-generator' );
}

function aatg_title_full_context_render() {
	$full = get_option( 'aatg_title_full_context', 'off' );
	echo '<input type="checkbox" id="aatg_title_full_context" name="aatg_title_full_context" value="on" ' .
	     checked( $full, 'on', false ) . ' /> ' .
	     esc_html__( 'Include site context and file name when generating titles (uses more tokens).', 'ai-auto-alt-text-generator' );
}

function aatg_bulk_delay_render() {
	$delay = get_option( 'aatg_bulk_delay', 3 );
	printf(
		'<input type="number" min="0" id="aatg_bulk_delay" name="aatg_bulk_delay" value="%d" style="width:70px;" />',
		(int) $delay
	);
	echo ' <span class="description">' .
	     esc_html__( 'Seconds to wait between each batch during a bulk run. Increase this if you hit 429 rate limits.', 'ai-auto-alt-text-generator' ) .
	     '</span>';
}

function aatg_bulk_batch_size_render() {
	$size = get_option( 'aatg_bulk_batch_size', 4 );
	printf(
		'<input type="number" min="1" max="10" id="aatg_bulk_batch_size" name="aatg_bulk_batch_size" value="%d" style="width:70px;" />',
		(int) $size
	);
	echo ' <span class="description">' .
	     esc_html__( 'Number of images per batch (lower values reduce rate-limit risk).', 'ai-auto-alt-text-generator' ) .
	     '</span>';
}

function aatg_request_timeout_render() {
	$timeout = aatg_get_request_timeout();
	printf(
		'<input type="number" min="10" max="120" id="aatg_request_timeout" name="aatg_request_timeout" value="%d" style="width:80px;" />',
		(int) $timeout
	);
	echo ' <span class="description">' .
	     esc_html__( 'How long to wait for OpenAI responses before WordPress aborts the request.', 'ai-auto-alt-text-generator' ) .
	     '</span>';
}

function aatg_get_request_timeout() {
	$timeout = (int) get_option( 'aatg_request_timeout', 30 );

	if ( $timeout < 10 ) {
		$timeout = 10;
	}

	if ( $timeout > 120 ) {
		$timeout = 120;
	}

	return $timeout;
}

function aatg_is_timeout_error( $response ) {
	if ( ! is_wp_error( $response ) ) {
		return false;
	}

	$message = strtolower( $response->get_error_message() );
	return strpos( $message, 'timed out' ) !== false || strpos( $message, 'timeout' ) !== false;
}

function aatg_openai_chat_completion_request( $payload, $api_key, $context ) {
	$timeout = aatg_get_request_timeout();

	$args = array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		'body'    => wp_json_encode( $payload ),
		'timeout' => $timeout,
	);

	$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
	if ( ! aatg_is_timeout_error( $response ) ) {
		return $response;
	}

	$args['timeout'] = min( 120, $timeout + 15 );
	aatg_write_log( sprintf( 'Retrying %s request after timeout. First timeout: %d s, retry timeout: %d s.', $context, $timeout, $args['timeout'] ) );

	return wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
}


/* ---------- page renderers ---------- */

function aatg_render_settings_page() {
	aatg_render_dashboard_page( 'settings' );
}


function aatg_render_bulk_page() {
	aatg_render_dashboard_page( 'bulk' );
}

function aatg_render_bulk_panel() { ?>
	<?php
	// Calculate the delay and output the explanatory paragraph.
	$delay      = (int) get_option( 'aatg_bulk_delay', 3 );
	$batch_size = (int) get_option( 'aatg_bulk_batch_size', 4 );

	printf(
		'<p>%s</p>',
		sprintf(
			/* translators: %1$d = seconds, %2$d = batch size */
			esc_html__(
				'This tool processes images without alt text in batches of %2$d, pausing %1$d seconds between batches.',
				'ai-auto-alt-text-generator'
			),
			$delay,
			$batch_size
		)
	);
	?>

	<button id="aatg-bulk-start" class="button button-primary">
		<?php esc_html_e( 'Start Bulk Update', 'ai-auto-alt-text-generator' ); ?>
	</button>

	<!-- Progress bar container (hidden until start) -->
	<div id="aatg-bulk-progress-container" style="text-align:center; margin-top:20px; display:none;">
		<progress id="aatg-bulk-progress" value="0" max="100" style="width:100%;"></progress>
		<div id="aatg-bulk-progress-text" style="margin-top:5px; font-weight:bold;"></div>
	</div>

	<div id="aatg-bulk-status" style="margin-top:20px;"></div>

	<details id="aatg-bulk-log" style="margin-top:20px; display:none;">
		<summary id="aatg-bulk-log-summary">
			<?php esc_html_e( 'Bulk update log (0 items)', 'ai-auto-alt-text-generator' ); ?>
		</summary>
		<div id="aatg-bulk-log-entries" style="margin-top:10px;"></div>
	</details>
<?php }

function aatg_render_dashboard_page( $active_tab = 'settings' ) {
	$logo_url = plugin_dir_url( __FILE__ ) . 'logo.png';
	?>
	<div class="wrap aatg-dashboard" data-default-tab="<?php echo esc_attr( $active_tab ); ?>">
		<div class="aatg-notices"></div>
		<div class="aatg-hero">
			<div class="aatg-hero-brand">
				<img class="aatg-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'AI Auto Alt Text Generator logo', 'ai-auto-alt-text-generator' ); ?>" />
				<div>
					<h1><?php esc_html_e( 'AI Auto Alt Text Generator', 'ai-auto-alt-text-generator' ); ?></h1>
					<p><?php esc_html_e( 'Generate accessible, SEO-friendly alt text and titles for your WordPress media library.', 'ai-auto-alt-text-generator' ); ?></p>
				</div>
			</div>
		</div>

		<nav class="aatg-tabs" role="tablist">
			<a class="aatg-tab <?php echo $active_tab === 'settings' ? 'is-active' : ''; ?>" href="#settings" data-aatg-tab="settings" role="tab" aria-controls="aatg-panel-settings">
				<?php esc_html_e( 'Settings', 'ai-auto-alt-text-generator' ); ?>
			</a>
			<a class="aatg-tab <?php echo $active_tab === 'bulk' ? 'is-active' : ''; ?>" href="#bulk" data-aatg-tab="bulk" role="tab" aria-controls="aatg-panel-bulk">
				<?php esc_html_e( 'Bulk Updater', 'ai-auto-alt-text-generator' ); ?>
			</a>
			<a class="aatg-tab <?php echo $active_tab === 'integrations' ? 'is-active' : ''; ?>" href="#integrations" data-aatg-tab="integrations" role="tab" aria-controls="aatg-panel-integrations">
				<?php esc_html_e( 'Integrations', 'ai-auto-alt-text-generator' ); ?>
			</a>
		</nav>

		<section id="aatg-panel-settings" class="aatg-panel" data-aatg-panel="settings" role="tabpanel">
			<form method="post" action="options.php" class="aatg-card">
				<?php
				settings_fields( 'aatg_options_group' );
				do_settings_sections( 'aatg-settings' );
				submit_button();
				?>
			</form>
		</section>

		<section id="aatg-panel-bulk" class="aatg-panel" data-aatg-panel="bulk" role="tabpanel">
			<div class="aatg-card">
				<?php aatg_render_bulk_panel(); ?>
			</div>
		</section>

		<section id="aatg-panel-integrations" class="aatg-panel" data-aatg-panel="integrations" role="tabpanel">
			<div class="aatg-card">
				<h2><?php esc_html_e( 'Integrations', 'ai-auto-alt-text-generator' ); ?></h2>
				<p><?php esc_html_e( 'Coming soon.', 'ai-auto-alt-text-generator' ); ?></p>
			</div>
		</section>

		<footer class="aatg-footer">
			<p><?php esc_html_e( 'Made with ❤️ by Connor Bulmer', 'ai-auto-alt-text-generator' ); ?></p>
			<div class="aatg-footer-links">
				<a href="https://connorbulmer.co.uk/" target="_blank" rel="noopener noreferrer">Website</a>
				<a href="https://www.linkedin.com/in/connor-bulmer/" target="_blank" rel="noopener noreferrer">LinkedIn</a>
				<a href="https://github.com/ConnorBulmer" target="_blank" rel="noopener noreferrer">GitHub</a>
			</div>
		</footer>
	</div>
	<?php
}


/* =============================================================================
   ALT TEXT AND TITLE GENERATION
============================================================================= */

/**
 * If the “send file name” option is on, return “Image file name: xyz.jpg. ”
 * otherwise return an empty string.
 */
function aatg_file_name_context( $post_ID ) {
	if ( get_option( 'aatg_send_filename', 'off' ) !== 'on' ) {
		return '';
	}

	$path = get_attached_file( $post_ID );
	if ( ! $path ) {
		return '';
	}

	$filename = wp_basename( $path );
	return "Image file name: {$filename}. ";
}

/**
 * Generate alt text for an image.
 */
function aatg_generate_alt_text( $post_ID ) {

	if ( ! wp_attachment_is_image( $post_ID ) ) {
		return new WP_Error( 'aatg_not_image', 'Attachment is not an image.' );
	}

	$size       = get_option( 'aatg_image_size', 'large' );
	$image_data = wp_get_attachment_image_src( $post_ID, $size );
	if ( ! $image_data || empty( $image_data[0] ) ) {
		return new WP_Error( 'aatg_missing_image', 'No image data available for this attachment.' );
	}

	$image_url = $image_data[0];
	$api_key   = get_option( 'aatg_openai_api_key' );
	if ( empty( $api_key ) ) {
		return new WP_Error( 'aatg_missing_api_key', 'Missing OpenAI API key.' );
	}

	/* build context */
	$context = '';

	$parent_id = get_post_field( 'post_parent', $post_ID );
	if ( $parent_id ) {
		$parent_title = get_the_title( $parent_id );
		if ( $parent_title ) {
			$context .= "This image is used on a page titled '{$parent_title}'. ";
		}
	}

	$site_context = get_option( 'aatg_site_context', '' );
	if ( ! empty( $site_context ) ) {
		$context .= "Site context: {$site_context}. ";
	}

	$context .= aatg_file_name_context( $post_ID );

	$base_prompt = 'Please provide a concise, context-aware alt-text description for the following image. Focus on key visual elements such as primary objects, colours and layout, avoid phrases like "image of", use clear language for screen readers, and keep it under 140 characters:';
	$full_prompt = $context . $base_prompt;

	/* language */
	$system_message = aatg_language_system_message();

	/* build messages */
	$messages = array();

	if ( $system_message ) {
		$messages[] = array(
			'role'    => 'system',
			'content' => $system_message,
		);
	}

	$messages[] = array(
		'role'    => 'user',
		'content' => array(
			array(
				'type' => 'text',
				'text' => $full_prompt,
			),
			array(
				'type'      => 'image_url',
				'image_url' => array(
					'url'    => $image_url,
					'detail' => get_option( 'aatg_image_detail', 'low' ),
				),
			),
		),
	);

	$payload = array(
		'model'    => aatg_get_selected_model(),
		'messages' => $messages,
	);

	$response = aatg_openai_chat_completion_request( $payload, $api_key, 'alt text' );
	if ( is_wp_error( $response ) ) {
		aatg_log_api_error( 'alt text', $response );
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return aatg_handle_openai_error_response( $response, 'alt text' );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
		$alt_text = aatg_trim_leading_quote( sanitize_text_field( $data['choices'][0]['message']['content'] ) );
		update_post_meta( $post_ID, '_wp_attachment_image_alt', $alt_text );
		return $alt_text;
	} else {
		aatg_log_unexpected_response( 'alt text', $body );
		return new WP_Error( 'aatg_unexpected_response', 'OpenAI API returned an unexpected response.' );
	}
}


/**
 * Generate an image title.
 */
function aatg_generate_image_title( $post_ID ) {

	if ( ! wp_attachment_is_image( $post_ID ) ) {
		return new WP_Error( 'aatg_not_image', 'Attachment is not an image.' );
	}

	$size       = get_option( 'aatg_image_size', 'large' );
	$image_data = wp_get_attachment_image_src( $post_ID, $size );
	if ( ! $image_data || empty( $image_data[0] ) ) {
		return new WP_Error( 'aatg_missing_image', 'No image data available for this attachment.' );
	}

	$image_url = $image_data[0];
	$api_key   = get_option( 'aatg_openai_api_key' );
	if ( empty( $api_key ) ) {
		return new WP_Error( 'aatg_missing_api_key', 'Missing OpenAI API key.' );
	}

	/* build context (parent title + site context + filename) */
	$parts = array();

	$parent_id = get_post_field( 'post_parent', $post_ID );
	if ( $parent_id ) {
		$pt = get_the_title( $parent_id );
		if ( $pt ) {
			$parts[] = "This image is used on a page titled '{$pt}'.";
		}
	}

	if ( get_option( 'aatg_title_full_context', 'off' ) === 'on' ) {
		$site_context = get_option( 'aatg_site_context', '' );
		if ( $site_context ) {
			$parts[] = "Site context: {$site_context}.";
		}
		$parts[] = aatg_file_name_context( $post_ID );
	}

	$context = implode( ' ', $parts );

	$title_prompt = "{$context} Please provide a concise, SEO-friendly image title for the following image. Output only the title in plain text. Summarise the image in about 50–70 characters, focusing on its key subject and context:";

	/* language */
	$system_message = aatg_language_system_message();

	/* build messages */
	$messages = array();

	if ( $system_message ) {
		$messages[] = array(
			'role'    => 'system',
			'content' => $system_message,
		);
	}

	$messages[] = array(
		'role'    => 'user',
		'content' => array(
			array(
				'type' => 'text',
				'text' => $title_prompt,
			),
			array(
				'type'      => 'image_url',
				'image_url' => array(
					'url'    => $image_url,
					'detail' => get_option( 'aatg_image_detail', 'low' ),
				),
			),
		),
	);

	$payload = array(
		'model'    => aatg_get_selected_model(),
		'messages' => $messages,
	);

	$response = aatg_openai_chat_completion_request( $payload, $api_key, 'title' );
	if ( is_wp_error( $response ) ) {
		aatg_log_api_error( 'title', $response );
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return aatg_handle_openai_error_response( $response, 'title' );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
		$title_text = aatg_trim_leading_quote( sanitize_text_field( $data['choices'][0]['message']['content'] ) );

		wp_update_post( array(
			'ID'         => $post_ID,
			'post_title' => $title_text,
		) );
		return $title_text;
	} else {
		aatg_log_unexpected_response( 'title', $body );
		return new WP_Error( 'aatg_unexpected_response', 'OpenAI API returned an unexpected response.' );
	}
}

/**
 * Trim a leading double-quote character from generated text.
 */
function aatg_trim_leading_quote( $text ) {
	$text = ltrim( $text );
	if ( strpos( $text, '"' ) === 0 ) {
		$text = ltrim( substr( $text, 1 ) );
	}

	return $text;
}

/**
 * Extract an OpenAI error message from a response body, if present.
 */
function aatg_extract_openai_error_message( $body ) {
	if ( empty( $body ) ) {
		return null;
	}

	$data = json_decode( $body, true );
	if ( is_array( $data ) && ! empty( $data['error']['message'] ) ) {
		$message = $data['error']['message'];
		if ( ! empty( $data['error']['type'] ) ) {
			$message .= ' (type: ' . $data['error']['type'] . ')';
		}
		return $message;
	}

	return null;
}

/**
 * Log a WP_Error from the OpenAI API call with helpful context.
 */
function aatg_log_api_error( $context, $error ) {
	if ( ! is_wp_error( $error ) ) {
		return;
	}

	error_log( sprintf( 'OpenAI API error (%s): %s', $context, $error->get_error_message() ) );
}

/**
 * Handle non-2xx OpenAI responses and return a WP_Error with detail.
 */
function aatg_handle_openai_error_response( $response, $context ) {
	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$detail = aatg_extract_openai_error_message( $body );

	$message = sprintf( 'OpenAI API error (%s): HTTP %d', $context, $code );
	if ( $detail ) {
		$message .= ' - ' . $detail;
	}

	error_log( $message );
	if ( $body ) {
		error_log( sprintf( 'OpenAI API response (%s): %s', $context, $body ) );
	}

	return new WP_Error( 'aatg_openai_http_error', $message, array( 'status' => $code ) );
}

/**
 * Log unexpected successful responses from the OpenAI API.
 */
function aatg_log_unexpected_response( $context, $body ) {
	error_log( sprintf( 'OpenAI API returned unexpected response (%s): %s', $context, $body ) );
}

/**
 * On upload: generate alt text, then (optionally) title.
 */
function aatg_generate_text_and_title( $post_ID ) {
	$result = array(
		'alt_text' => null,
		'title'    => null,
		'warning'  => null,
	);

	$alt_result = aatg_generate_alt_text( $post_ID );
	if ( is_wp_error( $alt_result ) ) {
		return $alt_result;
	}

	$result['alt_text'] = $alt_result;
	if ( get_option( 'aatg_auto_title', 'on' ) === 'on' ) {
		$title_result = aatg_generate_image_title( $post_ID );
		if ( is_wp_error( $title_result ) ) {
			$result['warning'] = $title_result->get_error_message();
		} else {
			$result['title'] = $title_result;
		}
	}

	return $result;
}
add_action( 'add_attachment', 'aatg_generate_text_and_title' );

/* =============================================================================
   MEDIA LIBRARY BUTTON & AJAX
============================================================================= */

/**
 * Add ‘Generate Alt Text & Title’ button to attachment edit form.
 */
function aatg_attachment_fields_to_edit( $fields, $post ) {

	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $fields;
	}

	$fields['aatg_generate_alt_text'] = array(
		'label' => __( 'Alt Text & Title Generator', 'ai-auto-alt-text-generator' ),
		'input' => 'html',
		'html'  => '<button type="button" class="button aatg-generate-alt" data-attachment-id="' . esc_attr( $post->ID ) . '">' .
						__( 'Generate Alt Text & Title', 'ai-auto-alt-text-generator' ) .
				   '</button>' .
				   '<span class="aatg-result" style="margin-left:10px;">' .
						esc_html( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ) .
				   '</span><br />' .
				   '<span class="aatg-title-result" style="margin-left:10px;">' .
						esc_html( get_the_title( $post->ID ) ) .
				   '</span>',
		'helps' => __( 'Click to generate alt text (and title if enabled) using OpenAI.', 'ai-auto-alt-text-generator' ),
	);

	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'aatg_attachment_fields_to_edit', 10, 2 );

/**
 * Enqueue admin JS on all admin pages.
 */
function aatg_enqueue_admin_scripts() {
	wp_enqueue_script(
		'aatg-admin-script',
		plugin_dir_url( __FILE__ ) . 'aatg-admin.js',
		array( 'jquery' ),
		'1.8',
		true
	);

	wp_localize_script( 'aatg-admin-script', 'aatg_ajax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'aatg_nonce' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'aatg_enqueue_admin_scripts' );

/**
 * Enqueue dashboard assets.
 */
function aatg_enqueue_dashboard_assets( $hook ) {
	$dashboard_hooks = array(
		'settings_page_aatg-settings',
		'tools_page_aatg-bulk-update',
	);

	if ( ! in_array( $hook, $dashboard_hooks, true ) ) {
		return;
	}

	wp_enqueue_style(
		'aatg-dashboard-style',
		plugin_dir_url( __FILE__ ) . 'aatg-dashboard.css',
		array(),
		'1.0'
	);

	wp_enqueue_script(
		'aatg-dashboard-tabs',
		plugin_dir_url( __FILE__ ) . 'aatg-dashboard.js',
		array(),
		'1.0',
		true
	);

	wp_enqueue_script(
		'aatg-bulk-script',
		plugin_dir_url( __FILE__ ) . 'aatg-bulk.js',
		array( 'jquery' ),
		'1.3',
		true
	);

	wp_localize_script( 'aatg-bulk-script', 'aatg_bulk_ajax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'aatg_nonce' ),
		'delay'    => (int) get_option( 'aatg_bulk_delay', 3 ),
	) );
}
add_action( 'admin_enqueue_scripts', 'aatg_enqueue_dashboard_assets' );

/**
 * AJAX – generate alt text & title on demand.
 */
function aatg_generate_alt_text_ajax() {
    
    if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( 'Unauthorised', 403 );
	}

	check_ajax_referer( 'aatg_nonce', 'nonce' );

	if ( empty( $_POST['attachment_id'] ) ) {
		wp_send_json_error( 'Missing attachment ID.' );
	}

	$attachment_id = absint( $_POST['attachment_id'] );
	$result = aatg_generate_text_and_title( $attachment_id );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	$alt   = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$post  = get_post( $attachment_id );
	$title = $post->post_title;

	if ( empty( $alt ) ) {
		wp_send_json_error( 'Failed to generate alt text.' );
	} else {
		wp_send_json_success( array(
			'alt_text'    => $alt,
			'image_title' => $title,
			'warning'     => $result['warning'],
		) );
	}
}
add_action( 'wp_ajax_aatg_generate_alt_text_ajax', 'aatg_generate_alt_text_ajax' );

/* =============================================================================
   BULK UPDATE FUNCTIONALITY
============================================================================= */

/**
 * AJAX – bulk‑update alt text for images without it (configurable batch size).
 * Sends optional debug data to JS **and** appends the same info to
 * wp‑content/uploads/aatg‑logs/bulk-debug.log
 */
function aatg_bulk_update_ajax() {
	/* -------------------------------------------------- security */
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( 'Unauthorised', 403 );
	}
	check_ajax_referer( 'aatg_nonce', 'nonce' );

	/* -------------------------------------------------- helpers  */
	$base = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'fields'         => 'ids',
	);

	$batch_size = max( 1, (int) get_option( 'aatg_bulk_batch_size', 4 ) );
	$meta_query = array(
		'relation' => 'OR',
		array(
			'key'     => '_wp_attachment_image_alt',
			'compare' => 'NOT EXISTS',
		),
		array(
			'relation' => 'OR',
			array(
				'key'     => '_wp_attachment_image_alt',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_wp_attachment_image_alt',
				'value'   => '^\\s*$',
				'compare' => 'REGEXP',
			),
		),
	);

	$batch_ids = get_posts( $base + array(
		'meta_query'     => $meta_query,
		'posts_per_page' => $batch_size,
	) );

	$processed_success = 0;
	$issues    = array();
	foreach ( $batch_ids as $att_id ) {
		$result = aatg_generate_text_and_title( $att_id );
		if ( is_wp_error( $result ) ) {
			$issues[] = array(
				'attachment_id' => $att_id,
				'type'          => 'error',
				'message'       => $result->get_error_message(),
			);
		} elseif ( ! empty( $result['warning'] ) ) {
			$issues[] = array(
				'attachment_id' => $att_id,
				'type'          => 'warning',
				'message'       => $result['warning'],
			);
		}
		$processed_success++;
	}

	/* -------- remaining count: use a single query to avoid double counting -- */
	$count_query = new WP_Query( $base + array(
		'meta_query'     => $meta_query,
		'posts_per_page' => 1,
		'no_found_rows'  => false,
	) );
	$remaining = (int) $count_query->found_posts;
	wp_reset_postdata();

	/* -------- debug + response ---------------------------------- */
	$debug = array(
		'batch_ids'     => $batch_ids,
		'query_sql'     => $count_query->request,
		'batch_size'    => $batch_size,
	);

	if ( function_exists( 'aatg_write_log' ) ) {
		aatg_write_log( $debug );
	}

	wp_send_json_success( array(
		'processed' => $processed_success,
		'remaining' => $remaining,
		'debug'     => $debug,
		'issues'    => $issues,
	) );
}

add_action( 'wp_ajax_aatg_bulk_update', 'aatg_bulk_update_ajax' );


add_filter( 'pre_update_option_aatg_openai_api_key', function ( $value, $old_value ) {
    // If the submitted value is empty, keep the old one.
    return $value === '' ? $old_value : trim( $value );
}, 10, 2 );

/**
 * Media → Bulk Alt Text Update (redirects to Tools page via load-hook).
 */
function aatg_register_media_submenu_redirect() {
	$hook = add_submenu_page(
		'upload.php',
		__( 'Bulk Alt Text Update', 'ai-auto-alt-text-generator' ),
		__( 'Bulk Alt Text Update', 'ai-auto-alt-text-generator' ),
		'manage_options', // keep as-is for now
		'aatg-bulk-update-media',
		'aatg_bulk_media_redirect' // still required, but won’t run visibly
	);

	// Redirect as early as possible when this page loads.
	add_action( "load-{$hook}", function () {
		wp_safe_redirect( admin_url( 'tools.php?page=aatg-bulk-update' ) );
		exit;
	} );
}
add_action( 'admin_menu', 'aatg_register_media_submenu_redirect', 15 );

/**
 * Fallback (shouldn’t be seen thanks to the load-hook redirect).
 */
function aatg_bulk_media_redirect() {
	// In case the load-hook didn’t fire for any reason.
	wp_safe_redirect( admin_url( 'tools.php?page=aatg-bulk-update' ) );
	exit;
}


function aatg_plugin_action_links( $links ) {
	$settings_url = admin_url( 'options-general.php?page=aatg-settings' );
	$bulk_url     = admin_url( 'tools.php?page=aatg-bulk-update' );

	array_unshift(
		$links,
		sprintf( '<a href="%s">%s</a>', esc_url( $bulk_url ), esc_html__( 'Bulk Update', 'ai-auto-alt-text-generator' ) ),
		sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), esc_html__( 'Settings', 'ai-auto-alt-text-generator' ) )
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aatg_plugin_action_links' );

/**
 * Languages dropdown.
 */
function aatg_language_render() {
	$current   = get_option( 'aatg_language', 'en_US' );
	$languages = aatg_get_language_options();

	echo '<select id="aatg_language" name="aatg_language">';
	foreach ( $languages as $code => $label ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $code ),
			selected( $current, $code, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

/**
 * Map of supported languages.
 * Keys are option values; values are visible labels.
 */
function aatg_get_language_options() {
	return array(
		'en_US' => __( 'English (US)', 'ai-auto-alt-text-generator' ),
		'en_GB' => __( 'English (UK)', 'ai-auto-alt-text-generator' ),
		'es_ES' => __( 'Spanish (ES)', 'ai-auto-alt-text-generator' ),
		'es_MX' => __( 'Spanish (LATAM)', 'ai-auto-alt-text-generator' ),
		'fr_FR' => __( 'French', 'ai-auto-alt-text-generator' ),
		'de_DE' => __( 'German', 'ai-auto-alt-text-generator' ),
		'it_IT' => __( 'Italian', 'ai-auto-alt-text-generator' ),
		'pt_PT' => __( 'Portuguese (PT)', 'ai-auto-alt-text-generator' ),
		'pt_BR' => __( 'Portuguese (BR)', 'ai-auto-alt-text-generator' ),
		'nl_NL' => __( 'Dutch', 'ai-auto-alt-text-generator' ),
		'sv_SE' => __( 'Swedish', 'ai-auto-alt-text-generator' ),
		'da_DK' => __( 'Danish', 'ai-auto-alt-text-generator' ),
		'fi_FI' => __( 'Finnish', 'ai-auto-alt-text-generator' ),
		'no_NO' => __( 'Norwegian', 'ai-auto-alt-text-generator' ),
		'pl_PL' => __( 'Polish',  'ai-auto-alt-text-generator' ),
		'cs_CZ' => __( 'Czech',   'ai-auto-alt-text-generator' ),
		'tr_TR' => __( 'Turkish', 'ai-auto-alt-text-generator' ),
		'ru_RU' => __( 'Russian', 'ai-auto-alt-text-generator' ),
		'ja_JP' => __( 'Japanese','ai-auto-alt-text-generator' ),
		'ko_KR' => __( 'Korean',  'ai-auto-alt-text-generator' ),
		'zh_CN' => __( 'Chinese (Simplified)',  'ai-auto-alt-text-generator' ),
		'zh_TW' => __( 'Chinese (Traditional)', 'ai-auto-alt-text-generator' ),
		'ar'    => __( 'Arabic',  'ai-auto-alt-text-generator' ),
		'hi_IN' => __( 'Hindi',   'ai-auto-alt-text-generator' ),
	);
}

/**
 * OpenAI model options.
 */
function aatg_get_model_options() {
	return array(
		'gpt-4o-mini' => __( 'GPT-4o mini (Default)', 'ai-auto-alt-text-generator' ),
		'gpt-5-mini'  => __( 'GPT 5 Mini - Higher Quality (BETA)', 'ai-auto-alt-text-generator' ),
		'gpt-5-nano'  => __( 'GPT 5 Nano - Cheaper (BETA)', 'ai-auto-alt-text-generator' ),
	);
}

/**
 * Resolve the configured model, falling back to the default if invalid.
 */
function aatg_get_selected_model() {
	$current = get_option( 'aatg_openai_model', 'gpt-4o-mini' );
	$options = aatg_get_model_options();

	if ( ! array_key_exists( $current, $options ) ) {
		return 'gpt-4o-mini';
	}

	return $current;
}

/**
 * Return a system message instructing the model to write in the selected language.
 * For en_US we return an empty string to preserve current behaviour.
 */
function aatg_language_system_message() {
	$code = get_option( 'aatg_language', 'en_US' );

	switch ( $code ) {
		case 'en_GB': return 'Write all outputs in English (UK) using British spellings.';
		case 'es_ES':
		case 'es_MX': return 'Write all outputs in Spanish.';
		case 'fr_FR': return 'Write all outputs in French.';
		case 'de_DE': return 'Write all outputs in German.';
		case 'it_IT': return 'Write all outputs in Italian.';
		case 'pt_PT': return 'Write all outputs in European Portuguese.';
		case 'pt_BR': return 'Write all outputs in Brazilian Portuguese.';
		case 'nl_NL': return 'Write all outputs in Dutch.';
		case 'sv_SE': return 'Write all outputs in Swedish.';
		case 'da_DK': return 'Write all outputs in Danish.';
		case 'fi_FI': return 'Write all outputs in Finnish.';
		case 'no_NO': return 'Write all outputs in Norwegian.';
		case 'pl_PL': return 'Write all outputs in Polish.';
		case 'cs_CZ': return 'Write all outputs in Czech.';
		case 'tr_TR': return 'Write all outputs in Turkish.';
		case 'ru_RU': return 'Write all outputs in Russian.';
		case 'ja_JP': return 'Write all outputs in Japanese.';
		case 'ko_KR': return 'Write all outputs in Korean.';
		case 'zh_CN': return 'Write all outputs in Simplified Chinese.';
		case 'zh_TW': return 'Write all outputs in Traditional Chinese.';
		case 'ar'   : return 'Write all outputs in Arabic.';
		case 'hi_IN': return 'Write all outputs in Hindi.';
		default:     return ''; // en_US or unknown → no change
	}
}

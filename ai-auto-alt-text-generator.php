<?php
/**
 * Plugin Name: AI Auto Alt Text Generator
 * Plugin URI:  https://github.com/ConnorBulmer/ai-auto-alt-text/
 * Description: Automatically generates alt text and image titles for uploaded images using OpenAI’s GPT‑4o mini vision model, improving accessibility and SEO.
 * Version:     1.16
 * Requires at least: 5.5
 * Tested up to: 6.8
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

	register_setting( 'aatg_options_group', 'aatg_image_size', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'large',
	) );

	register_setting( 'aatg_options_group', 'aatg_image_detail', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'high',
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
	'default'           => 2,          // ← new default
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

	add_settings_field(
		'aatg_openai_api_key',
		__( 'OpenAI API Key', 'ai-auto-alt-text-generator' ),
		'aatg_api_key_render',
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
		'aatg_settings_section'
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
	'aatg_settings_section'
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
	echo '<p>' . esc_html__( 'Enter your API key, choose the image size, select the image detail quality, decide whether to send the image file name for extra context, provide optional site context, and choose whether to automatically generate an image title.', 'ai-auto-alt-text-generator' ) . '</p>';
}

/* ---------- individual field render callbacks ---------- */

function aatg_api_key_render() {
	$api_key = get_option( 'aatg_openai_api_key', '' );
	printf(
		'<input type="text" id="aatg_api_key" name="aatg_openai_api_key" value="%s" class="regular-text ltr" />',
		esc_attr( $api_key )
	);
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
	$current = get_option( 'aatg_image_detail', 'high' );
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
	$delay = get_option( 'aatg_bulk_delay', 2 );
	printf(
		'<input type="number" min="0" id="aatg_bulk_delay" name="aatg_bulk_delay" value="%d" style="width:70px;" />',
		(int) $delay
	);
	echo ' <span class="description">' .
	     esc_html__( 'Seconds to wait between each five‑image batch during a bulk run.', 'ai-auto-alt-text-generator' ) .
	     '</span>';
}


/* ---------- page renderers ---------- */

function aatg_render_settings_page() { ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Alt Text Generator Settings', 'ai-auto-alt-text-generator' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'aatg_options_group' );
			do_settings_sections( 'aatg-settings' );
			submit_button();

			// NEW: quick link to the bulk tool
			printf(
				'<p><a class="button button-secondary" href="%s">%s</a></p>',
				esc_url( admin_url( 'tools.php?page=aatg-bulk-update' ) ),
				esc_html__( 'Go to Bulk Alt Text Update', 'ai-auto-alt-text-generator' )
			);
			?>
		</form>
	</div>
<?php }


function aatg_render_bulk_page() { ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bulk Alt Text Update', 'ai-auto-alt-text-generator' ); ?></h1>

		<?php
		// Calculate the delay and output the explanatory paragraph.
		$delay = (int) get_option( 'aatg_bulk_delay', 2 );

		printf(
			'<p>%s</p>',
			sprintf(
				/* translators: %d = seconds */
				esc_html__(
					'This tool processes images without alt text in batches of five, pausing %d seconds between batches.',
					'ai-auto-alt-text-generator'
				),
				$delay
			)
		);
		?>

		<button id="aatg-bulk-start" class="button button-primary">
			<?php esc_html_e( 'Start Bulk Update', 'ai-auto-alt-text-generator' ); ?>
		</button>

		<!-- Progress bar container (hidden until start) -->
		<div id="aatg-bulk-progress-container"
		     style="text-align:center; margin-top:20px; display:none;">
			<progress id="aatg-bulk-progress" value="0" max="100" style="width:100%;"></progress>
			<div id="aatg-bulk-progress-text" style="margin-top:5px; font-weight:bold;"></div>
		</div>

		<div id="aatg-bulk-status" style="margin-top:20px;"></div>
	</div>
<?php }


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
		return;
	}

	$size       = get_option( 'aatg_image_size', 'large' );
	$image_data = wp_get_attachment_image_src( $post_ID, $size );
	if ( ! $image_data || empty( $image_data[0] ) ) {
		return;
	}

	$image_url = $image_data[0];
	$api_key   = get_option( 'aatg_openai_api_key' );
	if ( empty( $api_key ) ) {
		return;
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
					'detail' => get_option( 'aatg_image_detail', 'high' ),
				),
			),
		),
	);

	$payload = array(
		'model'    => 'gpt-4o-mini',
		'messages' => $messages,
	);

	$args = array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		'body'    => wp_json_encode( $payload ),
		'timeout' => 15,
	);

	$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
	if ( is_wp_error( $response ) ) {
		error_log( 'OpenAI API error: ' . $response->get_error_message() );
		return;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
		$alt_text = sanitize_text_field( $data['choices'][0]['message']['content'] );
		update_post_meta( $post_ID, '_wp_attachment_image_alt', $alt_text );
	} else {
		error_log( 'OpenAI API returned unexpected response: ' . $body );
	}
}


/**
 * Generate an image title.
 */
function aatg_generate_image_title( $post_ID ) {

	if ( ! wp_attachment_is_image( $post_ID ) ) {
		return;
	}

	$size       = get_option( 'aatg_image_size', 'large' );
	$image_data = wp_get_attachment_image_src( $post_ID, $size );
	if ( ! $image_data || empty( $image_data[0] ) ) {
		return;
	}

	$image_url = $image_data[0];
	$api_key   = get_option( 'aatg_openai_api_key' );
	if ( empty( $api_key ) ) {
		return;
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
					'detail' => get_option( 'aatg_image_detail', 'high' ),
				),
			),
		),
	);

	$payload = array(
		'model'    => 'gpt-4o-mini',
		'messages' => $messages,
	);

	$args = array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		'body'    => wp_json_encode( $payload ),
		'timeout' => 15,
	);

	$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
	if ( is_wp_error( $response ) ) {
		error_log( 'OpenAI API error (title): ' . $response->get_error_message() );
		return;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
		$title_text = sanitize_text_field( $data['choices'][0]['message']['content'] );

		wp_update_post( array(
			'ID'         => $post_ID,
			'post_title' => $title_text,
		) );
	} else {
		error_log( 'OpenAI API returned unexpected response for title: ' . $body );
	}
}

/**
 * On upload: generate alt text, then (optionally) title.
 */
function aatg_generate_text_and_title( $post_ID ) {
	aatg_generate_alt_text( $post_ID );
	if ( get_option( 'aatg_auto_title', 'on' ) === 'on' ) {
		aatg_generate_image_title( $post_ID );
	}
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
 * Enqueue JS only on the bulk‑update page.
 */
function aatg_enqueue_bulk_script() {

	if ( isset( $_GET['page'] ) && $_GET['page'] === 'aatg-bulk-update' ) {

		wp_enqueue_script(
			'aatg-bulk-script',
			plugin_dir_url( __FILE__ ) . 'aatg-bulk.js',
			array( 'jquery' ),
			'1.2',           // bump to clear browser cache
			true
		);

		wp_localize_script( 'aatg-bulk-script', 'aatg_bulk_ajax', array(
	'ajax_url' => admin_url( 'admin-ajax.php' ),
	'nonce'    => wp_create_nonce( 'aatg_nonce' ),
	'delay'    => (int) get_option( 'aatg_bulk_delay', 2 ),
) );

	}
}
add_action( 'admin_enqueue_scripts', 'aatg_enqueue_bulk_script' );

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
	aatg_generate_text_and_title( $attachment_id );

	$alt   = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$post  = get_post( $attachment_id );
	$title = $post->post_title;

	if ( empty( $alt ) ) {
		wp_send_json_error( 'Failed to generate alt text.' );
	} else {
		wp_send_json_success( array(
			'alt_text'    => $alt,
			'image_title' => $title,
		) );
	}
}
add_action( 'wp_ajax_aatg_generate_alt_text_ajax', 'aatg_generate_alt_text_ajax' );

/* =============================================================================
   BULK UPDATE FUNCTIONALITY
============================================================================= */

/**
 * AJAX – bulk‑update alt text for images without it (five per batch).
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

	/* -------- first query: meta key completely missing ---------- */
	$ids_no_meta = get_posts( $base + array(
		'meta_query'     => array(
			array(
				'key'     => '_wp_attachment_image_alt',
				'compare' => 'NOT EXISTS',
			),
		),
		'posts_per_page' => 5,          // we only need up to 5 per pass
	) );

	/* -------- second query: key exists but value blank/whitespace */
	$ids_blank = get_posts( $base + array(
		'meta_query'     => array(
			array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '^\s*$',
					'compare' => 'REGEXP',
				),
			),
		),
		'posts_per_page' => 5,
	) );

	/* -------- merge and take up to five unique IDs -------------- */
	$batch_ids = array_slice( array_unique( array_merge( $ids_no_meta, $ids_blank ) ), 0, 5 );

	$processed = 0;
	foreach ( $batch_ids as $att_id ) {
		aatg_generate_text_and_title( $att_id );
		$processed++;
	}

	/* -------- remaining count: run both queries with found_rows -- */
	$count_no_meta = new WP_Query( $base + array(
		'meta_query'     => array(
			array(
				'key'     => '_wp_attachment_image_alt',
				'compare' => 'NOT EXISTS',
			),
		),
		'posts_per_page' => 1,
		'no_found_rows'  => false,
	) );
	$count_blank    = new WP_Query( $base + array(
		'meta_query'     => array(
			array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '^\s*$',
					'compare' => 'REGEXP',
				),
			),
		),
		'posts_per_page' => 1,
		'no_found_rows'  => false,
	) );
	$remaining = (int) $count_no_meta->found_posts + (int) $count_blank->found_posts;
	wp_reset_postdata();

	/* -------- debug + response ---------------------------------- */
	$debug = array(
		'batch_ids'     => $batch_ids,
		'no_meta_sql'   => $count_no_meta->request,
		'blank_sql'     => $count_blank->request,
	);

	if ( function_exists( 'aatg_write_log' ) ) {
		aatg_write_log( $debug );
	}

	wp_send_json_success( array(
		'processed' => $processed,
		'remaining' => $remaining,
		'debug'     => $debug,
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

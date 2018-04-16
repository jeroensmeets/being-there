<?php
/**
 * Plugin Name: Being There
 * Plugin URI: https://jeroensmeets.com
 * Description: Organize the administration of your choir in WordPress
 * Version: 0.1
 * Author: Jeroen Smeets
 * Author URI: https://jeroensmeets.com
 * License:     GNU General Public License v2.0 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: being-there
 * Domain Path: /languages/
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'beingThere' ) ) {

	class beingThere {

		/**
		 * Initialize the plugin and register hooks
		 * @since 0.1
		 */
		public function __construct() {

			define( 'BEINGTHERE_VERSION', '0.2' );
			define( 'BEINGTHERE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'BEINGTHERE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
			define( 'BEINGTHERE_PLUGIN_FNAME', plugin_basename( __FILE__ ) );
			define( 'BEINGTHERE_CHECKBOX_PREFIX', 'beingtherepresence-' );

			define( 'BEINGTHERE_STATUS_PRESENT', 'yes' );
			define( 'BEINGTHERE_STATUS_ABSENT', 'no' );
			define( 'BEINGTHERE_STATUS_PRESENT_LABEL', __( 'yes', 'being-there' ) );
			define( 'BEINGTHERE_STATUS_ABSENT_LABEL', __( 'no', 'being-there' ) );

			// load text domain
			load_plugin_textdomain( 'being-there', false, plugin_basename( dirname( __FILE__ ) ) . "/languages/" );

			// add custom roles
			add_action( 'init', array( $this, 'add_roles' ) );

			// load custom post types
			add_action( 'init', array( $this, 'add_cpts' ) );

			// add shortcodes
			add_action( 'init', array( $this, 'add_shortcodes' ) );

			// add columns to admin lists
			add_filter( 'manage_celebration_posts_columns', array( $this, 'modify_admin_overview_table' ) );
			add_filter( 'manage_rehearsal_posts_columns', array( $this, 'modify_admin_overview_table' ) );

			// add info to admin columns
			add_filter( 'manage_celebration_posts_custom_column', array( $this, 'modify_admin_overview_table_row' ), 10, 2 );
			add_filter( 'manage_rehearsal_posts_custom_column', array( $this, 'modify_admin_overview_table_row' ), 10, 2 );
			
			// TODO make this play nice with wordpress
			if ( array_key_exists( 'beingtherepresence', $_POST ) && ( '1' == $_POST['beingtherepresence'] ) ) {
				add_action( 'init', array( $this, 'process_presence_form' ) );
			}

			// flush the rewrite rules for the custom post types
			register_activation_hook( __FILE__, array( $this, 'rewrite_flush' ) );

			// enqueue scripts and styles
			// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		}

		/**
		 * Enqueue frontend scripts and styles
		 * @since 0.1
		 */
		public function register_assets() {

			// enqueue styles
			wp_enqueue_style( 'being-there-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), date( 'dMYHi' ) );

			// use dash icons in front end
			wp_enqueue_style( 'dashicons' );

		}

		/**
		 * Add a role for choir members
		 * @since 0.1
		 */
		public function add_roles() {

			$booking_manager = add_role(
				'beingthere_member',
				__( 'Choir member', 'being-there' ),
				array(
					'read'         => true,
					'edit_posts'   => false,
					'delete_posts' => false
				)
			);

		}

		/**
		 * Add custom post types
		 * @since 0.1
		 */
		public function add_cpts() {

			// Define the celebration custom post type
			$args = array(
				'labels' => array(
					'name'               => __( 'Celebrations',						'being-there' ),
					'singular_name'      => __( 'Celebration',						'being-there' ),
					'menu_name'          => __( 'Celebrations',						'being-there' ),
					'name_admin_bar'     => __( 'Celebrations',						'being-there' ),
					'add_new'            => __( 'Add New',							'being-there' ),
					'add_new_item'       => __( 'Add New Celebration',				'being-there' ),
					'edit_item'          => __( 'Edit Celebration',					'being-there' ),
					'new_item'           => __( 'New Celebration',					'being-there' ),
					'view_item'          => __( 'View Celebration',					'being-there' ),
					'search_items'       => __( 'Search Celebration',				'being-there' ),
					'not_found'          => __( 'No celebrations found',			'being-there' ),
					'not_found_in_trash' => __( 'No celebrations found in trash',	'being-there' ),
					'all_items'          => __( 'All Celebrations',					'being-there' )
				),
				'menu_icon' => 'dashicons-edit',
				'public' => true,
				'supports' => array(
					'title',
					'thumbnail',
					'editor',
					'revisions'
				)
			);

			// Create filter so addons can modify the arguments
			$args = apply_filters( 'beingthere_celebration_args', $args );

			// Add an action so addons can hook in before the post type is registered
			do_action( 'beingthere_celebration_pre_register' );

			// Register the post type
			register_post_type( 'celebration', $args );

			// Add an action so addons can hook in after the post type is registered
			do_action( 'beingthere_celebration_post_register' );

			// Define the rehearsal custom post type
			$args = array(
				'labels' => array(
					'name'               => __( 'Rehearsals',						'being-there' ),
					'singular_name'      => __( 'Rehearsal',						'being-there' ),
					'menu_name'          => __( 'Rehearsals',						'being-there' ),
					'name_admin_bar'     => __( 'Rehearsals',						'being-there' ),
					'add_new'            => __( 'Add New',							'being-there' ),
					'add_new_item'       => __( 'Add New Rehearsal',				'being-there' ),
					'edit_item'          => __( 'Edit Rehearsal',					'being-there' ),
					'new_item'           => __( 'New Rehearsal',					'being-there' ),
					'view_item'          => __( 'View Rehearsal',					'being-there' ),
					'search_items'       => __( 'Search Rehearsal',					'being-there' ),
					'not_found'          => __( 'No rehearsals found',				'being-there' ),
					'not_found_in_trash' => __( 'No rehearsals found in trash',		'being-there' ),
					'all_items'          => __( 'All Rehearsals',					'being-there' )
				),
				'menu_icon' => 'dashicons-edit',
				'public' => true,
				'supports' => array(
					'title',
					'thumbnail',
					'editor',
					'revisions'
				)
			);

			// Create filter so addons can modify the arguments
			$args = apply_filters( 'beingthere_rehearsal_args', $args );

			// Add an action so addons can hook in before the post type is registered
			do_action( 'beingthere_rehearsal_pre_register' );

			// Register the post type
			register_post_type( 'rehearsal', $args );

			// Add an action so addons can hook in after the post type is registered
			do_action( 'beingthere_rehearsal_post_register' );

			// meta boxes
			add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ) );

		}

		/**
		 * Register meta boxes
		 * @since 0.1
		 */
		public function register_meta_boxes() {
			add_meta_box( 'beingthere-datetime-metabox', __( 'Date and time', 'being-there' ), array( $this, 'meta_boxes_callback' ), 'celebration' );
			add_meta_box( 'beingthere-datetime-metabox', __( 'Date and time', 'being-there' ), array( $this, 'meta_boxes_callback' ), 'rehearsal' );
		}

		/**
		 * Meta box display callback.
		 * @since 0.1
		 *
		 * @param WP_Post $post Current post object.
		 *
		 */
		public function meta_boxes_callback( $post ) {

			// Nonce field
			wp_nonce_field('being_there_datetime', 'being_there_datetime_nonce');

			$_date = get_post_meta( $post->ID, 'beingthere-date', true );
			$_time = get_post_meta( $post->ID, 'beingthere-time', true );
			
	?>
			<table>
				<tr>
					<th width="120" style="text-align: left;"><label for="beingtheredate"><?php echo __( 'Date', 'being-there' ); ?></label></th>
					<td><p class="wp-core-ui">
						<input type="date" id="beingtheredate" name="beingtheredate" value="<?php echo $_date; ?>" />
					</p></td>
				</tr>
				<tr>
					<th style="text-align: left;"><label for="beingtheretime"><?php echo __( 'Time', 'being-there' ); ?></label></th>
					<td><p class="wp-core-ui">
						<input type="time" id="beingtheretime" name="beingtheretime" value="<?php echo $_time; ?>" />
					</p></td>
				</tr>
			</table>
	<?php
		}

		/**
		 * Save meta box content.
		 * @since 0.1
		 *
		 * @param int $post_id Post ID
		 */
		public function save_meta_box( $post_id ) {

			// Autosave? Do nuttin'.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// verify nonce
			if ( ! isset( $_POST['being_there_datetime_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['being_there_datetime_nonce'], 'being_there_datetime' ) ) {
				return;
			}


			// check the user's permissions
			if ( ! current_user_can( 'delete_others_pages', $post_id ) ) {
				return;
			}

			update_post_meta( $post_id, 'beingthere-date', sanitize_text_field( $_POST['beingtheredate'] ) );
			update_post_meta( $post_id, 'beingthere-time', sanitize_text_field( $_POST['beingtheretime'] ) );

		}

		/**
		 * Add shortcodes
		 * @since 0.1
		 */
		public function add_shortcodes() {

			add_shortcode( 'beingthere_presence', array( $this, 'show_presence_form' ) );

		}

		/**
		 * Process presence form
		 * @since 0.1
		 */
		public function process_presence_form() {

			global $current_user;

			foreach ( array( 'celebration', 'rehearsal' ) as $_posttype ) {

				$dates = get_posts( array( 'post_type' => array( $_posttype ), 'post_status' => 'publish' ) );

				foreach( $dates as $date ) {

					// in POST data?
					if ( array_key_exists( BEINGTHERE_CHECKBOX_PREFIX . $date->ID, $_POST ) ) {

						$presence = $_POST[ BEINGTHERE_CHECKBOX_PREFIX . $date->ID ];
						update_user_meta( $current_user->ID, 'beingthere_user_presence-' . $date->ID, $presence );

					} else {

						delete_user_meta( $current_user->ID, 'beingthere_user_presence-' . $date->ID );

					}

				}
				
			}

		}


		public function show_presence_form() {

			// only when user is logged in
			if ( ! current_user_can( 'read' ) ) {
				echo __( 'You must be logged in to view this page.', 'being-there' );

				$parts = parse_url( home_url() );
				$current_uri = "{$parts['scheme']}://{$parts['host']}" . add_query_arg( NULL, NULL );

				wp_login_form( array(
					'redirect'       => $current_uri,
					'label_username' => __( 'Username', 'being-there' ),
					'label_password' => __( 'Password', 'being-there' ),
					'label_remember' => __( 'Remember Me', 'being-there' ),
					'label_log_in'   => __( 'Log In', 'being-there' )
				) );
				return;
			}

?>
				<form method="post" id="wp-choir-presence-form">
					<input type="hidden" name="beingtherepresence" value="1" />
<?php

			foreach ( array( 'celebration', 'rehearsal' ) as $_posttype ) {

				$dates = new WP_Query(
					array(
						'post_type' => array( $_posttype ),
						'post_status' => 'publish',
						'meta_key' => 'beingthere-date',
						'orderby' => 'meta_value',
						'order' => 'ASC',
						'meta_query' => array(
							array(
								'key' => 'beingthere-date',
								'value' => date("Y-m-d"),
								'compare' => '>=',
								'type' => 'DATE'
							)
						)
					)
				);

				echo '<h2>' . __( ucfirst( $_posttype ), 'being-there' ) . '</h2>';

				if ( $dates->have_posts() ) {

					echo '<table class="beingthere-presence-dates" border="0" cellspacing="0" cellpadding="0">';

					// get saved info
					global $current_user;

					while ( $dates->have_posts() ) {
						$dates->the_post();

						$choicesaved = get_user_meta( $current_user->ID, 'beingthere_user_presence-' . get_the_id(), true );

						$_date = strtotime( get_post_meta( get_the_ID(), 'beingthere-date', true ) );
						$_time = get_post_meta( get_the_ID(), 'beingthere-time', true );

						$_fieldname = BEINGTHERE_CHECKBOX_PREFIX . get_the_id();
?>
						<tr class="<?php echo get_post_type(); ?>">
							<td class="switch-field">
								<input type="radio" id="<?php echo $_fieldname; ?>_yes" 
									name="<?php echo $_fieldname; ?>" value="<?php echo BEINGTHERE_STATUS_PRESENT; ?>"
									<?php checked( $choicesaved, BEINGTHERE_STATUS_PRESENT ); ?>
								/>
								<label for="<?php echo $_fieldname; ?>_yes"><?php echo BEINGTHERE_STATUS_PRESENT_LABEL; ?></label>
								<input type="radio" id="<?php echo $_fieldname; ?>_no" 
									name="<?php echo $_fieldname; ?>" value="<?php echo BEINGTHERE_STATUS_ABSENT; ?>" 
									<?php checked( $choicesaved, BEINGTHERE_STATUS_ABSENT ); ?>
								/>
								<label for="<?php echo $_fieldname; ?>_no"><?php echo BEINGTHERE_STATUS_ABSENT_LABEL; ?></label>
							</td>
							<td><?php echo date_i18n("j F Y", $_date ); ?></td>
							<td><?php echo $_time; ?></td>
							<td><?php echo get_the_title(); ?></td>
						</tr>
						<tr>
							<td />
							<td colspan="3"><i><?php the_content(); ?></i></td>
						</tr>
<?php
					}
					echo '</table>';

				} else {

					echo '<p><i>' . __( 'No dates found', 'being-there' ) . '</i></p>';

				}

				wp_reset_postdata();

			}
?>

					<input type="submit" style="padding: 7px 12px; background-color: #8958B5; border: 0; color: white; font-size: 15px;" value="<?php echo __( 'Save rehearsal presence', 'being-there' ); ?>" />
				</form>

<?php
		}


		/**
		 * Enqueue the admin-only CSS and Javascript
		 * @since 0.1
		 */
		public function enqueue_admin_assets() {

		}

		// add columns to admin lists
		public function modify_admin_overview_table( $columns ) {

			unset( $columns[ 'date' ] );

			return array_merge(
				$columns, 
				array(
					'beingthere_date' => __( 'Datum', 'being-there' ),
					'attendance' =>__( 'Aantal aanwezig', 'being-there' )
				)
			);

		}

		// add info to admin lists
		public function modify_admin_overview_table_row( $column_name, $post_id ) {
	
			switch ($column_name) {
				case 'beingthere_date':
					$_date = strtotime( get_post_meta( $post_id, 'beingthere-date', true ) );
					$_date = date_i18n("j F Y", $_date );
					echo $_date . ' (' . get_post_meta( $post_id, 'beingthere-time', true ) . ')';
					break;
				case 'attendance':
					global $wpdb;
					$_metakey = 'beingthere_user_presence-' . $post_id;
					$attendance = $wpdb->get_results(
						"SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = '" . $_metakey . "'",
						ARRAY_N
					);
					$totals = array(
						BEINGTHERE_STATUS_PRESENT => 0,
						BEINGTHERE_STATUS_ABSENT => 0
					);
					foreach( $attendance as $att ) {
						$choicesaved = ( is_array( $att ) ) ? array_shift( $att ) : $att;
						$totals[ $choicesaved ]++;
					}
					echo 'komt niet: <b>' . $totals[ BEINGTHERE_STATUS_ABSENT ] . '</b><br />'
						. 'komt wel: <b>' . $totals[ BEINGTHERE_STATUS_PRESENT ] . '</b>';

					break;
			}
		}

	} // Class definition

} // endif;

global $beingthere_controller;
$beingthere_controller = new beingThere();

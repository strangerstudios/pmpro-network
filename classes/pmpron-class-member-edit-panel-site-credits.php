<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPron_Member_Edit_Panel_Site_Credits extends PMPro_Member_Edit_Panel {

	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'site-credits';
		$this->title = __( 'Site Credits', 'pmpro-network' );
	}

	/**
	 * Display the panel contents.
	 *
	 * @since 0.6
	 */
	protected function display_panel_contents() {
		// Bail if user can't manage the network.
		if ( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		// Get the user being edited.
		$user = self::get_user();

		// Get the user's site credits.
		$all_blog_ids = pmpron_getBlogsForUser( $user->ID );
		$num = count( $all_blog_ids );
		$site_credits = $user->pmpron_site_credits;
		?>
		<table class="form-table">
			<tr>
				<th><label for="site_credits"><?php esc_html_e( 'Site Credits', 'pmpro-network' ); ?></label></th>
				<td>
					<input type="number" id="site_credits" name="site_credits" size="5" value="<?php echo esc_attr( $site_credits ); ?>" />
					<p class="description"><?php echo esc_html( sprintf( __( 'currently using %s', 'pmpro-network' ), $num ) ); ?></p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Update', 'pmpro-network' ); ?></button>
		</p>
		<?php if ( $num > 0 ) { ?>
			<h3><?php esc_html_e( 'Sites', 'pmpro-network' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Site', 'pmpro-network' ); ?></th>
						<th><?php esc_html_e( 'Address', 'pmpro-network' ); ?></th>
						<th><?php esc_html_e( 'Registered', 'pmpro-network' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'pmpro-network' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$date_format = get_option( 'date_format' );
					foreach ( $all_blog_ids as $blog_id ) {
						$blog_details = get_blog_details( $blog_id );
						if ( empty( $blog_details ) ) {
							continue;
						}
						$blog_address = get_blogaddress_by_id( $blog_id );
						$blog_name    = get_blog_option( $blog_id, 'blogname' );
						?>
						<tr>
							<td><?php echo esc_html( $blog_name ); ?></td>
							<td><a href="<?php echo esc_url( $blog_address ); ?>"><?php echo esc_html( $blog_address ); ?></a></td>
							<td><?php echo esc_html( mysql2date( $date_format, $blog_details->registered ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $blog_address ); ?>"><?php esc_html_e( 'Visit', 'pmpro-network' ); ?></a>
								|
								<a href="<?php echo esc_url( get_admin_url( $blog_id ) ); ?>"><?php esc_html_e( 'Dashboard', 'pmpro-network' ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		<?php }
	}

	/**
	 * Save the panel.
	 *
	 * @since 0.6
	 */
	public function save() {
		// Bail if user can't manage the network.
		if ( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		// Get the user being edited.
		$user = self::get_user();

		// Get the site credits.
		$site_credits = isset( $_POST['site_credits'] ) ? intval( $_POST['site_credits'] ) : 0;

		// Update the user's site credits.
		update_user_meta( $user->ID, 'pmpron_site_credits', $site_credits );

		// Show a success message.
		pmpro_setMessage( __( 'Site credits updated.', 'pmpro-network' ), 'pmpro_success' );
	}
}

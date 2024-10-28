<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Class PMPro_N_fields_update
 */
class PMPro_N_fields_update  extends PMPro_Member_Edit_Panel {

	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'pmpro-network';
		$this->title = __( 'Site Credits', 'pmpro-network' );
	}

	/**
	 * Display the panel contents.
	 *
	 * @since TBD
	 * return void
	 */
	protected function display_panel_contents() {
		// Get the user being edited.
		$user = self::get_user();

		//Bail if user can't manage network
		if( ! current_user_can( 'manage_network' ) ) {
			return;
		}

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
	<?php
	}

	/**
	 * Save the panel.
	 *
	 * @since TBD
	 * return void
	 */
	public function save() {
		// Get the user being edited.
		$user = self::get_user();

		//Bail if user can't manage network
		if( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		// Get the site credits.
		$site_credits = isset( $_POST['site_credits'] ) ? intval( $_POST['site_credits'] ) : 0;

		// Update the user's site credits.
		update_user_meta( $user->ID, 'pmpron_site_credits', $site_credits );
		//Show a success message
		pmpro_setMessage( __( 'Site credits updated.', 'pmpro-network' ), 'pmpro_success' );
	}
}
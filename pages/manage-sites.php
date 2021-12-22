<?php  	
/*
	Preheader
*/
function pmpron_manage_sites_preheader() {
	if(!is_admin()) {
		global $post, $current_user;
		if(!empty($post->post_content) && strpos($post->post_content, "[pmpron_manage_sites]") !== false) {
			/*
				Preheader operations here.
			*/

			//make sure they have site credits
			global $current_user;
			$credits = $current_user->pmpron_site_credits;

			if(empty($credits)) {
				//redirect to levels
				wp_redirect(pmpro_url("levels"));
				exit;
			}
		}
	}
}
add_action("wp", "pmpron_manage_sites_preheader", 1);

/*
	Shortcode Wrapper
*/
function pmpron_manage_sites_shortcode($atts, $content=null, $code="") {
	ob_start();

	global $current_user, $pmpro_msg, $pmpro_msgt;

	//adding a site?
	if(!empty($_POST['addsite'])) {
		$sitename = $_REQUEST['sitename'];
		$sitetitle = $_REQUEST['sitetitle'];
		if(pmpron_checkSiteName( $sitename, $sitetitle )) {
			$blog_id = pmpron_addSite($sitename, $sitetitle);
			if(is_wp_error($blog_id)) {
				$pmpro_msg = __( 'Error creating site.', 'pmpro-network' );
				$pmpro_msgt = "pmpro_error";
			} else {
				$pmpro_msg = __( 'Your site has been created.', 'pmpro-network' );
				$pmpro_msgt = "pmpro_success";
			}
		} else {
			//error message shown below
		}
	} else {
		//default values for form
		$sitename = '';
		$sitetitle = '';
	}

	//show page
	$blog_ids = pmpron_getBlogsForUser($current_user->ID);

	?>
	<?php if(!empty($pmpro_msg)) { ?>
		<div class="pmpro_message <?php echo $pmpro_msgt;?>"><?php echo $pmpro_msg;?></div>
	<?php } ?>
	<div class="pmpro_message <?php if( count($blog_ids) >= intval($current_user->pmpron_site_credits) ) { ?>pmpro_error<?php } ?>">
		<?php if( count($blog_ids) >= intval($current_user->pmpron_site_credits) ) { ?>
			<?php echo esc_html( sprintf( __( 'You have used %s of %s site credits.', 'pmpro-network' ), intval($current_user->pmpron_site_credits), intval($current_user->pmpron_site_credits) ) ); ?>

			<?php if(count($blog_ids) > intval($current_user->pmpron_site_credits)) { ?>
				<?php echo esc_html( sprintf( __( '%s of your sites have been deactivated.', 'pmpro-network' ), count($blog_ids) - intval($current_user->pmpron_site_credits) ) ); ?>

			<?php } ?>
		<?php } else { ?>
			<?php echo esc_html( sprintf( __( 'You have used %s of %s site credits.', 'pmpro-network' ), count($blog_ids), intval($current_user->pmpron_site_credits) ) ); ?>

		<?php } ?>
	</div>

	<?php if($current_user->pmpron_site_credits > count($blog_ids)) { ?>
		<div class="pmpro_network_add_site">
			<form id="pmpro_add_site" class="pmpro_form" action="" method="post">
				<div class="pmpro_checkout">
					<h3>
						<span class="pmpro_checkout-h3-name"><?php esc_html_e('Add a Site', 'pmpro-network'); ?></span>
					</h3>
					<div class="pmpro_checkout-fields">
						<div class="pmpro_checkout-field">
							<label for="sitename"><?php esc_html_e('Site Name', 'pmpro-network'); ?></label>
							<input id="sitename" name="sitename" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitename)); ?>" />
							<?php
								global $current_site;
								$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );

								if ( !is_subdomain_install() )
									$site = $current_site->domain . $current_site->path . __( 'sitename' );
								else
									$site = __( '{site name}' ) . '.' . $site_domain . $current_site->path;

								echo '<p><small class="lite"><strong>' . esc_html( sprintf( __('Your address will be %s', 'pmpro-network' ), $site ) ) . '</strong>.<br />' . __( 'Your <em>Site Name</em> must be at least 4 characters (letters/numbers only). Once your site is created the site name cannot be changed.', 'pmpro-network' ) . '</small></p>';

							?>
						</div>
						<div class="pmpro_checkout-field">
							<label for="sitetitle"><?php esc_html_e('Site Title', 'pmpro-network'); ?></label>
							<input id="sitetitle" name="sitetitle" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitetitle)); ?>" />
						</div>
						<div class="pmpro_submit">
							<input type="hidden" name="addsite" value="1" />
							<input type="submit" name="submit" value="<?php esc_attr_e( 'Add Site', 'pmpro-network' ); ?>" />

						</div>
					</div> <!-- end .pmpro_checkout-fields -->
				</div> <!-- end .pmpro_checkout -->
			</form>
		</div> <!-- end .pmpro_network_add_site -->
	<?php } ?>

	<?php if ( ! empty( $blog_ids ) ) { ?>
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e( 'Your Sites', 'pmpro-network' ); ?></span>
		</h3>
		<ul class="pmpro_network_sites">
			<?php
				foreach ( $blog_ids as $blog_id ) {
					if ( ! get_blog_details( $blog_id ) ) {
						continue;
					}

					// Build the selectors for the site based on status.
					$classes = array();
					$classes[] = "pmpro_network_site";
					if ( get_blog_status( $blog_id, 'deleted' ) ) {
						$classes[] = "pmpro_grey";
					}
					$class = implode( ' ', array_unique( $classes ) );
					?>
					<li class="<?php echo esc_attr( $class ); ?>">
						<?php if ( get_blog_status( $blog_id, "deleted" ) ) { ?>
							<strong><?php echo esc_html( get_blog_option($blog_id, 'blogname' ) ); ?></strong> <?php esc_html_e('(deactivated)', 'pmpro-network'); ?>

						<?php } else { ?>
							<strong><a href="<?php echo get_site_url( $blog_id );?>"><?php echo get_blog_option( $blog_id, 'blogname' ); ?></a></strong><br />
							<?php echo get_site_url( $blog_id ); ?>
							<div class="pmpro_actionlinks">
								<a href="<?php echo esc_url( get_site_url( $blog_id ) ); ?>"><?php esc_html_e('Visit', 'pmpro-network'); ?></a>&nbsp;|&nbsp;<a href="<?php echo esc_url( get_site_url( $blog_id, '/wp-admin/' ) ); ?>"><?php esc_html_e('Dashboard', 'pmpro-network'); ?></a>

							</div> <!-- end pmpro_actionlinks -->
						<?php } ?>
					</li>
			<?php } ?>
		</ul> <!-- end .pmpro_network_your_sites_wrap -->
	<?php } ?>
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode( 'pmpron_manage_sites', 'pmpron_manage_sites_shortcode');

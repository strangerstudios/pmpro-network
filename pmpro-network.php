<?php
/*
Plugin Name: Paid Memberships Pro - Member Network Sites Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-network-multisite-membership/
Description: Create a network site for the member as part of membership to the main site.
Version: .5.1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-network
Domain Path: /languages
*/

/**
 * Load the languages folder for translations.
 */
function pmpron_load_textdomain() {
	load_plugin_textdomain( 'pmpro-network', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpron_load_textdomain' );

/**
 * Get the number of site credits for a given level.
 */
function pmpron_getSiteCredits($level_id) {
	global $current_user;
	
	$option = get_option('pmpron_site_credits_' . $level_id, null);
	$site_credits = apply_filters( 'pmpron_site_credits', $option, $current_user->ID, $level_id);
	
	/*
		Backwards compatibility. If there are site credits for a level, but the option isn't saved,
		let's save this value (which is being set by the filter) in options.
	*/
	if(!empty($site_credits) && $option === null) {
		update_option( 'pmpron_site_credits_' . $level_id, $site_credits, 'no');
	}
	
	return $site_credits;
}
 

/*
 * Include Manage Sites page template
 * Update $pmpro_network_non_site_levels array
 */
function pmpron_init() {
	//include manage sites page template
	if ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/pages/manage-sites.php' ) ) {
		$template = get_stylesheet_directory() . '/paid-memberships-pro/pages/manage-sites.php';
	} elseif ( file_exists( get_template_directory() . '/paid-memberships-pro/pages/manage-sites.php' ) ) {
		$template = get_template_directory() . '/paid-memberships-pro/pages/manage-sites.php';
	} else {
		$template = 'pages/manage-sites.php';
	}
	require_once($template);
	
	//setup non site levels
	global $pmpro_network_non_site_levels;
	if(!is_array($pmpro_network_non_site_levels))
		$pmpro_network_non_site_levels = array();
	
	//filter for non site levels
	$pmpro_network_non_site_levels = apply_filters('pmpron_non_site_level_array', $pmpro_network_non_site_levels);
}
add_action('init', 'pmpron_init');

/*
	First we need to add some fields to the checkout page.
*/
//add the fields to the form 
function pmpron_pmpro_checkout_boxes() 
{
	global $current_user, $wpdb, $pmpro_network_non_site_levels;

	$level_id = null;

	// Get the level info
	if (isset($_REQUEST['level'])) {
		$level_id = intval($_REQUEST['level']);
	}

	// Return if requested level is in non site levels array
	if ( !empty($level_id) && !empty($pmpro_network_non_site_levels) && in_array( $level_id, $pmpro_network_non_site_levels ) )
		return;	

	// Return if site credits for this level is < 1	
	$site_credits = pmpron_getSiteCredits($level_id);
	if(intval($site_credits) < 1)
		return;
	
	if(!empty($_REQUEST['sitename']))
	{
		$sitename = $_REQUEST['sitename'];
		$sitetitle = $_REQUEST['sitetitle']; 
	}
	elseif(!empty($_SESSION['sitename']))
	{
		$sitename = $_SESSION['sitename'];
		$sitetitle = $_SESSION['sitetitle']; 
	}
    else {
        $sitename = '';
        $sitetitle = '';
    }
    ?>
	<div id="pmpro_site_fields" class="pmpro_checkout">
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e( 'Site Information', 'pmpro-network'); ?></span>
			<span class="pmpro_checkout-h3-msg"><?php echo sprintf( __( 'Sites Included: <strong>%s</strong>', 'pmpro-network' ), pmpron_getSiteCredits($level_id), 'pmpro-network'); ?></span>
		</h3>
		<div class="pmpro_checkout-fields">
			<?php
				//check if the user already has a blog
				if($current_user->ID)
				{
					$all_blog_ids = pmpron_getBlogsForUser($current_user->ID);
					$blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);					
					if(count($all_blog_ids) > 1)
					{
						$blogname = "many";
					}
					elseif($blog_id)
					{
						$user_blog = $wpdb->get_row("SELECT * FROM $wpdb->blogs WHERE blog_id = '" . $blog_id . "' LIMIT 1");						
						$blogname = get_blog_option($blog_id, "blogname");
					}
				}
				if(!empty($blogname)) {
                    if($blogname == "many")
                    {
                        ?>
                        <div class="pmpro_checkout-field">
                            <p><?php esc_html_e( 'You will be reclaiming your previous sites.', 'pmpro-network' ); ?></p>
                            <input type="hidden" name="blog_id" value="<?php echo esc_attr( $blog_id ); ?>" />

                        </div>
                    <?php
                    }
                    else
                    {
                        ?>
                        <div class="pmpro_checkout-field">
                            <p><?php echo sprintf( __( 'You will be reclaiming your site <strong>%s</strong>.', 'pmpro-network' ), $blogname ); ?></p>
                            <input type="hidden" name="blog_id" value="<?php echo esc_attr( $blog_id ); ?>" />

                        </div>
                    <?php
                    }
                }
				else
				{
				?>				
				<div class="pmpro_checkout-field pmpro_checkout-field-sitename">
					<label for="sitename"><?php esc_html_e( 'Site Name', 'pmpro-network' ); ?></label>
					<input id="sitename" name="sitename" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitename)); ?>" /><span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>				
					<?php
						global $current_site;
						$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );
					
						if ( !is_subdomain_install() )
							$site = $current_site->domain . $current_site->path . __( 'sitename' );
						else
							$site = __( '{site name}' ) . '.' . $site_domain . $current_site->path;

						echo '<p><strong>' . esc_html( sprintf( __( 'Your address will be %s', 'pmpro-network' ), $site ) ) . '</strong>.<br />' . __( 'Your <em>Site Name</em> must be at least 4 characters (letters/numbers only). Once your site is created the site name cannot be changed.', 'pmpro-network' ) . '</p>';

					?>
				</div> <!-- end pmpro_checkout-field-sitename -->
				<div class="pmpro_checkout-field pmpro_checkout-field-sitetitle">
					<label for="sitetitle"><?php esc_html_e( 'Site Title', 'pmpro-network' ); ?></label>
					<input id="sitetitle" name="sitetitle" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitetitle)); ?>" /><span class="pmpro_asterisk"> <abbr title="<?php esc_attr_e( 'Required Field', 'pmpro-network' ); ?>">*</abbr></span>

				</div> <!-- end pmpro_checkout-field-sitetitle -->
				<?php
				}
				?>
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_site_fields -->
<?php
}
add_action('pmpro_checkout_boxes', 'pmpron_pmpro_checkout_boxes');

//update the user after checkout
function pmpron_update_site_after_checkout($user_id)
{
	global $current_user, $current_site, $pmpro_network_non_site_levels;	
	
	if(isset($_REQUEST['sitename']))
	{   
		//new site, on-site checkout
		$sitename = $_REQUEST['sitename'];
		$sitetitle = $_REQUEST['sitetitle'];
		if(!empty($_REQUEST['blog_id']))
			$blog_id = intval($_REQUEST['blog_id']);
	}
	elseif(isset($_REQUEST['blog_id']))
	{
		//reclaiming, on-site checkout
		$blog_id = intval($_REQUEST['blog_id']);
	}
	elseif(isset($_SESSION['sitename']))
	{   
		//new site, off-site checkout
		$sitename = $_SESSION['sitename'];
		$sitetitle = $_SESSION['sitetitle'];
		if(!empty($_SESSION['blog_id']))
			$blog_id = intval($_SESSION['blog_id']);
	}	
	elseif(isset($_SESSION['blog_id']))
	{
		//reclaiming, off-site checkout
		$blog_id = intval($_SESSION['blog_id']);
	}
	
	$r = false;		//default return value
	$user_level = pmpro_getMembershipLevelForUser($user_id);
	
	if(!empty($blog_id))
	{
		//reclaiming, first check that this id is associated with the user	
		$all_blog_ids = pmpron_getBlogsForUser($user_id);
		if(in_array($blog_id, $all_blog_ids))
		{
			//activate the blog
			update_blog_status( $blog_id, 'deleted', '0' );
			do_action( 'activate_blog', $blog_id );
			$r = true;
		}		
		else
		{
			//uh oh, were they trying to claim someone else's blog?
			$r = new WP_Error('pmpron_reactivation_failed', __('<strong>ERROR</strong>: Site reactivation failed.'));			
		}
	}
	elseif( !empty( $user_level ) && !empty( $user_level->id ) && 
			!in_array( $user_level->id, $pmpro_network_non_site_levels ) && pmpron_getSiteCredits( $user_level->id ) > 0 )
	{
		$blog_id = pmpron_addSite($sitename, $sitetitle);
		if(is_wp_error($blog_id))
			$r = $blog_id;
	}
	
	//clear session vars
	unset($_SESSION['sitename']);
	unset($_SESSION['sitetitle']);
	unset($_SESSION['blog_id']);
	
	return $r;
}
add_action('pmpro_after_checkout', 'pmpron_update_site_after_checkout');

/*
	Set the "Manage Sites" page in Memberships > Page Setting
*/
function pmpron_extra_page_settings($pages) {
   $pages['manage_sites'] = array('title'=>'Manage Sites', 'content'=>'[pmpron_manage_sites]', 'hint'=>'Include the shortcode [pmpron_manage_sites].');
   return $pages;
}
add_action('pmpro_extra_page_settings', 'pmpron_extra_page_settings');

/**
 * Get the post object for the "Manage Sites" page.
 *
 * @since 0.5.2
 *
 * @return null|WP_Post The WP_Post object for the manage sites post or null if not set/found.
 */
function pmpron_get_manage_sites_post() {
	global $pmpro_pages;

	// If the Manage Sites page is set in the global variable, return it.
	$manage_post = $pmpro_pages['manage_sites'];
	if ( ! empty( $manage_post ) ) {
		return get_post( $manage_post );
	}

	// Check if the Manage Sites page is defined (this was an old way we offered before the UI).
	if ( defined( 'PMPRO_NETWORK_MANAGE_SITES_SLUG' ) ) {
		//check if the manage sites slug is defined.
		return get_page_by_path( PMPRO_NETWORK_MANAGE_SITES_SLUG );
	}

	return null;
}


/*
	Add "Manage Sites" link to Member Links
*/
function pmpron_pmpro_member_links_top() {	
	global $current_user;

	// Get the current user's number of site credits.
	$site_credits = $current_user->pmpron_site_credits;
	
	// Get the manage sites post object.
	$manage_post = pmpron_get_manage_sites_post();

	// Don't show a link if the "Manage Sites" page is the "Membership Account" page.
	global $pmpro_pages;
	if ( $manage_post === $pmpro_pages['account'] || $manage_post->ID == $pmpro_pages['account'] ) {
		return;
	}

	// Show a link to manage sites if the member has credits and the page exists.
	if ( ! empty( $site_credits ) && ! empty( $manage_post ) ) { ?>
		<li><a href="<?php echo esc_url( get_permalink( $manage_post ) ); ?>"><?php esc_html_e( get_the_title( $manage_post ) ); ?></a></li>
	<?php }
}
add_filter( 'pmpro_member_links_top', 'pmpron_pmpro_member_links_top' );

/*
	Save the "Site Credits" field on the Edit Membership Level page
*/
function pmpron_pmpro_save_membership_level( $level_id ) {
	if(isset($_REQUEST['pmpro_site_credits'])) {
		$pmpro_site_credits = intval($_REQUEST['pmpro_site_credits']);
	} else {
		$pmpro_site_credits = 0;
	}
	update_option('pmpron_site_credits_' . $level_id, $pmpro_site_credits, 'no');
}
add_action( 'pmpro_save_membership_level', 'pmpron_pmpro_save_membership_level' );

//Display the setting for the number of site credits on the Edit Membership Level page
function pmpron_pmpro_membership_level_after_other_settings() {
	$level_id = intval($_REQUEST['edit']);
	if($level_id > 0) {
		//want to specifically get the value from options here
		$pmpro_site_credits = get_option('pmpron_site_credits_' . $level_id, null);
		//check if there is something set via filter
		if($pmpro_site_credits === null) {
			$pmpro_site_credits = pmpron_getSiteCredits($level_id);
		}
	} else {
		$pmpro_site_credits = '';
	}
	?>
	<h3 class="topborder"><?php esc_html_e( 'Site Credits', 'pmpro-network' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="pmpro_show_level"><?php esc_html_e( 'Number of Sites', 'pmpro-network' );?>:</label></th>
				<td>
					<input type="text" id="pmpro_site_credits" name="pmpro_site_credits" value="<?php echo $pmpro_site_credits; ?>" size="5" />
					<p class="description"><?php esc_html_e( 'Set the number of sites members of this level will be allowed to create as part of membership. Numerical values only (no letters or special characters).', 'pmpro-network'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php 
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpron_pmpro_membership_level_after_other_settings' );

/*
	Function to add a site.
	Takes sitename and sitetitle
	Returns blog_id
*/
function pmpron_addSite($sitename, $sitetitle)
{
	global $current_user, $current_site;
		
	//figure out the new domain	
	$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );

	if ( !is_subdomain_install() )
	{
		$site = $current_site->domain;
		$path = $current_site->path . $sitename;
	}
	else
	{
		$site = $sitename . '.' . $site_domain;
		$path = $current_site->path;
	}

	//alright create the blog
	$meta = apply_filters('signup_create_blog_meta', array ('lang_id' => 'en', 'public' => 0));
	$blog_id = wpmu_create_blog($site, $path, $sitetitle, $current_user->ID, $meta);
	
	do_action("pmpro_network_new_site", $blog_id, $current_user->ID);

	if ( is_a($blog_id, "WP_Error") ) {
		return new WP_Error('blogcreate_failed', __('<strong>ERROR</strong>: Site creation failed.'));
	}
			
	//save array of all blog ids
	$blog_ids = pmpron_getBlogsForUser($current_user->ID);	
	if(!in_array($blog_id, $blog_ids))
	{
		$blog_ids[] = $blog_id;
		update_user_meta($current_user->ID, "pmpron_blog_ids", $blog_ids);
		
		//if this is the first site, set it as the main site
		if(count($blog_ids) == 1)
			update_user_meta($current_user->ID, "pmpron_blog_id", $blog_id);	
	}				
	
	do_action('wpmu_activate_blog', $blog_id, $current_user->ID, $current_user->user_pass, $sitetitle, $meta);
	
	return $blog_id;
}

/*
These bits are required for PayPal Express only.
*/
function pmpron_pmpro_paypalexpress_session_vars()
{
	//save our added fields in session while the user goes off to PayPal
	$_SESSION['sitename'] = $_REQUEST['sitename'];
	$_SESSION['sitetitle'] = $_REQUEST['sitetitle'];
	$_SESSION['blog_id'] = $_REQUEST['blog_id'];
}
add_action("pmpro_paypalexpress_session_vars", "pmpron_pmpro_paypalexpress_session_vars");

//require the fields and check for dupes
function pmpron_pmpro_registration_checks($pmpro_continue_registration)
{
	if ( !$pmpro_continue_registration )
		return $pmpro_continue_registration;

	global $pmpro_msg, $pmpro_msgt, $current_site, $current_user, $pmpro_network_non_site_levels, $pmpro_level;
	
	if(!empty($_REQUEST['sitename']))
		$sitename = $_REQUEST['sitename'];
	else
		$sitename = '';
		
	if(!empty($_REQUEST['sitetitle']))
		$sitetitle = $_REQUEST['sitetitle'];
	else
		$sitetitle = '';
		
	if(!empty($_REQUEST['blog_id']))
		$blog_id = $_REQUEST['blog_id'];
	else
		$blog_id = '';

	$site_credits = pmpron_getSiteCredits($pmpro_level->id);
	
	// Return if requested level is in non site levels array
	if ( ! empty( $pmpro_network_non_site_levels ) && in_array( $pmpro_level->id, $pmpro_network_non_site_levels ) ) {
			return $pmpro_continue_registration;
	}
	
	// Return if site credits for this level is < 1
	if( intval($site_credits) < 1 ) {
		return $pmpro_continue_registration;
	}
		
	if( !empty($sitename) && !empty($sitetitle) ) {
		if(pmpron_checkSiteName( $sitename, $sitetitle ) ) {
			//all good
			return $pmpro_continue_registration;	
		} else {
			//error set in checkSiteName
			return false;	
		}
	} elseif( !empty($blog_id) ) {
		//check that the blog id matches the user meta
		pmpron_updateBlogsForUser($current_user->ID);
		$meta_blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);
		if($meta_blog_id != $blog_id)
		{
			$pmpro_msg = "There was an error finding your old site. Make sure you are logged in. Contact the site owner for help signing up or reactivating your site.";
			$pmpro_msgt = "pmpro_error";
			return false;
		} else {
			//all good
			return true;	
		}
	} else {
		//error message shown below
		return false;
	}
}
add_filter( 'pmpro_registration_checks', 'pmpron_pmpro_registration_checks' );

/*
	Checks if a domain/site name is available.
*/
function pmpron_checkSiteName( $sitename, $sitetitle )
{
	$result = wpmu_validate_blog_signup( $sitename, $sitetitle );
	$errors = $result['errors']->get_error_messages();
	if( empty( $errors ) ) {
		return true;
	} else {
		$pmpro_msg = 'There were errors creating your site.';
		$pmpro_msg .= '<ul>';
		foreach($errors as $error) {
			$pmpro_msg .= '<li>' . $error . '</li>';
		}
		$pmpro_msg .= '</ul>';
		
		pmpro_setMessage($pmpro_msg, 'pmpro_error');
		
		return false;
	}
}

/*
	Shows how to change some of the blog settings on site creation.
*/
function pmpron_new_blogs_settings($blog_id) 
{
    global $wpdb;
	
	//change the default theme
	/*
	update_blog_option($blog_id, 'current_theme', 'Your Theme Name');
	update_blog_option($blog_id, 'template', 'your-theme-directory');
	update_blog_option($blog_id, 'stylesheet', 'your-theme-directory');
	*/
	
	//change the subtitle "blogdescription"
	update_blog_option($blog_id, 'blogdescription', 'Change your subtitle');			
				
	//change the category 1 to "general" (pet peeve of mine)
	$sqlQuery = "UPDATE " . $wpdb->prefix . $blog_id . "_terms SET name = 'General', slug = 'general' WHERE term_id = 1 LIMIT 1";			
	$wpdb->query($sqlQuery);
	
	//make the blog public
	$sqlQuery = "UPDATE $wpdb->blogs SET public = 1 WHERE blog_id = '" . $blog_id . "' LIMIT 1";		
	$wpdb->query($sqlQuery);
	
	//add some other categories		
	/*
	wls_add_category($blog_id, "Books", "books");
	wls_add_category($blog_id, "Events", "events");
	wls_add_category($blog_id, "Food", "food");
	wls_add_category($blog_id, "News and Interest", "news");
	*/		
}
//actions
add_action( 'wpmu_new_blog', 'pmpron_new_blogs_settings' );

/*
	Update the confirmation message to show links to the new site.
*/
function pmpron_pmpro_confirmation_message($message, $invoice)
{
	global $current_user, $wpdb, $pmpro_pages;
	
	//where is the user's site?
	pmpron_updateBlogsForUser($current_user->ID);
	$blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);
	
	// Get the manage sites post object.
	$manage_post = pmpron_get_manage_sites_post();

	if($blog_id)
	{
		//get the site address
		$address = get_blogaddress_by_id( $blog_id );
		$message .= '<hr />';
		$message .= '<h2>' . __('Your Primary Site', 'pmpro-network') . '</h2>';
		$message .= '<p><strong><a href="' . $address . '">' . $address . '</a></strong> | <a href="' . $address . '">' . __('Visit', 'pmpro-network') . '</a> | <a href="' . $address . 'wp-admin/">' . __('Dashboard', 'pmpro-network') . '</a></p>';
		if ( ! empty( $manage_post ) ) {
			$message .= '<p><a class="pmpro_btn" href="' . esc_url( get_permalink( $manage_post ) ) . '">' . esc_html( get_the_title( $manage_post ) ) . '</a></p>';
		}
		$message .= '<hr />';
	}

	return $message;
}
add_filter( 'pmpro_confirmation_message', 'pmpron_pmpro_confirmation_message', 10, 2 );

/*
	Set site credits, remove admin access and deactivate a blogs when a user's membership level changes.
*/
function pmpron_pmpro_after_change_membership_level($level_id, $user_id)
{	
	//set site credits		
	if(!pmpro_hasMembershipLevel(NULL, $user_id)) {
		$site_credits = 0;
	} else {
		$site_credits = pmpron_getSiteCredits($level_id);
	}
	update_user_meta($user_id, 'pmpron_site_credits', $site_credits);
	
	//activate user's blogs based on number of site credits they have
	$blog_ids = pmpron_getBlogsForUser($user_id);	
	$n = 0;
	foreach($blog_ids as $blog_id)
	{
		$n++;
		
		if($site_credits >= $n)
		{
			//as long as site_credits > $n, let's make sure this blog is active
			update_blog_status( $blog_id, 'deleted', '0' );
			do_action( 'activate_blog', $blog_id );
		}
		else
		{		
			//don't deactivate admin sites
			if(!user_can("manage_network", $user_id))
			{
				//site credits < $n, so let's deactivate blogs from now on
				do_action( 'deactivate_blog', $blog_id );
				update_blog_status( $blog_id, 'deleted', '1' );			
			}
		}
	}		
}
add_action( 'pmpro_after_change_membership_level', 'pmpron_pmpro_after_change_membership_level', 10, 2 );

/*
	Get an array of blog ids for a user.
*/
function pmpron_getBlogsForUser($user_id) {

	pmpron_updateBlogsForUser($user_id);

	$user = get_userdata($user_id);
	$main_blog_id = $user->pmpron_blog_id;
	$all_blog_ids = $user->pmpron_blog_ids;

	if(!empty($all_blog_ids)) {
		return $all_blog_ids;
	} elseif(!empty($main_blog_id)) {
		return array($main_blog_id);
	} else {
		return array();
	}
}

/*
 * Update a user's blogs.
 */
function pmpron_updateBlogsForUser($user_id) {

	$user = get_userdata($user_id);
	$main_blog_id = $user->pmpron_blog_id;
	$all_blog_ids = $user->pmpron_blog_ids;

	if(empty($main_blog_id) && empty($all_blog_ids)) {
		return;
	}

	if(!empty($all_blog_ids)) {

		// Make sure they exist
		foreach($all_blog_ids as $key => $blog_id) {
			if( ! get_blog_details($blog_id) ) {
				unset($all_blog_ids[$key]);
			}
		}

		// Update user blogs
		update_user_meta($user_id, 'pmpron_blog_ids', $all_blog_ids);
	}
	if(!empty($main_blog_id)) {
		if( ! get_blog_details($main_blog_id) ) {
			delete_user_meta($user_id, 'pmpron_blog_id');
		}
	}
}

/*
	Add link to add new sites for users with multiple site credits
*/
function pmpron_myblogs_allblogs_options()
{
	global $current_user, $pmpro_pages;
	
	//how many sites have they created?	
	$all_blog_ids = pmpron_getBlogsForUser($current_user->ID);	
	$num = count($all_blog_ids);
		
	//how many can they create?
	$site_credits = $current_user->pmpron_site_credits;

	// Get the manage sites post object.
	$manage_post = pmpron_get_manage_sites_post();

	//In case they have sites but no site credit yet. Assume they have $num site credits.
	//This will give 1 site credit to users on sites upgrading pmpro-network from .1/.2 to .3. 
	if(empty($site_credits) && !empty($num)) {
		$site_credits = $num;
		update_user_meta($current_user->ID, "pmpron_site_credits", $site_credits);
	}	
	?>
	<hr />
	<p><?php esc_html_e( 'Below is a list of all sites you are an owner or member of.', 'pmpro-network' ); ?>
	<?php
		if ( ! empty( $site_credits ) && ! empty( $manage_post ) ) { ?>
		<a class="button button-primary" href="<?php echo esc_url( get_permalink( $manage_post ) ); ?>"><?php esc_html_e( get_the_title( $manage_post ) ); ?></a>
		<?php }
	?>
	</p>
	<?php
}
add_action( 'myblogs_allblogs_options', 'pmpron_myblogs_allblogs_options' );

/*
	Add site credits field to profile for admins to adjust
*/
//show fields
function pmpron_profile_fields($profile_user)
{	
	if(current_user_can("manage_network"))
	{		
	?>
		<h3><?php esc_html_e( 'Site Credits', 'pmpro-network' ); ?></h3>
		<table class="form-table">
		<tr>
			<th><label for="site_credits"><?php esc_html_e( 'Site Credits', 'pmpro-network' ); ?></label></th>
			<td>
				<?php
					//how many sites have they created?	
					$all_blog_ids = pmpron_getBlogsForUser($profile_user->ID);	
					$num = count($all_blog_ids);
						
					//how many can they create?
					$site_credits = $profile_user->pmpron_site_credits;						
				?>
				<input type="text" id="site_credits" name="site_credits" size="5" value="<?php echo esc_attr( $site_credits ); ?>" /> <em><?php echo esc_html( sprintf( __( 'currently using %s', 'pmpro-network' ), $num ) ); ?></em>

			</td>
		</tr>
		</table>
	<?php
	}
}
add_action( 'show_user_profile', 'pmpron_profile_fields' );
add_action( 'edit_user_profile', 'pmpron_profile_fields' );

//save fields
function pmpron_profile_fields_update($user_id)
{
	//make sure they can edit
	if ( !current_user_can( 'manage_network') )
		return false;

	//if site credits is there, set it
	if(isset($_POST['site_credits']))
		update_user_meta( $user_id, 'pmpron_site_credits', intval($_POST['site_credits']) );	
}
add_action( 'profile_update', 'pmpron_profile_fields_update' );
add_action( 'user_edit_form_tag', 'pmpron_profile_fields_update' );

/*
	When a site is deleted, free up the site credit and blog id
*/
/*
function pmpron_delete_blog($blog_id, $drop) {
	//for now we are cleaning up the blog_id user metas when they are loaded.
}
add_action('delete_blog', 'pmpron_delete_blog', 10, 2);
*/

/*
	Function to add links to the plugin row meta
*/
function pmpron_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-network.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-network-multisite-membership/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-network' ) ) . '">' . __( 'Docs', 'pmpro-network' ) . '</a>',
			'<a href="' . esc_url('https://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-network' ) ) . '">' . __( 'Support', 'pmpro-network' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpron_plugin_row_meta', 10, 2 );

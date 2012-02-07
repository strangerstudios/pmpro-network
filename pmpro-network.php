<?php
/*
Plugin Name: Paid Memberships Pro Network Site Helper
Plugin URI: http://www.paidmembershipspro.com/network-sites/
Description: Sample Network/Multisite Setup for Sites Running Paid Memberships Pro. This plugin requires the Paid Memberships Pro plugin, which can be found in the WordPress repository.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/
/*	
	Copyright 2011	Stranger Studios	(email : jason@strangerstudios.com)	 
	This code is licensed under the GPLv2.
*/

/*
	First we need to add some fields to the checkout page.
*/
//add the fields to the form 
function pmpron_pmpro_checkout_boxes() 
{
	global $current_user, $wpdb;
	
	$sitename = $_REQUEST['sitename'];
	$sitetitle = $_REQUEST['sitetitle']; 
?>
	<table id="pmpro_site_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
	<thead>
		<tr>
			<th>Site Information</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
			
			<?php
				//check if the user already has a blog
				if($current_user->ID)
				{
					$blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);
					if($blog_id)
					{
						$user_blog = $wpdb->get_row("SELECT * FROM $wpdb->blogs WHERE blog_id = '" . $blog_id . "' LIMIT 1");
						$blogname = get_blog_option($blog_id, "blogname");
					}
				}
				
				if($blogname)
				{
				?>
				<div>
					<p>You will be reclaiming your site <strong><?=$blogname?></strong>.</p>
					<input type="hidden" name="blog_id" value="<?=$blog_id?>" />
				</div>
				<?php
				}
				else
				{
				?>				
				<div>
					<label for="sitename"><?php _e('Site Name') ?></label>
					<input id="sitename" name="sitename" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitename)); ?>" />				
					<?php
						global $current_site;
						$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );
					
						if ( !is_subdomain_install() )
							$site = $current_site->domain . $current_site->path . __( 'sitename' );
						else
							$site = __( 'domain' ) . '.' . $site_domain . $current_site->path;

						echo '<div>(<strong>' . sprintf( __('Your address will be %s.'), $site ) . '</strong>) ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!' ) . '</div>';						
					?>
				</div>
				<div>
					<label for="sitetitle"><?php _e('Site Title')?></label>
					<input id="sitetitle" name="sitetitle" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitetitle)); ?>" />
				</div> 
				<?php
				}
				?>			
			
			</td>
		</tr>
	</tbody>
	</table>
<?php
}
add_action('pmpro_checkout_boxes', 'pmpron_pmpro_checkout_boxes');

//update the user after checkout
function pmpron_update_site_after_checkout($user_id)
{
	global $current_user, $current_site;
	
	$sitename = $_REQUEST['sitename'];
	$sitetitle = $_REQUEST['sitetitle'];
	$blog_id = $_REQUEST['blog_id'];
	
	if($blog_id)
	{
		//reclaiming, first check that this id is associated with the user
		$meta_blog_id = get_user_meta($user_id, "pmpron_blog_id", true);
		if($blog_id == $meta_blog_id)
		{
			//activate the blog
			update_blog_status( $blog_id, 'deleted', '0' );
			do_action( 'activate_blog', $blog_id );
		}		
		else
		{
			//uh oh, were they trying to claim someone else's blog?
			return new WP_Error('pmpron_reactivation_failed', __('<strong>ERROR</strong>: Site reactivation failed.'));
		}
	}
	else
	{ 
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

		if ( is_a($blog_id, "WP_Error") ) {
			return new WP_Error('blogcreate_failed', __('<strong>ERROR</strong>: Site creation failed.'));
		}
	
		//save the blog id in user meta for access later
		update_user_meta($current_user->ID, "pmpron_blog_id", $blog_id);

		do_action('wpmu_activate_blog', $blog_id, $current_user->ID, $current_user->user_pass, $sitetitle, $meta);
	}
}
add_action('pmpro_after_checkout', 'pmpron_update_site_after_checkout');

//require the fields and check for dupes
function pmpron_pmpro_registration_checks()
{
	global $pmpro_msg, $pmpro_msgt, $current_user;
	$sitename = $_REQUEST['sitename'];
	$sitetitle = $_REQUEST['sitetitle'];
	$blog_id = $_REQUEST['blog_id'];
 
	if($sitename && $sitetitle)
	{
		//they entered something. is it available		
		$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );		
		if ( !is_subdomain_install() )
		{
			$site = $current_site->domain;
			$path = $current_site->path . "/" . $sitename;
		}
		else
		{
			$site = $sitename . '.' . $site_domain;
			$path = $current_site->path;
		}
		$domain = preg_replace( '/\s+/', '', sanitize_user( $site, true ) );

		if ( is_subdomain_install() )
			$domain = str_replace( '@', '', $domain );
		
		if ( empty($path) )
			$path = '/';

		// Check if the domain has been used already. We should return an error message.
		if ( domain_exists($domain, $path) )
		{
			//dupe
			$pmpro_msg = "That site name is already in use.";
			$pmpro_msgt = "pmpro_error";
			return false;
		}
		else
		{
			//looks good
			return true;
		}				
	}
	elseif($blog_id)
	{
		//check that the blog id matches the user meta
		$meta_blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);
		if($meta_blog_id != $blog_id)
		{
			$pmpro_msg = "There was an error finding your old site. Make sure you are logged in. Contact the site owner for help signing up or reactivating your site.";
			$pmpro_msgt = "pmpro_error";
			return false;
		}
		else
		{
			//all good
			return true;	
		}
	}
	else
	{
		$pmpro_msg = "You must enter a site name and title now.";
		$pmpro_msgt = "pmpro_error";
		return false;
	}
}
add_filter("pmpro_registration_checks", "pmpron_pmpro_registration_checks");

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
add_action('wpmu_new_blog', 'pmpron_new_blogs_settings');

/*
	Update the confirmation message to show links to the new site.
*/
function pmpron_pmpro_confirmation_message($message, $invoice)
{
	global $current_user, $wpdb;
	
	//where is the user's site?
	$blog_id = get_user_meta($current_user->ID, "pmpron_blog_id", true);
	
	if($blog_id)
	{
		//get the site address
		$address = "http://" . $wpdb->get_var("SELECT CONCAT(domain, path) FROM $wpdb->blogs WHERE blog_id = '" . $blog_id . "' LIMIT 1");
		$message .= "<p>Visit your new site here: <a href=\"" . $address . "\">" . $address . "</a></p>";
		$message .= "<p>Manage your new site here: <a href=\"" . $address . "\">" . $address . "wp-admin/</a></p>";
	}

	return $message;
}
add_filter("pmpro_confirmation_message", "pmpron_pmpro_confirmation_message", 10, 2);

/*
	Remove admin access and deactivate a blog when a user's membership level changes.
*/
function pmpron_pmpro_after_change_membership_level($level, $user_id)
{
	//if they don't have a membership, deactivate any site they might have
	if(!pmpro_hasMembershipLevel(NULL, $user_id))
	{
		//find their blog
		$blog_id = get_user_meta($user_id, "pmpron_blog_id", true);	
		
		if($blog_id)
		{
			//deactivate the blog
			do_action( 'deactivate_blog', $blog_id );
			update_blog_status( $blog_id, 'deleted', '1' );
		}
	}
}
add_action("pmpro_after_change_membership_level", "pmpron_pmpro_after_change_membership_level", 10, 2);
?>

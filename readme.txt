=== Paid Memberships Pro - Member Network Sites Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, multisite, network, network sites, wpmu
Requires at least: 4.0
Tested up to: 4.8
Stable tag: .5.1

Create a network site for the member as part of membership to the main site.

== Description ==

Allow a member to purchase one or more sites as part of membership. Once configured, the member can purchase membership at the network’s main site (the primary domain of the network) and specify the "Site Name" and "Site Title" for their new site on the network.

The site will be created for them after registering. Any sites attached to a member will be deactivated when membership expires or cancels. If they sign up for a membership again, the site will be reactivated.

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Make sure you have properly configured Network Sites on your WordPress Multisite.
1. Upload the 'pmpro-network' directory to the '/wp-content/plugins/' directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Defining the Membership Level "Site Credits" ==
You must define the constant PMPRO_NETWORK_MANAGE_SITES_SLUG in a plugin for PMPro Customizations on your main network site. This is the page that members will see to let them manage their sites on the network and create new sites based on the level’s allowance.

Set the number of "site credits" given per level by editing the level on the main site. Users checking out for these levels will be able to create a subsite on the network for each site credit they have.

You may also use the pmpron_site_credits filter (example below) to customize the number of sites allotted by level ID. The filter should be placed in your active theme’s functions.php file or a helper plugin for PMPro Customizations (our recommended method).

`
//set site credits for levels 1-3
function my_pmpron_site_credits( $credits, $user_id, $level_id ) {
	if($level_id == 1) {
		$credits = 1;
	} elseif($level_id == 2) {
		$credits = 3;
	} elseif($level_id == 3) {
		$credits = 9999;
	}
	return $credits;
}
add_filter( 'pmpron_site_credits', 'my_pmpron_site_credits', 10, 3 );
`

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the GitHub issue tracker here: https://github.com/strangerstudios/pmpro-network/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= .5.1 =
* ENHANCEMENT: Improved fields display on membership checkout page to use no tables

= .5 =
* BUG FIX: Fixed issue with levels set with 0 site credits.
* ENHANCEMENT: You can now set the number of site credits for a level by editing that level on the main site. These values will default to the value set via the pmpron_site_credits filter. You can remove your custom pmpron_site_credits filter code or continue to use the filter which will override the value set for the level.
* ENHANCEMENT: Added pmpron_getSiteCredits($level_id) function to get the number of site credits for a given level. This will get the option for the level and also apply the pmpron_site_credits filter.
* ENHANCEMENT: Improving readme documentation and updating add on name.

= .4.2 =
* BUG: Fixed some more warnings.

= .4.1 =
* BUG: Fixed some warnings during account creation.

= .4 =
* BUG/ENHANCEMENT: Fixed some issues where an old/deleted blog_id would still be associated with a user.
* ENHANCEMENT: Added pmpron_non_site_level_array filter to setup that array vs using a global var.
* ENHANCEMENT: If you use the pmpron_site_credits filter and the # of credits for a level is 0, then the level is treated as a non site level.

= .3.3.1 =
* BUG: Fixed some warnings.

= .3.3 =
* BUG: Fixed bug where we weren't checking $pmpro_network_non_site_levels global in pmpron_update_site_after_checkout.
* BUG: Now making sure sites still exist before displaying them on the Manage Sites page.
* FEATURE: Added ability to use custom templates for Manage Sites page.

= .3.2.1 =
* Fixed some warnings on the checkout page when not logged in.

= .3.2 =
* Added "site credits" field to profile for admins to override.

= .3.1 =
* Won't deactivate sites when changing levels on a network admin.
* Checking for blog_ids before showing sites table on manage sites page.
* Fixed pmpron_pmpro_after_change_membership_level to expect level_id instead of level object

= .3 =
* Added ability for users to register multiple sites on the network. Storing blog ids in pmpron_blog_ids user meta. The pmpron_blog_id (no s) meta value will still hold the first site created. Create a page with the [pmpron_manage_sites] shortcode on it to create the page to add new sites; use the pmpron_site_credits filter to change the number of site credits given to users signing up.
* Abstracted some of the code around site creation.
* Fixed a potential bug with the check to see if a sitename was already taken.

= .2 =
* Storing some vars in $_SESSION for when using PayPal Express or other offsite payment processors.
* Fixed wp-admin link to new site dashboard on confirmation page.

= .1 =
* Initial version.

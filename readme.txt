=== Paid Memberships Pro Network Site Helper ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, network sites, wpmu
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: .3

Sample Network/Multisite Setup for Sites Running Paid Memberships Pro. This plugin requires the Paid Memberships Pro plugin, which can be found in the WordPress repository.

== Description ==

With the Paid Memberships Pro plugin and this plugin activated, new users will be able to choose a site name and title at checkout. A site will be created for them after registering. If they cancel their membership or have it removed, the site will be deactivated. If they sign up for a membership again, the site will be reactivated.

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Make sure you have properly configured Network Sites on your WP install.
1. Upload the `pmpro-network` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the GitHub issue tracker here: https://github.com/strangerstudios/pmpro-network/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= .3 =
* Added ability for users to register multiple sites on the network. Storing blog ids in pmpron_blog_ids user meta. The pmpron_blog_id (no s) meta value will still hold the first site created. Create a page with the [pmpron_manage_sites] shortcode on it to create the page to add new sites; use the pmpron_site_credits filter to change the number of site credits given to users signing up.
* Abstracted some of the code around site creation.
* Fixed a potential bug with the check to see if a sitename was already taken.

= .2 =
* Storing some vars in $_SESSION for when using PayPal Express or other offsite payment processors.
* Fixed wp-admin link to new site dashboard on confirmation page.

= .1 =
* Initial version.

<?php
/*
Plugin Name: WPDCR
Plugin URI: http://www.WPDCR.wordpress.com
Description: WordPress Dashboard Credits Removal Removes all WordPress credits from dashboard
Version: 1.1
Author: OwenSJ
Author URI: http://www.owensinclairjones.co.uk
*/
?>
<?php
function ap_action_init()
{
// Localization
load_plugin_textdomain('domain', false, dirname(plugin_languages(__FILE__)));
}

// Add actions
add_action('init', 'ap_action_init');

// CUSTOM ADMIN LOGIN HEADER LOGO
  function my_custom_login_logo()
  {
  _e('
  <style  type="text/css"> 
  h1 a {  background-image:url(' . get_bloginfo('template_directory') . '/images/logo_admin.png)  !important; } 
  </style>
  ','wpdcr');
  }
  add_action('login_head',  'my_custom_login_logo'); 
  // CUSTOM ADMIN LOGIN LOGO LINK
  function change_wp_login_url()  
  {
      _e(bloginfo('url'),'wpdcr'); 
	}
	add_filter('login_headerurl', 'change_wp_login_url');
	// CUSTOM ADMIN LOGIN LOGO & ALT TEXT  
	function change_wp_login_title() 
	{
	_e(get_option('blogname'),'wpdcr');
	}
	add_filter('login_headertitle', 'change_wp_login_title'); 
	// Admin footer modification
	function change_footer_admin () {return '';}add_filter('admin_footer_text', 'change_footer_admin', 9999);function change_footer_version() {return '';}add_filter( 'update_footer', 'change_footer_version', 9999);
	add_action( 'admin_bar_menu', 'remove_wp_logo', 999 );

function remove_wp_logo( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'wp-logo' );
}

function jp_rm_menu() {
	if( class_exists( 'Jetpack' ) && !current_user_can( 'manage_options' ) ) {
		// This removes the page from the menu in the dashboard
		remove_menu_page( 'jetpack' );
	}
}
add_action( 'admin_init', 'jp_rm_menu' ); 

function jp_rm_icon() {
	if( class_exists( 'Jetpack' ) && !current_user_can( 'manage_options' ) ) {
		// This removes the small  jetpack icon in the admin bar
		_e("\n" . '<style type="text/css" media="screen">#wp-admin-bar-notes { display: none; }</style>' . "\n",'wpdcr');
	}
}
add_action( 'admin_head', 'jp_rm_icon' );
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
	die('Sorry, but you cannot access this page directly.');
}
/* Plugin config - user capability for the top level you want to hide everything from */
$wphd_user_capability = 'edit_posts'; /* [default for Subscriber role = edit_posts] */
/* WordPress 3.1 introduced the toolbar in both the admin area and the public-facing site (if enabled). For subscribers, there's now a link
	to the Dashboard when they are on the public-facing site. Let's remove the Dashboard link and customize the links in the admin bar. */

function wphd_custom_admin_bar_links() {
	global $blog, $current_user, $id, $wp_admin_bar, $wphd_user_capability, $wp_db_version;

	if ($wp_db_version < 20596) {
		return;

	} else if ((!current_user_can(''.$wphd_user_capability.'')) && is_admin_bar_showing() && is_user_logged_in() && $wp_db_version >= 20596) {

		/* If single site, remove Dashboard link on public-facing site and WordPress logo menu everywhere */
		if (!is_multisite() && !is_admin()) {
			$wp_admin_bar->remove_menu('dashboard');		/* Hide Dashboard link on public-facing site */
		}
		$wp_admin_bar->remove_menu('wp-logo');			/* Hide WordPress logo menu completely */

		/* If Multisite, check whether they are assigned to any network sites before removing links */
		$user_id = get_current_user_id();
		$blogs = get_blogs_of_user($user_id);

		if (is_multisite()) {

			/* Show only user account menu if the user is assigned to no sites. */
			if (count($wp_admin_bar->user->blogs) == 0) {
				$wp_admin_bar->remove_menu('wp-logo');			/* Hide WordPress logo menu completely */
				return;
			}

			/* Show single site menu if the user is assigned to only 1 site. */
			if (count($wp_admin_bar->user->blogs) == 1) {
				if (!is_admin()) {
					$wp_admin_bar->remove_menu('dashboard');	/* Hide Dashboard link on public-facing site */
				}
				$wp_admin_bar->remove_menu('wp-logo');			/* Hide WordPress logo menu completely */
				$wp_admin_bar->remove_menu('my-sites');			/* Hide My Sites menu */
				return;
			}

			/* Remove Dashboard and Visit Site links from My Sites menu if the user is assigned to two or more sites. */
			if (count($wp_admin_bar->user->blogs) >= 2) {
				$wp_admin_bar->remove_menu('dashboard');		/* Hide Dashboard link on public-facing site */

				foreach ((array) $wp_admin_bar->user->blogs as $blog) {
					$menu_d = 'blog-'.$blog->userblog_id.'-d';
					$menu_v = 'blog-'.$blog->userblog_id.'-v';

					$wp_admin_bar->remove_menu($menu_d);		/* Remove Dashboard link from My Sites menu*/
					$wp_admin_bar->remove_menu($menu_v);		/* Remove Visit Site link from My Sites menu */
				}

				/* Change URL for each site from admin URL to site URL */
				$menu_id  = 'blog-'.$blog->userblog_id;
				$blavatar = '<div class="blavatar"></div>';

				foreach ((array) $wp_admin_bar->user->blogs as $blog) {
					$menu_id   = 'blog-'.$blog->userblog_id;
					$blogname = ucfirst($blog->blogname);

					$wp_admin_bar->add_menu(array(
						'parent'  => 'my-sites-list',
						'id'        => $menu_id,
						'title' 	   => $blavatar.$blogname,
						'href' 	   => get_site_url($blog->userblog_id))
					);
				}

				return;

			}

		}

	}

}

add_action('wp_before_admin_bar_render', 'wphd_custom_admin_bar_links');

/* Replace toolbar Dashboard link on public-facing site with link to the user's profile */

function wphd_add_admin_bar_profile_link() {
	global $blog, $current_user, $id, $wp_admin_bar, $wphd_user_capability, $wp_db_version;

	if ($wp_db_version < 20596) {
		return;

	} else if ((!current_user_can(''.$wphd_user_capability.'')) && is_admin_bar_showing() && !is_admin() && $wp_db_version >= 20596) {
		$wp_admin_bar->add_menu(array(
			'parent' => 'site-name',
			'id'       => 'profile',
			'title'    => __('Profile'),
			'href'    => admin_url('profile.php'),
		));
	}

}

add_action('admin_bar_menu', 'wphd_add_admin_bar_profile_link');

/* Now for the admin sidebar menu and the profile page. Let's hide the Dashboard menu, Help menu, Upgrade notice, and Personal Options section. */

function wphd_hide_dashboard() {
	global $blog, $current_user, $id, $parent_file, $wphd_user_capability, $wp_db_version;

	if ($wp_db_version < 20596) {
		return;

	} else if ((!current_user_can(''.$wphd_user_capability.'')) && $wp_db_version >= 20596) {

		/* First, let's get rid of the Help menu, Update nag, Personal Options section */
		_e( "\n" . '<style type="text/css" media="screen">#your-profile { display: none; } .update-nag, #contextual-help-wrap, #contextual-help-link-wrap { display: none !important; }</style>','wpdcr');
		_e( "\n" . '<script type="text/javascript">jQuery(document).ready(function($) { $(\'form#your-profile > h3:first\').hide(); $(\'form#your-profile > table:first\').hide(); $(\'form#your-profile\').show(); });</script>,','wpdcr' . "\n");

		/* Now, let's fix the sidebar admin menu - go away, Dashboard link. */

		/* If Multisite, check whether they are in the User Dashboard before removing links */

		$user_id = get_current_user_id();
		$blogs = get_blogs_of_user($user_id);

		if (is_multisite() && is_admin() && empty($blogs)) {
			return;
		} else {
			remove_menu_page('index.php');		/* Hides Dashboard menu */
			remove_menu_page('separator1');		/* Hides separator under Dashboard menu*/
		}


		/* Last, but not least, let's redirect users to their profile when they login or if they try to access the Dashboard via direct URL */

		if (is_multisite() && is_admin() && empty($blogs)) {
			return;
		} else if ($parent_file == 'index.php') {
			if (headers_sent()) {
				echo '<meta http-equiv="refresh" content="0;url='.admin_url('profile.php').'">';
				echo '<script type="text/javascript">document.location.href="'.admin_url('profile.php').'"</script>';
			} else {
				wp_redirect(admin_url('profile.php'));
				exit();
			}
		}

	}

}

add_action('admin_head', 'wphd_hide_dashboard', 0);
remove_action('wp_head', 'wp_generator');
?>
	
	 
 

<?php
/*
Helper Name: Useful Functions for Wordpress
Author: Kadim Gültekin
Author URI: https://www.kadimgultekin.com
Version: 1.0.1
*/

/**
* Displays archive title for custom post types and regular posts
*
* Wordpress
* for displaying the title of the post type.
*
* @since 1.0.0
*
* @return string Archive title
*/
function ufw_archive_title(){
	if(is_post_type_archive()){
		return post_type_archive_title();
	} else {
		return get_the_archive_title();
	}
}


/**
* Decode Axios responses.
*
* When using Axios you may not decode json response. This functions helps to decode Axios response easily.
*
* @since 1.0.0
*
* @return object Response data
*/
function ufw_get_axios_data() {
	$request_body = file_get_contents( 'php://input' );
	$data         = (object) json_decode( $request_body, true );
	return $data;
}


/**
* When getting 404 error, logout user if you need.
*
* Because of security reasons you want to logout users when getting 404 errors.
*
* @since 1.0.0
*
*/
function ufw_redirect_to_logout_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	wp_logout();
	exit();
}


/**
* Remove admin bar.
*
* Remove admin bar from front of web site.
*
* @since 1.0.0
*
*/
add_action( 'after_setup_theme', 'ufw_remove_admin_bar' );
function ufw_remove_admin_bar() {
	show_admin_bar( false );
}


/**
* Hide admin dashborad for specific user roles.
*
* Because of security reasons you may want block users for using admin dashboard.
*
* @since 1.0.0
*
* @param string $user_role Wordpress User Roles: 'administrator', 'author', 'editor' etc.
*
*/
add_action( 'admin_init', 'ufw_block_wp_admin' );
function ufw_block_wp_admin(string $user_role) {
	if ( ! current_user_can( $user_role ) ) {
		wp_safe_redirect( home_url() );
		exit;
	}
}


/**
* Change wp_mail() content type.
*
* Convert the default content type for email sent through the wp_mail() function from ‘text/plain‘ to 'text/html'
*
* @since 1.0.0
*
* @return string Content type "text/html"
*/
add_filter( 'wp_mail_content_type', 'ufw_set_content_type' );
function ufw_set_content_type() {
	return "text/html";
}


/**
* Multiple file upload via wp_handle_upload()
*
* Easily upload multiple files with wp_handle_upload() function.
*
* @since 1.0.0
*
* @param string $file_name Only file name from file input, not $_FILE('file')
*
* @param int $i Index of current file
*
* @return int Id of uploaded file (attachment_id)
*/
function ufw_wp_multiple_handle_upload(string $file_name, int $i ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}
	$file             = array(
		'name'     => $_FILES[ $file_name ]['name'][ $i ],
		'type'     => $_FILES[ $file_name ]['type'][ $i ],
		'tmp_name' => $_FILES[ $file_name ]['tmp_name'][ $i ],
		'error'    => $_FILES[ $file_name ]['error'][ $i ],
		'size'     => $_FILES[ $file_name ]['size'][ $i ]
	);
	$upload_overrides = array( 'test_form' => false );
	$upload           = wp_handle_upload( $file, $upload_overrides );
	$filename         = $upload['file'];
	$filetype         = wp_check_filetype( basename( $filename ), null );
	$wp_upload_dir    = wp_upload_dir();
	$attachment       = array(
		'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);
	$attach_id        = wp_insert_attachment( $attachment, $filename );

	return $attach_id;
}


/**
* Single file upload via wp_handle_upload()
*
* Easily upload a file with wp_handle_upload() function.
*
* @since 1.0.0
*
* @param string $file_name Only file name from file input, not $_FILE('file')
*
* @return int Id of uploaded file (attachment_id)
*/
function ufw_wp_single_handle_upload( string $file_name ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}
	$uploadedfile     = $_FILES[ $file_name ];
	$upload_overrides = array(
		'test_form' => false
	);
	$movefile         = wp_handle_upload( $uploadedfile, $upload_overrides );
	$filename         = $movefile['file'];
	$filetype         = wp_check_filetype( basename( $filename ), null );
	$wp_upload_dir    = wp_upload_dir();
	$attachment       = array(
		'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);
	$attach_id        = wp_insert_attachment( $attachment, $filename );

	return $attach_id;
}


/**
* Returns Wordpress menu items as an array.
*
* For creating custom menus
*
* @since 1.0.0
*
* @param int|string|WP_Term $current_menu Menu ID, slug, name, or object.
*
* @return int Id of uploaded file (attachment_id)
*/
function ufw_wp_get_menu_array($current_menu) {

	$menu_array = wp_get_nav_menu_items($current_menu);

	$menu = array();

	function populate_children($menu_array, $menu_item)
	{
		$children = array();
		if (!empty($menu_array)){
			foreach ($menu_array as $k=>$m) {
				if ($m->menu_item_parent == $menu_item->ID) {
					$children[$m->ID] = array();
					$children[$m->ID]['ID'] = $m->ID;
					$children[$m->ID]['title'] = $m->title;
					$children[$m->ID]['url'] = $m->url;
					unset($menu_array[$k]);
					$children[$m->ID]['children'] = populate_children($menu_array, $m);
				}
			}
		};
		return $children;
	}

	foreach ($menu_array as $m) {
		if (empty($m->menu_item_parent)) {
			$menu[$m->ID] = array();
			$menu[$m->ID]['ID'] = $m->ID;
			$menu[$m->ID]['title'] = $m->title;
			$menu[$m->ID]['url'] = $m->url;
			$menu[$m->ID]['children'] = populate_children($menu_array, $m);
		}
	}

	return $menu;

}


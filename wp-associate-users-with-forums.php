<?php

/*
 * Plugin Name: WP Associate Users With Forums
 * Description: Associate users with forums. If a user is associated with a forum, they're able to view the topics/replies in that forum.
 * Version:     0.1
 * Plugin URI:  https://github.com/richardtape/wp-associate-users-with-forums
 * Author:      Richard Tape
 * Author URI:  https://richardtape.com/
 * Text Domain: wpauwf
 * License:     GPL v2 or later
 * Domain Path: languages
 *
 * wpauwf is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * wpauwf is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with wpauwf. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Nothing here for wp-cli
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	return;
}

class WP_Associate_Users_With_Forms {

	/**
	 * Initiazlie ourselves by setting up constants and hooks
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return null
	 */

	public function init() {

		// Set up actions and filters as necessary
		$this->add_hooks();

	}/* init() */


	/**
	 * Add our hooks (actions/filters)
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return null
	 */

	public function add_hooks() {

		// Add action hooks
		$this->add_actions();

		// Add filter hooks
		$this->add_filters();

	}/* add_hooks() */


	/**
	 * Add our action hook(s).
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return null
	 */

	public function add_actions() {

		// Add the fields to the user edit screens
		add_action( 'show_user_profile', array( $this, 'showedit_user_profile__add_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'showedit_user_profile__add_fields' ) );

		// Save the custom fields for the users
		add_action( 'personal_options_update', array( $this, 'personal_options_update__save_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'personal_options_update__save_fields' ) );

	}/* add_actions() */


	/**
	 * Add the forum association field to user profile screens
	 * The user themselves can't see or save these fields.
	 *
	 * @since 1.0.0
	 *
	 * @param (object) $user - The WP USer Object of the user
	 * @return null
	 */

	public function showedit_user_profile__add_fields( $user ) {

		// Determine if we should show these field(s)
		if ( ! $this->can_current_user_see_forum_association_fields() ) {
			return;
		}

		// Get a list of forum IDs
		$forums_list = $this->get_forum_ids_and_titles();

		// Display the fields
		$this->forum_association_field_markup( $user, $forums_list );

	}/* showedit_user_profile__add_fields() */


	/**
	 * Add our filter hook(s)
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return null
	 */

	public function add_filters() {

	}/* add_filters() */


	/**
	 * Determine if the person viewing/saving is able to see these fields.
	 * Admins only - as it's set for manage_options
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return null
	 */

	public function can_current_user_see_forum_association_fields() {

		return current_user_can( 'manage_options' );

	}/* can_current_user_see_forum_association_fields() */


	/**
	 * The output for the markup for the forum association field
	 *
	 * @since 1.0.0
	 *
	 * @param (object) $user - The User Object
	 * @param (array) $forums_list - Forum IDs and Titles
	 * @return null
	 */

	public function forum_association_field_markup( $user, $forums_list ) {

		// Output the title always
		echo wp_kses_post( $this->get_forum_association_field_title_markup() );

		// If we don't have any published forums, display a message saying that.
		if ( empty( $forums_list ) ) {
			echo wp_kses_post( $this->get_no_forums_message_markup() );
			return;
		}

		// Fetch the currently assoicated forums for this user
		$user_associated_forums = get_user_meta( $user->ID, 'forum_associations', true );

		?>

		<table class="form-table">

			<?php foreach ( $forums_list as $forum_id => $forum_title ) : ?>

			<?php $associated = ( in_array( $forum_id, $user_associated_forums, true ) ) ? true : false; ?>

			<tr>
				<th><label for="forum_associations_<?php echo absint( $forum_id ); ?>"><?php echo esc_html( $forum_title ); ?></label></th>

				<td>
					<input type="checkbox" name="forum_associations[<?php echo absint( $forum_id ); ?>]" id="forum_associations_<?php echo absint( $forum_id ); ?>" value="1" <?php checked( $associated, true ); ?> />
				</td>
			</tr>

			<?php endforeach; ?>

		</table>
		<?php
	}/* forum_association_field_markup() */


	/**
	 * The forum association field title markup
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return (string) Markup for the fields title
	 */

	public function get_forum_association_field_title_markup() {

		return '<h3>' . __( 'Forum Associations', 'wpauwf' ) . '</h3>';

	}/* get_forum_association_field_title_markup() */


	/**
	 * If there's no published forums, display a message saying that.
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return (string) Markup for the message displayed when there's no forums
	 */

	public function get_no_forums_message_markup() {

		return __( 'There are currently no published forums with which to associate users.', 'wpauwf' );

	}/* get_no_forums_message_markup() */


	/**
	 * Get a list of Forum IDs. Each forum can be associated with a user or not.
	 * Currently limited to 20 which should be plenty.
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 * @return (array) An (associative) array of (published) forum IDs => forum titles
	 */

	public function get_forum_ids_and_titles() {

		$forums = new WP_Query( array(
			'post_type' => 'forum',
			'post_status' => 'publish',
			'posts_per_page' => apply_filters( 'wpauwf_get_forum_ids_and_titles_posts_per_page', 20 ),
			'order' => 'ASC',
			'orderby' => 'ID',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		// Bail if we don't have anything returned
		if ( ! isset( $forums->posts ) || ! is_array( $forums->posts ) || empty( $forums->posts ) ) {
			return array();
		}

		// Sort our data into just what we need
		$data = array();

		foreach ( $forums->posts as $key => $forum_object ) {

			$this_forum_id = $forum_object->ID;
			$this_form_title = $forum_object->post_title;

			$data[ $this_forum_id ] = $this_form_title;
		}

		return $data;

	}/* get_forum_ids_and_titles() */


	/**
	 * Handle the saving of the forum association field when the user saves their profile.
	 * The user themselves can't see or save these fields.
	 *
	 * @since 1.0.0
	 *
	 * @param (int) $user_id - The ID of the user for whose profile we are viewing
	 * @return null
	 */

	public function personal_options_update__save_fields( $user_id ) {

		if ( ! $this->can_current_user_see_forum_association_fields() ) {
			return;
		}

		$submitted_associated_forums = isset( $_POST['forum_associations'] ) ? $_POST['forum_associations'] : false;

		// If empty, remove all associations
		if ( false === $submitted_associated_forums ) {
			update_user_meta( $user_id, 'forum_associations', array() );
			return;
		}

		// We have something in $_POST['forum_associations'] which will be an array of forum IDs
		// Sanitize first.
		$sanitized_associated_forums = array();

		foreach ( $submitted_associated_forums as $forum_id => $value ) {
			$sanitized_forum_id = absint( $forum_id );
			$sanitized_associated_forums[] = $forum_id;
		}

		// Now we have an array of forum IDs for which this user should be associated. Update user meta appropriately.
		update_user_meta( $user_id, 'forum_associations', $sanitized_associated_forums );

	}/* personal_options_update__save_fields() */

}/* WP_Associate_Users_With_Forms() */


// Set ourselves up.
add_action( 'plugins_loaded', 'rt_wp_associate_users_with_forums' );

/**
 * Initialize our class
 *
 * @since 1.0.0
 *
 * @param null
 * @return null
 */

function rt_wp_associate_users_with_forums() {

	$wpauwf = new WP_Associate_Users_With_Forms();
	$wpauwf->init();

}/* rt_wp_associate_users_with_forums() */

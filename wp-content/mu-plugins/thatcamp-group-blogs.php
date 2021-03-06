<?php

/**
 * This file provides the core functionality for the linking of BuddyPress
 * groups to WP sites
 *
 * Using this custom method because bp-groupblog provides too much overhead,
 * and doesn't really reflect the correct workflow anyway
 *
 * @author Boone Gorges
 */

/**
 * Utility for looping through and removing bbp_participants from all blogs
 */
function thatcamp_remove_bbp_participant() {
        if ( ! is_super_admin() ) {
                return;
        }

        if ( ! empty( $_GET['remove_bbp_participant'] ) ) {
                global $wpdb;

                $start = 1;

                $caps = $wpdb->get_results( "SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE 'wp_%_capabilities'" );

                foreach ( $caps as $cap ) {
                        $value = unserialize( $cap->meta_value );
                        foreach ( $value as $vk => $vv ) {
                                if ( 0 === strpos( $vk, 'bbp_' ) ) {
                                        unset( $value[ $vk ] );
                                }
                        }


                        if ( $value == unserialize( $cap->meta_value ) ) {
                                continue;
                                }

                        if ( empty( $value ) ) {
                                delete_user_meta( $cap->user_id, $cap->meta_key );
                                echo 'deleting ' . $cap->meta_key . ' for user ' . $cap->user_id;
                        } else {
                                update_user_meta( $cap->user_id, $cap->meta_key, $value );
                                echo 'updating ' . $cap->meta_key . ' for user ' . $cap->user_id;
                        }
                        echo '<br />';
                        //echo '<pre>';
//                        print_r( $value );
//                        echo '</pre>';
                }
        }
}
add_action( 'admin_init', 'thatcamp_remove_bbp_participant' );

/**
 * Don't let bbPress do its stupid add-user-to-blog crap on switch_blog
 */
add_action( 'init', create_function( "", "
remove_action( 'switch_blog', 'bbp_set_current_user_default_role' );
" ), 1 );

/**
 * Ensure that bbPress forum topics are posted to activity
 *
 * bbPress checks to see whether the current site is public before posting an
 * item to the activity stream. Not sure why. In any case, we need to work
 * around this.
 */
add_action( 'bbp_is_site_public', '__return_true', 9999 );

/**
 * Prevent regular 404s from getting the Site Not Found redirect
 *
 * WordPress lumps together blog-not-found redirects with regular page-not-
 * found redirects when using NOBLOGREDIRECT. This is dumb. In any case, here
 * we override blog_redirect_404 so that regular page-not-found 404s resolve
 * normally, while still preserving our NOBLOGREDIRECT for non-existent blogs
 */
add_action( 'blog_redirect_404', '__return_false' );

/**
 * Fallback logic for "current" group
 */
function thatcamp_fallback_group( $group_id ) {
	if ( ! intval( $group_id ) ) {
		if ( $group_id = bp_get_group_id() ) {}
		else if ( $group_id = bp_get_current_group_id() ) {}
	}

	return (int) $group_id;
}

/**
 * On blog creation, create a new group
 */
function thatcamp_create_group_for_new_blog( $blog_id  ) {
	// Assemble some data to create the group
	$create_args = array();

	// The group admin should be the blog admin. If no blog admin is found, default to Amanda (id 7)
	$blog_admin = get_user_by( 'email', get_blog_option( $blog_id, 'admin_email' ) );
	$create_args['creator_id'] = is_a( $blog_admin, 'WP_User' ) ? $blog_admin->ID : '7';

	$create_args['name'] = get_blog_option( $blog_id, 'blogname' );
	$create_args['description'] = $create_args['name'];

	$create_args['slug'] = sanitize_title( $create_args['name'] );

	$create_args['status'] = 'public';
	$create_args['enable_forum'] = false;
	$create_args['date_created'] = bp_core_current_time();

	$group_id = groups_create_group( $create_args );

	groups_update_groupmeta( $group_id, 'blog_id', $blog_id );

	groups_update_groupmeta( $group_id, 'total_member_count', 1 );
	groups_update_groupmeta( $group_id, 'invite_status', 'members' );

	return $group_id;
}
add_action( 'wpmu_new_blog', 'thatcamp_create_group_for_new_blog' );

/**
 * When a blog is spammed, archived, or deleted, delete the corresponding group
 *
 * @param int
 */
function thatcamp_delete_group_for_blog( $blog_id ) {
	global $bp;

	$group_id = thatcamp_get_blog_group( $blog_id );

	// do it manually, to avoid groups_delete_group() cap check
	do_action( 'groups_before_delete_group', $group_id );

	// Get the group object
	$group = groups_get_group( array( 'group_id' => $group_id ) );
	if ( !$group->delete() )
		return false;

	// Delete all group activity from activity streams
	if ( bp_is_active( 'activity' ) )
		bp_activity_delete_by_item_id( array( 'item_id' => $group_id, 'component' => $bp->groups->id ) );

	// Remove all outstanding invites for this group
	groups_delete_all_group_invites( $group_id );

	// Remove all notifications for any user belonging to this group
	bp_core_delete_all_notifications_by_type( $group_id, $bp->groups->id );

	do_action( 'groups_delete_group', $group_id);
}
add_action( 'make_spam_blog', 'thatcamp_delete_group_for_blog' );
add_action( 'archive_blog', 'thatcamp_delete_group_for_blog' );
add_action( 'make_delete_blog', 'thatcamp_delete_group_for_blog' );

/**
 * When a user is added to a blog, add him to the corresponding group
 *
 * @param int
 */
function thatcamp_add_user_to_group( $user_id, $role, $blog_id ) {
	$group_id = thatcamp_get_blog_group( $blog_id );

	if ( $group_id ) {
		$group_role = 'member';
		if ( 'administrator' == $role ) {
			$group_role = 'admin';
		} else if ( 'editor' == $role ) {
			$group_role = 'mod';
		}

		thatcamp_add_member_to_group( $user_id, $group_id, $group_role );
	}
}
add_action( 'add_user_to_blog', 'thatcamp_add_user_to_group', 10, 3 );

/**
 * Get a blog's group_id
 *
 * @param int $blog_id
 * @return int|bool Returns a group id if one is found; otherwise returns false
 */
function thatcamp_get_blog_group( $blog_id = 0 ) {
	global $wpdb, $bp;

	$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'blog_id' AND meta_value = %d", $blog_id ) );

	$retval = $group_id ? (int) $group_id : false;
	return $retval;
}

/**
 * Get a group's blog_id
 *
 * @param int $group_id
 * @return int|bool Returns a group id if one is found; otherwise returns false
 */
function thatcamp_get_group_blog( $group_id = 0 ) {
	$blog_id = groups_get_groupmeta( $group_id, 'blog_id' );

	if ( $blog_id ) {
		return (int) $blog_id;
	} else {
		return false;
	}
}

/**
 * Keep people from visiting group pages
 */
function thatcamp_redirect_from_group_pages() {
	if ( bp_is_group() ) {
		$blog_id = thatcamp_get_group_blog( bp_get_current_group_id() );
		$blog_url = get_blog_option( $blog_id, 'home' );
		if ( $blog_url ) {
			bp_core_redirect( $blog_url );
		}
	}
}
add_action( 'bp_actions', 'thatcamp_redirect_from_group_pages' );

/**
 * A short version of groups_join_group(), without notification baggage
 *
 * @param int $user_id
 * @param int $group_id
 * @role string Desired group role. 'member', 'mod', or 'admin'
 */
function thatcamp_add_member_to_group( $user_id, $group_id, $role ) {
	$new_member                = new BP_Groups_Member;
	$new_member->group_id      = $group_id;
	$new_member->user_id       = $user_id;
	$new_member->inviter_id    = 0;
	$new_member->is_admin      = 0;
	$new_member->user_title    = '';
	$new_member->date_modified = bp_core_current_time();
	$new_member->is_confirmed  = 1;
	$new_member->save();

	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') + 1 );

	if ( 'admin' == $role || 'mod' == $role ) {
		groups_promote_member( $user_id, $group_id, $role );
	}
}

/**
 * Converts a WP capabilities value to a corresponding group role
 *
 * 'administrator' becomes 'admin'; 'editor' becomes 'mod';
 * everything else becomes 'member'
 *
 * @param array $caps The value of get_user_meta( $uid, 'wp_x_capabilities', true )
 */
function thatcamp_convert_caps_to_group_role( $caps ) {
	// Convert blog caps to group caps
	$role = 'member';
	if ( isset( $caps['administrator'] ) ) {
		$role = 'admin';
	} else if ( isset( $caps['editor'] ) ) {
		$role = 'mod';
	}

	return $role;
}

////////////////////////
// TEMPLATE FUNCTIONS //
////////////////////////

/**
 * Echoes the permalink of a THATCamp
 *
 * Used within the groups loop, and returns a link to the group's blog
 *
 * Use this instead of bp_group_permalink()
 */
function thatcamp_camp_permalink() {
	echo thatcamp_get_camp_permalink();
}
	function thatcamp_get_camp_permalink() {
		$blog_id = thatcamp_get_group_blog( bp_get_group_id() );
		return get_blog_option( $blog_id, 'siteurl' );
	}

/**
 * Returns a pretty-formatted date + location for group directories
 */
function thatcamp_camp_description() {
	$date_array = array();

	/*
	if ( $start_date = thatcamp_get_camp_date( bp_get_group_id(), 'text' ) ) {
		$date_array[] = $start_date;
	}

	if ( $end_date = thatcamp_get_camp_date( bp_get_group_id(), 'text', 'end' ) ) {
		$date_array[] = $end_date;
	}

	if ( empty( $date_array ) ) {
		$pretty_date = 'TBA';
	} else {
		$pretty_date = implode( ' - ', $date_array );
	}

	$pretty_location = thatcamp_get_location( bp_get_group_id(), 'pretty' );

	$html = "<span class='thatcamp-meta-location thatcamp-meta-left'>$pretty_location</span>";
	$html .= '<br />';
*/

	global $groups_template;

	$date_created = date( 'M d, Y', strtotime( $groups_template->group->date_created ) );
	$last_active = bp_core_time_since( strtotime( $groups_template->group->last_activity ) );
	$members = 1 == $groups_template->group->total_member_count ? '1 camper' : sprintf( '%s campers', bp_core_number_format( $groups_template->group->total_member_count ) );
	$html = "<span class='thatcamp-date-created thatcamp-meta-left'>Created $date_created</span> <span class='thatcamp-last-active'>Active $last_active</span> <span class='thatcamp-member-count thatcamp-meta-left'>$members</span>";

	echo $html;
}

/**
 * Returns the THATCamp's date range in a pretty format
 */
function thatcamp_get_camp_date_pretty( $group_id = 0 ) {
	$group_id = thatcamp_fallback_group( $group_id );

	$date_array = array();

	if ( $start_date = thatcamp_get_camp_date( bp_get_group_id(), 'text' ) ) {
		$date_array[] = $start_date;
	}

	if ( $end_date = thatcamp_get_camp_date( bp_get_group_id(), 'text', 'end' ) ) {
		$date_array[] = $end_date;
	}

	if ( empty( $date_array ) ) {
		$pretty_date = 'TBA';
	} else {
		$pretty_date = implode( ' - ', $date_array );
	}

	// uber hack. To make things look OK on the front page, break right before the hyphen
	if ( is_front_page() ) {
		$pretty_date = str_replace( '-', '<br />-', $pretty_date );
	}

	return $pretty_date;
}

/**
 * Echoes the THATCamp's date
 *
 * Used within the groups loop
 */
function thatcamp_camp_date( $group_id = 0, $format = '', $type = 'start' ) {
	echo thatcamp_get_camp_date( $group_id, $format, $type );
}
	function thatcamp_get_camp_date( $group_id = 0, $format = '', $type = 'start' ) {
		$group_id = thatcamp_fallback_group( $group_id );

		$key = 'end' === $type ? 'thatcamp_end_date' : 'thatcamp_start_date';
		$date = groups_get_groupmeta( $group_id, $key );

		// backward compatibility
		if ( 'start' === $type && ! $date ) {
			$date = groups_get_groupmeta( $group_id, 'thatcamp_date' );
		}

		if ( ! $date ) {
			return '';
		}

		$formats = array( 'text', 'mmddyy', 'unix' );
		if ( ! in_array( $format, $formats ) ) {
			$format = 'text';
		}

		switch ( $format ) {
			case 'text' :
				$date = gmdate( "F j, Y", $date );
				break;

			case 'mmddyy' :
				$date = gmdate( "m/d/Y", $date );
				break;

			case 'unix' :
				$date = gmdate( "U", intval( $date ) );
				break;
		}

		return $date;
	}

function thatcamp_get_location( $group_id = 0, $type = 'pretty' ) {
	$group_id = thatcamp_fallback_group( $group_id );

	switch ( $type ) {
		case 'country' :
		case 'state' :
		case 'province' :
		case 'city' :
			$key = 'thatcamp_' . $type;
			$location = groups_get_groupmeta( $group_id, $key );
			break;

		case 'pretty' :
		default :
			$location_array = array();
			foreach ( array( 'country', 'state', 'province', 'city' ) as $ltype ) {
				$maybe_l = thatcamp_get_location( $group_id, $ltype );
				if ( $maybe_l ) {
					$location_array[ $ltype ] = $maybe_l;
				}

				$location = implode( ', ', array_reverse( $location_array ) );
			}
			break;

	}

	return $location;
}

/**
 * Is a camp in the future?
 *
 * We do a little adjustment to make sure that camps happening today show up in this list
 */
function thatcamp_is_in_the_future() {
	 $camp_date = thatcamp_get_camp_date( bp_get_group_id(), 'unix' );
	 $day_after = $camp_date + 60*60*24;
	 return $day_after >= time();
}

/**
 * Get a list of group ids that don't have dates associated with them
 */
function thatcamp_groups_without_dates() {
	global $bp, $wpdb;

	$all_groups  = array_map( 'intval', $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name}" ) );
	$date_groups = array_map( 'intval', $wpdb->get_col( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'thatcamp_date' AND meta_value != ''" ) );

	// Misc exceptions. Add more here if you want
	$except = array( bp_get_root_blog_id() );

	if ( function_exists( 'get_sitewide_tags_option' ) ) {
		$except[] = get_sitewide_tags_option( 'tags_blog_id' );
	}

	//var_dump( array_diff( $all_groups, $date_groups, $except ));
	return array_diff( $all_groups, $date_groups, $except );
}

/**
 * Does this THATCamp have workshops?
 *
 * @return string 'yes', 'maybe', 'no'
 */
function thatcamp_camp_has_workshops( $group_id = 0 ) {
	echo thatcamp_get_camp_has_workshops( $group_id );
}
	function thatcamp_get_camp_has_workshops( $group_id = 0 ) {
		$group_id = thatcamp_fallback_group( $group_id );

		$values = array( 'yes', 'maybe', 'no' );
		$value  = groups_get_groupmeta( $group_id, 'thatcamp_has_workshops' );
		if ( ! in_array( $value, $values ) ) {
			$value = 'no';
		}

		return $value;
	}

/**************************************************
 * MIGRATION
 *************************************************/

/**
 * Create groups for existing blogs
 *
 * This is for migrating existing blogs to the new group setup. To use, visit
 * wp-admin/network/?migrate_existing_blogs=1 as a super admin
 */
function thatcamp_migrate_existing_blogs() {
	global $wpdb;

	if ( ! is_network_admin() || ! is_super_admin() ) {
		return;
	}

	if ( empty( $_GET['migrate_existing_blogs'] ) ) {
		return;
	}

	$blog_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs" ) );

	// Skip the main site (and maybe others?)
	$exclude_blog_ids = array( 1 );
	foreach ( $blog_ids as $blog ) {
		$blog_id = $blog->blog_id;

		if ( in_array( $blog_id, $exclude_blog_ids ) ) {
			continue;
		}

		// If there's already a group, skip it
		if ( thatcamp_get_blog_group( $blog_id ) ) {
			continue;
		}

		$group_id = thatcamp_create_group_for_new_blog( $blog_id );

		// Get a real last activity time
		$last_post = $wpdb->get_var( $wpdb->prepare( "SELECT post_modified FROM {$wpdb->get_blog_prefix( $blog_id )}posts ORDER BY post_modified DESC LIMIT 1" ) );
		$last_comment = $wpdb->get_var( $wpdb->prepare( "SELECT comment_date FROM {$wpdb->get_blog_prefix( $blog_id )}comments ORDER BY comment_date DESC LIMIT 1" ) );
		$last_activity = strtotime( $last_post ) > strtotime( $last_comment ) ? $last_post : $last_comment;
		groups_update_groupmeta( $group_id, 'last_activity', $last_activity );

		// Run the member sync
		thatcamp_group_blog_member_sync( $blog_id );
	}
}
add_action( 'admin_init', 'thatcamp_migrate_existing_blogs' );

/**
 * Sync blog membership to group
 */
function thatcamp_group_blog_member_sync( $blog_id = 0 ) {
	$group_id = thatcamp_get_blog_group( $blog_id );

	if ( ! $group_id ) {
		return;
	}

	// Call up blog users
	$users = new WP_User_Query( array( 'blog_id' => $blog_id ) );

	foreach ( $users->results as $user ) {
		$caps_key = 'wp_' . $blog_id . '_capabilities';
		$caps = get_user_meta( $user->ID, $caps_key, true );

		$role = thatcamp_convert_caps_to_group_role( $caps );

		thatcamp_add_member_to_group( $user->ID, $group_id, $role );
	}
}


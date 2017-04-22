<?php
/**
 * Plugin Name: SSV Users
 * Plugin URI: https://bosso.nl/ssv-users/
 * Description: SSV Users is a plugin that allows you to manage members of a Students Sports Club the way you want to. With this plugin you can:
 * - Have a frontend registration and login page
 * - Customize member data fields,
 * - Easy manage, view and edit member profiles.
 * - Etc.
 * This plugin is fully compatible with the SSV library which can add functionality like: MailChimp, Events, etc.
 * Version: 3.1.0
 * Author: moridrin
 * Author URI: http://nl.linkedin.com/in/jberkvens/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/txt/copying/
 */
use mp_ssv_general\SSV_General;
use mp_ssv_general\User;
use mp_ssv_users\SSV_Users;

if (!defined('ABSPATH')) {
    exit;
}

#region Register
function mp_ssv_users_register_plugin()
{
    if (empty(SSV_Users::getPageIDsWithTag(SSV_Users::TAG_REGISTER_FIELDS))) {
        /* Pages */
        $registerPost = array(
            'post_content' => SSV_Users::TAG_REGISTER_FIELDS,
            'post_name'    => 'register',
            'post_title'   => 'Register',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($registerPost);
    }
    if (empty(SSV_Users::getPageIDsWithTag(SSV_Users::TAG_LOGIN_FIELDS))) {
        $loginPost = array(
            'post_content' => SSV_Users::TAG_LOGIN_FIELDS,
            'post_name'    => 'login',
            'post_title'   => 'Login',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($loginPost);
    }
    if (empty(SSV_Users::getPageIDsWithTag(SSV_Users::TAG_PROFILE_FIELDS))) {
        $profilePost = array(
            'post_content' => SSV_Users::TAG_PROFILE_FIELDS,
            'post_name'    => 'profile',
            'post_title'   => 'Profile',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($profilePost);
    }
    if (empty(SSV_Users::getPageIDsWithTag(SSV_Users::TAG_CHANGE_PASSWORD))) {
        $changePasswordPost = array(
            'post_content' => SSV_Users::TAG_CHANGE_PASSWORD,
            'post_name'    => 'change-password',
            'post_title'   => 'Change Password',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($changePasswordPost);
    }
    if (empty(SSV_Users::getPageIDsWithTag(SSV_Users::TAG_LOST_PASSWORD))) {
        $lostPasswordPost = array(
            'post_content' => SSV_Users::TAG_LOST_PASSWORD,
            'post_name'    => 'lost-password',
            'post_title'   => 'Lost Password',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        wp_insert_post($lostPasswordPost);
    }

    SSV_Users::resetOptions();
}

register_activation_hook(__FILE__, 'mp_ssv_users_register_plugin');
register_activation_hook(__FILE__, 'mp_ssv_general_register_plugin');
#endregion

#region Unregister
function mp_ssv_users_unregister()
{
    global $wpdb;
    $customFieldsTag = SSV_Users::TAG_PROFILE_FIELDS;
    $results         = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_content LIKE '%$customFieldsTag%'");
    foreach ($results as $key => $row) {
        wp_delete_post($row->ID);
    }
}

register_deactivation_hook(__FILE__, 'mp_ssv_users_unregister');
#endregion

#region Reset Options
/**
 * This function will reset the events options if the admin referer originates from the SSV Events plugin.
 *
 * @param $admin_referer
 */
function mp_ssv_users_reset_options($admin_referer)
{
    if (!mp_ssv_starts_with($admin_referer, 'ssv_users__')) {
        return;
    }
    SSV_Users::resetOptions();
}

add_filter(SSV_General::HOOK_RESET_OPTIONS, 'mp_ssv_users_reset_options');
#endregion

#region Avatar
/**
 * This function gets the user avatar (profile picture).
 *
 * @param string $avatar      is the avatar component that is requested in this method.
 * @param mixed  $id_or_email is either the User ID (int) or the User Email (string).
 * @param int    $size        is the size of the requested avatar in px. Default this is 150.
 * @param null   $default     If the user does not have an avatar the default is returned.
 * @param string $alt         is the alt text of the <img> component.
 * @param array  $args        is an array of extra arguments that can be given.
 *
 * @return string The <img> component of the avatar.
 */
function ssv_users_avatar($avatar, $id_or_email, $size = 150, $default = null, $alt = '', $args = array())
{
    $user = false;

    if (is_numeric($id_or_email)) {
        $id   = (int)$id_or_email;
        $user = get_user_by('id', $id);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $id   = (int)$id_or_email->user_id;
            $user = get_user_by('id', $id);
        }
    } else {
        $user = get_user_by('email', $id_or_email);
    }

    if ($user && is_object($user)) {
        $args['url'] = esc_url(get_user_meta($user->ID, 'profile_picture', true));
    }

    return $avatar ?: $default;
}

add_filter('get_avatar', 'ssv_users_avatar', 1, 6);
#endregion

#region Custom Authentication
/**
 * This function overrides the normal WordPress login function. With this function you can login with both your
 * username and your email.
 *
 * @param WP_User $user     is the current user component.
 * @param string  $login    is either the Users Email or the Username.
 * @param string  $password is the password for the user.
 *
 * @return false|WP_Error|WP_User returns a WP_Error if the login fails and returns the WP_User component for the user
 *                                that just logged in if the login is successful.
 */
function ssv_users_authenticate($user, $login, $password)
{
    if (empty($login) || empty ($password)) {
        $error = new WP_Error();
        if (empty($login)) {
            $error->add('empty_username', __('<strong>ERROR</strong>: Email/Username field is empty.'));
        }
        if (empty($password)) {
            $error->add('empty_password', __('<strong>ERROR</strong>: Password field is empty.'));
        }

        return $error;
    }

    if (!$user) {
        $user = get_user_by('email', $login);
    }
    if (!$user) {
        $user = get_user_by('login', $login);
    }
    if (!$user) {
        $error = new WP_Error();
        $error->add('invalid', __('<strong>ERROR</strong>: Either the email/username or password you entered is invalid. The email you entered was: ' . $login));

        return $error;
    } else {
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            $error = new WP_Error();
            $error->add('invalid', __('<strong>ERROR</strong>: The password you entered is invalid.'));

            return $error;
        } else {
            return $user;
        }
    }
}

add_filter('authenticate', 'ssv_users_authenticate', 20, 3);
#endregion

#region Set Profile Page Title
function mp_ssv_users_set_profile_page_title($title, $id)
{
    $pages       = SSV_Users::getPagesWithTag(SSV_Users::TAG_PROFILE_FIELDS);
    $correctPage = null;
    foreach ($pages as $page) {
        if ($page->ID == $id) {
            $correctPage = $page;
        }
    }
    if ($correctPage == null) {
        return $title;
    }
    if (isset($_GET['member']) && User::currentUserCan('edit_users')) {
        if (!User::getByID($_GET['member'])) {
            return $title;
        }
        return User::getByID($_GET['member'])->display_name;
    }
    return $title;
}

add_filter('the_title', 'mp_ssv_users_set_profile_page_title', 20, 2);
#endregion

#region Export
function mp_ssv_users_generate_data()
{
    if (SSV_General::isValidPOST(SSV_Users::ADMIN_REFERER_EXPORT)) {
        // Fields
        if (isset($_POST['field_names'])) {
            $fields = SSV_General::sanitize($_POST['field_names']);
            $fields = empty($fields) ? array() : explode(',', $fields);
            update_option(SSV_Users::OPTION_USER_EXPORT_COLUMNS, json_encode($fields));
        } else {
            $fields = json_decode(get_option(SSV_Users::OPTION_USER_EXPORT_COLUMNS));
        }
        if (empty($fields)) { //If nothing is specified, select all fields.
            $fields = SSV_Users::getInputFieldNames();
        }
        // Filters
        $filters = array();
        foreach ($_POST as $key => $value) {
            if (mp_ssv_starts_with($key, 'filter_')) {
                $filterKey = str_replace('filter_', '', $key);
                $filters[$filterKey] = $_POST[$filterKey];
            }
        }
//        SSV_General::var_export($filters, 1);
        // Users
        $users = array();
        foreach (get_users() as $user) {
            $matchesFilters = true;
            $user = new User($user);
            foreach ($filters as $key => $value) {
                if (strpos($user->getMeta($key), $value) === false) {
                    $matchesFilters = false;
                    break;
                }
            }
            if ($matchesFilters) {
                $users[] = $user;
            }
        }
        SSV_General::var_export($users, 1);
        SSV_Users::export($users, $fields);
    }
}

add_action('admin_init', 'mp_ssv_users_generate_data');
#endregion
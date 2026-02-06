<?php

/**
 * Update: user_roles in options with fresh data
 */
function jlt_admin_bar_editor_update_user_roles_data()
{
    global $wp_roles;

    $option_key = '_jltadminbar_settings';
    $existing_options = get_option($option_key, array());

    if (empty($existing_options)) {
        return;
    }

    $users = get_users();
    $roles = $wp_roles->roles;

    $new_roles_array = array();

    if (is_multisite()) {
        $new_roles_array[] = 'Super Admin';
    }

    foreach ($roles as $key => $role) {
        $new_roles_array['roles'][] = array(
            'value' => $key,
            'label' => $role['name'],
        );
    }

    foreach ($users as $user) {
        $new_roles_array['users'][] = array(
            'value' => $user->user_login,
            'label' => $user->display_name,
        );
    }

    $existing_options['user_roles'] = $new_roles_array;

    update_option($option_key, $existing_options);
}
jlt_admin_bar_editor_update_user_roles_data();

// update version once migration is completed.
update_option($this->option_name, $version);

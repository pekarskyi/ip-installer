<?php
/**
 * AJAX Handlers
 */

// Prevent direct access to file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for plugin activation
 */
function ip_installer_ajax_activate_plugin() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_installer_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ip-installer')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ip-installer')));
    }
    
    // Get plugin ID
    if (!isset($_POST['plugin_id'])) {
        wp_send_json_error(array('message' => __('Missing plugin ID.', 'ip-installer')));
    }
    
    // Get plugin data
    $plugin_id = sanitize_text_field($_POST['plugin_id']);
    $plugins_list = ip_installer_get_plugins_list();
    
    if (!isset($plugins_list[$plugin_id])) {
        wp_send_json_error(array('message' => __('Plugin not found.', 'ip-installer')));
    }
    
    // Get plugin directory name
    $plugin_dir = basename($plugins_list[$plugin_id]['github_url']);
    
    // Try to find the plugin file
    $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
    
    // Check if plugin file exists first
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        // Try to find main plugin file if the default naming convention is not used
        $plugin_files = glob(WP_PLUGIN_DIR . '/' . $plugin_dir . '/*.php');
        if (!empty($plugin_files)) {
            foreach ($plugin_files as $file) {
                if (!function_exists('get_plugin_data')) {
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                $plugin_data = get_plugin_data($file);
                if (!empty($plugin_data['Name'])) {
                    $plugin_file = $plugin_dir . '/' . basename($file);
                    break;
                }
            }
        } else {
            wp_send_json_error(array('message' => __('Plugin main file not found.', 'ip-installer')));
        }
    }
    
    // Include necessary WordPress functions
    if (!function_exists('activate_plugin')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    // Activate the plugin
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array('message' => __('Plugin activated successfully.', 'ip-installer')));
    }
}
add_action('wp_ajax_ip_installer_activate', 'ip_installer_ajax_activate_plugin');

/**
 * AJAX handler for plugin deactivation
 */
function ip_installer_ajax_deactivate_plugin() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_installer_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ip-installer')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ip-installer')));
    }
    
    // Get plugin ID
    if (!isset($_POST['plugin_id'])) {
        wp_send_json_error(array('message' => __('Missing plugin ID.', 'ip-installer')));
    }
    
    // Get plugin data
    $plugin_id = sanitize_text_field($_POST['plugin_id']);
    $plugins_list = ip_installer_get_plugins_list();
    
    if (!isset($plugins_list[$plugin_id])) {
        wp_send_json_error(array('message' => __('Plugin not found.', 'ip-installer')));
    }
    
    // Get plugin directory name
    $plugin_dir = basename($plugins_list[$plugin_id]['github_url']);
    
    // Try to find the plugin file
    $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
    
    // Check if plugin file exists first
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        // Try to find main plugin file if the default naming convention is not used
        $plugin_files = glob(WP_PLUGIN_DIR . '/' . $plugin_dir . '/*.php');
        if (!empty($plugin_files)) {
            foreach ($plugin_files as $file) {
                if (!function_exists('get_plugin_data')) {
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                $plugin_data = get_plugin_data($file);
                if (!empty($plugin_data['Name'])) {
                    $plugin_file = $plugin_dir . '/' . basename($file);
                    break;
                }
            }
        } else {
            wp_send_json_error(array('message' => __('Plugin main file not found.', 'ip-installer')));
        }
    }
    
    // Include necessary WordPress functions
    if (!function_exists('deactivate_plugins')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    // Deactivate the plugin
    deactivate_plugins($plugin_file);
    
    wp_send_json_success(array('message' => __('Plugin deactivated successfully.', 'ip-installer')));
}
add_action('wp_ajax_ip_installer_deactivate', 'ip_installer_ajax_deactivate_plugin');

/**
 * AJAX handler for plugin installation
 */
function ip_installer_ajax_install_plugin() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_installer_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ip-installer')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ip-installer')));
    }
    
    // Get plugin ID
    if (!isset($_POST['plugin_id'])) {
        wp_send_json_error(array('message' => __('Missing plugin ID.', 'ip-installer')));
    }
    
    // Get plugin data
    $plugin_id = sanitize_text_field($_POST['plugin_id']);
    $plugins_list = ip_installer_get_plugins_list();
    
    if (!isset($plugins_list[$plugin_id])) {
        wp_send_json_error(array('message' => __('Plugin not found.', 'ip-installer')));
    }
    
    $plugin = $plugins_list[$plugin_id];
    
    // Install plugin
    $result = false;
    $version = '';
    
    if ($plugin['installation_type'] === 'plugin') {
        $result = ip_installer_install_plugin($plugin);
        if (!is_wp_error($result)) {
            $plugin_dir = basename($plugin['github_url']);
            $version = ip_installer_get_plugin_version($plugin_dir);
        }
    } else {
        $result = ip_installer_install_script($plugin);
        if (!is_wp_error($result)) {
            $version = ip_installer_get_script_version($plugin['filename']);
        }
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array(
            'message' => sprintf(__('Plugin "%s" installed successfully.', 'ip-installer'), $plugin['name']),
            'version' => $version
        ));
    }
}
add_action('wp_ajax_ip_installer_install', 'ip_installer_ajax_install_plugin');

/**
 * AJAX handler for plugin uninstallation
 */
function ip_installer_ajax_uninstall_plugin() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_installer_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ip-installer')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ip-installer')));
    }
    
    // Get plugin ID
    if (!isset($_POST['plugin_id'])) {
        wp_send_json_error(array('message' => __('Missing plugin ID.', 'ip-installer')));
    }
    
    // Get plugin data
    $plugin_id = sanitize_text_field($_POST['plugin_id']);
    $plugins_list = ip_installer_get_plugins_list();
    
    if (!isset($plugins_list[$plugin_id])) {
        wp_send_json_error(array('message' => __('Plugin not found.', 'ip-installer')));
    }
    
    $plugin = $plugins_list[$plugin_id];
    
    // Uninstall plugin
    if ($plugin['installation_type'] === 'plugin') {
        // Get plugin directory
        $plugin_dir = basename($plugin['github_url']);
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_dir;
        
        // Try to find the plugin file for deactivation
        $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
        
        // Check if plugin file exists first
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            // Include necessary WordPress functions
            if (!function_exists('is_plugin_active') || !function_exists('deactivate_plugins')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // Deactivate the plugin if it's active
            if (is_plugin_active($plugin_file)) {
                deactivate_plugins($plugin_file);
            }
        } else {
            // Try to find main plugin file if the default naming convention is not used
            $plugin_files = glob($plugin_path . '/*.php');
            if (!empty($plugin_files)) {
                foreach ($plugin_files as $file) {
                    if (!function_exists('get_plugin_data')) {
                        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                    }
                    $plugin_data = get_plugin_data($file);
                    if (!empty($plugin_data['Name'])) {
                        $plugin_file = $plugin_dir . '/' . basename($file);
                        
                        // Deactivate the plugin if it's active
                        if (is_plugin_active($plugin_file)) {
                            deactivate_plugins($plugin_file);
                        }
                        
                        break;
                    }
                }
            }
        }
        
        // Now delete the plugin directory
        if (file_exists($plugin_path)) {
            WP_Filesystem();
            global $wp_filesystem;
            
            $deleted = $wp_filesystem->delete($plugin_path, true);
            
            if (!$deleted) {
                wp_send_json_error(array('message' => __('Failed to delete plugin files.', 'ip-installer')));
            } else {
                wp_send_json_success(array('message' => sprintf(__('Plugin "%s" uninstalled successfully.', 'ip-installer'), $plugin['name'])));
            }
        } else {
            wp_send_json_error(array('message' => __('Plugin directory not found.', 'ip-installer')));
        }
    } else {
        // For script, just delete the file
        $script_path = ABSPATH . $plugin['filename'];
        
        if (file_exists($script_path)) {
            if (unlink($script_path)) {
                wp_send_json_success(array('message' => sprintf(__('Script "%s" uninstalled successfully.', 'ip-installer'), $plugin['name'])));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete script file.', 'ip-installer')));
            }
        } else {
            wp_send_json_error(array('message' => __('Script file not found.', 'ip-installer')));
        }
    }
}
add_action('wp_ajax_ip_installer_uninstall', 'ip_installer_ajax_uninstall_plugin');

/**
 * AJAX handler for settings update
 */
function ip_installer_ajax_update_settings() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_installer_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'ip-installer')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'ip-installer')));
    }
    
    // Update settings
    $delete_on_uninstall = isset($_POST['delete_on_uninstall']) ? intval($_POST['delete_on_uninstall']) : 0;
    update_option('ip_installer_delete_on_uninstall', $delete_on_uninstall);
    
    wp_send_json_success(array('message' => __('Settings updated successfully.', 'ip-installer')));
}
add_action('wp_ajax_ip_installer_update_settings', 'ip_installer_ajax_update_settings'); 
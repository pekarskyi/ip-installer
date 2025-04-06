<?php
/**
 * Plugin Name: IP Installer
 * Plugin URI: https://github.com/pekarskyi/
 * Description: Plugin for installing other plugins and scripts from GitHub repositories.
 * Version: 1.0.0
 * Author: InwebPress
 * Author URI: https://inwebpress.com
 * Text Domain: ip-installer
 * Domain Path: /lang
 * Requires at least: 6.7.0
 * Tested up to: 6.7.2
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.7.1
 * WooCommerce: true
 * Custom_Order_Tables_Compatibility: compatible
 */

// Prevent direct access to file
if (!defined('ABSPATH')) {
    exit;
}

$github_api_key = '';

// Constants definition
$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');
define('IP_INSTALLER_VERSION', $plugin_data['Version']);
define('IP_INSTALLER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IP_INSTALLER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('IP_INSTALLER_SCRIPTS_DIR', ABSPATH);

// Plugin activation function
function ip_installer_activate() {
    // Create settings table
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_installer_settings';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        option_name varchar(255) NOT NULL,
        option_value longtext NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY option_name (option_name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set option for table deletion on plugin deactivation
    $wpdb->insert(
        $table_name,
        array(
            'option_name' => 'delete_on_uninstall',
            'option_value' => '0',
        )
    );
}
register_activation_hook(__FILE__, 'ip_installer_activate');

// Plugin deactivation function
function ip_installer_deactivate() {
    // Deactivation code
}
register_deactivation_hook(__FILE__, 'ip_installer_deactivate');

// Load localization
function ip_installer_load_textdomain() {
    load_plugin_textdomain('ip-installer', false, dirname(plugin_basename(__FILE__)) . '/lang');
}
add_action('plugins_loaded', 'ip_installer_load_textdomain');

// Declare HPOS compatibility for WooCommerce
function ip_installer_declare_hpos_compatibility() {
    // Перевірка наявності WooCommerce
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Реєстрація сумісності з HPOS через API WooCommerce
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'ip_installer_declare_hpos_compatibility');

// Add styles and scripts
function ip_installer_enqueue_scripts($hook) {
    // Check if we are on our plugin page
    if (strpos($hook, 'ip-installer') === false) {
        return;
    }
    
    wp_enqueue_style('ip-installer-css', IP_INSTALLER_PLUGIN_URL . 'css/ip-installer.css', array(), IP_INSTALLER_VERSION);
    wp_enqueue_script('ip-installer-js', IP_INSTALLER_PLUGIN_URL . 'js/ip-installer.js', array('jquery'), IP_INSTALLER_VERSION, true);
    
    $plugins_list = ip_installer_get_plugins_list();
    $plugin_files = array();
    
    foreach ($plugins_list as $plugin_id => $plugin) {
        if ($plugin['installation_type'] === 'plugin') {
            $plugin_dir = basename($plugin['github_url']);
            $plugin_file = ip_installer_find_plugin_file($plugin_dir);
            if ($plugin_file) {
                $plugin_files[$plugin_id] = $plugin_file;
            }
        }
    }
    
    wp_localize_script('ip-installer-js', 'ip_installer_obj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ip_installer_nonce'),
        'plugins_page' => admin_url('plugins.php'),
        'plugin_files' => $plugin_files,
        'wp_delete_nonce' => wp_create_nonce('bulk-plugins'),
    ));
    
    // Add localized strings for front-end
    wp_localize_script('ip-installer-js', 'ipInstallerL10n', array(
        'installed'    => __('Installed', 'ip-installer'),
        'notInstalled' => __('Not Installed', 'ip-installer'),
        'install'      => __('Install', 'ip-installer'),
        'uninstall'    => __('Uninstall', 'ip-installer'),
        'active'       => __('Active', 'ip-installer'),
        'inactive'     => __('Inactive', 'ip-installer'),
        'activate'     => __('Activate', 'ip-installer'),
        'deactivate'   => __('Deactivate', 'ip-installer'),
        'update'       => __('Update to %s', 'ip-installer'),
        'updated'      => __('Plugin %s updated successfully!', 'ip-installer'),
        'updatedLatest' => __('Plugin %s updated successfully to the latest version %s!', 'ip-installer'),
        'goTo'         => __('Go to', 'ip-installer'),
        'checkUpdates' => __('Check for updates', 'ip-installer'),
        'lastChecked'  => __('Last checked: %s', 'ip-installer'),
        'updateCheckComplete' => __('Update check completed. All plugins and scripts are up to date.', 'ip-installer'),
        'updateCheckCompleteFound' => _n_noop('Update check completed. Found %d update available.', 'Update check completed. Found %d updates available.', 'ip-installer'),
        'ajaxError'    => __('AJAX request error. Please try again.', 'ip-installer'),
        'confirmUninstall' => __('Are you sure you want to uninstall this plugin? You will be redirected to the WordPress plugins page to complete the uninstallation.', 'ip-installer'),
    ));
}
add_action('admin_enqueue_scripts', 'ip_installer_enqueue_scripts');

// Add admin menu item
function ip_installer_add_admin_menu() {
    add_menu_page(
        __('IP Installer', 'ip-installer'),
        __('IP Installer', 'ip-installer'),
        'manage_options',
        'ip-installer',
        'ip_installer_admin_page',
        'dashicons-download',
        50
    );
    
    add_submenu_page(
        'ip-installer',
        __('Settings', 'ip-installer'),
        __('Settings', 'ip-installer'),
        'manage_options',
        'ip-installer-settings',
        'ip_installer_settings_page'
    );
}
add_action('admin_menu', 'ip_installer_add_admin_menu');


function ip_installer_register_settings() {
}
add_action('admin_init', 'ip_installer_register_settings');

// Add settings link on plugins page
function ip_installer_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=ip-installer">' . __('Settings', 'ip-installer') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ip_installer_add_settings_link');

// List of available plugins and scripts
function ip_installer_get_plugins_list() {
    return array(
        'ip-get-logger' => array(
            'name' => 'IP GET Logger',
            'description' => __('WordPress plugin for monitoring, logging, and alerting suspicious GET requests on your website. It acts as an additional security layer, helping you detect potential threats at an early stage.', 'ip-installer'),
            'redirect_url' => admin_url('admin.php?page=ip-get-logger'),
            'github_url' => 'https://github.com/pekarskyi/ip-get-logger',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
        'ip-delivery-shipping' => array(
            'name' => 'Delivery for WooCommerce',
            'description' => __('Delivery (UA) Shipping Method for WooCommerce.', 'ip-installer'),
            'redirect_url' => admin_url('admin.php?page=ip-delivery-settings'),
            'github_url' => 'https://github.com/pekarskyi/ip-delivery-shipping',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
        'ip-wordpress-url-replacer' => array(
            'name' => 'IP WordPress URL Replacer',
            'description' => __('WordPress URL Replacer is a simple yet powerful tool that instantly replaces any URLs directly in your site\'s database. No complicated settings or bulky plugins — just fast, simple, and effective.', 'ip-installer'),
            'redirect_url' => site_url('/wur-script.php'),
            'github_url' => 'https://github.com/pekarskyi/ip-wordpress-url-replacer',
            'installation_type' => 'script',
            'install_path' => ABSPATH,
            'filename' => 'wur-script.php',
        ),
        'ip-debug-log-viewer' => array(
            'name' => 'IP Debug Log Viewer',
            'description' => __('A WordPress debugging tool that displays PHP errors from debug.log in a structured and user-friendly format.', 'ip-installer'),
            'redirect_url' => site_url('/ip-debug-viewer.php'),
            'github_url' => 'https://github.com/pekarskyi/ip-debug-log-viewer',
            'installation_type' => 'script',
            'install_path' => ABSPATH,
            'filename' => 'ip-debug-viewer.php',
        ),
        'ip-language-quick-switcher-for-wp' => array(
            'name' => 'IP Language Quick Switcher for WordPress',
            'description' => __('The plugin lets you quickly switch between different languages without digging into WordPress general or profile settings.', 'ip-installer'),
            'redirect_url' => '',
            'github_url' => 'https://github.com/pekarskyi/ip-language-quick-switcher-for-wp',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
        'ip-search-log' => array(
            'name' => 'IP Search Log',
            'description' => __('A WordPress plugin for logging user search queries on your site. It offers a convenient interface in the WordPress admin panel to view and analyze the collected data, along with the option to export it in Excel format.', 'ip-installer'),
            'redirect_url' => admin_url('admin.php?page=ip-search-log'),
            'github_url' => 'https://github.com/pekarskyi/ip-search-log',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
        'ip-woo-attribute-converter' => array(
            'name' => 'IP Woo Attributes Converter',
            'description' => __('A WordPress plugin that converts local (custom) product attributes into global attributes.', 'ip-installer'),
            'redirect_url' => admin_url('admin.php?page=ipwacg'),
            'github_url' => 'https://github.com/pekarskyi/ip-woo-attribute-converter',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
        'ip-woo-cleaner' => array(
            'name' => 'IP Woo Cleaner',
            'description' => __('A simple WordPress plugin for cleaning up the database by removing products, categories, attributes, tags, coupons, orders, and order notes.', 'ip-installer'),
            'redirect_url' => admin_url('admin.php?page=woo-cleaner'),
            'github_url' => 'https://github.com/pekarskyi/ip-woo-cleaner',
            'installation_type' => 'plugin',
            'install_path' => WP_PLUGIN_DIR,
        ),
    );
}

// Main plugin page
function ip_installer_admin_page() {
    require_once IP_INSTALLER_PLUGIN_PATH . 'templates/admin-page.php';
}

// Settings page
function ip_installer_settings_page() {
    require_once IP_INSTALLER_PLUGIN_PATH . 'templates/settings-page.php';
}

// Functions for working with settings
function ip_installer_get_setting($option_name, $default = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_installer_settings';
    
    $value = $wpdb->get_var(
        $wpdb->prepare("SELECT option_value FROM $table_name WHERE option_name = %s", $option_name)
    );
    
    return $value !== null ? $value : $default;
}

function ip_installer_update_setting($option_name, $option_value) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_installer_settings';
    
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE option_name = %s", $option_name)
    );
    
    if ($exists) {
        $wpdb->update(
            $table_name,
            array('option_value' => $option_value),
            array('option_name' => $option_name)
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'option_name' => $option_name,
                'option_value' => $option_value,
            )
        );
    }
}

function ip_installer_get_github_api_key() {
    global $github_api_key;
    
    if (defined('IP_INSTALLER_GITHUB_API_KEY')) {
        return IP_INSTALLER_GITHUB_API_KEY;
    }
    
    if (!empty($github_api_key)) {
        return $github_api_key;
    }
    
    return '';
}

// Include additional files
require_once IP_INSTALLER_PLUGIN_PATH . 'includes/installer-functions.php';
require_once IP_INSTALLER_PLUGIN_PATH . 'includes/ajax-handlers.php';

function ip_installer_process_forms() {
    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_install' && isset($_POST['plugin_id'])) {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $plugins = ip_installer_get_plugins_list();

        if (!isset($plugins[$plugin_id])) {
            wp_die('Plugin not found');
        }

        $plugin = $plugins[$plugin_id];
        $success = false;
        $version = '';

        if ($plugin['installation_type'] === 'plugin') {
            $success = ip_installer_install_plugin($plugin);
            if ($success) {
                $plugin_dir = basename($plugin['github_url']);
                $version = ip_installer_get_plugin_version($plugin_dir);
            }
        } else {
            $success = ip_installer_install_script($plugin);
            if ($success) {
                $version = ip_installer_get_script_version($plugin['filename']);
            }
        }

        if ($success) {
            wp_redirect(admin_url('admin.php?page=ip-installer&installed=1&plugin=' . urlencode($plugin['name']) . '&version=' . urlencode($version)));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_uninstall' && isset($_POST['plugin_id'])) {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $plugins = ip_installer_get_plugins_list();

        if (!isset($plugins[$plugin_id])) {
            wp_die('Plugin not found');
        }

        $plugin = $plugins[$plugin_id];
        
        if ($plugin['installation_type'] === 'plugin') {
            $plugin_dir = basename($plugin['github_url']);
            $plugin_file = '';
            
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_dir)) {
                $standard_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
                if (file_exists(WP_PLUGIN_DIR . '/' . $standard_file)) {
                    $plugin_file = $standard_file;
                } else {
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
                    }
                }
            }
            
            if (!empty($plugin_file) && is_plugin_active($plugin_file)) {
                deactivate_plugins($plugin_file);
            }
            
            $plugin_dir_path = WP_PLUGIN_DIR . '/' . $plugin_dir;
            if (file_exists($plugin_dir_path)) {
                WP_Filesystem();
                global $wp_filesystem;
                
                $wp_filesystem->delete($plugin_dir_path, true);
                
                wp_redirect(admin_url('admin.php?page=ip-installer&uninstalled=1&plugin=' . urlencode($plugin['name'])));
                exit;
            }
        } else {
            $script_path = ABSPATH . $plugin['filename'];
            
            if (file_exists($script_path)) {
                WP_Filesystem();
                global $wp_filesystem;
                
                $wp_filesystem->delete($script_path);
                
                wp_redirect(admin_url('admin.php?page=ip-installer&uninstalled=1&plugin=' . urlencode($plugin['name'])));
                exit;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_activate' && isset($_POST['plugin_id'])) {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $plugins = ip_installer_get_plugins_list();

        if (!isset($plugins[$plugin_id])) {
            wp_die('Plugin not found');
        }

        $plugin = $plugins[$plugin_id];
        
        if ($plugin['installation_type'] === 'plugin') {
            $plugin_dir = basename($plugin['github_url']);
            
            $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
            
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                // Шукаємо інші PHP файли
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
                    wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
                    exit;
                }
            }
            
            if (!function_exists('activate_plugin')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            $result = activate_plugin($plugin_file);
            
            if (is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=ip-installer&activated=1'));
                exit;
            }
        } else {
            wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_deactivate' && isset($_POST['plugin_id'])) {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $plugins = ip_installer_get_plugins_list();

        if (!isset($plugins[$plugin_id])) {
            wp_die('Plugin not found');
        }

        $plugin = $plugins[$plugin_id];
        
        if ($plugin['installation_type'] === 'plugin') {
            $plugin_dir = basename($plugin['github_url']);
            
            $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
            
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                // Шукаємо інші PHP файли
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
                    wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
                    exit;
                }
            }
            
            if (!function_exists('deactivate_plugins')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            deactivate_plugins($plugin_file);
            wp_redirect(admin_url('admin.php?page=ip-installer&deactivated=1'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_update' && isset($_POST['plugin_id'])) {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $plugins = ip_installer_get_plugins_list();

        if (!isset($plugins[$plugin_id])) {
            wp_die('Plugin not found');
        }

        $plugin = $plugins[$plugin_id];
        $success = false;
        $version = '';
        $was_active = false;

        if ($plugin['installation_type'] === 'plugin') {
            $plugin_dir = basename($plugin['github_url']);
            
            $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
            
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
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
                }
            }
            
            if (!empty($plugin_file)) {
                if (!function_exists('is_plugin_active')) {
                    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                $was_active = is_plugin_active($plugin_file);
                
                if ($was_active) {
                    deactivate_plugins($plugin_file);
                }
            }
        }

        if ($plugin['installation_type'] === 'plugin') {
            $success = ip_installer_install_plugin($plugin);
            if ($success) {
                $plugin_dir = basename($plugin['github_url']);
                $version = ip_installer_get_plugin_version($plugin_dir);
                
                if ($was_active) {
                    $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';
                    
                    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
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
                        }
                    }
                    
                    if (!empty($plugin_file)) {
                        if (!function_exists('activate_plugin')) {
                            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                        }
                        activate_plugin($plugin_file);
                    }
                }
                
                $available_updates = get_option('ip_installer_available_updates', array());
                
                $new_version = ip_installer_check_update($plugin['github_url'], $version);
                
                if ($new_version) {
                    $available_updates[$plugin_id] = $new_version;
                } else {
                    if (isset($available_updates[$plugin_id])) {
                        unset($available_updates[$plugin_id]);
                    }
                }
                
                update_option('ip_installer_available_updates', $available_updates);
            }
        } else {
            $success = ip_installer_install_script($plugin);
            if ($success) {
                $version = ip_installer_get_script_version($plugin['filename']);
                $available_updates = get_option('ip_installer_available_updates', array());
                
                $new_version = ip_installer_check_update($plugin['github_url'], $version);
                
                if ($new_version) {
                    $available_updates[$plugin_id] = $new_version;
                } else {
                    if (isset($available_updates[$plugin_id])) {
                        unset($available_updates[$plugin_id]);
                    }
                }
                
                update_option('ip_installer_available_updates', $available_updates);
            }
        }

        if ($success) {
            $is_latest = false;
            $available_updates = get_option('ip_installer_available_updates', array());
            if (!isset($available_updates[$plugin_id])) {
                $is_latest = true;
            }
            
            $url_params = array(
                'page' => 'ip-installer',
                'installed' => 1,
                'plugin' => urlencode($plugin['name']),
                'version' => urlencode($version),
                'updated' => 1
            );
            
            if ($is_latest) {
                $url_params['latest'] = 1;
            }
            
            wp_redirect(add_query_arg($url_params, admin_url('admin.php')));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=ip-installer&error=1'));
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_check_all_updates') {
        if (!check_admin_referer('ip_installer_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        delete_option('ip_installer_available_updates');
        
        $plugins = ip_installer_get_plugins_list();
        $available_updates = array();
        $updates_found = 0;

        foreach ($plugins as $plugin_id => $plugin) {
            if ($plugin['installation_type'] === 'plugin') {
                $plugin_dir = basename($plugin['github_url']);
                if (ip_installer_is_plugin_installed($plugin_dir)) {
                    $current_version = ip_installer_get_plugin_version($plugin_dir);
                    if (!empty($current_version)) {
                        $new_version = ip_installer_check_update($plugin['github_url'], $current_version);
                        if ($new_version) {
                            $available_updates[$plugin_id] = $new_version;
                            $updates_found++;
                        }
                    }
                }
            } else {
                if (file_exists(ABSPATH . $plugin['filename'])) {
                    $current_version = ip_installer_get_script_version($plugin['filename']);
                    if (!empty($current_version)) {
                        $new_version = ip_installer_check_update($plugin['github_url'], $current_version);
                        if ($new_version) {
                            $available_updates[$plugin_id] = $new_version;
                            $updates_found++;
                        }
                    }
                }
            }
        }

        update_option('ip_installer_available_updates', $available_updates);
        update_option('ip_installer_last_check', current_time('timestamp'));

        wp_redirect(admin_url('admin.php?page=ip-installer&updates_checked=1&count=' . $updates_found));
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'ip_installer_update_settings') {
        if (!check_admin_referer('ip_installer_settings_nonce', 'settings_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action');
        }

        $delete_on_uninstall = isset($_POST['delete_on_uninstall']) ? 1 : 0;
        update_option('ip_installer_delete_on_uninstall', $delete_on_uninstall);
        
        wp_redirect(admin_url('admin.php?page=ip-installer-settings&settings_updated=1'));
        exit;
    }
}
add_action('admin_init', 'ip_installer_process_forms');

// Adding update check via GitHub
require_once plugin_dir_path( __FILE__ ) . 'updates/github-updater.php';

$github_username = 'pekarskyi';
$repo_name = 'ip-installer';
$prefix = 'ip_installer';

if ( function_exists( 'ip_github_updater_load' ) ) {
    ip_github_updater_load($prefix);
    
    $updater_function = $prefix . '_github_updater_init';   
    
    if ( function_exists( $updater_function ) ) {
        call_user_func(
            $updater_function,
            __FILE__,       // Plugin file path
            $github_username, // Your GitHub username
            $github_api_key,              // Access token (empty)
            $repo_name       // Repository name (на основі префіксу)
        );
    }
}

function ip_installer_find_plugin_file($plugin_dir) {
    $standard_file = $plugin_dir . '/' . $plugin_dir . '.php';
    if (file_exists(WP_PLUGIN_DIR . '/' . $standard_file)) {
        return $standard_file;
    }

    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    foreach ($all_plugins as $file => $data) {
        if (strpos($file, $plugin_dir . '/') === 0) {
            return $file;
        }
    }
    
    return false;
}
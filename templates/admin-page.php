<?php
/**
 * Template for the main page of IP Installer
 */

// Prevent direct access to file
if (!defined('ABSPATH')) {
    exit;
}

// Get list of available plugins
$plugins = ip_installer_get_plugins_list();
?>

<!-- Заголовок сторінки -->
<div class="wrap">
    <h1><?php _e('IP Installer', 'ip-installer'); ?></h1>
    
    <?php if (isset($_GET['activated']) && $_GET['activated'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Plugin activated successfully!', 'ip-installer'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deactivated']) && $_GET['deactivated'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Plugin deactivated successfully!', 'ip-installer'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['installed']) && $_GET['installed'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                    if (isset($_GET['updated']) && $_GET['updated'] == 1) {
                        if (isset($_GET['latest']) && $_GET['latest'] == 1) {
                            printf(
                                __('Plugin %s updated successfully to the latest version %s!', 'ip-installer'), 
                                '<strong>' . esc_html($_GET['plugin']) . '</strong>',
                                '<strong>' . esc_html($_GET['version']) . '</strong>'
                            );
                        } else {
                            printf(
                                __('Plugin %s updated successfully!', 'ip-installer'), 
                                '<strong>' . esc_html($_GET['plugin']) . '</strong>'
                            );
                        }
                    } else {
                        printf(
                            __('Plugin %s installed successfully!', 'ip-installer'), 
                            '<strong>' . esc_html($_GET['plugin']) . '</strong>'
                        );
                    }
                    
                    if (!empty($_GET['version']) && (!isset($_GET['latest']) || $_GET['latest'] != 1)) {
                        echo ' ' . sprintf(__('Version: %s', 'ip-installer'), '<strong>' . esc_html($_GET['version']) . '</strong>');
                    }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['uninstalled']) && $_GET['uninstalled'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                    printf(
                        __('Plugin %s uninstalled successfully!', 'ip-installer'), 
                        '<strong>' . esc_html($_GET['plugin']) . '</strong>'
                    ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 1) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('An error occurred. Please try again.', 'ip-installer'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updates_checked']) && $_GET['updates_checked'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                    $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                    if ($count > 0) {
                        printf(
                            _n(
                                'Update check completed. Found %d update available.',
                                'Update check completed. Found %d updates available.',
                                $count,
                                'ip-installer'
                            ),
                            $count
                        );
                    } else {
                        _e('Update check completed. All plugins and scripts are up to date.', 'ip-installer');
                    }
                    
                    // Show last check time
                    $last_check = get_option('ip_installer_last_check');
                    if ($last_check) {
                        echo ' ';
                        printf(
                            __('Last checked: %s', 'ip-installer'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_check)
                        );
                    }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- WordPress Plugins Section -->
    <div id="plugins-section" class="ip-installer-section">
        <div class="card">
            <h2><?php _e('WordPress Plugins', 'ip-installer'); ?></h2>
            
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th class="column-name"><?php _e('Name', 'ip-installer'); ?></th>
                        <th class="column-description"><?php _e('Description', 'ip-installer'); ?></th>
                        <th class="column-status"><?php _e('Status', 'ip-installer'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'ip-installer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugins as $plugin_id => $plugin): ?>
                        <?php if ($plugin['installation_type'] === 'plugin'): ?>
                            <?php
                            // Get plugin info
                            $plugin_dir = basename($plugin['github_url']);
                            $is_installed = ip_installer_is_plugin_installed($plugin_dir);
                            
                            // If installed, check if it's active
                            $is_active = false;
                            $plugin_file = '';
                            
                            if ($is_installed) {
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
                                    }
                                }
                                
                                // Now check if the plugin is active
                                if (!empty($plugin_file)) {
                                    if (!function_exists('is_plugin_active')) {
                                        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                                    }
                                    $is_active = is_plugin_active($plugin_file);
                                }
                                
                                // Get plugin version
                                $version = ip_installer_get_plugin_version($plugin_dir);
                                
                                // Check for updates - тепер не перевіряємо автоматично
                                $new_version = false;
                                // Використовуємо дані з опції про перевірені оновлення
                                $updates = get_option('ip_installer_available_updates', array());
                                if (!empty($version) && isset($updates[$plugin_id])) {
                                    $new_version = $updates[$plugin_id];
                                }
                            }
                            ?>
                            <tr>
                                <td class="column-name">
                                    <strong><?php echo esc_html($plugin['name']); ?></strong>
                                </td>
                                <td class="column-description">
                                    <?php echo esc_html($plugin['description']); ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($is_installed): ?>
                                        <span class="status-installed"><?php _e('Installed', 'ip-installer'); ?></span>
                                        <?php if (!empty($version)): ?>
                                            <span class="version">v<?php echo esc_html($version); ?></span>
                                        <?php endif; ?>
                                        <br>
                                        <?php if ($is_active): ?>
                                            <span class="status-active"><?php _e('Active', 'ip-installer'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactive', 'ip-installer'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-not-installed"><?php _e('Not Installed', 'ip-installer'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <?php if ($is_installed) : ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="action" value="ip_installer_uninstall">
                                            <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                            <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                            <button type="submit" class="button red">
                                                <?php _e('Uninstall', 'ip-installer'); ?>
                                            </button>
                                        </form>
                                        
                                        <?php if ($is_active) : ?>
                                            <form method="post" action="" style="display: inline-block;">
                                                <input type="hidden" name="action" value="ip_installer_deactivate">
                                                <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                                <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                                <button type="submit" class="button red">
                                                    <?php _e('Deactivate', 'ip-installer'); ?>
                                                </button>
                                            </form>
                                        <?php else : ?>
                                            <form method="post" action="" style="display: inline-block;">
                                                <input type="hidden" name="action" value="ip_installer_activate">
                                                <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                                <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                                <button type="submit" class="button green">
                                                    <?php _e('Activate', 'ip-installer'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($new_version) : ?>
                                            <form method="post" action="" style="display: inline-block;">
                                                <input type="hidden" name="action" value="ip_installer_update">
                                                <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                                <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                                <button type="submit" class="button button-primary update-button">
                                                    <?php printf(__('Update to %s', 'ip-installer'), $new_version); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($is_active && !empty($plugin['redirect_url'])) : ?>
                                            <a 
                                                href="<?php echo esc_url($plugin['redirect_url']); ?>" 
                                                class="button button-primary" 
                                                target="_blank"
                                            >
                                                <?php _e('Go to', 'ip-installer'); ?>
                                            </a>
                                        <?php else : ?>
                                            <button class="button button-primary" disabled>
                                                <?php _e('Go to', 'ip-installer'); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="action" value="ip_installer_install">
                                            <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                            <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                            <button type="submit" class="button green">
                                                <?php _e('Install', 'ip-installer'); ?>
                                            </button>
                                        </form>
                                        
                                        <button class="button button-primary" disabled>
                                            <?php _e('Go to', 'ip-installer'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Scripts Section -->
    <div id="scripts-section" class="ip-installer-section">
        <div class="card">
            <h2><?php _e('Scripts', 'ip-installer'); ?></h2>
            
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th class="column-name"><?php _e('Name', 'ip-installer'); ?></th>
                        <th class="column-description"><?php _e('Description', 'ip-installer'); ?></th>
                        <th class="column-status"><?php _e('Status', 'ip-installer'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'ip-installer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugins as $plugin_id => $plugin): ?>
                        <?php if ($plugin['installation_type'] === 'script'): ?>
                            <?php
                            // Get script info
                            $is_installed = file_exists(ABSPATH . $plugin['filename']);
                            
                            // Get script version if installed
                            $version = '';
                            if ($is_installed) {
                                $version = ip_installer_get_script_version($plugin['filename']);
                                
                                // Check for updates - тепер не перевіряємо автоматично
                                $new_version = false;
                                // Використовуємо дані з опції про перевірені оновлення
                                $updates = get_option('ip_installer_available_updates', array());
                                if (!empty($version) && isset($updates[$plugin_id])) {
                                    $new_version = $updates[$plugin_id];
                                }
                            }
                            ?>
                            <tr>
                                <td class="column-name">
                                    <strong><?php echo esc_html($plugin['name']); ?></strong>
                                </td>
                                <td class="column-description">
                                    <?php echo esc_html($plugin['description']); ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($is_installed): ?>
                                        <span class="status-installed"><?php _e('Installed', 'ip-installer'); ?></span>
                                        <?php if (!empty($version)): ?>
                                            <span class="version">v<?php echo esc_html($version); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-not-installed"><?php _e('Not Installed', 'ip-installer'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <?php if ($is_installed) : ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="action" value="ip_installer_uninstall">
                                            <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                            <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                            <button type="submit" class="button red">
                                                <?php _e('Uninstall', 'ip-installer'); ?>
                                            </button>
                                        </form>
                                        
                                        <?php if ($new_version) : ?>
                                            <form method="post" action="" style="display: inline-block;">
                                                <input type="hidden" name="action" value="ip_installer_update">
                                                <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                                <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                                <button type="submit" class="button button-primary update-button">
                                                    <?php printf(__('Update to %s', 'ip-installer'), $new_version); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($plugin['redirect_url'])) : ?>
                                            <a 
                                                href="<?php echo esc_url($plugin['redirect_url']); ?>" 
                                                class="button button-primary" 
                                                target="_blank"
                                            >
                                                <?php _e('Go to', 'ip-installer'); ?>
                                            </a>
                                        <?php else : ?>
                                            <button class="button button-primary" disabled>
                                                <?php _e('Go to', 'ip-installer'); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="action" value="ip_installer_install">
                                            <input type="hidden" name="plugin_id" value="<?php echo esc_attr($plugin_id); ?>">
                                            <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
                                            <button type="submit" class="button green">
                                                <?php _e('Install', 'ip-installer'); ?>
                                            </button>
                                        </form>
                                        
                                        <button class="button button-primary" disabled>
                                            <?php _e('Go to', 'ip-installer'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Кнопка перевірки оновлень -->
    <div class="ip-installer-action">
        <form method="post" action="">
            <input type="hidden" name="action" value="ip_installer_check_all_updates">
            <?php wp_nonce_field('ip_installer_nonce', 'nonce'); ?>
            <button type="submit" class="button button-primary">
                <?php _e('Check for updates', 'ip-installer'); ?>
            </button>
        </form>
        
        <?php
        // Показуємо час останньої перевірки
        $last_check = get_option('ip_installer_last_check');
        if ($last_check) {
            echo '<p class="last-check-info">';
            printf(
                __('Last checked: %s', 'ip-installer'),
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_check)
            );
            echo '</p>';
        }
        ?>
    </div>
</div> 
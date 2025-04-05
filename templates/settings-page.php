<?php
/**
 * Template for the settings page of IP Installer
 */

// Prevent direct access to file
if (!defined('ABSPATH')) {
    exit;
}

// Get settings from database
$delete_on_uninstall = get_option('ip_installer_delete_on_uninstall', 0);
?>

<div class="wrap">
    <h1><?php _e('IP Installer Settings', 'ip-installer'); ?></h1>
    
    <div id="ip-installer-settings-messages"></div>
    
    <?php if (isset($_GET['settings_updated']) && $_GET['settings_updated'] == 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings updated successfully!', 'ip-installer'); ?></p>
        </div>
    <?php endif; ?>
    
    <form id="ip-installer-settings-form" method="post" action="">
        <input type="hidden" name="action" value="ip_installer_update_settings">
        <?php wp_nonce_field('ip_installer_settings_nonce', 'settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Plugin cleanup', 'ip-installer'); ?>
                </th>
                <td>
                    <div class="ip-toggle-container">
                        <label class="ip-toggle-switch">
                            <input type="checkbox" id="delete_on_uninstall" name="delete_on_uninstall" value="1" <?php checked($delete_on_uninstall, 1); ?>>
                            <span class="ip-toggle-slider"></span>
                        </label>
                        <span class="ip-toggle-label"><?php _e('Delete all plugin data when uninstalling', 'ip-installer'); ?></span>
                    </div>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php _e('Save Settings', 'ip-installer'); ?>
            </button>
        </p>
    </form>
</div> 
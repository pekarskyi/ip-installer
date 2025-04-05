/**
 * IP Installer JavaScript
 */

jQuery(document).ready(function($) {
    // Закриття сповіщень
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Обробник кнопки деінсталяції плагінів
    $(document).on('submit', '.ip-installer-uninstall-plugin-form', function(e) {
        e.preventDefault();
        
        const pluginId = $(this).find('input[name="plugin_id"]').val();
        const pluginFile = ip_installer_obj.plugin_files[pluginId];
        
        if (pluginFile) {
            if (confirm(ipInstallerL10n.confirmUninstall)) {
                // Перенаправлення на стандартну сторінку деінсталяції плагінів WordPress
                window.location.href = ip_installer_obj.plugins_page + 
                    '?action=delete-selected&checked[]=' + encodeURIComponent(pluginFile) + 
                    '&plugin_status=all&paged=1&s&_wpnonce=' + ip_installer_obj.wp_delete_nonce;
            }
        } else {
            // Для скриптів використовуємо стандартний обробник
            $(this).off('submit').submit();
        }
    });
}); 
<?php
/**
 * Functions for plugin and script installation
 */

// Prevent direct access to file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if plugin is installed
 *
 * @param string $plugin_dir Plugin directory
 * @return bool
 */
function ip_installer_is_plugin_installed($plugin_dir) {
    // For plugins
    if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_dir)) {
        return true;
    }
    
    return false;
}

/**
 * Check if script is installed
 *
 * @param string $script_path Path to script
 * @return bool
 */
function ip_installer_is_script_installed($script_path) {
    // For scripts
    if (file_exists(ABSPATH . $script_path)) {
        return true;
    }
    
    return false;
}

/**
 * Get download URL from GitHub
 *
 * @param string $github_url GitHub repository URL
 * @return string Download URL
 */
function ip_installer_get_github_release_url($github_url) {
    $repo_parts = explode('/', rtrim($github_url, '/'));
    $repo_name = end($repo_parts);
    $username = prev($repo_parts);
    
    // Підготовка заголовків запиту
    $headers = array(
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
    );
    
    // Додаємо токен аутентифікації, якщо він є
    $github_api_key = function_exists('ip_installer_get_github_api_key') ? ip_installer_get_github_api_key() : '';
    if (!empty($github_api_key)) {
        $headers['Authorization'] = 'token ' . $github_api_key;
    }
    
    // Спочатку перевіряємо наявність релізів
    $releases_url = "https://api.github.com/repos/{$username}/{$repo_name}/releases/latest";
    $releases_response = wp_remote_get($releases_url, array(
        'timeout' => 10,
        'headers' => $headers
    ));
    
    // Якщо знайдено останній реліз, використовуємо його
    if (!is_wp_error($releases_response) && wp_remote_retrieve_response_code($releases_response) === 200) {
        $release_data = json_decode(wp_remote_retrieve_body($releases_response), true);
        if (isset($release_data['zipball_url']) && !empty($release_data['zipball_url'])) {
            return $release_data['zipball_url'];
        }
    }
    
    // Якщо релізів немає, використовуємо прямі посилання на головну гілку (main або master)
    // Перевіряємо спочатку main, потім master
    $main_url = "https://github.com/{$username}/{$repo_name}/archive/refs/heads/main.zip";
    $master_url = "https://github.com/{$username}/{$repo_name}/archive/refs/heads/master.zip";
    
    // Перевіряємо доступність main.zip
    $main_response = wp_remote_head($main_url, array(
        'timeout' => 30,
        'headers' => $headers,
    ));
    
    if (!is_wp_error($main_response) && wp_remote_retrieve_response_code($main_response) === 200) {
        return $main_url;
    }
    
    // Якщо main.zip недоступний, повертаємо master.zip
    return $master_url;
}

/**
 * Download and install plugin
 *
 * @param string $plugin_data Plugin data
 * @return bool|WP_Error Installation result
 */
function ip_installer_install_plugin($plugin_data) {
    $download_url = ip_installer_get_github_release_url($plugin_data['github_url']);
    
    if (is_wp_error($download_url)) {
        return $download_url;
    }
    
    // Initialize WordPress file system
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
    }
    
    // Create temporary directory
    $temp_dir = get_temp_dir() . 'ip_installer_' . md5(time());
    if (!wp_mkdir_p($temp_dir)) {
        return new WP_Error(
            'temp_dir_create_failed',
            __('Failed to create temporary directory for download.', 'ip-installer')
        );
    }
    
    // Download archive
    $temp_file = $temp_dir . '/plugin.zip';
    
    // Підготовка заголовків запиту
    $headers = array(
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
    );
    
    // Додаємо токен аутентифікації, якщо він є
    $github_api_key = function_exists('ip_installer_get_github_api_key') ? ip_installer_get_github_api_key() : '';
    if (!empty($github_api_key)) {
        $headers['Authorization'] = 'token ' . $github_api_key;
    }
    
    // Use curl for download if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        $fp = fopen($temp_file, 'w+');
        
        curl_setopt($ch, CURLOPT_URL, $download_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        
        // Додаємо заголовок авторизації, якщо є API-ключ
        if (!empty($github_api_key)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: token ' . $github_api_key));
        }
        
        curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if (!empty($curl_error)) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'download_failed',
                sprintf(__('CURL Error: %s', 'ip-installer'), $curl_error)
            );
        }
        
        if ($http_code !== 200) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'download_failed',
                sprintf(__('Failed to download archive. Response code: %s', 'ip-installer'), $http_code)
            );
        }
    } else {
        // Alternative download method via wp_remote_get
        $download_response = wp_remote_get($download_url, array(
            'timeout' => 300,
            'stream' => true,
            'filename' => $temp_file,
            'headers' => $headers,
            'sslverify' => false,
            'redirection' => 5,
        ));
        
        if (is_wp_error($download_response)) {
            ip_installer_rrmdir($temp_dir);
            return $download_response;
        }
        
        $response_code = wp_remote_retrieve_response_code($download_response);
        if ($response_code !== 200) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'download_failed',
                sprintf(__('Failed to download archive. Response code: %s', 'ip-installer'), $response_code)
            );
        }
    }
    
    // Перевірка, чи файл існує і не порожній
    if (!file_exists($temp_file) || filesize($temp_file) == 0) {
        ip_installer_rrmdir($temp_dir);
        return new WP_Error(
            'download_failed',
            __('Downloaded file is missing or empty.', 'ip-installer')
        );
    }
    
    // Extract archive
    $unzip_result = unzip_file($temp_file, $temp_dir);
    
    if (is_wp_error($unzip_result)) {
        ip_installer_rrmdir($temp_dir);
        return $unzip_result;
    }
    
    // Find extracted directory (GitHub usually creates a directory with repository name and hash)
    $directories = glob($temp_dir . '/*', GLOB_ONLYDIR);
    
    if (empty($directories)) {
        ip_installer_rrmdir($temp_dir);
        return new WP_Error(
            'extract_failed',
            __('Failed to find extracted plugin files.', 'ip-installer')
        );
    }
    
    $extracted_dir = $directories[0];
    
    // Depending on the type, install in plugins folder or root directory
    if ($plugin_data['installation_type'] === 'plugin') {
        // Determine plugin directory name
        $plugin_dir_name = basename($plugin_data['github_url']);
        $target_dir = WP_PLUGIN_DIR . '/' . $plugin_dir_name;
        
        // Remove old version if exists
        if (file_exists($target_dir)) {
            ip_installer_rrmdir($target_dir);
        }
        
        // Create plugin directory
        wp_mkdir_p($target_dir);
        
        // Copy files
        if (!copy_dir($extracted_dir, $target_dir)) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'copy_failed',
                __('Failed to copy plugin files.', 'ip-installer')
            );
        }
    } else if ($plugin_data['installation_type'] === 'script') {
        // For scripts, determine filename
        $filename = isset($plugin_data['filename']) ? $plugin_data['filename'] : basename($plugin_data['github_url']) . '.php';
        
        // Search for PHP file in extracted directory
        $script_files = glob($extracted_dir . '/*.php');
        
        if (empty($script_files)) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'script_not_found',
                __('Failed to find PHP script file.', 'ip-installer')
            );
        }
        
        // Copy file to site root
        $source_file = $script_files[0]; // Take the first PHP file found
        $target_file = ABSPATH . $filename;
        
        if (!$wp_filesystem->copy($source_file, $target_file, true)) {
            ip_installer_rrmdir($temp_dir);
            return new WP_Error(
                'copy_failed',
                __('Failed to copy script file.', 'ip-installer')
            );
        }
    }
    
    // Clean up
    ip_installer_rrmdir($temp_dir);
    
    return true;
}

/**
 * Install script file
 *
 * @param string $plugin_data Script data
 * @return bool|WP_Error Installation result
 */
function ip_installer_install_script($plugin_data) {
    return ip_installer_install_plugin($plugin_data);
}

/**
 * Recursively delete directory
 *
 * @param string $dir Directory path
 * @return bool Result
 */
function ip_installer_rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (is_dir($dir . '/' . $object)) {
                    ip_installer_rrmdir($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
        }
        return rmdir($dir);
    }
    return false;
}

/**
 * Get plugin version
 *
 * @param string $plugin_dir Plugin directory
 * @return string|bool Plugin version or false if not found
 */
function ip_installer_get_plugin_version($plugin_dir) {
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    // Очищення кешу інформації про плагіни
    wp_cache_delete('plugins', 'plugins');
    
    // Try standard plugin file naming
    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_dir . '/' . basename($plugin_dir) . '.php';
    
    // If standard naming doesn't work, try to find main plugin file
    if (!file_exists($plugin_file)) {
        $plugin_files = glob(WP_PLUGIN_DIR . '/' . $plugin_dir . '/*.php');
        if (!empty($plugin_files)) {
            foreach ($plugin_files as $file) {
                // Read plugin data
                $plugin_data = get_plugin_data($file, false, false);
                
                // If file has plugin data like name and version, use it
                if (!empty($plugin_data['Name'])) {
                    $plugin_file = $file;
                    break;
                }
            }
        } else {
            return false;
        }
    }
    
    // If file doesn't exist after all checks
    if (!file_exists($plugin_file)) {
        return false;
    }
    
    // Get plugin data and return version
    $plugin_data = get_plugin_data($plugin_file, false, false);
    
    if (!empty($plugin_data['Version'])) {
        return $plugin_data['Version'];
    }
    
    return false;
}

/**
 * Get script version
 *
 * @param string $filename Script filename
 * @return string|bool Script version or false if not found
 */
function ip_installer_get_script_version($filename) {
    $file_path = ABSPATH . $filename;
    
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Read first 200 lines of the file to find a version
    $handle = fopen($file_path, 'r');
    $version = false;
    
    if ($handle) {
        $line_count = 0;
        $found = false;
        
        while (($line = fgets($handle)) !== false && $line_count < 200 && !$found) {
            // Look for version in PHP comments or version declaration
            if (preg_match('/[Vv]ersion:\s*([0-9.]+)/', $line, $matches) || 
                preg_match('/\$version\s*=\s*[\'"]([0-9.]+)[\'"]/', $line, $matches) ||
                preg_match('/define\s*\(\s*[\'"]VERSION[\'"],\s*[\'"]([0-9.]+)[\'"]/', $line, $matches)) {
                $version = $matches[1];
                $found = true;
            }
            
            $line_count++;
        }
        
        fclose($handle);
    }
    
    return $version;
}

/**
 * Перевірка наявності нової версії з GitHub
 * 
 * @param string $github_url URL GitHub репозиторію
 * @param string $current_version Поточна версія плагіна
 * @return string|false Нова версія або false, якщо нема оновлень
 */
function ip_installer_check_update($github_url, $current_version) {
    if (empty($current_version)) {
        return false;
    }
    
    // Отримуємо інформацію про репозиторій
    $repo_parts = explode('/', rtrim($github_url, '/'));
    $repo_name = end($repo_parts);
    $username = prev($repo_parts);
    
    // Підготовка заголовків запиту
    $headers = array(
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        'Accept' => 'application/vnd.github.v3+json', // Використовуємо останню версію API
    );
    
    // Додаємо токен аутентифікації, якщо він є
    $github_api_key = function_exists('ip_installer_get_github_api_key') ? ip_installer_get_github_api_key() : '';
    if (!empty($github_api_key)) {
        $headers['Authorization'] = 'token ' . $github_api_key;
    }
    
    // 1. Спочатку перевіряємо через API релізів GitHub
    $releases_url = "https://api.github.com/repos/{$username}/{$repo_name}/releases/latest";
    $releases_response = wp_remote_get($releases_url, array(
        'timeout' => 10,
        'headers' => $headers,
    ));
    
    if (!is_wp_error($releases_response) && wp_remote_retrieve_response_code($releases_response) === 200) {
        $release_data = json_decode(wp_remote_retrieve_body($releases_response), true);
        
        if (isset($release_data['tag_name'])) {
            // Форматуємо версію з тегу (видаляємо 'v' або інші префікси)
            $release_version = preg_replace('/^[vV]/', '', $release_data['tag_name']);
            
            // Очищаємо версію, щоб залишити тільки числа та крапки
            $release_version = preg_replace('/[^0-9\.]/', '', $release_version);
            
            // Порівнюємо версії
            if (!empty($release_version) && version_compare($release_version, $current_version, '>')) {
                return $release_version;
            }
        }
    }
    
    // 2. Якщо релізи не знайдені, перевіряємо файли у репозиторії
    $api_url = "https://api.github.com/repos/{$username}/{$repo_name}/contents";
    
    // Запит до GitHub API
    $response = wp_remote_get($api_url, array(
        'timeout' => 10,
        'headers' => $headers,
    ));
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $files = json_decode($body, true);
    
    if (!is_array($files)) {
        return false;
    }
    
    // Шукаємо файл з версією (README.md або основний PHP файл)
    $readme_url = "";
    $main_php_url = "";
    
    foreach ($files as $file) {
        if ($file['name'] === 'README.md') {
            $readme_url = $file['download_url'];
        } elseif ($file['name'] === $repo_name . '.php') {
            $main_php_url = $file['download_url'];
        }
    }
    
    // Спробуємо отримати версію з README.md
    if (!empty($readme_url)) {
        $readme_response = wp_remote_get($readme_url, array(
            'timeout' => 10,
            'headers' => $headers,
        ));
        
        if (!is_wp_error($readme_response) && wp_remote_retrieve_response_code($readme_response) === 200) {
            $readme_content = wp_remote_retrieve_body($readme_response);
            
            // Шукаємо рядок з версією в форматі "Version: X.X.X" або "v X.X.X"
            if (preg_match('/[Vv]ersion:?\s*([0-9\.]+)/', $readme_content, $matches)) {
                $github_version = $matches[1];
                
                // Порівнюємо версії
                if (version_compare($github_version, $current_version, '>')) {
                    return $github_version;
                }
            }
        }
    }
    
    // Якщо не знайшли версію в README, шукаємо в основному PHP файлі
    if (!empty($main_php_url)) {
        $php_response = wp_remote_get($main_php_url, array(
            'timeout' => 10,
            'headers' => $headers,
        ));
        
        if (!is_wp_error($php_response) && wp_remote_retrieve_response_code($php_response) === 200) {
            $php_content = wp_remote_retrieve_body($php_response);
            
            // Шукаємо рядок з версією у форматі, який використовується для плагінів WordPress
            if (preg_match('/Version:\s*([0-9\.]+)/', $php_content, $matches)) {
                $github_version = $matches[1];
                
                // Порівнюємо версії
                if (version_compare($github_version, $current_version, '>')) {
                    return $github_version;
                }
            }
        }
    }
    
    return false;
} 
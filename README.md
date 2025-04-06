# IP Installer

IP Installer is a WordPress plugin that simplifies the installation and update of my plugins and scripts directly from GitHub repositories.

[Читати опис українською](https://github.com/pekarskyi/ip-installer/blob/master/README_UA.md) <img src="https://github.com/pekarskyi/assets/raw/master/flags/ua.svg" width="17">

[![GitHub release (latest by date)](https://img.shields.io/github/v/release/pekarskyi/ip-installer?style=for-the-badge)](https://GitHub.com/pekarskyi/ip-installer/releases/)

## Features

- **One-Click Installation**: Install plugins and scripts from GitHub repositories with a single click
- **Plugin Management**: Activate, deactivate, and remove plugins directly from the administrative interface
- **Complete Plugin Uninstallation**: Uninstall plugins using the standard WordPress mechanism, ensuring full cleanup of their data in the database
- **Update Management**: Check for updates and easily upgrade to the latest versions
- **Script Support**: Install individual PHP scripts in the WordPress root directory
- **User-Friendly Interface**: Clean and intuitive interface for managing all your plugins and scripts

![https://github.com/pekarskyi/assets/raw/master/ip-installer/ip-installer.jpg](https://github.com/pekarskyi/assets/raw/master/ip-installer/ip-installer.jpg)

## How It Works

IP Installer connects to GitHub repositories to download and install plugins and scripts. The plugin provides an elegant administrative interface that allows you to:

1. View all available plugins and scripts
2. See which plugins are installed and their activation status
3. Check for available updates
4. Install, update, activate, deactivate, or remove with a single click
5. Completely uninstall plugins, including their data in the database, through the standard WordPress mechanism

## Supported Plugins and Scripts

IP Installer is pre-configured to work with several useful plugins and scripts:

### Plugins:
- [IP GET Logger](https://github.com/pekarskyi/ip-get-logger)
- [Delivery for WooCommerce](https://github.com/pekarskyi/ip-delivery-shipping)
- [IP Language Quick Switcher for WordPress](https://github.com/pekarskyi/ip-language-quick-switcher-for-wp)
- [IP Search Log](https://github.com/pekarskyi/ip-search-log)
- [IP Woo Attributes Converter](https://github.com/pekarskyi/ip-woo-attribute-converter)
- [IP Woo Cleaner](https://github.com/pekarskyi/ip-woo-cleaner)

### Scripts:
- [IP WordPress URL Replacer](https://github.com/pekarskyi/ip-wordpress-url-replacer)
- [IP Debug Log Viewer](https://github.com/pekarskyi/ip-debug-log-viewer)

## Localization:

- English
- Українська
- Русский

## Installation

1. Download the `IP Installer` plugin (green Code button - Download ZIP).
2. Upload it to your WordPress site. Make sure the plugin folder is named `ip-installer` (the name doesn't affect the plugin's operation, but it affects receiving further updates).
3. Activate the plugin.
4. Go to `IP Installer` in the admin sidebar.
5. Start installing and managing your plugins and scripts.

## Frequently Asked Questions

- How often does `IP Installer` check for updates? - `IP Installer` checks for updates only when you click the "Check for updates" button. This ensures minimal load on your server and GitHub API.

- Can I add my own GitHub repositories? - The current version supports a predefined list of repositories. The ability to add custom repositories may be added in future versions.

- Is this plugin compatible with multisite installations? - Yes, IP Installer works on WordPress multisite installations.

- Are plugin data removed from the database when uninstalled? - Yes, when removing plugins through IP Installer, the standard WordPress mechanism is used, which guarantees complete cleanup of the database from plugin data.

- How are scripts removed? - Scripts are removed directly from the file system since they don't create database records.

## Changelog

1.0.0 - 06.04.2025:
- Initial release 
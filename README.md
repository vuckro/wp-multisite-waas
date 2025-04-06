# WP Multisite WaaS

The WordPress Multisite Website as a Service (WaaS) plugin, now community maintained.

## Description

WP Multisite WaaS helps you transform your WordPress Multisite installation into a powerful Website as a Service (WaaS) platform. This plugin enables you to offer website creation, hosting, and management services to your customers through a streamlined interface.

## Installation

There are two ways to install WP Multisite WaaS:

### Method 1: Using the pre-packaged release (Recommended)

1. Download the latest release ZIP from the [Releases page](https://github.com/superdav42/wp-multisite-waas/releases)
2. Log in to your WordPress Network Admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Network Activate the plugin
6. Follow the setup wizard to complete the installation

### Method 2: Using Git and Composer (For developers)

This method requires command-line access to your server and familiarity with Git and Composer.

1. Clone the repository to your plugins directory:
   ```
   cd wp-content/plugins/
   git clone https://github.com/superdav42/wp-multisite-waas.git
   cd wp-multisite-waas
   ```

2. Install the required dependencies using Composer:
   ```
   composer install
   ```

3. Network Activate the plugin in your WordPress Network Admin dashboard
4. Follow the setup wizard to complete the installation

## Common Installation Issues

### "Failed opening required [...]/vendor/autoload_packages.php"

This error occurs when the required vendor files are missing. This typically happens when:
- You've downloaded the repository directly from GitHub without using a release package
- The composer dependencies haven't been installed

**Solution**: Use the pre-packaged release from the [Releases page](https://github.com/superdav42/wp-multisite-waas/releases) or run `composer install` in the plugin directory.

### "Cannot declare class ComposerAutoloaderInitWPUltimoDependencies, because the name is already in use"

This error usually occurs when updating from an older version of WP Ultimo or when multiple versions of the plugin are installed.

**Solution**: Deactivate and remove any older versions of WP Ultimo or WP Multisite WaaS before activating the new version.

### "Class 'WP_Ultimo\Database\Sites\Site_Query' not found"

This error can occur if the plugin's autoloader isn't properly loading all the necessary classes.

**Solution**: Use the pre-packaged release from the [Releases page](https://github.com/superdav42/wp-multisite-waas/releases) which includes all required files.

## Requirements

- WordPress Multisite 5.3 or higher
- PHP 7.4.30 or higher
- MySQL 5.6 or higher
- HTTPS enabled (recommended for secure checkout)

## Support

For support, please open an issue on the [GitHub repository](https://github.com/superdav42/wp-multisite-waas/issues).

## License

WP Multisite WaaS is licensed under the GPLv2 or later. 
<?php

if ( ! class_exists('MUCD_Files') ) {

	class MUCD_Files {

		/**
		 * Copy files from one site to another
		 *
		 * @since 0.2.0
		 * @param  int $from_site_id duplicated site id.
		 * @param  int $to_site_id   new site id.
		 */
		public static function copy_files($from_site_id, $to_site_id) {
			// Switch to Source site and get uploads info
			switch_to_blog($from_site_id);
			$wp_upload_info   = wp_upload_dir();
			$from_dir['path'] = $wp_upload_info['basedir'];
			MUCD_PRIMARY_SITE_ID === (int) $from_site_id ? $from_dir['exclude'] = MUCD_Option::get_primary_dir_exclude() : $from_dir['exclude'] = [];

			// Switch to Destination site and get uploads info
			switch_to_blog($to_site_id);
			$wp_upload_info = wp_upload_dir();
			$to_dir         = $wp_upload_info['basedir'];

			restore_current_blog();

			$dirs   = [];
			$dirs[] = [
				'from_dir_path' => $from_dir['path'],
				'to_dir_path'   => $to_dir,
				'exclude_dirs'  => $from_dir['exclude'],
			];

			$dirs = apply_filters('mucd_copy_dirs', $dirs, $from_site_id, $to_site_id);

			foreach ($dirs as $dir) {
				if (isset($dir['to_dir_path']) && ! self::init_dir($dir['to_dir_path'])) {
					self::mkdir_error($dir['to_dir_path'], $to_site_id);
				}

				MUCD_Duplicate::write_log('Copy files from ' . $dir['from_dir_path'] . ' to ' . $dir['to_dir_path']);
				self::recurse_copy($dir['from_dir_path'], $dir['to_dir_path'], $dir['exclude_dirs']);
			}

			return true;
		}

		/**
		 * Copy files from one directory to another
		 *
		 * @since 0.2.0
		 * @param  string $src source directory path.
		 * @param  string $dst destination directory path.
		 * @param  array  $exclude_dirs directories to ignore.
		 */
		public static function recurse_copy($src, $dst, $exclude_dirs = []): void {
			$src = rtrim($src, '/');
			$dst = rtrim($dst, '/');
			$dir = opendir($src);
			@mkdir($dst);
			while (false !== ($file = readdir($dir)) ) {
				if (('.' != $file) && ('..' != $file)) {
					if ( is_dir($src . '/' . $file) ) {
						if ( ! in_array($file, $exclude_dirs, true)) {
							self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
						}
					} else {
						copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}

			closedir($dir);
		}

		/**
		 * Set a directory writable, creates it if not exists, or return false
		 *
		 * @since 0.2.0
		 * @param  string $path the path.
		 * @return boolean True on success, False on failure
		 */
		public static function init_dir($path) {
			/** @var $wp_filesystem WP_Filesystem_Base */
			global $wp_filesystem;
			WP_Filesystem();
			if ( ! $wp_filesystem->exists($path)) {
				return $wp_filesystem->mkdir($path, 0777);
			} elseif (! $wp_filesystem->is_writable($path)) {
				return $wp_filesystem->chmod($path, 0777, true);
			}
		}

		/**
		 * Removes a directory and all its content
		 *
		 * @since 0.2.0
		 * @param string $dir the path.
		 */
		public static function rrmdir($dir): void {
			/** @var $wp_filesystem WP_Filesystem_Base */
			global $wp_filesystem;
			WP_Filesystem();
			$wp_filesystem->rmdir($dir, true);
		}

		/**
		 * Stop process on Creating dir Error, print and log error, removes the new blog
		 *
		 * @since 0.2.0
		 * @param  string $dir_path the path.
		 */
		public static function mkdir_error($dir_path): void {
			$error_1 = 'ERROR DURING FILE COPY : CANNOT CREATE ' . $dir_path;
			MUCD_Duplicate::write_log($error_1);
			$error_2 = sprintf(MUCD_NETWORK_PAGE_DUPLICATE_COPY_FILE_ERROR, MUCD_Functions::get_primary_upload_dir());
			MUCD_Duplicate::write_log($error_2);
			MUCD_Duplicate::write_log('Duplication interrupted on FILE COPY ERROR');
			echo '<br />Duplication failed :<br /><br />' . esc_html($error_1) . '<br /><br />' . esc_html($error_2) . '<br /><br />';
			$log_url = MUCD_Duplicate::log_url();
			if ( $log_url ) {
				echo '<a href="' . esc_attr($log_url) . '">' . esc_html(MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG) . '</a>';
			}

			MUCD_Functions::remove_blog(self::$to_site_id);
			wp_die();
		}
	}
}

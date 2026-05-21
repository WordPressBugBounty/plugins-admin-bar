<?php
namespace JewelTheme\AdminBarEditor\Inc\Classes;

use JewelTheme\AdminBarEditor\Libs\Recommended;

if ( ! class_exists( 'Recommended_Plugins' ) ) {
	/**
	 * Recommended Plugins class
	 *
	 * Jewel Theme <support@jeweltheme.com>
	 */
	class Recommended_Plugins extends Recommended {

		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct(
				'jlt_admin_bar_editor-settings',
				'pixarlabs',
				'',
				70
			);
		}
	}
}
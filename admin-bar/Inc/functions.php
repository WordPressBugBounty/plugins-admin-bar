<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/*
 * @version       1.0.0
 * @package       AdminBarEditor
 * @license       Copyright AdminBarEditor
 */

if ( ! function_exists( 'jlt_admin_bar_editor_option' ) ) {
	/**
	 * Get setting database option
	 *
	 * @param string $section default section name jlt_admin_bar_editor_general .
	 * @param string $key .
	 * @param string $default .
	 *
	 * @return string
	 */
	function jlt_admin_bar_editor_option( $section = 'jlt_admin_bar_editor_general', $key = '', $default = '' ) {
		$settings = get_option( $section );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}

if ( ! function_exists( 'jlt_admin_bar_editor_exclude_pages' ) ) {
	/**
	 * Get exclude pages setting option data
	 *
	 * @return string|array
	 *
	 * @version 1.0.0
	 */
	function jlt_admin_bar_editor_exclude_pages() {
		return jlt_admin_bar_editor_option( 'jlt_admin_bar_editor_triggers', 'exclude_pages', array() );
	}
}

if ( ! function_exists( 'jlt_admin_bar_editor_exclude_pages_except' ) ) {
	/**
	 * Get exclude pages except setting option data
	 *
	 * @return string|array
	 *
	 * @version 1.0.0
	 */
	function jlt_admin_bar_editor_exclude_pages_except() {
		return jlt_admin_bar_editor_option( 'jlt_admin_bar_editor_triggers', 'exclude_pages_except', array() );
	}
}

// Check if Premium
if (! function_exists('jlt_admin_bar_editor_is_premium')) {
	/**
	 * Check if the plugin is premium
	 *
	 * @return bool
	 */
	function jlt_admin_bar_editor_is_premium()
	{
		return (class_exists('\\JewelTheme\\AdminBarEditor\\Pro\\AdminBarEditorPro') && ! empty(\JewelTheme\AdminBarEditor\Pro\AdminBarEditorPro::is_premium()));
	}
}

// Check if Plan
if (! function_exists('jlt_admin_bar_editor_is_plan')) {
	/**
	 * Check if the plugin is plan
	 *
	 * @param string $plan
	 *
	 * @return bool
	 */
	function jlt_admin_bar_editor_is_plan($plan = 'starter')
	{
		return (class_exists('\\JewelTheme\\AdminBarEditor\\Pro\\AdminBarEditorPro') && ! empty(\JewelTheme\AdminBarEditor\Pro\AdminBarEditorPro::is_plan($plan)));
	}
}


function jlt_admin_bar_editor_update_logout_nonce_script() {
    // Get the logout URL with the correct nonce
    $logout_url = wp_logout_url(home_url());
    ?>
    <script>
    (function() {
        var logoutUrl = <?php echo json_encode($logout_url); ?>;
        var $logoutLink = document.querySelector("#wp-admin-bar-logout a");
        if ($logoutLink && logoutUrl) {
            var currentHref = $logoutLink.getAttribute("href");
            var newNonceMatch = logoutUrl.match(/_wpnonce=([a-zA-Z0-9]+)/);
            if (newNonceMatch && newNonceMatch[1]) {
                var newNonce = newNonceMatch[1];
                var updatedHref = currentHref.replace(/(_wpnonce=)[a-zA-Z0-9]+/, "$1" + newNonce);
                $logoutLink.setAttribute("href", updatedHref);
            }
        }
    })();
    </script>
    <?php
}
add_action('admin_footer', 'jlt_admin_bar_editor_update_logout_nonce_script');
add_action('wp_footer', 'jlt_admin_bar_editor_update_logout_nonce_script');


function jlt_admin_bar_fix_admin_menu_submenu_position() {
	?>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const menuItems = document.querySelectorAll('#adminmenu li.menu-top');
			menuItems.forEach(item => {
				const observer = new MutationObserver(mutations => {
					mutations.forEach(mutation => {
						if (
							mutation.attributeName === 'class' &&
							item.classList.contains('opensub')
						) {
							const submenu = item.querySelector('ul');
							if (!submenu) return;

							submenu.style.marginTop = '0px';
							setTimeout(() => {
								const rect = submenu.getBoundingClientRect();
								const bottom = rect.bottom;
								const gap = 30;
								const overflow = bottom + gap - window.innerHeight;
								if (overflow > 0) {
									submenu.style.marginTop = `-${overflow}px`;
								}
							}, 10);
						}
					});
				});
				observer.observe(item, { attributes: true });
			});
		});
	</script>
	<?php
}
add_action('admin_footer', 'jlt_admin_bar_fix_admin_menu_submenu_position');


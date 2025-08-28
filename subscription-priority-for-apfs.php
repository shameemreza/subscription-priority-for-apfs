<?php
/**
 * Plugin Name: Subscription Priority for All Products for WooCommerce Subscriptions
 * Plugin URI: https://github.com/shameemreza/subscription-priority-for-apfs
 * Description: Makes subscription plans the default selection and visually prioritizes them over one-time purchases in All Products for WooCommerce Subscriptions.
 * Version: 1.0.1
 * Author: Shameem Reza
 * Author URI: https://shameem.me
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: subscription-priority-apfs
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.1.2
 * Requires Plugins: woocommerce, woocommerce-subscriptions, woocommerce-all-products-for-subscriptions
 *
 * @package SubscriptionPriorityAPFS
 * @since   1.0.0
 */

namespace SubscriptionPriorityAPFS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'SPAPFS_VERSION', '1.0.0' );
define( 'SPAPFS_PLUGIN_FILE', __FILE__ );
define( 'SPAPFS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPAPFS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPAPFS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class for Subscription Priority for APFS.
 *
 * @since 1.0.0
 */
final class Subscription_Priority_APFS {

	/**
	 * The single instance of the class.
	 *
	 * @var Subscription_Priority_APFS|null
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Main instance of the plugin.
	 *
	 * Ensures only one instance of the plugin class is loaded.
	 *
	 * @since  1.0.0
	 * @return Subscription_Priority_APFS Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		
		// Declare compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		
		// Add plugin action links.
		add_filter( 'plugin_action_links_' . SPAPFS_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
		
		// Load text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Check dependencies.
		if ( ! $this->check_dependencies() ) {
			add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
			return;
		}

		// Initialize features.
		$this->init_features();
	}

	/**
	 * Check if required plugins are active.
	 *
	 * @since  1.0.0
	 * @return bool True if all dependencies are met.
	 */
	private function check_dependencies() {
		// Check for WooCommerce.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Check for WooCommerce Subscriptions.
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return false;
		}

		// Check for All Products for WooCommerce Subscriptions.
		if ( ! class_exists( 'WCS_ATT' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize plugin features.
	 *
	 * @since 1.0.0
	 */
	private function init_features() {
		// Make subscription the default selection on product pages.
		add_filter( 'wcsatt_get_default_subscription_scheme_id', array( $this, 'set_subscription_as_default' ), 10, 4 );
		
		// Allow direct subscription add-to-cart from shop pages.
		add_filter( 'wcsatt_prompt_plan_selection_in_catalog', array( $this, 'enable_direct_subscription_add' ), 20, 2 );
		
		// Force subscription scheme when adding from catalog pages.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'apply_subscription_to_cart_item' ), 5, 3 );
		
		// Customize add-to-cart button text for subscriptions.
		add_filter( 'wcsatt_add_to_cart_text', array( $this, 'subscription_button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'subscription_button_text' ), 10, 2 );
		
		// Enable AJAX add-to-cart for subscription products.
		add_filter( 'wcsatt_product_supports_ajax_add_to_cart', '__return_true', 100 );
		
		// Enqueue styles for visual enhancements.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
		
		// Add admin notices for successful activation.
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
		
		// Modify subscription price display to be bold and highlighted.
		add_filter( 'wcsatt_single_product_subscription_option_description', array( $this, 'highlight_subscription_text' ), 10, 3 );
	}

	/**
	 * Set subscription as the default selection.
	 *
	 * @since  1.0.0
	 * @param  string     $default_key Current default scheme key.
	 * @param  array      $schemes Available subscription schemes.
	 * @param  bool       $forced Whether subscription is forced.
	 * @param  WC_Product $product Product object.
	 * @return string Modified default scheme key.
	 */
	public function set_subscription_as_default( $default_key, $schemes, $forced, $product ) {
		// Allow customization via filter.
		if ( ! apply_filters( 'spapfs_enable_default_subscription', true, $product ) ) {
			return $default_key;
		}

		// Get the first available subscription scheme.
		if ( ! empty( $schemes ) && is_array( $schemes ) ) {
			$first_scheme = reset( $schemes );
			if ( $first_scheme && is_object( $first_scheme ) && method_exists( $first_scheme, 'get_key' ) ) {
				$default_key = $first_scheme->get_key();
			}
		}

		return $default_key;
	}

	/**
	 * Enable direct subscription add-to-cart from catalog pages.
	 *
	 * @since  1.0.0
	 * @param  bool       $prompt Current prompt setting.
	 * @param  WC_Product $product Product object.
	 * @return bool Modified prompt setting.
	 */
	public function enable_direct_subscription_add( $prompt, $product ) {
		// Allow direct add-to-cart without prompting for plan selection.
		return apply_filters( 'spapfs_disable_plan_selection_prompt', false, $product, $prompt );
	}

	/**
	 * Apply subscription scheme to cart items from catalog pages.
	 *
	 * @since  1.0.0
	 * @param  array $cart_item_data Cart item data.
	 * @param  int   $product_id Product ID.
	 * @param  int   $variation_id Variation ID.
	 * @return array Modified cart item data.
	 */
	public function apply_subscription_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
		// Skip if already has subscription data.
		if ( isset( $cart_item_data['wcsatt_data'] ) ) {
			return $cart_item_data;
		}

		// Check context - catalog pages or AJAX.
		// When clicking "Sign up" from shop pages, it's always via AJAX
		$is_ajax_add = wp_doing_ajax() && ! empty( $_REQUEST['add-to-cart'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_catalog = is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy();
		
		// Check if request is coming from a shop/archive page via AJAX
		$referer = wp_get_referer();
		$is_from_catalog = false;
		if ( $referer ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			$is_from_catalog = ( $shop_page_id && strpos( $referer, get_permalink( $shop_page_id ) ) !== false ) ||
							   strpos( $referer, '/product-category/' ) !== false ||
							   strpos( $referer, '/product-tag/' ) !== false ||
							   strpos( $referer, '/shop/' ) !== false;
		}

		// Apply subscription if from catalog, AJAX add-to-cart, or referred from catalog
		if ( ! apply_filters( 'spapfs_apply_subscription_scheme', ( $is_catalog || $is_ajax_add || $is_from_catalog ), $product_id ) ) {
			return $cart_item_data;
		}

		// Get product object.
		$product = wc_get_product( $variation_id ?: $product_id );

		if ( ! $product || ! is_object( $product ) ) {
			return $cart_item_data;
		}

		// Check for subscription schemes.
		if ( ! class_exists( 'WCS_ATT_Product_Schemes' ) ) {
			return $cart_item_data;
		}

		if ( ! \WCS_ATT_Product_Schemes::has_subscription_schemes( $product ) ) {
			return $cart_item_data;
		}

		// Get available schemes.
		$schemes = \WCS_ATT_Product_Schemes::get_subscription_schemes( $product );

		if ( empty( $schemes ) || ! is_array( $schemes ) ) {
			return $cart_item_data;
		}

		// Find first subscription scheme (not one-time purchase).
		foreach ( $schemes as $scheme ) {
			if ( $scheme && is_object( $scheme ) && method_exists( $scheme, 'get_key' ) ) {
				// Skip one-time purchase options
				if ( method_exists( $scheme, 'is_one_time' ) && $scheme->is_one_time() ) {
					continue;
				}
				
				$cart_item_data['wcsatt_data'] = array(
					'active_subscription_scheme' => $scheme->get_key(),
				);
				break;
			}
		}

		return $cart_item_data;
	}

	/**
	 * Customize add-to-cart button text for subscription products.
	 *
	 * @since  1.0.0
	 * @param  string     $text Current button text.
	 * @param  WC_Product $product Product object.
	 * @return string Modified button text.
	 */
	public function subscription_button_text( $text, $product ) {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return $text;
		}

		if ( ! class_exists( 'WCS_ATT_Product_Schemes' ) ) {
			return $text;
		}

		if ( \WCS_ATT_Product_Schemes::has_subscription_schemes( $product ) ) {
			$text = apply_filters( 'spapfs_subscribe_button_text', __( 'Sign up', 'subscription-priority-apfs' ), $product );
		}

		return $text;
	}

	/**
	 * Highlight subscription text with bold formatting.
	 *
	 * @since  1.0.0
	 * @param  string $description Current description.
	 * @param  array  $scheme_data Scheme data.
	 * @param  object $product Product object.
	 * @return string Modified description.
	 */
	public function highlight_subscription_text( $description, $scheme_data, $product ) {
		// Make subscription text bold and highlighted.
		if ( false !== strpos( $description, 'subscription-price' ) ) {
			$description = str_replace( 'class="subscription-price"', 'class="subscription-price spapfs-highlighted"', $description );
		}
		
		return $description;
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_styles() {
		if ( ! $this->should_load_styles() ) {
			return;
		}

		$inline_css = '
			/* Subscription Priority for APFS Styles */
			
			/* Highlight selected subscription option */
			.wcsatt-options-product .subscription-option input[type="radio"]:checked + label {
				font-weight: 700 !important;
				color: #0073aa;
				background: linear-gradient(90deg, rgba(0,115,170,0.05) 0%, rgba(0,115,170,0.02) 100%);
				padding: 8px 12px;
				border-radius: 4px;
				display: inline-block;
				width: 100%;
				margin: 4px 0;
			}
			
			/* Make subscription options more prominent */
			.wcsatt-options-product .subscription-option:not(.one-time-option) label {
				font-size: 1.1em;
				font-weight: 600;
				color: #0073aa;
			}
			
			/* De-emphasize one-time purchase option */
			.wcsatt-options-product .one-time-option label {
				opacity: 0.75;
				font-size: 0.95em;
			}
			
			/* Cart subscription options */
			.cart_item .wcsatt-options .subscription-option input[type="radio"]:checked + label {
				font-weight: 700 !important;
				color: #0073aa;
			}
			
			/* Highlighted subscription text */
			.spapfs-highlighted {
				font-weight: 700 !important;
				color: #0073aa;
			}
			
			/* Subscription price in options */
			.wcsatt-options-product .subscription-price {
				font-weight: 700 !important;
				color: #0073aa;
			}
			
			/* Improve focus accessibility */
			.wcsatt-options-product input[type="radio"]:focus + label {
				outline: 2px solid #0073aa;
				outline-offset: 2px;
			}
			
			/* Shop page subscription buttons */
			.products .product .button.product_type_simple:contains("Subscribe"),
			.products .product .button.ajax_add_to_cart:contains("Subscribe") {
				background-color: #0073aa !important;
				font-weight: 600;
			}
		';

		// Allow customization of styles.
		$inline_css = apply_filters( 'spapfs_inline_styles', $inline_css );

		// Add inline styles to WooCommerce stylesheet.
		wp_add_inline_style( 'woocommerce-general', $inline_css );
	}

	/**
	 * Check if styles should be loaded.
	 *
	 * @since  1.0.0
	 * @return bool True if styles should load.
	 */
	private function should_load_styles() {
		return is_product() || is_shop() || is_product_category() || is_product_tag() || is_cart();
	}

	/**
	 * Declare HPOS compatibility.
	 *
	 * @since 1.0.0
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				SPAPFS_PLUGIN_FILE,
				true
			);
		}
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'subscription-priority-apfs',
			false,
			dirname( SPAPFS_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Add plugin action links.
	 *
	 * @since  1.0.0
	 * @param  array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$plugin_links = array(
			'<a href="https://github.com/shameemreza/subscription-priority-for-apfs" target="_blank">' . esc_html__( 'Documentation', 'subscription-priority-apfs' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Show dependency notice.
	 *
	 * @since 1.0.0
	 */
	public function dependency_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Subscription Priority for All Products for WooCommerce Subscriptions', 'subscription-priority-apfs' ); ?></strong>
				<?php esc_html_e( 'requires the following plugins to be active:', 'subscription-priority-apfs' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'WooCommerce', 'subscription-priority-apfs' ); ?></li>
				<li><?php esc_html_e( 'WooCommerce Subscriptions', 'subscription-priority-apfs' ); ?></li>
				<li><?php esc_html_e( 'All Products for WooCommerce Subscriptions', 'subscription-priority-apfs' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Show activation notice.
	 *
	 * @since 1.0.0
	 */
	public function activation_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show on first activation.
		if ( get_option( 'spapfs_activation_notice_shown' ) ) {
			return;
		}

		// Check if we're on the plugins page.
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Subscription Priority for APFS activated successfully!', 'subscription-priority-apfs' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Subscription plans will now be selected by default on all products with subscription options.', 'subscription-priority-apfs' ); ?>
			</p>
		</div>
		<?php

		// Mark as shown.
		update_option( 'spapfs_activation_notice_shown', true );
	}
}

/**
 * Returns the main instance of Subscription_Priority_APFS.
 *
 * @since  1.0.0
 * @return Subscription_Priority_APFS Main instance.
 */
function subscription_priority_apfs() {
	return Subscription_Priority_APFS::instance();
}

// Initialize the plugin.
subscription_priority_apfs();

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate_subscription_priority_apfs' );

/**
 * Plugin activation callback.
 *
 * @since 1.0.0
 */
function activate_subscription_priority_apfs() {
	delete_option( 'spapfs_activation_notice_shown' );
	
	// Set activation timestamp.
	update_option( 'spapfs_activation_time', time() );
}

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate_subscription_priority_apfs' );

/**
 * Plugin deactivation callback.
 *
 * @since 1.0.0
 */
function deactivate_subscription_priority_apfs() {
	// Clean up options.
	delete_option( 'spapfs_activation_notice_shown' );
	delete_option( 'spapfs_activation_time' );
}

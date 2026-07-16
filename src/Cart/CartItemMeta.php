<?php
/**
 * Captures, validates, and displays PriceBlueprint selections in the cart.
 *
 * @package PRBP\Cart
 */

namespace PRBP\Cart;

use PRBP\Utils\BlueprintType;
use PRBP\Utils\RulesCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CartItemMeta {

	public static function register(): void {
		add_filter( 'woocommerce_add_to_cart_validation', [ self::class, 'validate' ], 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data',     [ self::class, 'capture' ],  10, 2 );
		add_filter( 'woocommerce_get_item_data',          [ self::class, 'display' ],  10, 2 );
	}

	/**
	 * Server-side validation: every attribute from the template must carry a
	 * selection that matches one of that attribute's rule value slugs. A bare
	 * "not empty" check is not enough — a tampered slug would silently skip
	 * rule matching in capture() and the option's price would never be added.
	 *
	 * @param bool $passed
	 * @param int  $product_id
	 * @return bool
	 */
	public static function validate( bool $passed, int $product_id ): bool {
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->get_type() !== 'prbp_configurable_product' ) {
			return $passed;
		}

		$template_id = (int) get_post_meta( $product_id, 'prbp_template_id', true );
		if ( ! $template_id ) {
			return $passed;
		}

		if ( BlueprintType::isInformational( $template_id ) ) {
			return $passed;
		}

		$rules      = RulesCache::get( $template_id );
		$attributes = array_unique( array_column( $rules, 'attribute' ) );
		// Nonce verified by WooCommerce add-to-cart handler before this filter fires.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$selections = array_map( 'sanitize_key', wp_unslash( (array) ( $_POST['prbp_selections'] ?? [] ) ) );

		foreach ( $attributes as $attr ) {
			$slug     = $selections[ $attr ] ?? '';
			$is_valid = false;

			if ( '' !== $slug ) {
				foreach ( $rules as $rule ) {
					if ( $rule['attribute'] === $attr
						&& in_array( $slug, (array) ( $rule['value_slugs'] ?? [] ), true )
					) {
						$is_valid = true;
						break;
					}
				}
			}

			if ( ! $is_valid ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: Attribute name (e.g. "pa_color") */
						__( 'Please select a value for: %s', 'priceblueprint-for-woocommerce' ),
						esc_html( $attr )
					),
					'error'
				);
				$passed = false;
			}
		}

		return $passed;
	}

	/**
	 * Capture selections and store matched rule objects in cart item data.
	 *
	 * @param array $cart_item_data
	 * @param int   $product_id
	 * @return array
	 */
	public static function capture( array $cart_item_data, int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->get_type() !== 'prbp_configurable_product' ) {
			return $cart_item_data;
		}

		$template_id = (int) get_post_meta( $product_id, 'prbp_template_id', true );

		if ( BlueprintType::isInformational( $template_id ) ) {
			return $cart_item_data;
		}

		$rules       = RulesCache::get( $template_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$selections  = array_map( 'sanitize_key', wp_unslash( (array) ( $_POST['prbp_selections'] ?? [] ) ) );
		$matched     = [];

		foreach ( $rules as $rule ) {
			$attr = $rule['attribute'];
			if ( ! isset( $selections[ $attr ] ) ) {
				continue;
			}
			$slug = $selections[ $attr ];
			$idx  = array_search( $slug, $rule['value_slugs'] ?? [], true );
			if ( $idx === false ) {
				continue;
			}
			$matched[] = [
				'attribute'       => $rule['attribute'],
				'attribute_label' => $rule['attribute_label'],
				'value_slug'      => $slug,
				'value_label'     => $rule['value_labels'][ $idx ] ?? $slug,
				'price'           => $rule['price'],
				'operator'        => $rule['operator'],
			];
		}

		$cart_item_data['prbp_selections']  = $matched;
		$cart_item_data['prbp_template_id'] = $template_id;
		$cart_item_data['prbp_base_price']  = (float) $product->get_price();

		return $cart_item_data;
	}

	/**
	 * Display selections in cart and checkout item details.
	 *
	 * @param array $item_data
	 * @param array $cart_item
	 * @return array
	 */
	public static function display( array $item_data, array $cart_item ): array {
		$selections = $cart_item['prbp_selections'] ?? [];
		if ( empty( $selections ) ) {
			return $item_data;
		}

		foreach ( $selections as $rule ) {
			$item_data[] = [
				'key'   => esc_html( $rule['attribute_label'] ),
				'value' => esc_html( $rule['value_label'] ),
			];
		}

		return $item_data;
	}
}

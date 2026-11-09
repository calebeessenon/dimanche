<?php
/**
 * Sortable + toggle list control for the homepage sections.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drag & drop list stored as "id:1,id:0,…".
 */
class Premium_Shop_Sortable_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'premium-shop-sortable';

	/**
	 * Render the control.
	 */
	public function render_content() {
		$value = premium_shop_sanitize_sections( $this->value() );
		$items = array();

		foreach ( explode( ',', $value ) as $token ) {
			list( $id, $on ) = explode( ':', $token );
			if ( isset( $this->choices[ $id ] ) ) {
				$items[ $id ] = ( '1' === $on );
			}
		}
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<ul class="ps-sortable" data-ps-sortable>
			<?php foreach ( $items as $id => $enabled ) : ?>
				<li class="ps-sortable__item" data-id="<?php echo esc_attr( $id ); ?>">
					<span class="ps-sortable__handle dashicons dashicons-menu" aria-hidden="true"></span>
					<label>
						<input type="checkbox" <?php checked( $enabled ); ?> />
						<?php echo esc_html( $this->choices[ $id ] ); ?>
					</label>
					<span class="ps-sortable__moves">
						<button type="button" class="button-link" data-move="up" aria-label="<?php esc_attr_e( 'Move up', 'premium-shop' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
						<button type="button" class="button-link" data-move="down" aria-label="<?php esc_attr_e( 'Move down', 'premium-shop' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr( $value ); ?>" />
		<?php
	}
}

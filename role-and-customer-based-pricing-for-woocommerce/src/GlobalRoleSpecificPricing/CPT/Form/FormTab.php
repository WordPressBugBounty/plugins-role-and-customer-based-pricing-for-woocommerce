<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form;

use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

abstract class FormTab {

	use ServiceContainerTrait;

	/**
	 * Form
	 *
	 * @var Form
	 */
	protected $form;

	public function __construct( Form $form ) {
		$this->form = $form;
	}

	abstract public function getId();

	abstract public function getTitle();

	abstract public function getDescription();

	abstract public function getIcon(): string;

	abstract public function render( GlobalPricingRule $pricingRule );

	public function renderSectionTitle( $sectionTitle, $args = array() ) {

		$args = wp_parse_args( $args, array(
				'description'      => '',
		) );

		?>

		<div class="rcbp-global-pricing-title">
			<?php echo esc_attr( $sectionTitle ); ?>
			<?php
				if ( $args['description'] ) {
					echo wc_help_tip( $args['description'] );
				}
			?>
		</div>
		<?php
	}

	public function renderSelect2( $args = array() ) {

		$args = wp_parse_args( $args, array(
				'id'                   => '',
				'search_action'        => '',
				'value'                => '',
				'options'              => null,
				'placeholder'          => '',
				'multiple'             => true,
				'width'                => '50%',
				'description'          => '',
				'desc_tip'             => true,
				'minimum_input_length' => 1,
				'css_class'            => 'wc-product-search',
		) );

		?>
		<p class="form-field rcbp-custom-field <?php echo esc_attr( $args['id'] ); ?>_field">
			<label for="<?php echo esc_attr( $args['id'] ); ?>">
				<?php echo esc_html( $args['label'] ); ?>
			</label>

			<select class="<?php echo esc_attr( $args['css_class'] ); ?>" <?php echo esc_attr( $args['multiple'] ? 'multiple="multiple"' : '' ); ?>
			        style="width: <?php echo esc_attr( $args['width'] ); ?>"
			        id="<?php echo esc_attr( $args['id'] ); ?>"
			        name="<?php echo esc_attr( $args['multiple'] ? $args['id'] . '[]' : $args['id'] ); ?>"
			        data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
			        data-action="<?php echo esc_attr( $args['search_action'] ); ?>"
			        data-minimum_input_length="<?php echo esc_attr( $args['minimum_input_length'] ); ?>">

				<?php if ( $args['options'] ) : ?>

					<?php foreach ( $args['options'] as $optionId => $label ) : ?>
						<option
								<?php selected( in_array( $optionId, $args['value'] ) ); ?>
								value="<?php echo esc_attr( $optionId ); ?>">
							<?php echo esc_attr( $label ); ?>
						</option>
					<?php endforeach; ?>

				<?php else : ?>

					<?php foreach ( $args['value'] as $optionId => $label ) : ?>
						<option selected
						        value="<?php echo esc_attr( $optionId ); ?>">
							<?php echo esc_attr( $label ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>

			<?php if ( $args['description'] ) : ?>
				<?php if ( $args['desc_tip'] ) : ?>
					<?php echo wp_kses_post( wc_help_tip( $args['description'] ) ); ?>
				<?php elseif ( is_callable( $args['description'] ) ): ?>
					<?php call_user_func( $args['description'] ); ?>
				<?php else : ?>
					<span class="description">
						<?php echo esc_html( $args['description'] ); ?>
					</span>
				<?php endif; ?>
			<?php endif; ?>
		</p>
		<?php
	}

	public function renderHint( $hint, $args = array() ) {

		$args = wp_parse_args( $args, array(
				'only_for_new_rules' => false,
				'show_icon'          => true,
				'custom_class'       => '',
		) );

		if ( ! $hint ) {
			return;
		}

		if ( $args['only_for_new_rules'] && ! $this->form->isNewRule() ) {
			return;
		}

		?>
		<div class="rcbp-global-pricing-rule-hint <?php echo esc_attr( $args['custom_class'] ); ?>">
			<?php if ( $args['show_icon'] ) : ?>
				<div class="rcbp-global-pricing-rule-hint__icon">
					<span class="dashicons dashicons-editor-help"></span>
				</div>
			<?php endif; ?>
			<div class="rcbp-global-pricing-rule-hint__content">
				<?php echo wp_kses_post( $hint ); ?>
			</div>
		</div>
		<?php
	}
}

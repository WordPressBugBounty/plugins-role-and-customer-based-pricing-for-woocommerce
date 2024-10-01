<?php defined( 'ABSPATH' ) || die;

/**
 * Available variables
 *
 * @var array $present_rules
 */
?>

<div class="rcbp-add-new-rule-form">
	<select class="rcbp-add-new-rule-form__identifier-selector rcbp-add-new-rule-form__identifier-selector--role"
			style="width: 200px;">
		<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
			<?php if ( ! in_array( $key, $present_rules ) ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $WPRole['name'] ); ?></option>
			<?php endif; ?>
		<?php endforeach; ?>
	</select>

	<button class="button rcbp-add-new-rule-form__add-button"> <?php esc_attr_e( 'Setup for role', 'role-specific-pricing' ); ?></button>

	<div class="clear"></div>
</div>

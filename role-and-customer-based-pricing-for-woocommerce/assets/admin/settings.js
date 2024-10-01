jQuery(document).ready(function ($) {
    let addToCartNoticeForNonLoggedInUsers = $('[name=role_and_customer_based_pricing_non_logged_in_users_purchase_message]').closest('tr');

    $('[name=role_and_customer_based_pricing_prevent_purchase_for_non_logged_in_users]').on('change', function () {

        if ($(this).is(':checked')) {
            addToCartNoticeForNonLoggedInUsers.show();
        } else {
            addToCartNoticeForNonLoggedInUsers.hide();
        }

    }).trigger('change');

    console.log(234);
});
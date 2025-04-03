/**
 * IP Installer JavaScript
 */

jQuery(document).ready(function($) {
    // Закриття сповіщень
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
}); 
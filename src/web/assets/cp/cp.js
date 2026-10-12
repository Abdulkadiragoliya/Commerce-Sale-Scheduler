/**
 * Commerce Sale Scheduler - Control Panel JS
 */
(function ($) {
    'use strict';

    $(function () {
        // Handle bulk checkbox selection
        var $selectAll = $('#select-all-sales');
        var $checkboxes = $('.sale-checkbox');
        var $bulkBtn = $('#bulk-action-btn');

        $selectAll.on('change', function () {
            $checkboxes.prop('checked', $(this).is(':checked'));
        });

        // Quick status toggle
        $('.js-toggle-sale-status').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var saleId = $btn.data('sale-id');

            Craft.postActionRequest('commerce-sale-scheduler/sales/toggle-status', { id: saleId }, function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    Craft.cp.displayError(response.error || 'Failed to toggle status.');
                }
            });
        });
    });
})(jQuery);

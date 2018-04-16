console.log('main.js loaded');
(function ($) {

    // Modal Popup Functionality
    $(document).on('click', '.close', function () {
        $('#QuickViewProductPopup').fadeOut();
        $('body').removeClass('qvwp-no-scroll');
    });
    $(document).on('click', '#QuickViewProductPopup', function (e) {
        if (e.target !== this)
            return;
        $('#QuickViewProductPopup').fadeOut();
        $('body').removeClass('qvwp-no-scroll');
    });

    // Display quick view popup
    $(document).on('click', 'a.qvwp-open-single-product', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        if (id < 1)
            return;
        $.ajax({
            type: 'POST',
            url: $('#QuickViewProductPopup').data('url'),
            data: {
                action: 'qvwp_aaction',
                id: id
            }
        }).done(function (result) {
            $('#QuickViewProductPopup>.modal-content').html(result + '<span class="close">&times;</span>');
            $('#QuickViewProductPopup').fadeIn();
            $('body').addClass('qvwp-no-scroll');
        });
        return false;
    });

})(jQuery);

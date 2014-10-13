jQuery(document).ready(function ($) {

    $('body').on('click', 'a[data-deploy]', function (e) {
        e.preventDefault();

        var downloadButton = $(this);

        var data = {
            action: 'edd_deployer_check_download',
            download: downloadButton.data('deploy')
        }

        $.post(ajaxurl, data, function (res) {
            res = $.parseJSON( res );

            var data = {
                action: 'edd_deployer_deploy',
                download: downloadButton.data('deploy')
            };
        });

    });

});
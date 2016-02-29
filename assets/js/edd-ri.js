jQuery(function ($) {

    var select = 'select#edd_ri_api_url',
        $edd_api = $(select),
        api_url = '-' != $edd_api.children('option:selected').val() ?
            $edd_api.children('option:selected').val() : edd_ri.default_api_url,
        $wrapper = $('div#edd-ri-wrapper-inner'),
        data;

    $('div#edd-ri-wrapper').css("height", $(document).height());

    /**
     * @link http://code.runnable.com/UhY_jE3QH-IlAAAP/how-to-parse-a-json-file-using-jquery
     */
    $.ajax({
        url: edd_ri.api_urls,
        //force to handle it as text
        dataType: "text",
        success: function (data) {

            //data downloaded so we call parseJSON function
            //and pass downloaded data
            var json = $.parseJSON(data);

            $.each(json.api_urls, function(value, text){
                $edd_api.append($('<option>').text(text).attr('value', value));
            });
        }
    });

    $(document).ready(function () {

        $(document).on('change', select, function (e) {
            e.preventDefault();
            api_url = $edd_api.children('option:selected').val();
            get_downloads();
        });

        var $spinner = $('img#edd-ri-loading'),
            get_downloads = function () {

                $spinner.show();
                $wrapper.children('.section').hide();

                if ('-' === api_url) {
                    $spinner.hide();
                    $wrapper.children('.section').show();
                    return;
                }

                data = {
                    action: 'edd_ri_get_downloads',
                    api_url: api_url,
                    nonce: edd_ri.nonce
                };

                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    data: data,
                    success: function (res) {

                        if (res.success !== false) {
                            $spinner.hide();
                            $wrapper.empty().html(res.data).fadeIn('slow');
                            if (api_url !== $edd_api.children('option:selected').val()) {
                                $edd_api.children('option[value="' + api_url + '"]').prop('selected', true);
                            }
                        } else {
                            $spinner.hide();
                            $wrapper.children('.section').fadeIn();
                        }
                    },
                    error: function () {
                    }
                });
            };

        get_downloads();
    });

    $(document).on('click', 'button[data-edd-ri]', function (e) {
        e.preventDefault();

        var $this = $(this),
            $spinner = $this.closest(".edd-ri-actions").find(".spinner"),
            install = function (license) {
                license = typeof license == "undefined" ? "" : license;
                $spinner.show();
                tb_remove();

                data = {
                    action: 'edd_ri_install',
                    download: $this.data('edd-ri'),
                    license: license,
                    api_url: api_url,
                    nonce: edd_ri.nonce
                };

                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    data: data,
                    success: function (res) {

                        $spinner.hide();
                        if (res.success === false) {
                            $this.attr("disabled", false);
                            $('.message-popup').html(res.data);
                            tb_show("", "#TB_inline?width=400&height=450&inlineId=MessagePopup");
                        } else {
                            $('.message-popup').html(res);
                            tb_show("", "#TB_inline?width=400&height=450&inlineId=MessagePopup");
                            $this.text("Installed");
                        }

                    },
                    error: function () {
                        console.log("erere");
                        $this.attr("disabled", false);
                    }
                });
            };

        $this.attr("disabled", true);

        if (!$this.data("free")) {
            tb_show("", "#TB_inline?width=400&height=150&inlineId=edd_ri_license_thickbox");
            $("#edd_ri_license_form").on("submit", function (e) {
                e.preventDefault();
                var $license = $("input#edd_ri_license"),
                    license = $license.val();
                if (license.length === 0) {
                    $license.css({
                        borderColor: "red"
                    })
                } else {
                    $license.css({
                        borderColor: "#ccc"
                    });
                    install(license);
                }
            });
        } else {
            install();
        }

        /**
         * http://stackoverflow.com/a/29689988
         */
        var tb_unload_count = 1;
        $(window).bind('tb_unload', function () {
            if (tb_unload_count > 1) {
                tb_unload_count = 1;
            } else {
                $this.attr("disabled", false);
                tb_unload_count = tb_unload_count + 1;
            }
        });
    });
});
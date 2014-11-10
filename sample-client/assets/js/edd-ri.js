jQuery(function ($) {

    $(document).on('click', 'button[data-edd-ri]', function (e) {
        e.preventDefault();
        var $this = $(this),
            $spinner = $this.closest(".edd-ri-actions").find(".spinner"),
            install = function( license ){
                license = typeof license == "undefined" ? "" : license;
                $spinner.show();
                tb_remove();
                var data = {
                    action: 'edd_ri_install',
                    download: $this.data('edd-ri'),
                    license: license
                };

                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    data: data,
                    success: function(res){

                        $spinner.hide();
                        if( res.success === false ){
                            $this.attr("disabled", false);
                            alert(res.data);
                        }else{
                            alert(res);
                            $this.text("Installed");
                        }

                    },
                    error: function(){
                        console.log("erere");
                        $this.attr("disabled", false);
                    }
                });
            }
            ;

        $this.attr("disabled", true);

        if( !$this.data("free") ){
            tb_show("", "#TB_inline?width=400&height=150&inlineId=edd_ri_license_thickbox");
            $("#edd_ri_license_form").on("submit", function(e){
                e.preventDefault();
                var $license =  $("#edd_ri_license"),
                    license = $license.val();
                if( license.length === 0 ){
                    $license.css({
                        borderColor: "red"
                    })
                }else{
                    $license.css({
                        borderColor: "#ccc"
                    });
                    install(license);
                }

            })
        }else{
            install();
        }



    });

});

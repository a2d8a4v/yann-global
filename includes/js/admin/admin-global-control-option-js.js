(function ($) {

    $( document ).ready(function(){

        $("input#submit").on('click', function(e) {
            e.preventDefault();
            $("input#submit").removeClass('is-valid');
            var data = {};
            var savelist = {};
            var targets = document.querySelectorAll(`.${GLOBAL_CONTROL.save_target}`); //.checked
            data.nonce = GLOBAL_CONTROL.nonce;
            data.action = GLOBAL_CONTROL.action_save;
            for (let i = 0; i < targets.length; i++) {
                t = targets[i];
                id = t.id;
                savelist[id] = t.checked;
            }
            data.savelist = savelist;

            $.ajax({
                url: GLOBAL_CONTROL.ajaxurl ,
                type: "POST",
                dataType: "json",
                delay: 250,
                data: data,
                success: function( data ) {
                    if (data.data.success == true) {
                        $("input#submit").addClass('is-valid');
                    }
                    if (data.data.error == true) {
                        $("input#submit").addClass('is-invalid');
                        alert( 'Error. Please, try again!' );
                    }
                },error: function (jqXHR, exception) {
                    $("input#submit").addClass('is-invalid');
                    alert( 'Error. Please, try again!' );
                }
            });
        });
    });

})(jQuery);
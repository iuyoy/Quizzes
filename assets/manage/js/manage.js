(function($) {
    $('.login_button').click(function(){
        $.ajax({
            cache: true,
            type: "POST",
            url: "",
            data: $(".login").serialize(),
            async: false,
            error: function(request) {
                alert("出现未知错误，请稍后重试或者联系管理员。");
            },
            success: function(data) {
                data = JSON.parse(data);
                alert(data['result']);
                if(data['status']=="success")
                {
                    window.location.replace("./manage");
                }

            }
        });
    });
})(jQuery);

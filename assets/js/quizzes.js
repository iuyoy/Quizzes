(function($) {
    $('#regist').bootstrapValidator({
        message: 'This value is not valid',
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            name: {
                message: 'The name is not valid',
                validators: {
                    notEmpty: {
                        message: 'The name is required and can\'t be empty'
                    },
                }
            },
            college: {
                validators: {
                    notEmpty: {
                        message: 'The college address is required and can\'t be empty'
                    },
                }
            },
            major: {
                validators: {
                    notEmpty: {
                        message: 'The major is required and can\'t be empty'
                    }
                }
            },
            tel: {
                validators: {
                    notEmpty: {
                        message: 'The tel is required and can\'t be empty'
                    },
                    stringLength: {
                        min: 8,
                        max: 11,
                        message: 'The phone number must be more than 8 and less than 11 characters long'
                    },
                    regexp: {
                        regexp: /^[0-9]+$/,
                        message: 'The phone number can only consist of number.'
                    }
                }
            }
        }
    }).on('success.form.bv', function(e) {
        // Prevent form submission
        e.preventDefault();
        // Get the form instance
        var $form = $(e.target);
        // Get the BootstrapValidator instance
        var bv = $form.data('bootstrapValidator');
        // Use Ajax to submit form data
        $.ajax({
            cache: true,
            type: "POST",
            url: "",
            data: $form.serialize(),
            async: false,
            error: function(request) {
                alert("出现未知错误，请稍后重试或者联系管理员。");
            },
            success: function(data) {
                start(data);
            }
        });
    });
})(jQuery);

function start(data) {

    var container = $('.container'),
        questions = JSON.parse(data),
        current_id = 1;
    container.empty();
    var quizzes =
        '<div class="col-xs-12">' +
        '<div class="col-xs-10">' +
        '<div class="progress">' +
        '<div class="progress-bar progress-bar-danger progress-bar-striped left-time-graph" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">' +
        '<span class="sr-only"></span>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="col-xs-2 left-time">' +
        '剩余' +
        '</div>' +
        '</div>' +
        '<form class="col-xs-12 exam" role="form" method="POST" action="exam">';
    for (var qid in questions) {
        var quiz =
            '<div class="panel panel-primary quiz quiz-' + qid + ' hidden">' +
            '<div class="panel-heading type">' +
            '<h3 class="panel-title">' + questions[qid]['type'] + '</h3>' +
            '</div>' +
            '<div class="panel-body">' +
            '<div class="question">' +
            questions[qid]['question'] +
            '</div>' +
            '<div class="options">';
        for (var oid in questions[qid]['options']) {
            quiz +=
                '<div class="radio">' +
                '<label>' +
                '<input class="question-' + qid + '" type="radio" name="' + qid + '" id="q_' + qid + '_o_' + oid + '" value="' + oid + '">' + questions[qid]['options'][oid] +
                '</label>' +
                '</div>';
        }
        quiz +=
            '</div>' +
            '<button type="button" class="btn btn-success next">下一题</button>' +
            '</div>' +
            '</div>';
        quizzes += quiz;
    }
    quizzes +=
        '</form>' +
        '<div class="col-md-12">' +
        '<div class="alert alert-danger hidden" role="alert">请选择一个选项。</div>' +
        '</div>';
    container.append(quizzes);
    $('.quiz-1').toggleClass('hidden');
    $('.quiz-' + qid + ' button').toggleClass("next finish");
    $('.quiz-' + qid + ' button').html('完成交卷');
    $('.next').click(function() {
        if ($('.question-' + current_id + ':checked').val()) {
            $(".alert").toggleClass("hidden", true);
            $('.quiz-' + current_id).toggleClass('hidden');
            current_id += 1;
            $('.quiz-' + current_id).toggleClass('hidden');
        } else {
            $(".alert").toggleClass("hidden", false);
        }
    });

    var total = parseInt(3600);
    var intDiff = total; //倒计时总秒数量
    var stoptimer = window.setInterval(function() {
            var day = 0,
                hour = 0,
                minute = 0,
                second = 0; //时间默认值
            if (intDiff > 0) {
                day = Math.floor(intDiff / (60 * 60 * 24));
                hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
                minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
                second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
            }
            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            // $('#day_show').html(day + "天");
            // $('#hour_show').html('<s id="h"></s>' + hour + '时');
            // $('#minute_show').html('<s></s>' + minute + '分');
            $('.left-time').html('剩余 ' + minute + '分' + second + '秒');
            $('.left-time-graph').attr({
                'style': 'width: ' + String((total - intDiff) / total * 100) + '%',
                'aria-valuenow': String((total - intDiff) / total * 100)
            })
            if (intDiff === 0){
                intDiff = -1;
                finish(stoptimer);
            }
            if (intDiff > 0){
                intDiff--;
            }
        }, 1000);

    $('.finish').click(function() {
        if ($('.question-' + current_id + ':checked').val()) {
            finish(stoptimer);
        } else {
            $(".alert").toggleClass("hidden", false);
        }
    });

    function finish(stoptimer) {
        window.clearInterval(stoptimer);
        $.ajax({
            cache: true,
            type: "POST",
            url: "./exam",
            data: $(".exam").serialize(),
            async: true,
            error: function(request) {
                alert("出现未知错误，请稍后重试或者联系管理员。");
            },
            success: function(data) {
                if (data!==null)
                    alert(data);
                window.location.replace("./");
            }
        });
    };
}

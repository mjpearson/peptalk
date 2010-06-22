function ptChat() {

    var operator = false;
    var startbeat = false;

    var timerid = 0;

    this.init = function() {
        this.render();
    }

    this.render = function() {

        // Make a call to the chat controller
        $.ajax({
           type: 'get',
           url: 'chat',
           success: function(data) {
               el = $.find('#peptalk_div');
                if (el) {
                    $('#peptalk_div').html(data);
                    $('#chatdialog').css('display', 'block');

                    $('#chatdialog').draggable({ handle : 'div.#banner'});

                    // key bindings
                    $("#msglocal").keydown(function(e) {
                        var key = e.which;

                        // all keys including return
                        if (key >= 33) {
                            var maxLength = $(this).attr("maxlength");
                            var length = this.value.length;

                            if (length >= maxLength) {
                                e.preventDefault();
                            }
                        }
                    });

                    $('#msglocal').keyup(function(e) {

                        if (e.keyCode == 13) {

                            var text = $(this).val();
                            var maxLength = $(this).attr("maxlength");
                            var length = text.length;

                            if (length <= maxLength + 1) {
                                ptc.send(text);
                                $(this).val("");
                            } else {
                                $(this).val(text.substring(0, maxLength));
                            }
                        }
                    });

                    $("#msglocal").focus();
                }
            }
        });
    };

    this.send = function(message) {
        $.ajax({
           type: 'post',
           url: 'chat/in',
           data: {
                'message': message,
                'ajs': '1'
                },
           dataType: "json",
           success: function(data) {
               ptc.refresh(false);
               if (!ptc.startbeat) {
                   ptc.startbeat = true;
                   ptc.timerid = setInterval(function() {
                                                               ptc.refresh(true);
                                                           }, 2000);
               }
           }
        });
    }

    this.logout = function() {
        $.getJSON('chat/logout');
    }

    this.disconnect = function(closedialog) {
        if (this.operator) {
            $.getJSON('chat/drop');
        } else {
            this.logout();
        }

        if (this.timerid) {
            if (this.startbeat) {
                clearInterval(this.timerid);
                this.timerid = null;
                this.startbeat = false;
            }
        }

        if (closedialog) {
            $('#peptalk_div > #chatdialog').remove();
        }
    }

    this.refresh = function(poll) {
        $.getJSON('chat/ping', { 'ajs' : '1', 'poll' : poll}, function (data) {
            ptAppend(data);
        });
    }
}

function ptAppend(data) {
    $.each(data, function(status, payload) {
        if (status == 'OK') {
            $.each(payload, function(idx, content) {
                var user = ((content.type == 'guest' && !ptc.operator) || (content.type == 'operator' && ptc.operator)) ? '&gt;' : '<b>' + content.user + '</b>&gt;';
                var msgStyle = ptc.operator ? 'operator' : 'guest';
                // ** note: use content.servertime to display the server time
                $('#chat').append($("<p class='" + msgStyle + "'>" + user + " "+ content.message +"</p>"));
                document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight;
            });
        } else if (status == 'status') {
            if (payload == 'DC' || payload == 'TIMEOUT') {
                if (payload == 'DC') {
                    $('#chat').append($("<p class='dc'>** disconnected</p>"));
                } else if (payload == 'TIMEOUT') {
                    $('#chat').append($("<p class='timeout'>** timeout</p>"));
                }
                document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight;
                ptc.disconnect(false);
            }
        }
    })
}

var ptc = new ptChat();

// Stop any chats if we navigate away etc.
//if (jQuery.support.onbeforeunload) {
//    window.onbeforeunload = function() { ptc.disconnect(); };
//} else if (jQuery.support.onunload) {
//    window.onunload = function() { ptc.disconnect(); }
//}
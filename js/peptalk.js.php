<?php
    require_once(dirname(__FILE__).'/../config.php');
?>
var ptc = {

    operator: false,

    startbeat: false,

    timerid: 0,

    displaywelcome: false,

    init: function() {
        if (!this.operator) {
            this.displaywelcome = true;
        }
        this.render();
    },

    render: function() {

        // Make a call to the chat controller
        $.ajax({
           type: 'get',
           url: '<?php echo PT_BASE_URL; ?>chat',
           success: function(data) {
               el = $.find('#peptalk_div');
               if (el) {
                $('#peptalk_div').html(data);
                $('#chatdialog').css('display', 'block');
                $('#chatdialog').draggable({ handle : 'div.#banner'});
               } else {
                alert('bad integration?');
               }
           }
        });
    },

    send: function(message) {
        if (ptc.displaywelcome) {
            ptc.displaywelcome = false;
            $('#chat').html('');
        }

        var self = this;

        $.ajax({
           type: 'post',
           url: '<?php echo PT_BASE_URL; ?>chat/in',
           data: {
                'message': message,
                'ajs': '1'
                },
           dataType: "json",
           success: function(data) {
               self.refresh(false);
               if (!self.startbeat) {
                   self.startbeat = true;
                   self.timerid = setInterval(function() { self.refresh(true); }, 2000);
               }
           }
        });
    },

    logout: function() {
        $.getJSON('<?php echo PT_BASE_URL; ?>chat/logout');
    },

    disconnect: function(closedialog) {
        if (this.operator) {
            $.getJSON('<?php echo PT_BASE_URL; ?>chat/drop');
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
    },

    refresh: function(poll) {
        var self = this;
        $.getJSON('<?php echo PT_BASE_URL; ?>chat/ping', { 'ajs' : '1', 'poll' : poll}, function (data) {
            self.ptAppend(data);
        });
    },

    ptAppend: function(data) {
        var self = this;
        $.each(data, function(status, payload) {
            if (status == 'OK') {
                $.each(payload, function(idx, content) {
                    var user = ((content.type == 'guest' && !self.operator) || (content.type == 'operator' && self.operator)) ? '&gt;' : '<b>' + content.user + '</b>&gt;';
                    var msgStyle = self.operator ? 'operator' : 'guest';
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
                    self.disconnect(false);
                }
            }
        })
}

}

// Stop any chats if we navigate away etc.
if (jQuery.support.onbeforeunload) {
    window.onbeforeunload = function() { ptc.disconnect(); };
} else if (jQuery.support.onunload) {
    window.onunload = function() { ptc.disconnect(); }
}

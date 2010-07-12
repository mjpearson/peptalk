<?php
    require_once(dirname(__FILE__).'/../config.php');
?>
var qidPrefix = 'pt_';
var breakoutMode = true;

var queue = {
    timerid: 0,

    emptyq: true,

    opStatus: false,

    breakout: breakoutMode,

    onlineStatus: function(data) {
        this.opStatus = (data == 1);
        if (this.opStatus) {
           this.timerid = setInterval("queue.refresh();", 5000);           
        }
    },

    init: function() {
        this.emptyq = true;
        this.opStatus = false;
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/statme', this.onlineStatus);
        return this.opStatus;
    },

    clearPollingTimer: function() {
        clearInterval(this.timerid);
        $('#' + qidPrefix + 'queue').html($("<div class='queuerow' align='center'><b>You are Offline</b></div>"));
    },

    qrefresh: function(data) {
        $('#' + qidPrefix + 'queue').html('');
        odd = false;

        var emptynow = this.emptyq;

        qNum = $('#peptalk_queue_waiting');

        this.emptyq = (data == '');

        if (!this.emptyq) {

            if (emptynow) $('#peptalk_queueheader').animate( { backgroundColor: '#7fff2a'}, 100);

            $.each(data, function(cid, meta) {
                ip = meta.host;
                msg = meta.msg;
                $('#' + qidPrefix + 'queue').append($("<div class='queuerow" + (odd ? " queuerowodd" : '') + "'><div id='qcontrol'><a id='replybutton' onclick='queue.reply(\"" + cid + "\")'>reply</a></div><div id='cidinfo'>[" + ip + "] " + msg + "</div></div>"));
                odd = !odd;
            });

            if (emptynow) $('#peptalk_queueheader').animate( { backgroundColor: '#e5e5e5'}, 1000);

            if (qNum) qNum.html($('#' + qidPrefix + 'queue').size());

        } else {
            $('#' + qidPrefix + 'queue').html($("<div class='queuerow' align='center'><b>Queue is Empty</b></div>"));
            if (qNum) qNum.html('0');
        }
    },

    refresh: function () {
        var self = this;
        $.ajax({
           type: 'get',
           url: '<?php echo PT_BASE_URL; ?>operator/qpoll',
           dataType: 'json',
           success: function (data) {
                            self.qrefresh(data);
            },
           error: this.clearPollingTimer,
       });
    },

    // Dequeues chat and begins reply
    reply: function (cid) {
        if (this.breakout) {
           $.getJSON('<?php echo PT_BASE_URL; ?>operator/dequeue', {'cid' : cid});
            window.open('<?php echo PT_BASE_URL;?>chat/?breakout=1', 'Live Support Chat', 'menubar=0,toolbar=0,status=0,scrollbars=0,resizable=0,width=305,height=360');
        } else {
            $.getJSON('<?php echo PT_BASE_URL; ?>operator/dequeue',
                    {'cid' : cid},
                    function () {
                        ptc.operator = true;
                        ptc.render();
                        ptc.refresh();
                    }
            );
        }
    }
}

queue.init();
function ptkQueue() {

    var timerid = 0;

    this.init = function() {
        this.timerid = setInterval(this.refresh, 5000);
    }

    var empty = true;

    this.refresh = function () {
        $.getJSON('operator/qpoll', function(data) {
            $('#queue').html('');
            odd = false;
            var emptynow = ptkQueue.empty;

            ptkQueue.empty = (data == '');
            if (!ptkQueue.empty) {
                
                if (emptynow) $('#queueheader').animate( { backgroundColor: '#7fff2a'}, 100);
                $.each(data, function(cid, meta) {
                    ip = meta.host;
                    msg = meta.msg;
                    $('#queue').append($("<div class='queuerow" + (odd ? " queuerowodd" : '') + "'><div id='qcontrol'><a id='replybutton' onclick='operator.reply(\"" + cid + "\")'>reply</a></div><div id='cidinfo'>[" + ip + "] " + msg + "</div></div>"));

                    odd = !odd;
                });                
                if (emptynow) $('#queueheader').animate( { backgroundColor: '#fff'}, 1000);
            } else {
                $('#queue').append($("<div class='queuerow' align='center'><b>Queue is Empty</b></div>"));
            }
        });
    }
}

var queue = new ptkQueue();
queue.init();
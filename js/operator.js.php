<?php
    require_once(dirname(__FILE__).'/../config.php');
?>
operator = {

    active: null,

    alt: false,

    parseResponse: function(data) {
        if (data.status == 'OK') {
            return true;
        } else if (data.status = 'NOAUTH') {
            window.location('auth');            
        }
    },

    flashE: function(target) {
        $('#' + target).animate( { backgroundColor: '#7fff2a'}, 200, function () {
            $('#' + target).animate( { backgroundColor: '#fff'}, 200)
        });
    },

    init: function() {

        var self = this;

        $("#opcontrol").accordion({ active : 1});

        $(function() {
                $("#dateto").datepicker({ dateFormat: 'yymmdd' });
                $("#datefrom").datepicker({ dateFormat: 'yymmdd' });
        });

        $('#session_displayname').editInPlace(
            {url : 'operator/profileupdate', success: function() {
                self.flashE('profileheader');
            }
        });

        $('#session_emailaddress').editInPlace(
            {url : 'operator/profileupdate', success: function() {
                self.flashE('profileheader');
            }
        });

        $('#session_password').editInPlace(
            {url : 'operator/profileupdate', success: function() {
                self.flashE('profileheader');
            }
        });
    },

    auth: function() {
        var self = this;
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/auth', { 'authUn' : $('#authUn').attr('value'), 'authPw' : $('#authPw').attr('value')}, function(data) {
            self.init();
        });
    },

    logout: function() {
        var self = this;
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/logout',  function() {
            // drop any open chats
            ptc.disconnect(true);
            location.reload();
        });
    },

    render: function(view) {
        active = this.active;
        if (active != null) {
           $('#' + active).css('display', 'none');
        }

        if (view == 'reporting') {
            $('#reportTool').fadeIn();
        } else {
            $('#reportTool').fadeOut();
        }

        if ($('#' + view).html() == '' || view != 'dashboard' || view != 'reporting') {

            $.ajax({
               type: 'get',
               url: '<?php echo PT_BASE_URL; ?>operator/' + view,
               success: function(data) {
                   $('#' + view).html(data);
               }
            });
        }
        
        $('#' + view).css('display', 'block');
        this.active = view;
    },

    // Dequeues chat and begins reply
    reply: function (cid) {
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/dequeue',
            {'cid' : cid},
            function () {
                ptc.operator = true;
                ptc.render();
                ptc.refresh();
            }
    )},

    profileUpdate: function() {

        var profile = $('#profileSelf');
        var opStat = $('#operationstatus');

        opStat.fadeOut();

         $.ajax({
               type: 'post',
               url: '<?php echo PT_BASE_URL; ?>operator/profileupdate',
               data: {
                   'password' : $('#password').val(),
                   'emailaddress' : $('#emailaddress').val(),
                   'displayname' : $('#displayname').val(),
               },
               success: function(data) {
                   opStat.html('OK');
                   opStat.css('display', 'block');
                   opStat.fadeIn();
                   setTimeout(opStat.fadeOut(), 1000);
               }
            });       
    },

    // runs and returns search results for historical transcripts
    search: function() {
        var self = this;
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/datesearch', { 'datefrom' : $('#datefrom').val(), 'dateto' : $('#dateto').val() },
            function(data) {
                $.each(data, function(status, payload) {
                    // render results
                    if (status == 'OK') {
                        $('#searchresult').html('');
                        $.each(payload, function(ctime, entity) {
                            $.each(entity, function(ctime, struct) {                                
                                $('#searchresult').append($("<div class='resultrow " + (self.alt ? "odd" : "") + "'><div id='qcontrol'><a id='replybutton' onclick='javascript:self.loadTranscript(\"" + struct.cid + "\");'>display</a></div><div id='cidinfo'><b>" + struct.time + " " + struct.operator + "</b> [" + struct.remote + "] " + struct.message + "</div></div>  <div class='transcript' id='cidtranscript_" + struct.cid + "' style='display:none'></div>"));
                                self.alt = !self.alt;
                            });
                        });
                    }                    
                });
            });
    },

    opents: null,
    ts: '',

    loadTranscript: function (cid) {
        var self = this;
        // close the transcript on second click
        if (cid == this.opents) {
            $('#cidtranscript_' + this.opents).css('display', 'none');
            this.opents = null;
            this.ts = '';
        } else {
            $.getJSON('<?php echo PT_BASE_URL; ?>operator/loadTranscript', { 'cid' : cid },
                function(data) {
                    $.each(data, function(status, payload) {
                        if (status == 'OK') {
                            if (self.opents != null) {
                                $('#cidtranscript_' + self.opents).css('display', 'none');
                            }
                            self.opents = cid;
                            $('#cidtranscript_' + cid).html('');
                            $.each(payload, function(idx, message) {
                                $.each(message, function(cid, struct) {
                                    var who = (struct.type == 'operator') ? struct.user : 'Guest';
                                    self.ts = self.ts + "<div><b>" + who + "</b> > "+ struct.message +"</div>";
                                });
                            });
                            $('#cidtranscript_' + cid).html(self.ts);
                            self.ts = '';
                            $('#cidtranscript' + cid).css('display', 'block');
                            $('#cidtranscript_' + self.opents).fadeIn(1000);
                        }
                    });
                });
        }
    },

    create: function() {
        ok = false;
        payload = new Array();

        // check required inputs
        var un = $('#new_username');

        if (un.val() == '') {
            un.addClass('inputError');
        } else {
            un.removeClass('inputError');
            payload += '&username=' + un.val();
            ok = true;
        }        

        var type = $('#new_type');
        payload += '&type=' + type.val();

        var email = $('#new_emailaddress');
        if (email.val() == '') {
            email.addClass('inputError');
        } else {
            email.removeClass('inputError');
            payload += '&emailaddress=' + email.val();
            ok = true;
        }

        var pwd = $('#new_pwd');

        if (pwd.val() == '') {
            pwd.addClass('inputError');
        } else {
            pwd.removeClass('inputError');
            payload += '&password=' + pwd.val();
            ok = true;
        }

        if (ok) {
            $.ajax({
               type: 'post',
               url: '<?php echo PT_BASE_URL; ?>operator/newuser',
               data: payload,
               dataType: 'html',
               success: function(data) {
                   self.flashE('uadminheader');
               },
               error : function(request, status) {
                   alert(request.responseText);
               }
            });
        }        
    },

    rmuser: function(user) {
        $.getJSON('<?php echo PT_BASE_URL; ?>operator/deluser', { 'username' : user } );
    }
}
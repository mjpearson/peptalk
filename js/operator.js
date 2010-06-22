function ptkOp() {

    this.active = null;

    this.parseResponse = function(data) {
        if (data.status == 'OK') {
            return true;
        } else if (data.status = 'NOAUTH') {
            window.location('auth');            
        }
    }

    this.flashE = function(target) {
        $('#' + target).animate( { backgroundColor: '#7fff2a'}, 200, function () {
            $('#' + target).animate( { backgroundColor: '#fff'}, 200)
        });
    }

   //
    this.init = function() {

        $("#opcontrol").accordion({ active : 1});


        $(function() {
                $("#dateto").datepicker({ dateFormat: 'yymmdd' });
                $("#datefrom").datepicker({ dateFormat: 'yymmdd' });
        });

        // @todo fix...
        // setup in-place editor
        $('#session_displayname').editInPlace( {url : 'operator/profileupdate', success: function() {
                    operator.flashE('profileheader');
                }
        });

        $('#session_emailaddress').editInPlace( {url : 'operator/profileupdate', success: function() {
                    operator.flashE('profileheader');
                }
        });

        $('#session_password').editInPlace( {url : 'operator/profileupdate', success: function() {
                    operator.flashE('profileheader');
                }
        });

    };

    this.auth = function() {
        $.getJSON('operator/auth', { 'authUn' : $('#authUn').attr('value'), 'authPw' : $('#authPw').attr('value')}, function(data) {
            operator.init();
        });
    };


    this.logout = function() {
        $.getJSON('operator/logout',  function() {
            // drop any open chats
            ptc.disconnect(true);
            //ptc.logout();
            location.reload();
        });
    }

    this.render = function(view) {
        active = operator.active;
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
               url: 'operator/' + view,
               success: function(data) {
                   $('#' + view).html(data);
               }
            });
        }
        
        $('#' + view).css('display', 'block');
        operator.active = view;
    }

    // Dequeues chat and begins reply
    this.reply = function (cid) {
        $.getJSON('operator/dequeue', 
                    {'cid' : cid},
                    function () {
                        ptc.operator = true;
                        ptc.render();
                        ptc.refresh();
                    }
        )};

    this.profileUpdate = function() {

        var profile = $('#profileSelf');
        var opStat = $('#operationstatus');

        opStat.fadeOut();

         $.ajax({
               type: 'post',
               url: 'operator/profileupdate',
               data: {
                   'password' : $('#password').val(),
                   'emailaddress' : $('#emailaddress').val(),
                   'displayname' : $('#displayname').val(),
               },
               success: function(data) {
                   opStat.html('OK');
                   opStat.css('display', 'block');
                   opStat.fadeIn();
                   setTimeout(function () { $('#operationstatus').fadeOut() }, 1000);
               }
            });
        
    }

    var alt = false;

    // runs and returns search results for historical transcripts
    this.search = function() {
        $.getJSON('operator/datesearch', { 'datefrom' : $('#datefrom').val(), 'dateto' : $('#dateto').val() },
            function(data) {
                $.each(data, function(status, payload) {
                    // render results
                    if (status == 'OK') {
                        $('#searchresult').html('');
                        $.each(payload, function(ctime, entity) {
                            $.each(entity, function(ctime, struct) {                                
                                $('#searchresult').append($("<div class='resultrow " + (operator.alt ? "odd" : "") + "'><div id='qcontrol'><a id='replybutton' onclick='javascript:operator.loadTranscript(\"" + struct.cid + "\");'>display</a></div><div id='cidinfo'><b>" + struct.time + " " + struct.operator + "</b> [" + struct.remote + "] " + struct.message + "</div></div>  <div class='transcript' id='cidtranscript_" + struct.cid + "' style='display:none'></div>"));
                                operator.alt = !operator.alt;
                            });
                        });
                    }
                    
                });
            });
    }

    var opents = null;

    var ts = '';

    this.loadTranscript = function (cid) {
        // close the transcript on second click
        if (cid == this.opents) {
            $('#cidtranscript_' + this.opents).css('display', 'none');
            this.opents = null;
            this.ts = '';
        } else {
            $.getJSON('operator/loadTranscript', { 'cid' : cid },
                function(data) {
                    $.each(data, function(status, payload) {
                        if (status == 'OK') {
                            if (operator.opents != null) {
                                $('#cidtranscript_' + operator.opents).css('display', 'none');
                            }

                            operator.opents = cid;
                            $('#cidtranscript_' + cid).html('');

                            $.each(payload, function(idx, message) {
                                $.each(message, function(cid, struct) {
                                    var who = (struct.type == 'operator') ? struct.user : 'Guest';
                                    operator.ts = operator.ts + "<div><b>" + who + "</b> > "+ struct.message +"</div>";
                                });
                            });

                            $('#cidtranscript_' + cid).html(operator.ts);
                            operator.ts = '';

                            $('#cidtranscript' + cid).css('display', 'block');
                            $('#cidtranscript_' + operator.opents).fadeIn(1000);
                        }
                    });
                });
        }
    }

    this.create = function() {

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
               url: 'operator/newuser',
               data: payload,
               dataType: 'html',
               success: function(data) {
                   operator.flashE('uadminheader');
               },
               error : function(request, status) {
                   alert(request.responseText);
               }
            });

        }        
    }

    this.rmuser = function(user) {
        $.getJSON('operator/deluser', { 'username' : user } );
    }
}

var operator = new ptkOp();

$(document).ready(function() {
    operator.init();
    //ptChatInit();
    //window.setInterval('refresh(true)', 1000);
 });



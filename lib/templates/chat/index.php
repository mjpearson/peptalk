<?php
$online = Session::onlinePoll();
$op = Session::isOperator();
$breakout = $controller->isBreakout();
?>
<div id="chatdialog" <?php echo $breakout ? "style='display:block'" : '';  ?>>
    <div id="banner">
        <?php if (!$breakout) { ?>
            <div id="bannercontrol"><a id="closebutton" onclick="javascript:ptc.disconnect(true)">X</a></div>
        <?php } ?>
            
        <div id="banntertitle">peptalk - <?php echo ($online) ? 'online' : '<span class="ptk_stat_offline">OFFLINE</span>' ?></div>        
    </div>
<?php
    if ($online) {
?>
    <div id="chat"><?php  if (!$op) echo PT_WELCOME_MESSAGE; ?></div>
    <div id="msglocaldiv">
        <textarea class="msglocal" id="msglocal" maxlength='200'></textarea></div>
    </div>
<?php
    } else {
        echo '<div id="msglocaldiv">'.PT_OFFLINE_MESSAGE.'</div>';
    }
?>
<script type="text/javascript">  
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

    $('#msglocal').focus();
</script>
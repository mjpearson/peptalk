<?php
//echo 'SESSION ID '.session_id().'<br>';
//echo 'CID: '.$_SESSION['cid'].'<br>';
?>
<div id="chatdialog">
    <div id="banner">
        <div id="bannercontrol"><a id="closebutton" onclick="javascript:ptc.disconnect(true)">X</a></div>
        <div id="banntertitle">peptalk</div>
        
    </div>
    <div id="chat"></div>
    <div id="msglocaldiv"><textarea class="msglocal" id="msglocal" maxlength='200'></textarea></div>
</div>
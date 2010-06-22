<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">    
    <head>
        <base href="<?php echo PT_BASE_URL; ?>"></base>
        <script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.7.3.custom.min.js"></script>
        <link rel="stylesheet" href="css/op.css" type="text/css" />
        <link rel="stylesheet" href="css/jquery-ui-1.7.3.custom.css" type="text/css" />
    </head>    
    <body style="font-size:62.5%;">
        <div id="authd" title="Login">
            <form action="operator/auth" method="post">
                <label for="authUn">Username</label> <input type="text" name="authUn" id="authUn" value="" />
                <label for="authUn">Password</label> <input type="password" name="authPw" id="authPw" value="" />
                <input type="submit" value="login" name="login" id="login" />
            </form>
        </div>
    </body>
</html>
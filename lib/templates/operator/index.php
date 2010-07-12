<?php
$auth = new Auth($_SESSION[PT_SESSION_PFX.'username']);
$auth->load();

// Load the operators list for admin
if (Session::isAdmin()) {
    // grab a list of users
    $users = PandraCore::getRangeKeys('Peptalk', array('', ''), new cassandra_ColumnParent(array('column_family' => 'Auth')),
            new PandraSlicePredicate(PandraSlicePredicate::TYPE_COLUMNS, $auth->getColumnNames()));

    // strip tombstoned users
    foreach ($users as $idx => $keySlice) {
        if (empty($keySlice->columns)) unset($users[$idx]);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <base href="<?php echo PT_BASE_URL; ?>"></base>
    <script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.7.3.custom.min.js"></script>
    <link rel="stylesheet" href="css/pt.css" type="text/css" />
    <link rel="stylesheet" href="css/op.css" type="text/css" />
    <link rel="stylesheet" href="css/jquery-ui-1.7.3.custom.css" type="text/css" />

    <script type="text/javascript" src="<?php echo jSig('peptalk'); ?>"></script>
    <script type="text/javascript" src="<?php echo jSig('operator'); ?>"></script>
    <script type="text/javascript" src="<?php echo jSig('queue'); ?>"></script>

    <script type="text/javascript" src="js/jquery-edit-in-place.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            operator.init();
            //$('#peptalk_online_toggle').attr('checked', true);
        });
    </script>
    <body class="ptbody" style="font-size:62.5%;">
        
        <?php if ($_SESSION[PT_SESSION_PFX.'userlocal']) { ?>
        <div id="banner">
            Peptalk v0.1
                <a class="ptlink" href="javascript:operator.logout()" style="float:right">Logout</a>
        </div>
        <?php } ?>

        <div id="container">
            <div style="position:absolute; left:400px; top: 50px;" id="peptalk_div"></div>
            <div id="opcontrol">
                <?php if ($_SESSION[PT_SESSION_PFX.'userlocal']) { ?>
                <h3><a id="profileheader" href="#">My Profile</a></h3>
                <div>
                    <p>
                        <div style="padding: 0px;" class="ptklist">
                            <div id="profileSelf">
                                <div class="editable" align="center">Click field to edit, enter to save and esc to cancel</div>
                                <table cellpadding="5px">
                                    <tr>
                                        <td class="label">Username</td>
                                        <td><?php echo $_SESSION[PT_SESSION_PFX.'username'];?></td>
                                    </tr>

                                    <tr>
                                        <td class="label">Display Name</td>
                                        <td><div class="editable" id="session_displayname"><?php echo $auth['displayname'];?></div></td>
                                    </tr>

                                    <tr>
                                        <td class="label">Email</td>
                                        <td><div class="editable" id="session_emailaddress"><?php echo $auth['emailaddress'];?></div></td>
                                    </tr>

                                    <tr>
                                        <td class="label">Password</td>
                                        <td><div class="editable" id="session_password"><?php echo str_repeat('*', 10);?></div></td>
                                    </tr>
                                    <tr>
                                        <td align="left" colspan="2">
                                            <div id="operationstatus" name="operationstatus"></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </p>
                </div>
                <?php } ?>
                
                <h3><a id="peptalk_queueheader" href="#">Support Queue</a></h3>
                <div style="padding: 0px;">
                    <div id="pt_queue"></div>
                </div>

                <h3><a href="#">Transcripts</a></h3>
                <div>
                    <p>
                        <div style="padding: 0px;" class="ptklist">
                            date from
                            <input size="10" id="datefrom" type="text" />
                            to
                            <input size="10"  id="dateto" type="text" />
                            <input type="button" value="search" onclick="javascript:operator.search()" />

                            <div class="results" id="searchresult" name="searchresult"></div>
                        </div>
                    </p>


                </div>

                <?php if (Session::isAdmin()) { ?>
                <h3><a id="uadminheader" href="#">User Admin</a></h3>
                <div style="padding: 0px;">
                    <p>
                        <div class="editable" align="center">Click field to edit, enter to save and esc to cancel</div>
                    </p>

                    <table class="controltable">
                        <thead class="tableheader">
                            <td>Username</td>
                            <td>Type</td>
                            <td>Email</td>
                            <td>Password</td>
                        </thead>

                        <tr id="newUserRow">
                            <td><input type="text" name="new_username" id="new_username" value="" /></td>
                            <td>
                                <select id="new_type" name="new_type">
                                    <option value="<?php echo Auth::OPERATOR?>">Operator</option>
                                    <option value="<?php echo Auth::ADMIN?>">Admin</option>
                                </select>
                            </td>

                            <td><input style="width: 120px;" type="text" name="new_emailaddress" id="new_emailaddress" value="" /></td>
                            <td><input type="password" name="new_pwd" id="new_pwd" value="" /></td>
                            <td><a href="javascript:void(0)" onclick="operator.create()">create</a></td>
                        </tr>

                            <?php
                            $a = new Auth();
                            $odd = false;

                            $editable = array('type', 'emailaddress', 'password');
                            $ipl = '';

                            foreach ($users as $keySlice) {

                                $key = $keySlice->key;

                                // setup inplace edits
                                foreach ($editable as $edit) {
                                    $extra = '';
                                    if ($edit == 'emailaddress') {
                                        $extra = ", inputstyle: 'width: 120px;'";
                                    } else if ($edit == 'type') {
                                        $extra = ", field_type: 'select', select_text: '-', select_options: ['Admin:".Auth::ADMIN."', 'Operator:".Auth::OPERATOR."']";
                                    }
                                    $ipl .= "$('#".$key."_".$edit."').editInPlace( {url : 'operator/userupdate', success: function() { operator.flashE('uadminheader');} $extra});\n";
                                }

                                $odd = !$odd;
                                $a->populate($keySlice);
                                ?>
                        <tr class="userrow <?php echo ($odd) ? 'odd' : '';?>" id="user_<?php echo $a->getKeyID();?>">
                            <td><?php echo $a->getKeyID(); ?></td>
                            <td class="editable" id="<?php echo $a->getKeyID();?>_type"><?php echo ($a['type'] == Auth::ADMIN ? 'Admin' : 'Operator');?></td>
                            <td class="editable" id="<?php echo $a->getKeyID();?>_emailaddress"><?php echo $a['emailaddress'];?></td>
                            <td class="editable" id="<?php echo $a->getKeyID();?>_password"><?php echo str_repeat('*', 10);?></td>
                            <td><a href="javascript:void(0)" onclick="javascript:operator.rmuser('<?php echo $a->getKeyID(); ?>')">delete</a></td>
                        </tr>
                                <?php
                            }
                            ?>
                    </table>

                 <script type="text/javascript">
                     <?php echo $ipl; ?>
                    </script>
                </div>
                <?php } ?>
            </div>
        </div>
    </body>
</html>

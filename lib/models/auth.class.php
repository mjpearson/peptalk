<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Operator authentication and session handling
 *
 * @name      Peptalk v0.1
 * @author    Michael Pearson <michael@phpgrease.net>
 * @copyright (c) 2010 Envoy Media Group
 * @link      http://www.envoymediagroup.com
 * @license   GPL v2.0
 * @version   $Rev: $
 * @internal  $Id$
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */
class Auth extends PandraColumnFamily {

    /* @var int Admins can create operators and respond to chat requests */
    const ADMIN = 0;

    /* @var int operators can reply to chat requests */
    const OPERATOR = 1;

    /**
     *
     */
    public function init() {
        $this->setName('Auth');
        $this->setKeySpace('Peptalk');

        // Key = username
        $this->setKeyValidator(array('string20'));

        $this->addColumn('password', 'string', 'md5');
        $this->addColumn('type', 'enum='.self::ADMIN.','.self::OPERATOR);

        $this->addColumn('displayname', array('string', 'maxlength=64'));
        $this->addColumn('joindate', 'int', 'time');
        $this->addColumn('emailaddress', 'email');

        $this->setAutoCreate(FALSE);
    }

    static public function getTypeStr($type) {
        $str = '';
        if ($type == self::ADMIN) {
            $str = 'Admin';
        } elseif ($type == self::OPERATOR) {
            $str = 'Operator';
        }
        return $str;
    }

    static public function ok() {
        $ok = isset($_SESSION['username']) &&
                (!empty($_SESSION['username']) &&
                        $_SESSION['ptkoperator']
        );
        return $ok;
    }

    /**
     *
     */
    static public function check($username, $password) {
        $auth = new Auth($username);
        $found = $auth->load();
        
        if ($found && $auth->getColumn('password')->compareToCB($password)) {

            if ($auth['type'] == (int) self::ADMIN) {
                $_SESSION['ptkadmin'] = TRUE;
            } else {
                $_SESSION['ptkadmin'] = FALSE;
            }

            $_SESSION['username'] = $auth->getKeyID();
            $_SESSION['displayname'] = $auth['displayname'];
            $_SESSION['ptkoperator'] = TRUE;

            return TRUE;
        }
        return FALSE;
    }

    /**
     *
     * @return bool Session is an operator
     */
    static public function isOperator() {
        return isset($_SESSION['ptkoperator']) && $_SESSION['ptkoperator'];
    }

    /**
     *
     */
    static public function isAdmin() {
        return self::isOperator() && isset($_SESSION['ptkadmin']) && $_SESSION['ptkadmin'];
    }

    static public function setAdmin($bool) {
        $_SESSION['ptkadmin'] = $bool;

    }

    static public function setOperator($bool) {
        $_SESSION['ptkoperator'] = $bool;
    }

    static public function getOperatorName() {
        return $_SESSION['username'];
    }
}
?>
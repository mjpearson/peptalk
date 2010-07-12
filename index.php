<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Library setup and controller handler
 *
 * @name      Peptalk v0.1
 * @author    Michael Pearson <michael@phpgrease.net>
 * @copyright (c) 2010 Envoy Media Group
 * @link      http://www.envoymediagroup.com
 * @license   GPL v2.0
 * @version   $Rev: $
 * @internal  $Id$
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
// -----------------------------------------------------------------------------
if (file_exists(dirname(__FILE__).'/install.php')) {
    echo 'Please remove install.php';
    exit;
}

error_reporting(E_ALL);

set_include_path(
                    get_include_path().PATH_SEPARATOR.
                    dirname(__FILE__).'/lib/'.PATH_SEPARATOR.
                    dirname(__FILE__).'/lib/models'.PATH_SEPARATOR.
                    dirname(__FILE__).'/lib/controllers'
        );

spl_autoload_extensions('.class.php');
spl_autoload_register();

if (!class_exists('Memcache')) throw new RuntimeException('Memcached module not detected');


require_once(dirname(__FILE__).'/pandra/config.php');

define('PT_INSTALL_BASE', dirname(__FILE__));
require_once(PT_INSTALL_BASE.'/config.php');

// Memcached setup
ini_set('session.save_handler', 'memcache');
ini_set('session.save_path', PT_MEMCACHED_SERVER.':'.PT_MEMCACHED_PORT);

// expire operator sessions in 15 mins, guest in 60 seconds
ini_set('session.gc_maxlifetime', isset($_REQUEST['controller']) && $_REQUEST['controller'] == 'operator' ? 900 : Meta::IDLE_TIMEOUT);

session_start();
ob_start();

// setup our memcache capabilities
if (class_exists('Memcached')) {
    $_MemCached = new Memcached();
    
} elseif (class_exists('Memcache')) {
    $_MemCached = new Memcache();
    
}

$_MemCached->addServer(PT_MEMCACHED_SERVER, PT_MEMCACHED_PORT);
//$_mcPfx = 'memc.sess.key.';
//$_mcPfxLock = 'memc.sess.key_lock.';
$_mcPfx = '';
$_mcPfxLock = '';

// Connect to our cluster
PandraCore::auto(PT_CLUSTER_HOST);

// Set consistency
PandraCore::setConsistency(PT_CASSANDRA_CONSISTENCY);
/*
$a = new Auth('admin');
$a['displayname'] = 'Administrator';
$a['password'] = 'password';
$a['type'] = Auth::ADMIN;
$a['joindate'] = time();
$a['emailaddress'] = 'yourname@yourdomain.com';
$a->save();
*/

function jSig($filename) {    
    return 'js/'.$filename.'.'.md5_file(dirname(__FILE__).'/js/'.$filename.'.js.php').'.js';
}

if (isset($_REQUEST['controller'])) {
    $controller = $_REQUEST['controller'];
    if (empty($_REQUEST['view'])) {
        $_REQUEST['view'] = 'index';
    }

    $view = $_REQUEST['view'];

    $controllerClass = 'ptk'.$controller;

    if (class_exists($controllerClass)) {
        $_controller = new $controllerClass();
        $_controller->parseRequest($_REQUEST);
        $_controller->execute($view);
    } else {
        ptkController::redir404($controller, $view);
    }
} else {
    ptkController::redir404();
}
session_write_close();
ob_flush();
?>

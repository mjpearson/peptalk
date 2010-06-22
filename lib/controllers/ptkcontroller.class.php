<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Base controller class handles request sanitisation and routing
 *
 * Available keys :
 *
 *  - queue             Latest unanswered live support requests
 *  - yyyymmdd		Daily breakdown of dequeued (replied) cids
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
class ptkController {

    private $_ajSource = FALSE;

    private $_tplBase = '';

    protected $_request = array();

    protected $_authRequired = FALSE;

    public function __construct() {
        global $_controller;
    }

    // should be implemented by child controllers requiring authentication
    protected function checkAuth() {

    }

    static public function redir404() {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	require_once(PT_INSTALL_BASE.'/404.html');
        exit;
    }

    protected function redirect($controller, $view = '') {
        if (!empty($view)) {
            $controller .= '/'.$view;
        }
        header('location: '.PT_BASE_URL.$controller);
    }

    /**
     * Finds templates for the view
     */
    public function display($view) {
        $controller = $this;

        $tpl = $this->_tplBase.$view.'.php';
        if (!empty($tpl)) {
            if (!$this->_ajSource && file_exists($this->_tplBase.'_header.php')) include $this->_tplBase.'_header.php';
            if (file_exists($tpl)) require_once $tpl;

            if (!$this->_ajSource && file_exists($this->_tplBase.'_footer.php')) include $this->_tplBase.'_footer.php';
        }

    }

    /**
     * @todo input sanitation
     */
    protected function sanitise(&$value) {
        $value = utf8_encode(strip_tags($value));
    }

    /**
     *
     */
    public function parseRequest($request) {
        $this->_ajSource = (isset($request['ajs']) && $request['ajs'] == 1);
        $this->_request = $request;
        array_walk($this->_request, array($this, 'sanitise'));
    }

    /**
     * validates and loads controller/view
     */
    public function execute($view) {

        $method = 'exec'.$view;
        $methodOK = method_exists($this, $method);

        // check for a view template
        $this->_tplBase =  PT_INSTALL_BASE.
                DIRECTORY_SEPARATOR.
                'lib'.
                DIRECTORY_SEPARATOR.
                'templates'.
                DIRECTORY_SEPARATOR.
                preg_replace('/^ptk/i', '', strtolower(get_class($this))).
                DIRECTORY_SEPARATOR;

        if ($methodOK) {

            if (   $view == 'index' ||
                    $view == 'auth' ||
                    $view == 'logout' ||
                    !$this->_authRequired ||
                    ($this->_authRequired && $this->checkAuth())) {

                try {
                    $this->$method();
                    $this->display($view);
                } catch (ControllerAuthException $e) {
                    $this->responseNOAUTH();
                }

            } else {
                $this->responseNOAUTH();
            }
        } else {
            self::redir404(get_class($this), $view);
        }
    }

    // -------------------------------------------------------------------------
    // standard responses

    public function responseNOP() {
        $this->responseStatus('NOP');
    }

    public function responseDC() {
        $this->responseStatus('DC');
    }

    public function responseTIMEOUT() {
        $this->responseStatus('TIMEOUT');
    }

    public function responseOK() {
        $this->responseStatus('OK');
    }

    public function responseNOAUTH() {
        $this->responseErr('Not Authorised');
    }

    public function responseStatus($status) {
        echo json_encode(array('status' => $status));
    }

    public function responseERR($errMsg) {
        header('HTTP/1.1 500 Internal Server Error');
        echo $errMsg;
    }
}

class ControllerAuthException extends Exception {

}

?>

<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Chat controller
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
class ptkChat extends ptkController {

    private $_chatLog = NULL;

    private $_meta = NULL;

    public $cid = NULL;

    public function __construct() {
        parent::__construct();
        if (empty($_SESSION[PT_SESSION_PFX.'cid'])) {
            $_SESSION[PT_SESSION_PFX.'cid'] = UUID::v1();
        }

        // Grab generated chat id, meta and chat log
        $this->cid = $_SESSION[PT_SESSION_PFX.'cid'];

        $this->_meta = new Meta($this->cid);

        $this->_chatLog = new Log($this->cid);
    }

    public function cleanup () {
        $this->cid = NULL;
        unset($_SESSION[PT_SESSION_PFX.'cid']);
        unset($_SESSION[PT_SESSION_PFX.'last']);
    }

    public function drop() {
        if ($this->cid) Meta::close($this->cid);
        $this->cleanup();
    }

    // setup any chat dialog init
    public function execIndex() {}

    /**
     * Appends a message to the chat log.  If the chat does not exist and the
     * message is not empty, it creates it in the response queue
     */
    public function execIn() {

        $message = $this->_request['message'];

        $cid = $this->cid;

        $new = !$this->_meta->load();

        $op = Session::isOperator();

        // new guest?  Create a new meta + queue entry
        if ($new && !$op) {
            $this->_meta = $this->_meta->createQueue($cid, $message);
        }

        if (empty($message)) {
            $this->responseNOP();

        } elseif ($this->_meta['status'] == Meta::STATUS_CLOSED || $this->_meta['status'] == Meta::STATUS_TIMEOUT) {
            $this->responseDC();

        } else {

            $cl = $this->_chatLog;

            $msgID = $cl->addMessage($message);

            // Save the last message id to session
            if ($cl->save()) {
                $_SESSION[PT_SESSION_PFX.'last'] = $msgID;
                $this->responseOK();
            } else {
                $this->responseERR();
            }
        }

        // save the last activity time
        $this->_meta['lastMessageTime'] = time();
        $this->_meta->save();

    }

    /**
     * Polls the chatlog for any new messages since the $_SESSION['last'] message id
     */
    public function execPing() {

        $r = $this->_request;
        $cl = $this->_chatLog;
        $cid = $this->cid;
        $m = $this->_meta;

        $op = Session::isOperator();

        // If chat does not exist or is a guest without a last message, then
        // we have a problem
        if (!$m->load() || (!$op && !isset($_SESSION[PT_SESSION_PFX.'last']))) {

            $this->responseNOP();

        } elseif ($m['status'] == Meta::STATUS_CLOSED) {
            $this->responseDC();

        } elseif ($m['status'] == Meta::STATUS_TIMEOUT) {
            $this->responseTIMEOUT();

        } else {

            // Load whole of log if it hasn't been retrieved before
            if (empty($_SESSION[PT_SESSION_PFX.'last'])) {
                $cl->reverse(FALSE)->load();
            } else {
                $cl->start($_SESSION[PT_SESSION_PFX.'last'])->reverse(FALSE)->load();
            }

            // polling ignores the last message the client sent
            if ($r['poll'] == 'true') {
                $lastUUID = UUID::toStr($_SESSION[PT_SESSION_PFX.'last']);
                unset($cl[$lastUUID]);
            }

            $response = array();

            if (count($cl)) {
                $cSuper = $cl->current();
                $_SESSION[PT_SESSION_PFX.'last'] = $cSuper->getName();

                foreach ($cl as $sc => $column) {
                    if ($column['type'] == 'guest') {
                        $column['user'] = 'Guest';
                    }
                    $column['servertime'] = date('H:i:s', $column['servertime']);
                    $response[] = $column->toArray();
                }
            }

            echo json_encode(array('OK' => $response));
        }
    }

    /*
    * Drop destroys knowledge of the cid in the session, and marks conversation
    * as closed
    */
    public function execDrop() {
        $this->drop();
        $this->responseOK();
    }

    /**
     * Drops the active chat and logs out
     */
    public function execLogout() {
        $this->drop();
        session_destroy();

        $this->responseOK();
    }
}
?>

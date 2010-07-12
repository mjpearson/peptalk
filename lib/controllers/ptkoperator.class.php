<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Operator controller handles all /operator functions
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
class ptkOperator extends ptkController {

    protected $_authRequired = TRUE;

    public function __construct() {
        $this->_queue = new Queue('queue');
        parent::__construct();
    }

    /**
     * Helper method to check authentication
     * @return bool user has an active operator session
     */
    public function checkAuth() {
        return Auth::ok();
    }

    /**
     * Prompts user to authenticate if they're not logged in
     */
    public function execIndex() {
        if (!$this->checkAuth()) {
            header('location: operator/auth');
            exit;
        }
    }

    /**
     * Attempts to authenticate the user and redirects to operator/index if so
     */
    public function execAuth() {
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $r = $this->_request;

            if (Auth::check($r['authUn'], $r['authPw'])) {
                $this->responseOK();
                $this->redirect('operator');
            }
        }
    }

    /**
     * Searches between from and to dates in Queue, echo's json econded result set
     */
    public function execDateSearch() {
        
        $r = $this->_request;

        if (empty($r['datefrom'])) {
            $this->responseNOP();
        } elseif (empty($r['dateto'])) {
            $this->responseNOP();
        } else {
            $from = $r['datefrom'];
            $to = $r['dateto'];

            $rangeResult = PandraCore::getRangeKeys('Peptalk',
                                                    array('start' => $from,
                                                            'finish' => $to
                                                    ),
                                                    new cassandra_ColumnParent(array('column_family' => 'Queue')),
                                                    new PandraSlicePredicate(PandraSlicePredicate::TYPE_RANGE, array('start' => '', 'finish' => '', 'count' => 100) )
                                                );

            $results = array();

            foreach ($rangeResult as $keySlice) {
                $date = $keySlice->key;
                if ($date == 'queue') continue;
                $results[$date] = array();

                foreach ($keySlice->columns as $column) {

                    $c = $column->column;

                    $payload = json_decode($c->value, TRUE);

                    $results[$date][] = array(
                        'time' => date('G:i:s', $c->timestamp / 1000),
                        'cid' => UUID::toStr($c->name),
                        'remote' => $payload['REMOTE_ADDR'],
                        'message' => $payload['MSG'],
                        'operator' => $payload['OPERATOR']
                    );
                }
            }

            if (count($results)) {
                echo json_encode(array('OK' => $results));
            } else {
                $this->responseNOP();
            }
        }
    }

    /**
     * Loads a transcript by cid
     */
    public function execLoadTranscript() {
        $r = $this->_request;

        $meta = new Meta($r['cid']);

        if ($meta->load()) {
            // load transcript
            $log = new Log($r['cid']);
            $lines = array();
            if ($log->reverse(FALSE)->load()) {
                $lines[] = $log->toArray();
            } else {
                $this->responseERR($lines->getLastError());
            }
            
            //$lines['meta'] = json_decode($meta['guestinfo'], TRUE);
            echo json_encode(array('OK' => $lines));

        } else {
            $this->responseERR($meta->getLastError());
        }
    }

    /**
     * Updates the profile of the currently logged in user
     */
    public function execProfileUpdate() {
        $r = $this->_request;

        $a = new Auth($_SESSION[PT_SESSION_PFX.'username']);
        if ($a->load()) {
            $a->reset();

            $newValue = $r['update_value'];
            list($pfx, $columnName) = explode('_', $r['element_id']);;

            if ($a->getColumn($columnName)->setValue($newValue)) {
                $a->save();
            
                // edit-in=place display hack
                if ($columnName == 'password') {
                    echo str_repeat('*', 10);
                } else {
                    echo $newValue;
                }
            } else {
                $this->responseERR($a->getLastError(TRUE));
            }
        } else {
            $this->responseERR('User does not exist');
        }
    }

    /**
     * Updates the profile of a named user (admin only)
     */
    public function execUserUpdate() {
        if (!Session::isAdmin()) {
            throw new ControllerAuthException();
        }

        $r = $this->_request;

        list($key, $columnName) = explode('_', $r['element_id']);;
        $a = new Auth($key);
        
        if ($a->load()) {
            $newValue = $r['update_value'];
            
            if ($a->getColumn($columnName)->setValue($newValue)) {
                $a->save();
                
                // edit-in=place display hack
                if ($columnName == 'password') {
                    echo str_repeat('*', 10);
                } else if ($columnName == 'type') {
                    echo Auth::getTypeStr($newValue);
                } else {
                    echo $newValue;
                }
            } else {
                $this->responseERR($a->getLastError(TRUE));
            }
        } else {
            $this->responseERR('User does not exist');
        }        
    }

    /**
     * Creates a new user in Cassandra (admin only)
     */
    public function execNewUser() {
        if (!Session::isAdmin()) {
            throw new ControllerAuthException();
        }
        
        $r = $this->_request;

        $a = new Auth($r['username']);
        if (!$a->setKeyID($r['username'])) {
            $this->responseERR($a->getLastError(TRUE));
        } elseif ($a->load()) {
            $this->responseERR('User already exists');
        } else {
            if (!$a->populate($r) || $a->getColumn('displayname')->setValue($r['username']) || !$a->save()) {
                $this->responseERR($a->getLastError(TRUE));
            } else {
                $this->responseOK();
            }
        }
    }

    /**
     * Deletes a user from Cassandra (admin only)
     */
    public function execDelUser() {
        if (!Session::isAdmin()) {
            throw new ControllerAuthException();
        }

        $r =  $this->_request;
        $a = new Auth($r['username']);

        $a->load();

        if ($a->load()) {
            $a->delete();
            $ok = $a->save();
        }

        if ($ok) {
            $this->responseOK();
        } else {            
            $this->responseERR($a->getLastError());
        }
    }

    /**
     * Destroys any open chats and drops the
     */
    public function execLogout() {
        if (!empty($_SESSION[PT_SESSION_PFX.'cid'])) {
            Meta::close($_SESSION[PT_SESSION_PFX.'cid']);
        }

        session_destroy();
        echo json_encode(array('result' => 'OK'));
    }

    /**
     * Polls the queue for new entries, expires entries which are marked as
     * timed out or disconnected by client, or if the session has expired from
     * memcached
     */
    public function execQPoll() {
        $q = $this->_queue;
        $q->load();

        $queue = array();

        // Grab all metas, so we can disconnect or timeout any which are old
        // and dump them from the queue
        $m = new Meta();
        $predicate = new cassandra_SlicePredicate();
        $predicate->slice_range = new cassandra_SliceRange();
        $predicate->slice_range->start = '';
        $predicate->slice_range->finish = '';

        $metaMap = PandraCore::getCFSliceMulti(
                $q->getKeySpace(),
                $q->getColumnNames(),
                new cassandra_ColumnParent(
                array(
                        'column_family' => $m->getName())),
                $predicate);

        // iterates through the queue and expires any bad sessions
        //
        foreach ($q as $cid => $data) {

            $client = json_decode($data->value);

            $status = Meta::STATUS_CLOSED;

            foreach ($metaMap[$cid] as $column) {
                if ($column->column->name == 'status') {
                    $status = $column->column->value;
                }
            }

            // Active sessions for 'new' chats should be in the queue
            if (Session::sessionExists($client->SESSION_ID) && $status == Meta::STATUS_NEW) {
                $queue[$cid] = array(
                        'host' => $client->REMOTE_ADDR,
                        'msg' => $client->MSG
                );
            } else {

                // as long as another op hasn't taken it in the meantime, then
                // close
                if ($status != Meta::STATUS_CHAT) {
                    Meta::close($cid);
                }

                Queue::removeCID($cid);
            }
        }

        echo json_encode($queue);
    }

    /**
     * Moves a chat from the queue to 'yyyymmdd' log for reporting
     */
    public function execDequeue() {
        $r = $this->_request;
        $cid = $r['cid'];

        $q = $this->_queue;

        $ok = $q->start($cid)->finish($cid)->load();

        $m = new Meta($cid);
        $loaded = $m->load();

        // Bind the operator session to the cid
        if ($ok && $loaded && $m['status'] == Meta::STATUS_NEW) {

            $_SESSION[PT_SESSION_PFX.'cid'] = $cid;

            // first poll should be from beginning
            $_SESSION[PT_SESSION_PFX.'last'] = '';

            $m['operator'] = $_SESSION[PT_SESSION_PFX.'username'];
            $m['status'] = Meta::STATUS_CHAT;
            $m->save();

            // save cid log for operator
            $opidx = new OpTranscriptIDX();
            $opidx->setKeyID($m['operator']);
            $opidx[$cid] = date('Ymd');
            $opidx->save();

            // move to yyyymmdd for reporting
            $ok = $q->moveToReporting($m['operator']);

        } else {
            $ok = FALSE;
        }

        $ok ? $this->responseOK() : $this->responseERR($q->getLastError());
    }

    /**
     * Sets 'online' status
     */
    public function execStat() {
        $r = $this->_request;
        if (isset($r['online']) && is_numeric($r['online'])) {
            $ok = Session::onlineStatus((bool) $r['online']);
            return ($ok) ? $this->responseOK() : $this->responseErr('Could not mark online status');
        } else {
            $this->responseERR('Invalid Request');
        }
    }

    /**
     * Tells the client whether the active session is marked as an 'online' operator
     */
    public function execStatMe() {
        echo (int) Session::isOnline(session_id());
    }

    public function execOnlinePoll() {
        $ok = Session::onlinePoll();
        echo $ok;
    }
}
?>
<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Meta stores general state information about a cid
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
class Meta extends PandraColumnFamily {

    const STATUS_NEW = 0;

    const STATUS_CHAT = 1;

    const STATUS_CLOSED = 2;

    const STATUS_TIMEOUT = 3;

    // number of seconds idle a chat can be, before timing out
    const IDLE_TIMEOUT = 60;

    public function init() {
        $this->setName('Meta');
        $this->setKeySpace('Peptalk');

        $this->addColumn('status', array('enum='.implode(',', $this->statusMap())));
        $this->addColumn('startTime', 'int');
        $this->addColumn('lastMessageTime', 'int');
        $this->addColumn('operator', 'string');
        $this->addColumn('guestinfo', 'string');

        $this->setAutoCreate(FALSE);
    }

    /**
     * Automatically marks old chats as closed if pings have timed out
     */
    public function load($keyID = NULL, $consistencyLevel = cassandra_ConsistencyLevel::ONE) {
        $loaded = parent::load($keyID, $consistencyLevel);
        if ($loaded) {
            if (time() - $this['lastMessageTime'] > self::IDLE_TIMEOUT) $this->column_status = self::STATUS_TIMEOUT;
            return $this->save();
        }
    }

    public function statusMap() {
        // @todo reflection
        return array(self::STATUS_NEW, self::STATUS_CHAT, self::STATUS_CLOSED, self::STATUS_TIMEOUT);
    }

    static public function close($cid) {
        // Remove from queue
        Queue::removeCID($cid);

        $m = new Meta($cid);
        if ($m->load()) {
            $m['status'] = self::STATUS_CLOSED;
            return $m->save();
        }
        return FALSE;
    }

    /**
     * Creates a 'meta' chat pointer and initialises the conversation in the
     * support queue
     * @param <type> $cid
     * @param <type> $message
     * @return <type>
     */
    public function createQueue($cid, $message) {
        if (!empty($message)) {
            $m = new Meta($cid);
            $m['status'] = Meta::STATUS_NEW;
            $m['startTime'] = time();
            $m['lastMessageTime'] = time();
            $m['operator'] = '';
            $m['guestinfo'] = json_encode($_SERVER);
            $m->save();

            $q = new Queue();
            $q->setKeyID('queue');
            $q[$cid] = json_encode(array(
                                            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
                                            'MSG' => $message,
                                            'SESSION_ID' => session_id()
                    ));
            $q->save();
            return $m;
        }
        return NULL;
    }
}
?>
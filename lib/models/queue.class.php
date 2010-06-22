<?php
/**
 * Peptalk - An instant support tool for PHP
 *
 * Chat queue stores a range of time-series chat id's (requires  OrderPreservingPartitioner
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
class Queue extends PandraColumnFamily {

    const QUEUE = 'queue';

    public function init() {
        $this->setKeySpace('Peptalk');
        $this->setName('Queue');
        $this->setType(PandraColumnContainer::TYPE_UUID);
    }

    static public function removeCID($cid) {
        $q = new Queue(Queue::QUEUE);

        if ($q->start($cid)->finish($cid)->load()) {
            $q->getColumn($cid)->delete();
            return $q->save();
        }

        return FALSE;
    }

    public function moveToReporting($takenBy = NULL) {

        // Todays's answered queue
        $active = new Queue(date('Ymd'));

        foreach ($this->_columns as $column) {
            $c = clone $column;
            $column->delete();

            $c->setParent($active);
            $c->setModified(TRUE);

            $oPayload = json_decode($c->getValue(), TRUE);
            $oPayload['OPERATOR'] = $takenBy;

            $c->setValue(json_encode($oPayload));

            $active->addColumnObj($c);
        }

        $this->save();
        return $active->save();
    }
}
?>

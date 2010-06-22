<?php
/**
 *
 * Peptalk - An instant support tool for PHP
 *
 * Maintains historical cid index for an operator
 *
 *
 * key = operator username
 * struct = cid => Ymd (yyyymmdd date)
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
class OpTranscriptIDX extends PandraColumnFamily {
    public function init() {
        $this->setName('OpTranscriptIDX');
        $this->setKeySpace('Peptalk');
        $this->setType(PandraColumnContainer::TYPE_UUID);
    }
}


?>

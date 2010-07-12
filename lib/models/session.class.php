<?php

class Session {

    const MC_ONLINE_KEY = 'PTK_ops';

    /**
     * binds an authenticated user to Peptalk
     */
    static public function bindUser($userName, $displayName, $admin = FALSE) {
        $_SESSION[PT_SESSION_PFX.'username'] = $userName;
        $_SESSION[PT_SESSION_PFX.'displayname'] = $displayName;
        $_SESSION[PT_SESSION_PFX.'operator'] = TRUE;
        $_SESSION[PT_SESSION_PFX.'admin'] = $admin;
        $_SESSION[PT_SESSION_PFX.'userlocal'] = FALSE;
    }

    static public function isUserLocal() {
        return $_SESSION[PT_SESSION_PFX.'userlocal'];
    }

    static public function setUserLocal($local) {
        $_SESSION[PT_SESSION_PFX.'userlocal'] = $local;
    }

    /**
     *
     * @return bool Session is an operator
     */
    static public function isOperator() {
        return isset($_SESSION[PT_SESSION_PFX.'operator']) && $_SESSION[PT_SESSION_PFX.'operator'];
    }

    /**
     *
     */
    static public function isAdmin() {
        return self::isOperator() && isset($_SESSION[PT_SESSION_PFX.'admin']) && $_SESSION[PT_SESSION_PFX.'admin'];
    }

    static public function sessionExists($sessionID) {
        global $_MemCached, $_mcPfx, $_mcPfxLock;

        $mc = $_MemCached->get($_mcPfx.$sessionID);
        if (!$mc) {
            $mc = $_MemCached->get($_mcPfxLock.$sessionID);
        }
        return (bool) $mc;
    }

    /**
     *
     */
    static public function onlineStatus($stat, $sessionID = NULL) {
        global $_MemCached;

        $onlineOps = $_MemCached->get(self::MC_ONLINE_KEY);

        if (!is_array($onlineOps)) {
            $onlineOps = array();
        }

        $sid = ($session_id === NULL) ? session_id() : $sessionID;

        // already marked or unmarked?
        if (($stat && in_array($sid, $onlineOps)) ||
            (!$stat && !in_array($sid, $onlineOps))) {
            return TRUE;
        }

        if ($stat) {
            array_push($onlineOps, $sid);
        } else {
            //unset($onlineOps[$sessionID]);
            // I hate you, PHP
            foreach ($onlineOps as $idx => $ss) {
                if ($ss == $sid) unset($onlineOps[$idx]);
            }
        }
        return $_MemCached->set(self::MC_ONLINE_KEY, $onlineOps);
    }

    static public function isOnline($sessionID) {
        global $_MemCached;
        $online = FALSE;

        if (self::sessionExists($sessionID)) {
            /*
            $onlineOps = $_MemCached->get(self::MC_ONLINE_KEY);
            $opSessions = array_values($onlineOps);
            */
            $ol = $_MemCached->get(self::MC_ONLINE_KEY);
            if (!empty($ol)) {
                $opSessions = array_values($ol);
                return in_array($sessionID, $opSessions);
            }
        }

        return $online;
    }

    /**
     * Checks that all operators marked as 'online' still exist. Returns
     * whether or not any operators are available
     */
    static public function onlinePoll() {
        global $_MemCached;

        $onlineOps = $_MemCached->get(self::MC_ONLINE_KEY);

        if (!empty($onlineOps) && is_array($onlineOps)) {
            foreach ($onlineOps as $idx => $sessionID) {
                if (!self::sessionExists($sessionID)) {
                    self::onlineStatus(FALSE, $sessionID);
                    unset($onlineOps[$idx]);
                }
            }
        }

        return count($onlineOps);
    }
}
?>
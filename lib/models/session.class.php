<?php

class Session {

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

}
?>
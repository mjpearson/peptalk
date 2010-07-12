<?php
    // Cassandra Host
    define('PT_CLUSTER_HOST', 'localhost');

    // Memcached host
    define('PT_MEMCACHED_SERVER', '127.0.0.1');

    // Memcached port
    define('PT_MEMCACHED_PORT', '11211');

    // Read/Write Cassandra consistency
    define('PT_CASSANDRA_CONSISTENCY', 1);

    define('PT_BASE_URL', 'http://projects.local/peptalk_git/peptalk/');

    define('PT_SESSION_PFX', 'PTK_');

    define('PT_OFFLINE_MESSAGE', 'Sorry, no operators are available');

    $welcome = <<<end
    <div width=100% style="text-align: center;
                            background-color: #fff;
                            color: #888;
                            text-transform: uppercase">Styled welcome message</div>
end;

    define('PT_WELCOME_MESSAGE', $welcome);
?>

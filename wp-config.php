<?php
define('DISABLE_WP_CRON', true);

/**
 * The base configuration for WordPress
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 * @package WordPress
 */

// ** Database and URL settings - Environment Switching ** //

if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    /** LOCAL SETTINGS **/
    define( 'WP_HOME', 'http://localhost/jasonwpclone' );
    define( 'WP_SITEURL', 'http://localhost/jasonwpclone' );

    define('DB_NAME', 'maxussql');
    define('DB_USER', 'root');
    define('DB_PASSWORD', '');
    define('DB_HOST', 'localhost:3306'); 
} else {
    /** VPS SETTINGS **/
    define( 'WP_HOME', 'https://maxusvanparts.acstestweb.co.uk' );
    define( 'WP_SITEURL', 'https://maxusvanparts.acstestweb.co.uk' );

    define( 'DB_NAME', 'wp_cw2rp' );
    define( 'DB_USER', 'wp_khnpj' );
    define( 'DB_PASSWORD', 'eq0R@M^5?1#q@THS' );
    define( 'DB_HOST', 'localhost' ); 
}

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define( 'WP_MEMORY_LIMIT', '512M' );

/**#@+
 * Authentication unique keys and salts.
 */
define( 'AUTH_KEY',         'o?h~53{pb~}1E76(a+~q<jLO$o{4qL60@cS%{m023?r>vwm]md|-vY4O#3-,bUj5' );
define( 'SECURE_AUTH_KEY',  'E]Rlt+m7J}&&%sXm>?:#n9IyLjXh$y}6%Op!N-]o@KQ+<3Cj&lEsJmVmJ*=~UcSg' );
define( 'LOGGED_IN_KEY',    'TTy ig4+V+8^OYL}{[]6?2`tmmmLz.G>=v$U${8y7ii)wr^f@=]a+Cok$,DbjeML' );
define( 'NONCE_KEY',         '%;rD0PJvyPY#/Cb->UtUxf5Twcl$L-U6^LqWe4.=4:rmx!+)_Z89`4@Ed[bK/-z*' );
define( 'AUTH_SALT',         'WV<vL8JiDpE1?>A.j%:%{hp+c1M}=:-U#5K0x8LQlhHz:kqFG2.5S4 kIHCU8rD&' );
define( 'SECURE_AUTH_SALT',  '}m!(nFojDa}7?3uFjg,mEvC45#MlK/FTF!gJp;l%1dhZ]+Cdu~/RB>d=+;B{T7qf' );
define( 'LOGGED_IN_SALT',    ' :-oXwFF$jh;<UTz9l*r-n:lBf6fI>$hF}[Wl4c#.C<B1xNxGCU^pwk)T!l].&^6' );
define( 'NONCE_SALT',        '+u(w:iVal4OV2-?;47Y_b:>6M2zbK`:}G]<FlC~cH=i%UqOZhw0!y:Bc|xL{2y7v' );
define( 'WP_CACHE_KEY_SALT', '*,Qo[$KB)9_DU&cf5Sq6)bJ0ug!9>zGrffa2A3(WcV7-d4?; 9l45Sgs_kb:1!tr' );

/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 */
if ( ! defined( "WP_DEBUG" ) ) {
    define( "WP_DEBUG", false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
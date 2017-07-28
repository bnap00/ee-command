<?php

class EE_Command extends WP_CLI_Command {

	/**
	 * Set to true if website is created/updated
	 * with php7. Used to override version 5.6.
	 *
	 * @var boolean
	 */
	public $php_version_update = false;

	/**
	 * Name of the site.
	 *
	 * @var string
	 */
	public $site_name          = '';

	/**
	 * Type of the site.
	 *
	 * Example
	 * - HTML
	 * - PHP
	 * - WordPress
	 *
	 * @var string
	 */
	public $site_type          = '';

	/**
	 * Code of the type of site. Useful during update()
	 * operation.
	 *
	 * @var string
	 */
	public $site_type_code     = '';

	/**
	 * Type of cache used.
	 *
	 * @var string
	 */
	public $cache_type         = '';

	/**
	 * Version of PHP used.
	 *
	 * @var string
	 */
	public $php               = '';

	/**
	 * Set to `enabled` while installing letsencrypt.
	 *
	 * @var string
	 */
	public $letsencrypt        = '';

	/**
	 * Set to `yes`.
	 *
	 * @var string
	 */
	public $mysql              = '';

	/**
	 * Databse connection object.
	 *
	 * @var object
	 */
	public $conn               = '';

	/**
	 * Array stores information which is used
	 * to create configuration files.
	 *
	 * @var string
	 */
	public $printer_array      = array();

	/**
	 * This array is referred while during
	 * create() and update() operations.
	 *
	 * @var array
	 */
	public $site_data          = array(
		'html' => array(
			'site-type'  => 'HTML',
		),
		'mysql' => array(
			'site-type'  => 'PHP',
			'mysql'      => 'yes',
			'php'        => '5.6',
		),
		'php' => array(
			'site-type'  => 'PHP',
			'php'        => '5.6',
		),
		'php7' => array(
			'php'        => '7.0',
		),
		'wp' => array(
			'site-type'  => 'WordPress',
			'mysql'      => 'yes',
			'php'        => '5.6',
		),
		'wpfc' => array(
			'site-type'   => 'WordPress',
			'mysql'       => 'yes',
			'php'         => '5.6',
			'cache-type'  => 'nginx fastcgi_cache',
		),
		'wpredis' => array(
			'site-type'   => 'WordPress',
			'mysql'       => 'yes',
			'php'         => '5.6',
			'cache-type'  => 'nginx redis_cache',
		),
		'letsencrypt' => array(
			'letsencrypt' => 'enabled',
		),
	);

	/**
	 * This array is referred during update() operation.
	 *
	 * Example: wp ee site update example.com --wp
	 * The index of the argument `wp` from the below array
	 * will be used for update() operation.
	 *
	 * @var array
	 */
	public $site_update_argument = array(
		'html',
		'php',
		'php7',
		'mysql',
		'wp',
		'wpfc',
		'wpredis',
	);

	/**
	 * During update() operation, the `site_type_code` will be fetched and
	 * the index returned from $site_update_argument array will be used
	 * to compare it with the below null, true and false values.
	 *
	 * The columns of booelan values are in this order:
	 * html, php, php7, mysql, wp, wpfc, wpredis
	 *
	 * @var array
	 */
	public $site_update_constraints = array(
		'html'    => array( null, true, true, true, true, true, true ),
		'php'     => array( false, null, true, true, true, true, true ),
		'php7'    => array( false, false, null, true, true, true, true ),
		'mysql'   => array( false, false, false, null, true, true, true ),
		'wp'      => array( false, false, false, false, null, true, true ),
		'wpfc'    => array( false, false, false, false, true, null, true ),
		'wpredis' => array( false, false, false, true, true, true, null ),
	);

	/**
	 * Creates a site and stores site data in database and also creates
	 * config files.
	 *
	 * Example: wp ee site create example.com --wpfc --letsencrypt
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function create( $_, $assoc_args ) {
		if ( empty( $_[0] ) ) {
			WP_CLI::error( 'Site name is missing!' );
			return;
		} else {
			$this->site_name = $_[0];
		}

		if ( array_key_exists( 'php7', $assoc_args ) ) {
			$this->php_version_update = true;
		}

		if ( 1 === count( $assoc_args ) && array_key_exists( 'php7', $assoc_args ) ) {
			$this->site_data['php7']['site-type'] = 'PHP';
		}

		foreach ( $assoc_args as $key => $value ) {
			if ( ! array_key_exists( $key, $this->site_data ) ) {
				WP_CLI::error( 'Incorrect argument: ' . $key );
				return;
			} else {
				foreach ( $this->site_data[ $key ] as $context => $context_value ) {
					$this->printer_array[] = $context . '=' . $context_value;
					switch ( $context ) {
						case 'site-type':
							$this->site_type_code = $key;
							$this->site_type = $context_value;
							break;

						case 'mysql':
							$this->mysql = $context_value;
							break;

						case 'php':
							$this->php = $context_value;
							break;

						case 'cache-type':
							$this->cache_type = $context_value;
							break;

						case 'letsencrypt':
							$this->letsencrypt = $context_value;
							break;
					}
				}
			}
		}

		if ( $this->php_version_update ) {
			$this->php = '7.0';
			$this->printer_array = array_diff( $this->printer_array, array( 'php=5.6' ) );
		}

		$this->_insert_to_db(
			$this->site_name,
			$this->site_type_code,
			$this->site_type,
			$this->cache_type,
			$this->php,
			$this->letsencrypt,
			$this->mysql
		);

		$this->_create_directory();
	}

	/**
	 * Shows information of a particular site in a tabular form.
	 *
	 * Example: wp ee site info example.com
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function info( $_, $assoc_args ) {
		if ( empty( $_[0] ) ) {
			WP_CLI::error( 'Site name is empty!' );
			return;
		}

		if ( false === $this->_connect_to_db() ) {
			return;
		}

		$query = "SELECT * FROM ee.ee_site_data WHERE ee_site_data.site_name = '" . $_[0] . "'";

		if ( $result = $this->conn->query( $query ) ) {
			if ( 0 === $result->num_rows ) {
				WP_CLI::log( 'Table is empty.' );
			} else {
				$items[] = $result->fetch_assoc();
				$args = array(
					'ID',
					'site_name',
					'site_type',
					'cache_type',
					'php',
					'letsencrypt',
					'mysql',
				);
				WP_CLI\Utils\format_items( 'table', $items, $args );
			}
		}

		$this->conn->close();
	}

	/**
	 * Displays site configuration.
	 *
	 * Example: wp ee site show example.com
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function show( $_, $assoc_args ) {
		$site_data = array();

		if ( ! empty( $_[0] ) ) {
			$this->site_name = $_[0];
		} else {
			WP_CLI::error( 'Site name missing!' );
			return;
		}

		$path = getcwd();
		$file_to_read = $path . '/sites/' . $this->site_name;

		if ( ! file_exists( $file_to_read ) ) {
			WP_CLI::error( '"' . basename( $file_to_read ) . '" does not exist' );
			return;
		}

		$file = fopen( $file_to_read, 'r' );

		while( ! feof( $file ) ) {
			WP_CLI::line( trim( fgets( $file ) ) );
		}

		fclose($file);
	}

	/**
	 * Displays a list of domain names of all sites.
	 *
	 * Example: wp ee site list
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function list( $_, $assoc_args ) {
		if ( false === $this->_connect_to_db() ) {
			return;
		}

		$query = "SELECT site_name FROM ee.ee_site_data";

		if ( $result = $this->conn->query( $query ) ) {
			if ( 0 === $result->num_rows ) {
				WP_CLI::log( 'Database is empty' );
				return;
			}
			while ( $row = $result->fetch_assoc() ) {
				WP_CLI::line( WP_CLI::colorize( "%B" . $row['site_name'] . "%n " ) );
			}
		}

		$this->conn->close();
	}

	/**
	 * Deletes a site from the database.
	 *
	 * Example: wp ee site delete example.com
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function delete( $_, $assoc_args ) {
		if ( empty( $_[0] ) ) {
			WP_CLI::log( 'Site name field is empty' );
			return;
		} else {
			if ( false === $this->_connect_to_db() ) {
				return;
			}

			$site_name = $_[0];
			$query     = "SELECT site_name FROM ee.ee_site_data WHERE site_name LIKE '" . $site_name . "'";

			if ( $result = $this->conn->query( $query ) ) {
				if ( 0 === $result->num_rows ) {
					WP_CLI::error(  $site_name . ' not found in database!' );
				} else {
					if ( ! WP_CLI\Utils\get_flag_value( $assoc_args, 'noprompt' ) ) {
						WP_CLI::confirm( 'Are you sure you want to delete ' . $site_name . '?' );
					}
					$query = "DELETE FROM ee.ee_site_data WHERE site_name LIKE '" . $site_name . "'";
					$this->conn->query( $query );

					$path = getcwd();
					$path .= '/sites/' . $site_name;

					if ( ! file_exists( $path ) ) {
						WP_CLI::error( 'Configuration file not found for: ' . $site_name );
					}

					unlink( $path );

					WP_CLI::success( $site_name . ' deleted successfully' );
				}
			}
		}

		$this->conn->close();
	}

	/**
	 * Updates the specified website.
	 *
	 * Example:
	 * wp ee site update example.com --wpfc --letsencrypt
	 *
	 * @param array $_          Positional argument.
	 * @param array $assoc_args Associative argument.
	 *
	 * @when before_wp_load
	 */
	public function update( $_, $assoc_args ) {
		if ( empty( $_[0] ) ) {
			WP_CLI::error( 'Site name cannot be empty!' );
			return;
		}

		if ( empty( $assoc_args ) ) {
			WP_CLI::error( 'Update type missing.' );
			return;
		}

		$site_name   = $_[0];
		$site_type_new = array_keys( $assoc_args )[0];
		$site_type_old = '';
		$update_site_type = true;

		if ( 'letsencrypt' === array_keys( $assoc_args )[0] ) {
			$update_site_type = false;
		}

		if ( false === $this->_connect_to_db() ) {
			return;
		}

		$query = "SELECT site_name, site_type_code FROM ee.ee_site_data WHERE site_name LIKE '" . $site_name . "'";

		if ( $result = $this->conn->query( $query ) ) {
			if ( 0 === $result->num_rows ) {
				WP_CLI::error( $site_name . ' does not exist!' );
				return;
			}

			if ( true === $update_site_type ) {
				$site_type_old = $result->fetch_assoc()['site_type_code'];
				$site_type_new_index = array_search( $site_type_new, $this->site_update_argument );
				$can_update = $this->site_update_constraints[ $site_type_old ][ $site_type_new_index ];

				if ( false === $can_update ) {
					WP_CLI::error( 'Cannot update ' . $site_name . ' from ' . $site_type_old . ' to ' . $site_type_new . '. See this for more info - https://goo.gl/yKmGTX' );
					return;
				}

				$update_string = '';
				foreach ( $this->site_data[ $site_type_new ] as $key => $value ) {
					$update_string .= str_replace( '-', '_', $key ) . '="' . $value . '",';
				}
				$update_string .= 'site_type_code="' . $site_type_new . '"';
				if ( count( $assoc_args ) > 1 ) {
					$update_string .= ',letsencrypt="enabled"';
				}
				$query = "UPDATE ee.ee_site_data SET $update_string WHERE site_name LIKE '" . $site_name . "'";
			} else {
				$query = "UPDATE ee.ee_site_data SET letsencrypt='enabled' WHERE site_name LIKE '" . $site_name . "'";
			}

			$this->conn->query( $query );
			WP_CLI::success( 'Site successfully updated!' );
		}

		$this->conn->close();
	}

	/**
	 * Sets the path for directory which stores configuration files.
	 *
	 * @param string $path_link Link to the directory.
	 */
	public function _create_directory( $path_link = '' ) {
		if ( ! empty( $path_link ) ) {
			$path = $path_link;
		} else {
			$path = getcwd();
		}

		if ( ! $this->_is_writable( $path ) ) {
			WP_CLI::error( 'Directory is not writable!' );
			return;
		} else {
			$this->_write_to_file( $path . '/sites/', $this->site_name );
		}
	}

	/**
	 * Checks if a given directory is writable.
	 *
	 * @param string $file_or_directory Path to a directory or file.
	 */
	public function _is_writable( $file_or_directory ) {
		if ( ! is_writable( $file_or_directory ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Creates configuration file and writes to it.
	 *
	 * @param string $path     Path to a directory.
	 * @param string $file_name File name.
	 */
	public function _write_to_file( $path, $file_name ) {
		// Check if directory has write permissions.
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}

		// Open the file in write mode.
		$file = fopen( $path . $file_name , 'w+' );

		if ( false === $this->_connect_to_db() ) {
			return;
		}

		$query = "
					SELECT site_type, cache_type, php, letsencrypt, mysql
					FROM ee.ee_site_data
					WHERE site_name
					LIKE '" . $this->site_name . "'
				";

		if ( $result = $this->conn->query( $query ) ) {
			foreach ( $result->fetch_assoc() as $key => $value ) {
				if ( ! empty( $value ) ) {
					fwrite( $file, $key . '=' . $value . PHP_EOL );		
				}
			}
		}

		fclose( $file );
	}

	/**
	 * Inserts site data to database when create() is called.
	 *
	 * @param string $site_name Name of the site.
	 * @param string $site_type_code Code of the type of site. Eg `wpfc`, `wpredis`, etc.
	 * @param string $site_type Type of site. Eg `WordPress`, `PHP`, etc.
	 * @param string $cache_type Type of cache enabled.
	 * @param string $php Version of PHP.
	 * @param string $letsencrypt Enable letsencrypt.
	 * @param string $mysql Add database.
	 */
	public function _insert_to_db( $site_name, $site_type_code, $site_type, $cache_type, $php, $letsencrypt, $mysql ) {
		if ( false === $this->_connect_to_db() ) {
			return;
		}

		$query = "
					INSERT INTO ee.ee_site_data (ID, site_name, site_type_code, site_type, cache_type, php, letsencrypt, mysql)
					VALUES (NULL, '$site_name', '$site_type_code', '$site_type', '$cache_type', '$php', '$letsencrypt', '$mysql')
				";

		if ( true === $this->conn->query( $query ) ) {
			WP_CLI::success( 'New site created successfully!' );
		} else {
			if ( 1062 === $this->conn->errno ) {
				WP_CLI::log( $this->site_name . ' already exists!' );
			} else {
				WP_CLI::error( 'Error: ' . $query );
				WP_CLI::log( $this->conn->error );
			}
		}

		$this->conn->close();
	}

	/**
	 * Connects to database
	 */
	public function _connect_to_db() {
		$this->conn = new mysqli( $_ENV[ 'MYSQL_HOST' ], $_ENV[ 'MYSQL_USER' ], $_ENV[ 'MYSQL_PASSWORD' ] );

		if ( $this->conn->connect_error ) {
			WP_CLI::error( 'Connection failed: ' . $this->conn->connect_error );
			return false;
		} else {
			return true;
		}
	}
}

WP_CLI::add_command( 'ee site', 'EE_Command' );

<?php

class EE_Command extends WP_CLI_Command {
	public $site_name          = '';
	public $php_version_update = false;

	public $site_type          = '';
	public $cache_type         = '';
	public $php_version        = '';
	public $letsencrypt        = '';
	public $mysql              = '';
	public $php                = '';

	public $conn               = '';

	public $printer_array      = array();
	public $site_data          = array(
									'html' => array(
										'site-type' => 'html',
									),
									'mysql' => array(
										'site-type' => 'php',
										'mysql' => 'yes',
										'php' => '5.6',
									),
									'php' => array(
										'site-type' => 'php',
										'php' => '5.6',
									),
									'php7' => array(
										'php' => '7.0',
									),
									'wp' => array(
										'site-type' => 'WordPress',
										'mysql' => 'yes',
										'php' => '5.6',
									),
									'wpfc' => array(
										'site-type' => 'WordPress',
										'mysql' => 'yes',
										'php' => '5.6',
										'cache-type' => 'nginx fastcgi_cache',
									),
									'wpredis' => array(
										'site-type' => 'WordPress',
										'mysql' => 'yes',
										'php' => '5.6',
										'cache-type' => 'nginx redis_cache',
									),
									'letsencrypt' => array(
										'letsencrypt' => 'enabled',
									),
								);

	/**
	 * @when before_wp_load
	 */
	public function create( $_, $assoc_args ) {
		if ( empty( $_[0] ) ) {
			return;
		} else {
			$this->site_name = $_[0];
		}

		foreach ( $assoc_args as $key => $value ) {
			if ( 'php7' === $key ) {
				$this->php_version_update = true;
			}

			if ( ! array_key_exists( $key, $this->site_data ) ) {
				WP_CLI::error( 'Incorrect argument: ' . $key );
				return;
			} else {
				foreach ( $this->site_data[ $key ] as $context => $context_value ) {
					$this->printer_array[] = $context . '=' . $context_value;
					switch ( $context ) {
						case 'site-type':
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
			$this->site_type,
			$this->cache_type,
			$this->php,
			$this->letsencrypt,
			$this->mysql
		);

		$this->_create_directory();
	}

	/**
	 * @when before_wp_load
	 */
	public function info( $_, $assoc_args ) {
		if ( false === $this->_connect_to_db( 'localhost', 'root', 'AqRcKrLo' ) ) {
			return;
		}

		$query = "
					SELECT * FROM ee.ee_site_data
					WHERE ee_site_data.site_name = '" . $_[0] . "'
				";


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
					'php_version',
					'letsencrypt',
					'mysql'
				);
				WP_CLI\Utils\format_items( 'table', $items, $args );
			}
		}

		$this->conn->close();
	}

	/**
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
	 * @when before_wp_load
	 */
	public function list( $_, $assoc_args ) {
		if ( false === $this->_connect_to_db( 'localhost', 'root', 'AqRcKrLo' ) ) {
			return;
		}

		$query = "SELECT site_name FROM ee.ee_site_data";
		if ( $result = $this->conn->query( $query ) ) {
			while ( $row = $result->fetch_assoc() ) {
				WP_CLI::line( WP_CLI::colorize( "%B" . $row['site_name'] . ":%n " ) );
			}
		}

		$this->conn->close();
	}

	/**
	 * @when before_wp_load
	 */
	public function delete( $_, $assoc_args ) {
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'no-prompt' ) ) {
			
		}
	}

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

	public function _is_writable( $file_or_directory ) {
		if ( ! is_writable( $file_or_directory ) ) {
			return false;
		} else {
			return true;
		}
	}

	public function _write_to_file( $path, $file_name ) {
		// Check if directory has write permissions.
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}

		// Open the file in write mode.
		$file = fopen( $path . $file_name , 'w+' );

		// Write to file.
		foreach ( $this->printer_array as $value ) {
			fwrite( $file, $value . PHP_EOL );
		}

		fclose( $file );
	}

	public function _insert_to_db( $site_name, $site_type, $cache_type, $php_version, $letsencrypt, $mysql ) {
		if ( false === $this->_connect_to_db( 'localhost', 'root', 'AqRcKrLo' ) ) {
			return;
		}

		$query = "
					INSERT INTO ee.ee_site_data (ID, site_name, site_type, cache_type, php_version, letsencrypt, mysql)
					VALUES (NULL, '$site_name', '$site_type', '$cache_type', '$php_version', '$letsencrypt', '$mysql')
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

	public function _connect_to_db( $server_name, $username, $password ) {
		$this->conn = new mysqli( $server_name, $username, $password );

		if ( $this->conn->connect_error ) {
			WP_CLI::error( 'Connection failed: ' . $this->conn->connect_error );
			return false;
		} else {
			return true;
		}
	}
}

WP_CLI::add_command( 'ee site', 'EE_Command' );
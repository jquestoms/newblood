<?php

namespace Nexcess\MAPPS\Commands;

use Nexcess\MAPPS\Modules\Robots as Module;

/**
 * WP-CLI sub-commands for the StellarWP Plugin Installer.
 */
class Robots extends Command {

	/**
	 * @var \Nexcess\MAPPS\Modules\Robots
	 */
	protected $module;

	/**
	 * @param Module $module
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Subcommand for managing interactions with AI bots.
	 *
	 * ## OPTIONS
	 *
	 * [<action>...]
	 * : allow, block, or status
	 *
	 * ## EXAMPLES
	 *
	 * # Block known AI bots.
	 * $ wp nxmapps robots ai block
	 *
	 * # Allow AI bots.
	 * $ wp nxmapps robots ai allow
	 *
	 * # Status.
	 * $ wp nxmapps robots ai status
	 *
	 * @param mixed[] $args Positional arguments.
	 */
	public function ai( array $args ) {

		if ( empty( $args[0] ) ) {
			$this->warning( __( 'Please pass an action, or run `wp nxmapps robots ai --help` for available actions.', 'nexcess-mapps' ) );
		}

		switch ( $args[0] ) {
			case 'allow':
				$this->module->disableAiBlocklist();
				$this->success( __( 'AI bot blocking has been disabled. Bots can now access your website.', 'nexcess-mapps' ) );
				break;

			case 'block':
				$this->module->enableAiBlocklist();
				$this->success( __( 'AI bot blocking enabled. Incoming requests from known AI bots will be rejected.', 'nexcess-mapps' ) );
				break;

			case 'status':
			default:
				$enabled = get_option( 'nexcess_block_ai_bots', false );
				$message = $enabled ? __( 'Enabled', 'nexcess-mapps' ) : __( 'Disabled', 'nexcess-mapps' );
				$this->log( $message );
				break;
		}
	}
}

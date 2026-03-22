<?php

namespace Nexcess\MAPPS\Modules;

use Nexcess\MAPPS\Services\Options;
use Nexcess\MAPPS\SettingsPF;
use StellarWP\PluginFramework\Modules\Robots as RobotsModule;

class Robots extends RobotsModule {

	/**
	 * @var Options $options
	 */
	protected $options;

	/**
	 * @var SettingsPF $settings
	 */
	protected $settings;

	/**
	 * @param SettingsPF $settings
	 * @param Options    $options
	 */
	public function __construct( SettingsPF $settings, Options $options ) {
		$this->options  = $options;
		$this->settings = $settings;
	}

	/**
	 * Perform any necessary setup for the module.
	 */
	public function setup() {
		$this->registerOption();

		add_filter( 'stellarwp_robots_bots_blocklist', [ $this, 'aiBlockList' ] );

		parent::setup();
	}

	/**
	 * Add a toggle to the settings page.
	 */
	public function registerOption() {
		$this->options->addOption(
			'nexcess_block_ai_bots',
			'checkbox',
			__( 'Block AI Bots', 'nexcess-mapps' ),
			[
				'description' => __( 'Block known AI bots.', 'nexcess-mapps' ),
				'default'     => false,
			]
		);
	}

	/**
	 * Enable StellarWP Plugin Installer.
	 */
	public function enableAiBlocklist() {
		update_option( 'nexcess_block_ai_bots', true );
	}

	/**
	 * Disable StellarWP Plugin Installer.
	 */
	public function disableAiBlocklist() {
		update_option( 'nexcess_block_ai_bots', false );
	}

	/**
	 * Filter the blocklist to block known AI bots.
	 *
	 * @param array $bots Existing blocklist.
	 *
	 * @return array
	 */
	public function aiBlockList( $bots ) {

		if ( empty( get_option( 'nexcess_block_ai_bots' ) ) ) {
			return $bots;
		}

		$ai_bots = apply_filters( 'nexcess_ai_bots_blocklist', [
			'anthropic-ai',
			'Claude-Web',
			'CCbot',
			'FacebookBot',
			'Google-Extended',
			'GPTBot',
			'PiplBot',
			'ChatGPT-User',
			'PerplexityBot',
			'Bytespider',
			'Omgilibot',
			'Omgili',
			'ImagesiftBot',
			'BardBot',
			'KomoBot',
			'Meta-ExternalAgent',
			'Meta-ExternalFetcher',
			'Diffbot',
			'cohere-ai',
			'Timpibot',
			'Webzio-Extended',
			'YouBot',
			'AI2Bot',
			'AmazonBot',
			'Applebot-Extended',
			'ClaudeBot',
			'OAI-SearchBot',
			'PetalBot',
			'webzio',
		] );

		return array_merge( $bots, $ai_bots );
	}
}

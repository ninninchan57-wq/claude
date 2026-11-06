<?php
/**
 * Plugin Name: Aqua Rank（ウォーターサーバー比較ランキング for Cocoon）
 * Description: ウォーターサーバー比較サイト用。サーバー情報の一元管理、ランキング・比較表・タイプ診断・料金シミュレーターのショートコード、Product / ItemList / FAQPage 構造化データを追加します。SEOメタ・目次・パンくず・Article構造化データ・PR表記は Cocoon の機能を使う前提で、重複出力しません。
 * Version: 1.0.0
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * Text Domain: aqua-rank
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

define( 'AQUA_RANK_VERSION', '1.0.0' );
define( 'AQUA_RANK_DIR', plugin_dir_path( __FILE__ ) );
define( 'AQUA_RANK_URI', plugin_dir_url( __FILE__ ) );

require_once AQUA_RANK_DIR . 'inc/helpers.php';
require_once AQUA_RANK_DIR . 'inc/post-types.php';
require_once AQUA_RANK_DIR . 'inc/meta-boxes.php';
require_once AQUA_RANK_DIR . 'inc/schema.php';
require_once AQUA_RANK_DIR . 'inc/shortcodes.php';
require_once AQUA_RANK_DIR . 'inc/server-content.php';
require_once AQUA_RANK_DIR . 'inc/settings.php';
require_once AQUA_RANK_DIR . 'inc/sample-import.php';

register_activation_hook( __FILE__, 'aqua_rank_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Assets: Cocoon のスタイルの後に読み込む。JS は defer・jQuery 非依存。
 */
function aqua_rank_assets() {
	wp_enqueue_style( 'aqua-rank', AQUA_RANK_URI . 'assets/css/aqua-rank.css', array(), AQUA_RANK_VERSION );
	wp_enqueue_script(
		'aqua-rank',
		AQUA_RANK_URI . 'assets/js/aqua-rank.js',
		array(),
		AQUA_RANK_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'aqua_rank_assets', 20 );

/**
 * サーバー画像サイズ。
 */
function aqua_rank_image_sizes() {
	add_image_size( 'aqua-card', 480, 480, false );
}
add_action( 'after_setup_theme', 'aqua_rank_image_sizes' );

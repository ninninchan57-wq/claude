<?php
/**
 * Custom post type "server" and taxonomies.
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register CPT and taxonomies.
 */
function aqua_rank_register_types() {
	register_post_type(
		'server',
		array(
			'labels'       => array(
				'name'          => 'ウォーターサーバー',
				'singular_name' => 'ウォーターサーバー',
				'add_new_item'  => 'サーバーを追加',
				'edit_item'     => 'サーバーを編集',
				'all_items'     => 'サーバー一覧',
			),
			'public'       => true,
			// 一覧は固定ページ＋ショートコードで作る（Cocoonのアーカイブは比較表を持たないため）。
			'has_archive'  => false,
			'rewrite'      => array(
				'slug'       => 'server',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-filter',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
		)
	);

	$taxonomies = array(
		'server_type'      => array( 'タイプ（浄水型・天然水など）', 'type' ),
		'server_placement' => array( '設置（卓上・床置き）', 'placement' ),
		'server_purpose'   => array( '目的（一人暮らし・赤ちゃん等）', 'purpose' ),
	);
	foreach ( $taxonomies as $taxonomy => $conf ) {
		register_taxonomy(
			$taxonomy,
			'server',
			array(
				'label'             => $conf[0],
				'hierarchical'      => true,
				// 分類は絞り込み用。タクソノミーアーカイブは中身の薄い重複ページになるため公開しない。
				// 「卓上型 ウォーターサーバー」等のKWは固定ページ＋[aqua_ranking placement="takujo"]で狙う。
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'rewrite'            => false,
			)
		);
	}
}
add_action( 'init', 'aqua_rank_register_types' );

/**
 * プラグイン有効化時に基本タームを作成し、パーマリンクを更新。
 */
function aqua_rank_activate() {
	aqua_rank_register_types();

	$defaults = array(
		'server_type'      => array(
			'josui'    => '浄水型（水道水補充）',
			'tennen'   => '天然水',
			'ro'       => 'RO水',
			'chokketsu' => '水道直結型',
		),
		'server_placement' => array(
			'takujo'  => '卓上型',
			'yukaoki' => '床置き型',
		),
		'server_purpose'   => array(
			'hitorigurashi' => '一人暮らし',
			'baby'          => '赤ちゃん・ミルク',
			'yasui'         => '安い・コスパ',
			'oshare'        => 'おしゃれ',
			'office'        => 'オフィス・法人',
			'bousai'        => '防災・停電対策',
		),
	);
	foreach ( $defaults as $taxonomy => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			}
		}
	}
	flush_rewrite_rules();
}

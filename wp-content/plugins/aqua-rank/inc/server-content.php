<?php
/**
 * サーバー個別ページ（「〇〇 口コミ・評判」系KWの受け皿）。
 *
 * Cocoon の single テンプレートをそのまま使い、本文の前後にカード・スペック表・比較表を差し込む。
 * タイトル（h1）・パンくず・目次・著者情報・SNSボタンは Cocoon 側が出力する。
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap server content.
 *
 * @param string $content Content.
 * @return string
 */
function aqua_rank_server_content( $content ) {
	if ( ! is_singular( 'server' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$id = get_the_ID();

	ob_start();
	echo aqua_rank_pr_notice(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="ar-ranking ar-ranking--single">';
	aqua_rank_render_card( get_post( $id ), 0, 'h2' );
	echo '</div>';
	$before = ob_get_clean();

	$rows = array(
		'monthly_cost'    => '月額目安',
		'water_price'     => '水代',
		'rental_fee'      => 'サーバーレンタル料',
		'electricity'     => '電気代目安',
		'delivery_fee'    => '配送料',
		'contract_period' => '最低契約期間',
		'cancel_fee'      => '解約金',
		'min_order'       => '注文ノルマ',
		'bottle_position' => 'ボトル位置',
		'size'            => 'サイズ',
	);
	ob_start();
	printf( '<h2>%sの料金・スペック一覧</h2>', esc_html( get_the_title( $id ) ) );
	echo '<table class="ar-spec-table"><tbody>';
	foreach ( $rows as $key => $label ) {
		$v = aqua_rank_get( $key, $id );
		if ( '' === $v ) {
			continue;
		}
		if ( in_array( $key, array( 'monthly_cost', 'rental_fee', 'electricity' ), true ) ) {
			$v = aqua_rank_yen( $v );
		}
		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $v ) );
	}
	printf( '<tr><th scope="row">タイプ</th><td>%s</td></tr>', esc_html( aqua_rank_terms( $id, 'server_type' ) ) );
	printf( '<tr><th scope="row">設置</th><td>%s</td></tr>', esc_html( aqua_rank_terms( $id, 'server_placement' ) ) );
	echo '</tbody></table>';
	if ( aqua_rank_get( 'official_url', $id ) ) {
		printf(
			'<p class="ar-source">出典：<a href="%s" target="_blank" rel="noopener nofollow">公式サイト</a>%s</p>',
			esc_url( aqua_rank_get( 'official_url', $id ) ),
			aqua_rank_get( 'data_checked', $id ) ? esc_html( '（' . wp_date( 'Y年n月j日', strtotime( aqua_rank_get( 'data_checked', $id ) ) ) . '確認）' ) : ''
		);
	}
	$spec = ob_get_clean();

	ob_start();
	if ( false === strpos( $content, 'ar-faq' ) ) {
		$faq = aqua_rank_parse_faq( $id );
		if ( $faq ) {
			printf( '<h2>%sのよくある質問</h2>', esc_html( get_the_title( $id ) ) );
			aqua_rank_faq_collector( $faq );
			echo aqua_rank_render_faq( $faq ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
	$cta = aqua_rank_cta( $id, '公式サイトで最新キャンペーンを見る' );
	if ( $cta ) {
		printf( '<div class="ar-cta-block"><p class="ar-cta-block__lead">%sが気になったら公式サイトをチェック</p>%s</div>', esc_html( get_the_title( $id ) ), $cta ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<h2>他のウォーターサーバーと比較する</h2>';
	echo aqua_rank_sc_compare( array( 'filter' => '0', 'limit' => 6 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	$after = ob_get_clean();

	return $before . $spec . $content . $after;
}
// 優先度 9：Cocoon の目次（the_content 後段）より前に h2 を差し込み、目次に反映させる。
add_filter( 'the_content', 'aqua_rank_server_content', 9 );

/**
 * サーバーページ用 PR 表記。
 * Cocoon の「広告 → PR表記」は投稿・固定ページ向けのため、カスタム投稿のサーバーページにも明示する。
 *
 * @return string
 */
function aqua_rank_pr_notice() {
	if ( ! get_option( 'aqua_rank_pr_enabled', '1' ) ) {
		return '';
	}
	$text = get_option( 'aqua_rank_pr_text', '' );
	if ( ! $text ) {
		$text = '本ページはプロモーション（広告）を含みます。掲載順位・評価は編集部の独自基準に基づき決定しています。';
	}
	return '<p class="ar-pr-notice"><span class="ar-pr-notice__label">PR</span>' . esc_html( $text ) . '</p>';
}

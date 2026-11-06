<?php
/**
 * Field definitions and helper functions.
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * サーバー投稿のメタフィールド定義（管理画面・表示・構造化データで共通利用）。
 *
 * type: text | number | url | textarea | score | date
 *
 * @return array<string, array<string, string>>
 */
function aqua_rank_fields() {
	return array(
		'brand'           => array( 'label' => 'メーカー / ブランド名', 'type' => 'text', 'group' => 'basic' ),
		'catch'           => array( 'label' => 'キャッチコピー（一言で特徴）', 'type' => 'text', 'group' => 'basic' ),
		'rank'            => array( 'label' => '掲載順位（小さいほど上位）', 'type' => 'number', 'group' => 'basic' ),
		'monthly_cost'    => array( 'label' => '月額目安（円・税込／水代＋レンタル料）', 'type' => 'number', 'group' => 'cost' ),
		'water_price'     => array( 'label' => '水代（例：12Lあたり1,900円 / 定額）', 'type' => 'text', 'group' => 'cost' ),
		'rental_fee'      => array( 'label' => 'サーバーレンタル料（円/月）', 'type' => 'number', 'group' => 'cost' ),
		'electricity'     => array( 'label' => '電気代目安（円/月）', 'type' => 'number', 'group' => 'cost' ),
		'delivery_fee'    => array( 'label' => '配送料', 'type' => 'text', 'group' => 'cost' ),
		'contract_period' => array( 'label' => '最低契約期間', 'type' => 'text', 'group' => 'contract' ),
		'cancel_fee'      => array( 'label' => '解約金', 'type' => 'text', 'group' => 'contract' ),
		'min_order'       => array( 'label' => '注文ノルマ', 'type' => 'text', 'group' => 'contract' ),
		'bottle_position' => array( 'label' => 'ボトル位置（上置き/下置き/給水式/不要）', 'type' => 'text', 'group' => 'spec' ),
		'size'            => array( 'label' => 'サイズ（幅×奥行×高さ mm）', 'type' => 'text', 'group' => 'spec' ),
		'features'        => array( 'label' => '機能（カンマ区切り：チャイルドロック,自動クリーン,温度調整 など）', 'type' => 'text', 'group' => 'spec' ),
		'recommended_for' => array( 'label' => 'こんな人におすすめ（1行1項目）', 'type' => 'textarea', 'group' => 'review' ),
		'pros'            => array( 'label' => 'メリット（1行1項目）', 'type' => 'textarea', 'group' => 'review' ),
		'cons'            => array( 'label' => 'デメリット・注意点（1行1項目）', 'type' => 'textarea', 'group' => 'review' ),
		'verdict'         => array( 'label' => '編集部の総評（独自検証・体験を記載）', 'type' => 'textarea', 'group' => 'review' ),
		'score_cost'      => array( 'label' => '評価：料金の安さ（1〜5）', 'type' => 'score', 'group' => 'score' ),
		'score_ease'      => array( 'label' => '評価：ボトル交換・給水の楽さ（1〜5）', 'type' => 'score', 'group' => 'score' ),
		'score_taste'     => array( 'label' => '評価：水のおいしさ（1〜5）', 'type' => 'score', 'group' => 'score' ),
		'score_design'    => array( 'label' => '評価：デザイン・サイズ（1〜5）', 'type' => 'score', 'group' => 'score' ),
		'score_hygiene'   => array( 'label' => '評価：衛生・メンテナンス（1〜5）', 'type' => 'score', 'group' => 'score' ),
		'campaign'        => array( 'label' => 'キャンペーン（期限も記載）', 'type' => 'text', 'group' => 'link' ),
		'affiliate_url'   => array( 'label' => '申込みリンク（アフィリエイトURL）', 'type' => 'url', 'group' => 'link' ),
		'official_url'    => array( 'label' => '公式サイトURL（情報の出典）', 'type' => 'url', 'group' => 'link' ),
		'data_checked'    => array( 'label' => '情報確認日', 'type' => 'date', 'group' => 'link' ),
	);
}

/**
 * スコア項目の短いラベル。
 *
 * @return array<string, string>
 */
function aqua_rank_score_labels() {
	return array(
		'score_cost'    => '料金',
		'score_ease'    => '交換の楽さ',
		'score_taste'   => '水の味',
		'score_design'  => 'デザイン',
		'score_hygiene' => '衛生',
	);
}

/**
 * Get a server meta value.
 *
 * @param string   $key     Field key without prefix.
 * @param int|null $post_id Post ID.
 * @return string
 */
function aqua_rank_get( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return (string) get_post_meta( $post_id, '_ws_' . $key, true );
}

/**
 * Split a textarea / comma field into a list.
 *
 * @param string   $key     Field key.
 * @param int|null $post_id Post ID.
 * @param string   $sep     "line" or "comma".
 * @return string[]
 */
function aqua_rank_list( $key, $post_id = null, $sep = 'line' ) {
	$raw   = aqua_rank_get( $key, $post_id );
	$parts = 'comma' === $sep ? preg_split( '/[,、，]/u', $raw ) : preg_split( '/\r\n|\r|\n/', $raw );
	return array_values( array_filter( array_map( 'trim', (array) $parts ) ) );
}

/**
 * 総合評価（各スコアの平均・小数1桁）。未入力なら 0。
 *
 * @param int|null $post_id Post ID.
 * @return float
 */
function aqua_rank_overall( $post_id = null ) {
	$scores = array();
	foreach ( array_keys( aqua_rank_score_labels() ) as $key ) {
		$v = (float) aqua_rank_get( $key, $post_id );
		if ( $v > 0 ) {
			$scores[] = min( 5, $v );
		}
	}
	return $scores ? round( array_sum( $scores ) / count( $scores ), 1 ) : 0.0;
}

/**
 * Star rating HTML.
 *
 * @param float $score 0-5.
 * @return string
 */
function aqua_rank_stars( $score ) {
	$pct = max( 0, min( 100, $score / 5 * 100 ) );
	return sprintf(
		'<span class="ar-stars" role="img" aria-label="5点中%1$s点"><span class="ar-stars__fill" style="width:%2$s%%"></span></span><span class="ar-stars__num">%1$s</span>',
		esc_html( number_format( $score, 1 ) ),
		esc_attr( (string) $pct )
	);
}

/**
 * 円表記。
 *
 * @param string|int $value Number.
 * @return string
 */
function aqua_rank_yen( $value ) {
	if ( '' === $value || null === $value ) {
		return '—';
	}
	return number_format( (int) $value ) . '円';
}

/**
 * 申込みリンク（rel="sponsored" を付与）。
 *
 * @param int|null $post_id Post ID.
 * @param string   $label   Button label.
 * @param string   $class   Extra class.
 * @return string
 */
function aqua_rank_cta( $post_id = null, $label = '公式サイトを見る', $class = '' ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$url     = aqua_rank_get( 'affiliate_url', $post_id );
	if ( ! $url ) {
		$url = aqua_rank_get( 'official_url', $post_id );
	}
	if ( ! $url ) {
		return '';
	}
	return sprintf(
		'<a class="ar-btn ar-btn--cta %3$s" href="%1$s" target="_blank" rel="sponsored noopener">%2$s</a>',
		esc_url( $url ),
		esc_html( $label ),
		esc_attr( $class )
	);
}

/**
 * ランキング順のサーバー一覧を取得。
 *
 * @param array $args Extra WP_Query args (type, placement, purpose, limit).
 * @return WP_Post[]
 */
function aqua_rank_get_servers( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'     => -1,
			'type'      => '',
			'placement' => '',
			'purpose'   => '',
		)
	);

	$query = array(
		'post_type'      => 'server',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'meta_key'       => '_ws_rank', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => array(
			'meta_value_num' => 'ASC',
			'title'          => 'ASC',
		),
		'no_found_rows'  => true,
	);

	$tax = array();
	foreach ( array(
		'type'      => 'server_type',
		'placement' => 'server_placement',
		'purpose'   => 'server_purpose',
	) as $arg => $taxonomy ) {
		if ( $args[ $arg ] ) {
			$tax[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $args[ $arg ] ) ),
			);
		}
	}
	if ( $tax ) {
		$query['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
	}

	return get_posts( $query );
}

/**
 * Term names as a string.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return string
 */
function aqua_rank_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return implode( ' / ', wp_list_pluck( $terms, 'name' ) );
}

/**
 * Term slugs (space-separated) for data attributes.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return string
 */
function aqua_rank_term_slugs( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return implode( ' ', wp_list_pluck( $terms, 'slug' ) );
}

/**
 * Render one server card.
 *
 * @param WP_Post $server  Server post.
 * @param int     $pos     Rank position (0 = no rank badge).
 * @param string  $heading Heading tag.
 */
function aqua_rank_render_card( $server, $pos = 0, $heading = 'h3' ) {
	$heading = in_array( $heading, array( 'h2', 'h3', 'h4' ), true ) ? $heading : 'h3';
	include AQUA_RANK_DIR . 'templates/server-card.php';
}

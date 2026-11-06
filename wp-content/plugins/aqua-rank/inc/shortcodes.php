<?php
/**
 * Shortcodes.
 *
 * [aqua_ranking limit="10" type="josui" placement="takujo" purpose="baby" heading="h3"]
 * [aqua_compare type="" placement="" purpose="" filter="1"]
 * [aqua_server id="123"]
 * [aqua_diagnosis]
 * [aqua_cost_sim]
 * [aqua_faq] Q:質問 / A:回答 [/aqua_faq]
 * [aqua_faq_list]   … 投稿編集画面の「よくある質問」欄の内容を表示
 * [aqua_updated]
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ranking cards.
 *
 * @param array $atts Attributes.
 * @return string
 */
function aqua_rank_sc_ranking( $atts ) {
	$atts    = shortcode_atts(
		array(
			'limit'     => 10,
			'type'      => '',
			'placement' => '',
			'purpose'   => '',
			'heading'   => 'h3',
		),
		$atts,
		'aqua_ranking'
	);
	$servers = aqua_rank_get_servers( $atts );
	if ( ! $servers ) {
		return current_user_can( 'edit_posts' ) ? '<p class="ar-notice">該当するサーバーが登録されていません（管理画面「ウォーターサーバー」から追加）。</p>' : '';
	}
	aqua_rank_itemlist_collector( wp_list_pluck( $servers, 'ID' ) );

	$heading = in_array( $atts['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $atts['heading'] : 'h3';
	ob_start();
	echo '<ol class="ar-ranking">';
	foreach ( $servers as $i => $server ) {
		aqua_rank_render_card( $server, $i + 1, $heading );
	}
	echo '</ol>';
	return ob_get_clean();
}
add_shortcode( 'aqua_ranking', 'aqua_rank_sc_ranking' );

/**
 * Single server card.
 *
 * @param array $atts Attributes.
 * @return string
 */
function aqua_rank_sc_server( $atts ) {
	$atts   = shortcode_atts(
		array(
			'id'      => 0,
			'heading' => 'h3',
		),
		$atts,
		'aqua_server'
	);
	$server = get_post( (int) $atts['id'] );
	if ( ! $server || 'server' !== $server->post_type ) {
		return '';
	}
	ob_start();
	echo '<div class="ar-ranking ar-ranking--single">';
	aqua_rank_render_card( $server, 0, $atts['heading'] );
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'aqua_server', 'aqua_rank_sc_server' );

/**
 * Comparison table with client-side filter & sort.
 *
 * @param array $atts Attributes.
 * @return string
 */
function aqua_rank_sc_compare( $atts ) {
	$atts    = shortcode_atts(
		array(
			'type'      => '',
			'placement' => '',
			'purpose'   => '',
			'filter'    => '1',
			'limit'     => -1,
		),
		$atts,
		'aqua_compare'
	);
	$servers = aqua_rank_get_servers( $atts );
	if ( ! $servers ) {
		return '';
	}

	ob_start();
	?>
	<div class="ar-compare" data-compare>
		<?php if ( '1' === $atts['filter'] ) : ?>
		<div class="ar-compare__filters" role="group" aria-label="比較表の絞り込み">
			<button type="button" class="ar-chip is-active" data-filter="">すべて</button>
			<?php
			foreach ( array( 'server_type', 'server_placement' ) as $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
					)
				);
				if ( is_wp_error( $terms ) ) {
					continue;
				}
				foreach ( $terms as $term ) {
					printf( '<button type="button" class="ar-chip" data-filter="%s">%s</button>', esc_attr( $term->slug ), esc_html( $term->name ) );
				}
			}
			?>
		</div>
		<p class="ar-compare__hint">見出しをタップすると並べ替えできます。表は横にスクロールできます。</p>
		<?php endif; ?>
		<div class="ar-compare__scroll" tabindex="0" role="region" aria-label="ウォーターサーバー比較表">
		<table class="ar-compare__table">
			<thead>
				<tr>
					<th scope="col" class="ar-is-sticky">サーバー</th>
					<th scope="col" data-sort="num">総合評価</th>
					<th scope="col" data-sort="num">月額目安</th>
					<th scope="col">タイプ</th>
					<th scope="col">設置</th>
					<th scope="col">水代</th>
					<th scope="col" data-sort="num">レンタル料</th>
					<th scope="col" data-sort="num">電気代</th>
					<th scope="col">ボトル</th>
					<th scope="col">契約期間</th>
					<th scope="col">解約金</th>
					<th scope="col">ノルマ</th>
					<th scope="col">申込み</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $servers as $server ) :
				$id    = $server->ID;
				$slugs = trim( aqua_rank_term_slugs( $id, 'server_type' ) . ' ' . aqua_rank_term_slugs( $id, 'server_placement' ) );
				$score = aqua_rank_overall( $id );
				?>
				<tr data-terms="<?php echo esc_attr( $slugs ); ?>">
					<th scope="row" class="ar-is-sticky">
						<a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
							<?php
							if ( has_post_thumbnail( $id ) ) {
								echo get_the_post_thumbnail( $id, 'thumbnail', array( 'loading' => 'lazy', 'class' => 'ar-compare__thumb' ) );
							}
							?>
							<span><?php echo esc_html( get_the_title( $id ) ); ?></span>
						</a>
					</th>
					<td data-value="<?php echo esc_attr( (string) $score ); ?>"><?php echo $score ? aqua_rank_stars( $score ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td data-value="<?php echo esc_attr( aqua_rank_get( 'monthly_cost', $id ) ); ?>" class="ar-is-strong"><?php echo esc_html( aqua_rank_yen( aqua_rank_get( 'monthly_cost', $id ) ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_terms( $id, 'server_type' ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_terms( $id, 'server_placement' ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_get( 'water_price', $id ) ); ?></td>
					<td data-value="<?php echo esc_attr( aqua_rank_get( 'rental_fee', $id ) ); ?>"><?php echo esc_html( aqua_rank_yen( aqua_rank_get( 'rental_fee', $id ) ) ); ?></td>
					<td data-value="<?php echo esc_attr( aqua_rank_get( 'electricity', $id ) ); ?>"><?php echo esc_html( aqua_rank_yen( aqua_rank_get( 'electricity', $id ) ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_get( 'bottle_position', $id ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_get( 'contract_period', $id ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_get( 'cancel_fee', $id ) ); ?></td>
					<td><?php echo esc_html( aqua_rank_get( 'min_order', $id ) ); ?></td>
					<td><?php echo aqua_rank_cta( $id, '公式へ', 'btn--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<p class="ar-compare__note">※料金は税込の目安です。最新の料金・キャンペーンは必ず各公式サイトでご確認ください。</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'aqua_compare', 'aqua_rank_sc_compare' );

/**
 * タイプ診断（3問）。回答に合うサーバー上位3件をその場で表示する。
 *
 * @return string
 */
function aqua_rank_sc_diagnosis() {
	$data = array();
	foreach ( aqua_rank_get_servers() as $server ) {
		$id     = $server->ID;
		$url    = aqua_rank_get( 'affiliate_url', $id ) ? aqua_rank_get( 'affiliate_url', $id ) : aqua_rank_get( 'official_url', $id );
		$data[] = array(
			'title'     => get_the_title( $id ),
			'url'       => get_permalink( $id ),
			'cta'       => $url,
			'type'      => aqua_rank_term_slugs( $id, 'server_type' ),
			'placement' => aqua_rank_term_slugs( $id, 'server_placement' ),
			'cost'      => aqua_rank_yen( aqua_rank_get( 'monthly_cost', $id ) ),
			'score'     => aqua_rank_overall( $id ),
		);
	}
	ob_start();
	?>
	<div class="ar-diagnosis" data-diagnosis>
		<p class="ar-diagnosis__title">10秒でわかる！あなたに合うウォーターサーバー診断</p>
		<fieldset class="ar-diagnosis__q">
			<legend>Q1. 一番重視するのは？</legend>
			<label><input type="radio" name="dq1" value="josui"> 料金の安さ・飲み放題</label>
			<label><input type="radio" name="dq1" value="tennen"> 水の味・天然水の品質</label>
		</fieldset>
		<fieldset class="ar-diagnosis__q">
			<legend>Q2. 重いボトルの交換や受け取りは？</legend>
			<label><input type="radio" name="dq2" value="josui"> できれば避けたい</label>
			<label><input type="radio" name="dq2" value="tennen"> 気にならない（宅配が便利）</label>
		</fieldset>
		<fieldset class="ar-diagnosis__q">
			<legend>Q3. 置き場所は？</legend>
			<label><input type="radio" name="dq3" value="takujo"> キッチンカウンターなど狭め</label>
			<label><input type="radio" name="dq3" value="yukaoki"> 床に専用スペースを確保できる</label>
		</fieldset>
		<button type="button" class="ar-btn ar-btn--primary" data-diagnosis-run>診断する</button>
		<div class="ar-diagnosis__result" aria-live="polite" hidden></div>
		<script type="application/json" data-diagnosis-data><?php echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'aqua_diagnosis', 'aqua_rank_sc_diagnosis' );

/**
 * 年間コストシミュレーター（ペットボトル / 宅配水 / 浄水型）。単価は利用者が変更可能。
 *
 * @param array $atts Attributes (defaults for unit prices).
 * @return string
 */
function aqua_rank_sc_cost_sim( $atts ) {
	$atts = shortcode_atts(
		array(
			'pet'    => 100,  // 2Lペットボトル1本の価格.
			'bottle' => 1900, // 宅配水12Lの価格.
			'rental' => 0,    // 宅配水のレンタル料/月.
			'josui'  => 3300, // 浄水型の定額/月.
			'elec'   => 700,  // 電気代/月.
		),
		$atts,
		'aqua_cost_sim'
	);
	ob_start();
	?>
	<div class="ar-costsim" data-costsim>
		<p class="ar-costsim__title">年間コストシミュレーター</p>
		<div class="ar-costsim__inputs">
			<label>1日に使う水の量（L）<input type="number" min="0.5" max="20" step="0.5" value="2" data-k="liters"></label>
			<label>2Lペットボトル1本（円）<input type="number" min="0" step="10" value="<?php echo esc_attr( $atts['pet'] ); ?>" data-k="pet"></label>
			<label>宅配水 12L（円）<input type="number" min="0" step="50" value="<?php echo esc_attr( $atts['bottle'] ); ?>" data-k="bottle"></label>
			<label>宅配水のレンタル料（円/月）<input type="number" min="0" step="50" value="<?php echo esc_attr( $atts['rental'] ); ?>" data-k="rental"></label>
			<label>浄水型の定額（円/月）<input type="number" min="0" step="50" value="<?php echo esc_attr( $atts['josui'] ); ?>" data-k="josui"></label>
			<label>サーバー電気代（円/月）<input type="number" min="0" step="50" value="<?php echo esc_attr( $atts['elec'] ); ?>" data-k="elec"></label>
		</div>
		<table class="ar-costsim__result">
			<thead><tr><th scope="col">方法</th><th scope="col">月額</th><th scope="col">年額</th></tr></thead>
			<tbody>
				<tr data-row="pet"><th scope="row">ペットボトル</th><td></td><td></td></tr>
				<tr data-row="bottle"><th scope="row">宅配水サーバー</th><td></td><td></td></tr>
				<tr data-row="josui"><th scope="row">浄水型サーバー</th><td></td><td></td></tr>
			</tbody>
		</table>
		<p class="ar-costsim__note">※水道代は1Lあたり約0.2円で計算（浄水型）。数値は目安です。</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'aqua_cost_sim', 'aqua_rank_sc_cost_sim' );

/**
 * FAQ accordion + FAQPage schema.
 *
 * @param array  $atts    Attributes.
 * @param string $content Q:/A: lines.
 * @return string
 */
function aqua_rank_sc_faq( $atts, $content = '' ) {
	$text  = str_replace( array( '<br />', '<br>', '</p>', '<p>' ), array( "\n", "\n", "\n", '' ), (string) $content );
	$items = aqua_rank_parse_faq_text( wp_strip_all_tags( $text ) );
	if ( ! $items ) {
		return '';
	}
	aqua_rank_faq_collector( $items );
	return aqua_rank_render_faq( $items );
}
add_shortcode( 'aqua_faq', 'aqua_rank_sc_faq' );

/**
 * Render FAQ items.
 *
 * @param array $items Q/A items.
 * @return string
 */
function aqua_rank_render_faq( $items ) {
	$html = '<div class="ar-faq">';
	foreach ( $items as $item ) {
		$html .= sprintf(
			'<details class="ar-faq__item"><summary class="ar-faq__q">%s</summary><div class="ar-faq__a">%s</div></details>',
			esc_html( $item['q'] ),
			wpautop( esc_html( $item['a'] ) )
		);
	}
	return $html . '</div>';
}

/**
 * 投稿編集画面の FAQ 欄を表示（FAQPage 構造化データ付き）。
 *
 * @return string
 */
function aqua_rank_sc_faq_list() {
	$items = aqua_rank_parse_faq();
	if ( ! $items ) {
		return '';
	}
	aqua_rank_faq_collector( $items );
	return aqua_rank_render_faq( $items );
}
add_shortcode( 'aqua_faq_list', 'aqua_rank_sc_faq_list' );

/**
 * Last updated date.
 *
 * @return string
 */
function aqua_rank_sc_updated() {
	return '<time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . esc_html( get_the_modified_date( 'Y年n月j日' ) ) . '</time>';
}
add_shortcode( 'aqua_updated', 'aqua_rank_sc_updated' );

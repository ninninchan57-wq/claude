<?php
/**
 * Settings page（設定 → Aqua Rank）and sticky CTA.
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option definitions.
 *
 * @return array<string, array{0:string,1:string,2:string}>
 */
function aqua_rank_option_defs() {
	return array(
		'aqua_rank_pr_enabled' => array( 'サーバー個別ページにPR表記を表示（ステマ規制対応・原則ON）', 'checkbox', '1' ),
		'aqua_rank_pr_text'    => array( 'PR表記の文言', 'text', '本ページはプロモーション（広告）を含みます。掲載順位・評価は編集部の独自基準に基づき決定しています。' ),
		'aqua_rank_schema'     => array( '構造化データ（Product / ItemList / FAQPage）を出力', 'checkbox', '1' ),
		'aqua_rank_sticky_cta' => array( 'スマホ下部に1位サーバーの固定ボタンを表示（Cocoonの「モバイルフッターメニュー」と併用時は表示位置が上にずれます）', 'checkbox', '' ),
	);
}

/**
 * Register settings.
 */
function aqua_rank_register_settings() {
	foreach ( aqua_rank_option_defs() as $name => $def ) {
		register_setting(
			'aqua_rank',
			$name,
			array(
				'type'              => 'string',
				'default'           => $def[2],
				'sanitize_callback' => 'checkbox' === $def[1] ? static fn( $v ) => $v ? '1' : '' : 'sanitize_text_field',
			)
		);
	}
}
add_action( 'admin_init', 'aqua_rank_register_settings' );

/**
 * Menu.
 */
function aqua_rank_settings_menu() {
	add_options_page( 'Aqua Rank 設定', 'Aqua Rank', 'manage_options', 'aqua-rank', 'aqua_rank_settings_page' );
}
add_action( 'admin_menu', 'aqua_rank_settings_menu' );

/**
 * Settings page.
 */
function aqua_rank_settings_page() {
	?>
	<div class="wrap">
		<h1>Aqua Rank 設定</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'aqua_rank' ); ?>
			<table class="form-table">
				<?php foreach ( aqua_rank_option_defs() as $name => $def ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
						<td>
							<?php if ( 'checkbox' === $def[1] ) : ?>
								<input type="checkbox" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( get_option( $name, $def[2] ), '1' ); ?>>
							<?php else : ?>
								<input type="text" class="large-text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( get_option( $name, $def[2] ) ); ?>">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>ショートコード一覧</h2>
		<table class="widefat striped">
			<tbody>
				<tr><td><code>[aqua_ranking limit="10"]</code></td><td>総合ランキング（ItemList構造化データ付き）。<code>type</code>（josui/tennen/ro/chokketsu）、<code>placement</code>（takujo/yukaoki）、<code>purpose</code>（hitorigurashi/baby/yasui/oshare/office/bousai）で絞り込み、<code>heading="h4"</code>で見出しレベル変更</td></tr>
				<tr><td><code>[aqua_compare]</code></td><td>比較表（絞り込み・並べ替え）。ranking と同じ絞り込み属性、<code>filter="0"</code>でボタン非表示</td></tr>
				<tr><td><code>[aqua_server id="123"]</code></td><td>サーバー1件のカード（記事内での紹介用）</td></tr>
				<tr><td><code>[aqua_diagnosis]</code></td><td>3問のタイプ診断（結果に合うサーバーを表示）</td></tr>
				<tr><td><code>[aqua_cost_sim]</code></td><td>ペットボトル／宅配水／浄水型の年間コスト比較</td></tr>
				<tr><td><code>[aqua_faq_list]</code></td><td>編集画面の「よくある質問」欄を表示（FAQPage構造化データ付き）</td></tr>
				<tr><td><code>[aqua_faq]Q:…<br>A:…[/aqua_faq]</code></td><td>本文中に直接書くFAQ</td></tr>
				<tr><td><code>[aqua_updated]</code></td><td>最終更新日</td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * スマホ固定CTA（トップページと投稿のみ）。
 */
function aqua_rank_sticky_cta() {
	if ( ! get_option( 'aqua_rank_sticky_cta', '' ) || ! ( is_front_page() || is_singular( 'post' ) ) ) {
		return;
	}
	$top = aqua_rank_get_servers( array( 'limit' => 1 ) );
	if ( ! $top ) {
		return;
	}
	$cta = aqua_rank_cta( $top[0]->ID, '1位 ' . get_the_title( $top[0]->ID ) . 'を見る' );
	if ( $cta ) {
		echo '<div class="ar-sticky-cta" data-sticky-cta hidden>' . $cta . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_footer', 'aqua_rank_sticky_cta', 5 );

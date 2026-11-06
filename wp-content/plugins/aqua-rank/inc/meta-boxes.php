<?php
/**
 * Admin meta boxes: server specs and FAQ (SEOタイトル等は Cocoon の投稿設定を使用).
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register meta boxes.
 */
function aqua_rank_add_meta_boxes() {
	add_meta_box( 'aqua_rank_server', 'サーバー情報（比較表・ランキング・構造化データに使用）', 'aqua_rank_server_box', 'server', 'normal', 'high' );
	foreach ( array( 'post', 'page', 'server' ) as $type ) {
		add_meta_box( 'aqua_rank_seo', 'よくある質問（FAQPage構造化データ）', 'aqua_rank_seo_box', $type, 'normal', 'default' );
	}
}
add_action( 'add_meta_boxes', 'aqua_rank_add_meta_boxes' );

/**
 * Server spec box.
 *
 * @param WP_Post $post Post.
 */
function aqua_rank_server_box( $post ) {
	// nonce は同じ画面に表示される FAQ ボックス側で出力する。
	$groups = array(
		'basic'    => '基本情報',
		'cost'     => '料金',
		'contract' => '契約条件',
		'spec'     => 'スペック',
		'review'   => 'レビュー（独自情報＝E-E-A-Tの核。必ず自分で検証した内容を）',
		'score'    => '評価スコア',
		'link'     => 'リンク・出典',
	);
	$fields = aqua_rank_fields();
	echo '<style>.ar-meta h4{margin:1.5em 0 .5em;border-bottom:1px solid #ddd}.ar-meta label{display:block;font-weight:600;margin-top:.8em}.ar-meta input[type=text],.ar-meta input[type=url],.ar-meta textarea{width:100%}</style><div class="ar-meta">';
	foreach ( $groups as $group => $title ) {
		printf( '<h4>%s</h4>', esc_html( $title ) );
		foreach ( $fields as $key => $f ) {
			if ( $f['group'] !== $group ) {
				continue;
			}
			$id    = 'ws_' . $key;
			$value = get_post_meta( $post->ID, '_ws_' . $key, true );
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $f['label'] ) );
			switch ( $f['type'] ) {
				case 'textarea':
					printf( '<textarea id="%1$s" name="%1$s" rows="4">%2$s</textarea>', esc_attr( $id ), esc_textarea( $value ) );
					break;
				case 'number':
					printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" min="0" step="1">', esc_attr( $id ), esc_attr( $value ) );
					break;
				case 'score':
					printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" min="0" max="5" step="0.1">', esc_attr( $id ), esc_attr( $value ) );
					break;
				case 'url':
					printf( '<input type="url" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ) );
					break;
				case 'date':
					printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ) );
					break;
				default:
					printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ) );
			}
		}
	}
	echo '</div>';
}

/**
 * FAQ box. SEOタイトル・ディスクリプション・noindex は Cocoon の「SEO設定」欄を使う。
 *
 * @param WP_Post $post Post.
 */
function aqua_rank_seo_box( $post ) {
	wp_nonce_field( 'aqua_rank_save', 'aqua_rank_nonce' );
	$faq = get_post_meta( $post->ID, '_ar_faq', true );
	?>
	<p>1行目「Q:質問」、2行目「A:回答」の形式で、空行区切りで繰り返し入力。<br>
	本文に <code>[aqua_faq_list]</code> を置いた位置に表示され、FAQPage 構造化データが出力されます。</p>
	<textarea name="ar_faq" rows="8" style="width:100%" placeholder="Q:ウォーターサーバーの電気代はいくら？&#10;A:一般的に月500〜1,000円程度です。&#10;&#10;Q:..."><?php echo esc_textarea( $faq ); ?></textarea>
	<?php
}

/**
 * Save meta.
 *
 * @param int $post_id Post ID.
 */
function aqua_rank_save_meta( $post_id ) {
	if ( ! isset( $_POST['aqua_rank_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aqua_rank_nonce'] ) ), 'aqua_rank_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( 'server' === get_post_type( $post_id ) ) {
		foreach ( aqua_rank_fields() as $key => $f ) {
			$name = 'ws_' . $key;
			if ( ! isset( $_POST[ $name ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			switch ( $f['type'] ) {
				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;
				case 'number':
					$value = '' === $raw ? '' : (string) absint( $raw );
					break;
				case 'score':
					$value = '' === $raw ? '' : (string) min( 5, max( 0, round( (float) $raw, 1 ) ) );
					break;
				case 'url':
					$value = esc_url_raw( $raw );
					break;
				default:
					$value = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, '_ws_' . $key, $value );
		}
		// 掲載順が空だと meta_key ソートで一覧から漏れるため既定値を入れる。
		if ( '' === get_post_meta( $post_id, '_ws_rank', true ) ) {
			update_post_meta( $post_id, '_ws_rank', '99' );
		}
	}

	if ( isset( $_POST['ar_faq'] ) ) {
		update_post_meta( $post_id, '_ar_faq', sanitize_textarea_field( wp_unslash( $_POST['ar_faq'] ) ) );
	}
}
add_action( 'save_post', 'aqua_rank_save_meta' );

/**
 * Parse FAQ textarea into Q/A pairs.
 *
 * @param int|null $post_id Post ID.
 * @return array<int, array{q:string,a:string}>
 */
function aqua_rank_parse_faq( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$raw     = (string) get_post_meta( $post_id, '_ar_faq', true );
	return aqua_rank_parse_faq_text( $raw );
}

/**
 * Parse "Q:..\nA:.." blocks.
 *
 * @param string $raw Text.
 * @return array<int, array{q:string,a:string}>
 */
function aqua_rank_parse_faq_text( $raw ) {
	$items = array();
	$q     = '';
	$a     = '';
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( preg_match( '/^Q[:：]\s*(.+)$/u', $line, $m ) ) {
			if ( $q && $a ) {
				$items[] = array( 'q' => $q, 'a' => trim( $a ) );
			}
			$q = $m[1];
			$a = '';
		} elseif ( preg_match( '/^A[:：]\s*(.+)$/u', $line, $m ) ) {
			$a = $m[1];
		} elseif ( '' !== $line && $q && '' !== $a ) {
			$a .= "\n" . $line;
		}
	}
	if ( $q && $a ) {
		$items[] = array( 'q' => $q, 'a' => trim( $a ) );
	}
	return $items;
}

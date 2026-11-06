<?php
/**
 * 初期セットアップ：サンプルサーバーとトップページ構成を一括作成する管理画面ツール。
 *
 * 管理画面「ウォーターサーバー → 初期設定」から実行。
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add admin page.
 */
function aqua_rank_setup_menu() {
	add_submenu_page( 'edit.php?post_type=server', 'Aqua Rank 初期設定', '初期設定', 'manage_options', 'aqua-rank-setup', 'aqua_rank_setup_page' );
}
add_action( 'admin_menu', 'aqua_rank_setup_menu' );

/**
 * Admin page.
 */
function aqua_rank_setup_page() {
	$done = '';
	if ( isset( $_POST['aqua_rank_setup'] ) && check_admin_referer( 'aqua_rank_setup' ) && current_user_can( 'manage_options' ) ) {
		aqua_rank_activate();
		$count = aqua_rank_import_servers();
		$page  = aqua_rank_create_front_page();
		aqua_rank_create_guide_pages();
		aqua_rank_create_landing_pages();
		$done = sprintf( 'サンプルサーバーを %d 件作成し、トップページ（ID:%d）を設定しました。', $count, $page );
	}
	?>
	<div class="wrap">
		<h1>Aqua Rank 初期設定</h1>
		<?php if ( $done ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $done ); ?></p></div>
		<?php endif; ?>
		<p>以下を自動作成します（何度実行しても同名のものは重複作成されません）。</p>
		<ol>
			<li>サーバーのタイプ・設置・目的タクソノミーの基本ターム</li>
			<li>サンプルのウォーターサーバー5件（<strong>架空データ</strong>です。必ず実在サービスの正確な情報に置き換えてください）</li>
			<li>診断・ランキング・比較表・選び方・FAQ を含むトップページ（固定ページ「ホーム」）と表示設定</li>
			<li>目的別の固定ページ（卓上型／浄水型／天然水／安い／赤ちゃん／一人暮らし）＝ロングテールKWの受け皿</li>
			<li>運営者情報 / ランキング評価基準 / プライバシーポリシー / お問い合わせ の固定ページ（E-E-A-T・信頼性用）</li>
		</ol>
		<form method="post">
			<?php wp_nonce_field( 'aqua_rank_setup' ); ?>
			<p><button class="button button-primary" name="aqua_rank_setup" value="1">初期設定を実行</button></p>
		</form>
	</div>
	<?php
}

/**
 * Import sample servers from JSON.
 *
 * @return int Created count.
 */
function aqua_rank_import_servers() {
	$file = AQUA_RANK_DIR . '/sample-data/servers.json';
	if ( ! file_exists( $file ) ) {
		return 0;
	}
	$data  = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$count = 0;
	foreach ( (array) $data as $row ) {
		$exists = get_posts(
			array(
				'post_type'   => 'server',
				'title'       => $row['title'],
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $exists ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'server',
				'post_status'  => 'publish',
				'post_title'   => $row['title'],
				'post_excerpt' => $row['meta']['catch'] ?? '',
				'post_content' => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $row['title'] ) . "を実際に使ってわかったこと</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>ここに独自の検証内容（設置の様子の写真、水温の計測結果、1か月の実費、問い合わせ対応など）を記載します。一次情報が多いほど評価されやすくなります。</p>\n<!-- /wp:paragraph -->",
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		foreach ( $row['meta'] as $key => $value ) {
			update_post_meta( $id, '_ws_' . $key, (string) $value );
		}
		wp_set_object_terms( $id, $row['type'], 'server_type' );
		wp_set_object_terms( $id, $row['placement'], 'server_placement' );
		wp_set_object_terms( $id, $row['purpose'], 'server_purpose' );
		++$count;
	}
	return $count;
}

/**
 * Create the front page with recommended structure.
 *
 * @return int Page ID.
 */
function aqua_rank_create_front_page() {
	$existing = get_page_by_path( 'home' );
	if ( $existing ) {
		$id = $existing->ID;
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => '【' . wp_date( 'Y年n月' ) . '】ウォーターサーバーおすすめランキング｜料金・タイプ別に徹底比較',
				'post_name'    => 'home',
				'post_content' => aqua_rank_front_page_content(),
			)
		);
		update_post_meta(
			$id,
			'_ar_faq',
			"Q:ウォーターサーバーの電気代はいくら？\nA:機種や使い方によりますが、月500〜1,000円程度が目安です。エコモードや省エネ機能付きの機種を選ぶと抑えられます。\n\nQ:ウォーターサーバーと浄水器はどっちがお得？\nA:冷水・温水をすぐ使いたいならウォーターサーバー、コスト最優先なら浄水器が有利です。浄水型ウォーターサーバーはその中間で、定額で冷水・温水が使えます。\n\nQ:ウォーターサーバーは卓上と床置きのどちらがいい？\nA:キッチンカウンターなど限られたスペースなら卓上型、家族が多く水の消費量が多いなら容量の大きい床置き型がおすすめです。\n\nQ:注文ノルマはありますか？\nA:宅配水タイプの多くは月の最低注文量があります。スキップ（休止）できるか、休止手数料がかかるかを契約前に確認しましょう。浄水型はノルマがありません。\n\nQ:途中で解約すると解約金はかかる？\nA:多くのメーカーで最低契約期間（1〜3年程度）内の解約に解約金がかかります。契約期間のないプランを選ぶと安心です。"
		);
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $id );
	return (int) $id;
}

/**
 * Block content for front page (editable after creation).
 *
 * @return string
 */
function aqua_rank_front_page_content() {
	$h2 = static function ( $text ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$text}</h2>\n<!-- /wp:heading -->\n\n";
	};
	$h3 = static function ( $text ) {
		return "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$text}</h3>\n<!-- /wp:heading -->\n\n";
	};
	$p = static function ( $text ) {
		return "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$sc = static function ( $code ) {
		return "<!-- wp:shortcode -->\n{$code}\n<!-- /wp:shortcode -->\n\n";
	};

	$c  = $p( '料金・ボトル交換の手間・サイズ・契約条件をすべて同じ基準で比較しました。まずは10秒診断で、自分に合うタイプをチェックしましょう。' );
	$c .= $sc( '[aqua_diagnosis]' );
	$c .= $h2( wp_date( 'Y年n月' ) . '版 ウォーターサーバーおすすめ総合ランキング' );
	$c .= $p( '料金・交換の楽さ・水の味・デザイン・衛生の5項目で評価した総合ランキングです。' );
	$c .= $sc( '[aqua_ranking limit="10"]' );
	$c .= $h2( 'ウォーターサーバー比較表｜料金・タイプ・契約条件を一覧でチェック' );
	$c .= $sc( '[aqua_compare]' );
	$c .= $h2( '後悔しないウォーターサーバーの選び方' );
	$c .= $p( 'ウォーターサーバー選びで失敗する原因の多くは「思ったより費用がかかった」「ボトル交換が大変」「置き場所が足りない」の3つです。次の順番でチェックすれば、自分に合わない機種を確実に除外できます。' );
	$c .= $h3( '1. 浄水型か宅配水（天然水・RO水）かを決める' );
	$c .= $p( '水道水を補充する浄水型は定額で使い放題、ボトルの受け取りや保管も不要です。天然水やRO水の宅配型は、水の味や品質にこだわりたい人、災害用の備蓄も兼ねたい人に向いています。' );
	$c .= $h3( '2. ボトル交換の手間をチェックする' );
	$c .= $p( '12Lボトルは約12kg。上置きタイプは持ち上げる必要があります。足元でセットできる下置きタイプ、軽量パック、給水式（浄水型）なら負担が大きく減ります。' );
	$c .= $h3( '3. 月額は「水代＋レンタル料＋電気代」の総額で比較する' );
	$c .= $p( '「サーバー無料」でも水代が高ければ割高になります。月の使用量を想定して総額で比べましょう。下のシミュレーターで試算できます。' );
	$c .= $h3( '4. 卓上型か床置き型か、設置スペースを測る' );
	$c .= $p( '本体サイズに加えて、交換用ボトルの保管場所、コンセントの位置、背面の放熱スペースも確認しましょう。' );
	$c .= $h3( '5. 契約期間・解約金・注文ノルマを確認する' );
	$c .= $p( '最低契約期間内の解約には解約金がかかるのが一般的です。宅配水は注文ノルマやスキップ条件も必ずチェックしましょう。' );
	$c .= $h3( '6. 衛生機能（自動クリーン・UV殺菌）とチャイルドロック' );
	$c .= $p( '内部を自動で清潔に保つ機能があればメンテナンスの手間が減ります。小さな子どもがいる家庭は温水・冷水ともにチャイルドロックがある機種が安心です。' );

	$c .= $h2( '目的別おすすめウォーターサーバー' );
	$c .= $h3( '安さ・コスパで選ぶなら' );
	$c .= $sc( '[aqua_ranking purpose="yasui" limit="3" heading="h4"]' );
	$c .= $h3( '赤ちゃんのミルク作りにおすすめ' );
	$c .= $sc( '[aqua_ranking purpose="baby" limit="3" heading="h4"]' );
	$c .= $h3( '一人暮らしにおすすめ' );
	$c .= $sc( '[aqua_ranking purpose="hitorigurashi" limit="3" heading="h4"]' );
	$c .= $h3( 'コンパクトな卓上型ならこれ' );
	$c .= $sc( '[aqua_ranking placement="takujo" limit="3" heading="h4"]' );

	$c .= $h2( 'ペットボトル・浄水器とウォーターサーバーの費用比較' );
	$c .= $p( '1日の使用量と単価を入力すると、ペットボトル・宅配水サーバー・浄水型サーバーの月額と年額を比較できます。' );
	$c .= $sc( '[aqua_cost_sim]' );

	$c .= $h2( 'ウォーターサーバーはやめた方がいい？メリット・デメリット' );
	$c .= $h3( 'メリット' );
	$c .= $p( '冷水・温水がすぐに使える／重いペットボトルを買いに行く手間がなくなる／ペットボトルのゴミが減る／宅配水は災害時の備蓄になる。' );
	$c .= $h3( 'デメリット・やめた方がいい人' );
	$c .= $p( '水をほとんど飲まない人は割高になりがちです。また、注文ノルマや解約金の条件を確認せずに契約すると後悔しやすくなります。条件のゆるい機種や浄水型を選ぶことでリスクを減らせます。' );

	$c .= $h2( 'はじめての人向け ウォーターサーバーの基礎知識' );
	$c .= $h3( '天然水・RO水・浄水の違い' );
	$c .= $p( '天然水は特定の水源の地下水をろ過・殺菌したもので、ミネラルを含み味に特徴があります。RO水は逆浸透膜でろ過した純水に近い水、浄水は水道水をフィルターでろ過した水です。' );
	$c .= $h3( 'ボトルの種類（ワンウェイ／リターナブル）' );
	$c .= $p( 'ワンウェイ方式は使い捨てで家庭ゴミとして処分、リターナブル方式は空ボトルを回収してもらう方式です。保管スペースとゴミ出しの手間で選びましょう。' );

	$c .= $h2( '当サイトのランキング評価基準' );
	$c .= $p( '料金の安さ・交換の楽さ・水の味・デザイン・衛生の5項目を、実際の利用と公式情報の確認に基づいて5段階で評価しています。詳しくは評価基準のページをご覧ください。' );

	$c .= $h2( 'ウォーターサーバーについてよくある質問' );
	$c .= $sc( '[aqua_faq_list]' );

	$c .= $h2( 'まとめ：迷ったら総合ランキング上位から選ぼう' );
	$c .= $p( 'コストとラクさを重視するなら浄水型、味と備蓄を重視するなら天然水の宅配型がおすすめです。契約条件まで比較して、長く使える1台を選びましょう。' );

	return $c;
}

/**
 * 信頼性ページ（運営者情報・評価基準・プライバシーポリシー・お問い合わせ）。
 */
function aqua_rank_create_guide_pages() {
	$pages = array(
		'about'      => array( '運営者情報', '<!-- wp:paragraph --><p>運営者名、所在地、連絡先、運営目的、編集方針、執筆者・監修者のプロフィールを記載してください。</p><!-- /wp:paragraph -->' ),
		'criteria'   => array( 'ランキング評価基準・検証方法', '<!-- wp:paragraph --><p>評価項目（料金・交換の楽さ・水の味・デザイン・衛生）ごとの採点基準、実機検証の方法（期間、計測器具、写真）、情報の更新頻度、広告との関係を記載してください。</p><!-- /wp:paragraph -->' ),
		'privacy'    => array( 'プライバシーポリシー・広告について', '<!-- wp:paragraph --><p>個人情報の取り扱い、アクセス解析（Googleアナリティクス）の利用、アフィリエイトプログラムへの参加について記載してください。</p><!-- /wp:paragraph -->' ),
		'contact'    => array( 'お問い合わせ', '<!-- wp:paragraph --><p>お問い合わせフォーム（Contact Form 7 等）を設置してください。</p><!-- /wp:paragraph -->' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page[0],
				'post_name'    => $slug,
				'post_content' => $page[1],
			)
		);
	}
}

/**
 * 目的別ランディングページ（固定ページ）。タクソノミーアーカイブの代わりに、
 * 独自の導入文・選び方を書き足せる固定ページで「卓上型 ウォーターサーバー」等を狙う。
 */
function aqua_rank_create_landing_pages() {
	$pages = array(
		'takujo'        => array( '卓上型ウォーターサーバーおすすめ比較', 'placement="takujo"', '卓上型', 'キッチンカウンターに置けるコンパクトな卓上型。設置に必要な幅・奥行き・高さ（上部の給水スペース含む）を必ず測ってから選びましょう。' ),
		'josui'         => array( '浄水型ウォーターサーバーおすすめ比較', 'type="josui"', '浄水型', '水道水を入れるだけで使える浄水型は、定額で飲み放題・ボトルの受け取り不要が魅力。フィルター交換の頻度と除去物質の数もチェックしましょう。' ),
		'tennen'        => array( '天然水ウォーターサーバーおすすめ比較', 'type="tennen"', '天然水', '水源や硬度で味が変わる天然水。採水地、加熱処理の有無、ボトル交換方式（上置き/下置き）で比較しましょう。' ),
		'yasui'         => array( '安いウォーターサーバーおすすめ比較', 'purpose="yasui"', '安い', '月額は「水代＋レンタル料＋電気代」の総額で比較するのがポイント。解約金や配送料も含めた実質コストで選びましょう。' ),
		'baby'          => array( '赤ちゃんのミルク作りにおすすめのウォーターサーバー', 'purpose="baby"', '赤ちゃん向け', '70℃以上のお湯がすぐ使えるか、チャイルドロックの有無、水の安全性（放射性物質検査など）を確認しましょう。' ),
		'hitorigurashi' => array( '一人暮らしにおすすめのウォーターサーバー', 'purpose="hitorigurashi"', '一人暮らし向け', '一人暮らしは水を使い切れずボトルが溜まりがち。注文ノルマなし・スキップ可能・浄水型を中心に選ぶと失敗しにくくなります。' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		$content  = "<!-- wp:paragraph -->\n<p>{$page[3]}</p>\n<!-- /wp:paragraph -->\n\n";
		$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$page[2]}ウォーターサーバーの比較表</h2>\n<!-- /wp:heading -->\n\n";
		$content .= "<!-- wp:shortcode -->\n[aqua_compare {$page[1]} filter=\"0\"]\n<!-- /wp:shortcode -->\n\n";
		$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$page[2]}ウォーターサーバーおすすめランキング</h2>\n<!-- /wp:heading -->\n\n";
		$content .= "<!-- wp:shortcode -->\n[aqua_ranking {$page[1]}]\n<!-- /wp:shortcode -->\n\n";
		$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$page[2]}ウォーターサーバーの選び方</h2>\n<!-- /wp:heading -->\n\n";
		$content .= "<!-- wp:paragraph -->\n<p>（ここに独自の選び方・実機検証の内容を追記してください）</p>\n<!-- /wp:paragraph -->";
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => '【' . wp_date( 'Y年' ) . '】' . $page[0],
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);
	}
}

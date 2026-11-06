<?php
/**
 * JSON-LD structured data（Cocoon が出力しないものだけを追加）。
 *
 * Cocoon が出力：Article / BreadcrumbList / Organization 等 → ここでは出さない。
 * 本プラグインが出力：
 * - サーバー個別ページ: Product + Review（編集部レビュー・長所/短所）
 * - ランキングを含むページ: ItemList
 * - FAQ を含むページ: FAQPage
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

/**
 * ランキングで出力されたサーバーIDを収集（ItemList 用）。
 *
 * @param int[]|null $add IDs to add.
 * @return int[]
 */
function aqua_rank_itemlist_collector( $add = null ) {
	static $ids = array();
	if ( is_array( $add ) ) {
		$ids = array_values( array_unique( array_merge( $ids, $add ) ) );
	}
	return $ids;
}

/**
 * 表示された FAQ を収集（FAQPage 用）。
 *
 * @param array|null $add Q/A items.
 * @return array
 */
function aqua_rank_faq_collector( $add = null ) {
	static $items = array();
	if ( is_array( $add ) ) {
		foreach ( $add as $item ) {
			$items[ md5( $item['q'] ) ] = $item;
		}
	}
	return array_values( $items );
}

/**
 * Product schema for a server post.
 *
 * @param int $id Post ID.
 * @return array
 */
function aqua_rank_product_schema( $id ) {
	$product = array(
		'@type' => 'Product',
		'name'  => get_the_title( $id ),
		'url'   => get_permalink( $id ),
	);
	$brand = aqua_rank_get( 'brand', $id );
	if ( $brand ) {
		$product['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand,
		);
	}
	$desc = aqua_rank_get( 'catch', $id );
	if ( $desc ) {
		$product['description'] = $desc;
	}
	if ( has_post_thumbnail( $id ) ) {
		$product['image'] = get_the_post_thumbnail_url( $id, 'large' );
	}
	$score = aqua_rank_overall( $id );
	if ( $score > 0 ) {
		$review = array(
			'@type'        => 'Review',
			'reviewRating' => array(
				'@type'       => 'Rating',
				'ratingValue' => $score,
				'bestRating'  => 5,
				'worstRating' => 1,
			),
			'author'       => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ) . ' 編集部',
			),
		);
		$verdict = aqua_rank_get( 'verdict', $id );
		if ( $verdict ) {
			$review['reviewBody'] = mb_strimwidth( $verdict, 0, 300, '…', 'UTF-8' );
		}
		$pros = aqua_rank_list( 'pros', $id );
		$cons = aqua_rank_list( 'cons', $id );
		if ( $pros ) {
			$review['positiveNotes'] = aqua_rank_item_list_notes( $pros );
		}
		if ( $cons ) {
			$review['negativeNotes'] = aqua_rank_item_list_notes( $cons );
		}
		$product['review'] = $review;
	}
	return $product;
}

/**
 * Pros / cons → ItemList（Google「長所と短所」形式）。
 *
 * @param string[] $notes Notes.
 * @return array
 */
function aqua_rank_item_list_notes( $notes ) {
	$list = array();
	foreach ( $notes as $i => $note ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $note,
		);
	}
	return array(
		'@type'           => 'ItemList',
		'itemListElement' => $list,
	);
}

/**
 * Print JSON-LD in footer (ショートコードの収集結果を反映するため wp_footer)。
 */
function aqua_rank_print_schema() {
	if ( is_admin() || is_feed() || ! get_option( 'aqua_rank_schema', '1' ) ) {
		return;
	}
	$graph = array();

	if ( is_singular( 'server' ) ) {
		$graph[] = aqua_rank_product_schema( get_queried_object_id() );
	}

	$ids = aqua_rank_itemlist_collector();
	if ( $ids ) {
		$elements = array();
		foreach ( $ids as $i => $sid ) {
			$elements[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => get_permalink( $sid ),
				'name'     => get_the_title( $sid ),
			);
		}
		$graph[] = array(
			'@type'           => 'ItemList',
			'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
			'numberOfItems'   => count( $elements ),
			'itemListElement' => $elements,
		);
	}

	$faq = aqua_rank_faq_collector();
	if ( $faq ) {
		$main = array();
		foreach ( $faq as $item ) {
			$main[] = array(
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['a'],
				),
			);
		}
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => $main,
		);
	}

	if ( ! $graph ) {
		return;
	}
	$json = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
	echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
add_action( 'wp_footer', 'aqua_rank_print_schema', 20 );

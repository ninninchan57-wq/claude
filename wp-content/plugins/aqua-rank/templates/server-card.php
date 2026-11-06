<?php
/**
 * Ranking card for one server.
 *
 * Variables: $server (WP_Post), $pos (int, 0 = no rank), $heading (h2|h3|h4). aqua_rank_render_card() から読み込む。
 *
 * @package AquaRank
 */

defined( 'ABSPATH' ) || exit;

if ( ! $server instanceof WP_Post ) {
	return;
}
$id       = $server->ID;
$score    = aqua_rank_overall( $id );
$pros     = aqua_rank_list( 'pros', $id );
$cons     = aqua_rank_list( 'cons', $id );
$for      = aqua_rank_list( 'recommended_for', $id );
$features = aqua_rank_list( 'features', $id, 'comma' );
$campaign = aqua_rank_get( 'campaign', $id );
$tag      = $pos ? 'li' : 'div';
?>
<<?php echo esc_html( $tag ); ?> class="ar-card<?php echo $pos && $pos <= 3 ? ' ar-card--top ar-card--top' . esc_attr( (string) $pos ) : ''; ?>" id="server-<?php echo esc_attr( (string) $id ); ?>">
	<header class="ar-card__head">
		<?php if ( $pos ) : ?>
			<span class="ar-card__rank" aria-label="<?php echo esc_attr( $pos . '位' ); ?>"><?php echo esc_html( (string) $pos ); ?><small>位</small></span>
		<?php endif; ?>
		<<?php echo esc_html( $heading ); ?> class="ar-card__title">
			<?php if ( aqua_rank_get( 'catch', $id ) ) : ?>
				<span class="ar-card__catch"><?php echo esc_html( aqua_rank_get( 'catch', $id ) ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( get_the_title( $id ) ); ?>
		</<?php echo esc_html( $heading ); ?>>
	</header>

	<div class="ar-card__body">
		<div class="ar-card__media">
			<?php
			if ( has_post_thumbnail( $id ) ) {
				echo get_the_post_thumbnail(
					$id,
					'aqua-card',
					array(
						'loading' => $pos && $pos <= 1 ? 'eager' : 'lazy',
						'alt'     => get_the_title( $id ),
					)
				);
			} else {
				echo '<div class="ar-card__noimg" aria-hidden="true">💧</div>';
			}
			?>
		</div>
		<div class="ar-card__summary">
			<?php if ( $score ) : ?>
				<p class="ar-card__score">総合評価 <?php echo aqua_rank_stars( $score ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<ul class="ar-scorebars">
					<?php
					foreach ( aqua_rank_score_labels() as $key => $label ) :
						$v = (float) aqua_rank_get( $key, $id );
						if ( ! $v ) {
							continue;
						}
						?>
						<li><span><?php echo esc_html( $label ); ?></span><span class="ar-scorebars__bar"><span style="width:<?php echo esc_attr( (string) ( $v / 5 * 100 ) ); ?>%"></span></span><span><?php echo esc_html( number_format( $v, 1 ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<dl class="ar-card__spec">
				<div><dt>月額目安</dt><dd class="ar-is-strong"><?php echo esc_html( aqua_rank_yen( aqua_rank_get( 'monthly_cost', $id ) ) ); ?></dd></div>
				<div><dt>タイプ</dt><dd><?php echo esc_html( aqua_rank_terms( $id, 'server_type' ) ); ?></dd></div>
				<div><dt>設置</dt><dd><?php echo esc_html( aqua_rank_terms( $id, 'server_placement' ) ); ?></dd></div>
				<div><dt>契約期間</dt><dd><?php echo esc_html( aqua_rank_get( 'contract_period', $id ) ); ?></dd></div>
			</dl>
		</div>
	</div>

	<?php if ( $features ) : ?>
		<ul class="ar-tags"><?php foreach ( $features as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul>
	<?php endif; ?>

	<?php if ( $for ) : ?>
		<div class="ar-card__for">
			<p class="ar-card__label">こんな人におすすめ</p>
			<ul class="ar-checklist"><?php foreach ( $for as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>

	<?php if ( $pros || $cons ) : ?>
		<div class="ar-proscons">
			<?php if ( $pros ) : ?>
				<div class="ar-proscons__col ar-proscons__col--pros"><p class="ar-card__label">メリット</p><ul><?php foreach ( $pros as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<?php if ( $cons ) : ?>
				<div class="ar-proscons__col ar-proscons__col--cons"><p class="ar-card__label">デメリット</p><ul><?php foreach ( $cons as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( aqua_rank_get( 'verdict', $id ) ) : ?>
		<div class="ar-card__verdict">
			<p class="ar-card__label">編集部の評価</p>
			<?php echo wpautop( esc_html( aqua_rank_get( 'verdict', $id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	<?php endif; ?>

	<?php if ( $campaign ) : ?>
		<p class="ar-card__campaign"><span>キャンペーン</span><?php echo esc_html( $campaign ); ?></p>
	<?php endif; ?>

	<div class="ar-card__actions">
		<?php echo aqua_rank_cta( $id, get_the_title( $id ) . 'の公式サイトへ' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( ! is_singular( 'server' ) || get_queried_object_id() !== $id ) : ?>
			<a class="ar-btn ar-btn--ghost" href="<?php echo esc_url( get_permalink( $id ) ); ?>">詳しいレビューを見る</a>
		<?php endif; ?>
	</div>
	<?php if ( aqua_rank_get( 'data_checked', $id ) ) : ?>
		<p class="ar-card__checked">情報確認日：<?php echo esc_html( wp_date( 'Y年n月j日', strtotime( aqua_rank_get( 'data_checked', $id ) ) ) ); ?></p>
	<?php endif; ?>
</<?php echo esc_html( $tag ); ?>>

<?php
/**
 * Template: Card de rancho (part) — usado em archive e AJAX
 *
 * @package GuiaPradoAqui
 *
 * @var WP_Post $post Post atual
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = get_the_ID();
$status    = get_post_meta( $post_id, '_gpa_status_verificacao', true ) ?: 'pendente';
$verificado = $status === 'verificado';

$cidade    = gpa_get_cidade_nome( $post_id );
$nota      = gpa_get_nota_final( $post_id );
$preco     = gpa_format_preco( $post_id );
$capacidade= gpa_format_capacidade( $post_id );
$pesca_list= gpa_get_pesca_list( $post_id );

$foto_id   = get_post_thumbnail_id( $post_id );
$foto_src  = '';
if ( $foto_id ) {
	$img = wp_get_attachment_image_src( $foto_id, 'medium_large' );
	$foto_src = $img ? $img[0] : '';
}

$wa_url    = gpa_get_whatsapp_url( $post_id );
$maps_url  = gpa_get_maps_url( $post_id );
$ranch_url = get_permalink( $post_id );

$id_original = get_post_meta( $post_id, '_gpa_id_original', true );

// Pesca — exibe os 2 primeiros para o card
$pesca_tags = array_slice( $pesca_list, 0, 3 );
?>
<article class="gpa-card" itemscope itemtype="https://schema.org/LodgingBusiness">
	<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>">
	<?php if ( $cidade ) : ?>
	<meta itemprop="addressLocality" content="<?php echo esc_attr( $cidade ); ?>">
	<?php endif; ?>

	<!-- Foto -->
	<div class="gpa-card-photo">
		<?php if ( $foto_src ) : ?>
			<img
				data-src="<?php echo esc_url( $foto_src ); ?>"
				src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E"
				alt="<?php echo esc_attr( get_the_title() ); ?>"
				loading="lazy"
				width="400" height="190"
			>
		<?php else : ?>
			<div class="gpa-card-placeholder">🏡</div>
		<?php endif; ?>

		<?php if ( $verificado ) : ?>
			<div class="gpa-card-verified-badge">🟢 VERIFICADO</div>
		<?php endif; ?>

		<?php if ( $nota !== null ) : ?>
			<div class="gpa-card-nota-badge">⭐ <?php echo esc_html( number_format( $nota, 1 ) ); ?></div>
		<?php endif; ?>
	</div>

	<!-- Body -->
	<div class="gpa-card-body">
		<div class="gpa-card-topline">
			<?php if ( $id_original ) : ?>
				<span class="gpa-card-id">#<?php echo esc_html( str_pad( (string) $id_original, 2, '0', STR_PAD_LEFT ) ); ?></span>
			<?php else : ?>
				<span class="gpa-card-id">#<?php echo esc_html( $post_id ); ?></span>
			<?php endif; ?>
			<span class="gpa-card-badge <?php echo $verificado ? 'ok' : ''; ?>">
				<?php echo $verificado ? '● VERIFICADO' : '○ EM CADASTRO'; ?>
			</span>
		</div>

		<h2 itemprop="name"><?php the_title(); ?></h2>

		<?php if ( $cidade ) : ?>
			<div class="gpa-card-city">
				📍 <?php echo esc_html( $cidade ); ?>
			</div>
		<?php endif; ?>

		<!-- Info rápida -->
		<div class="gpa-card-quick">
			<div class="gpa-card-info">
				<span class="gpa-card-info-label">CAPACIDADE</span>
				<span class="gpa-card-info-value <?php echo ! get_post_meta( $post_id, '_gpa_capacidade', true ) ? 'confirmar' : ''; ?>">
					<?php echo esc_html( $capacidade ); ?>
				</span>
			</div>
			<div class="gpa-card-info">
				<span class="gpa-card-info-label">PREÇO</span>
				<span class="gpa-card-info-value <?php echo ! get_post_meta( $post_id, '_gpa_preco', true ) ? 'confirmar' : ''; ?>">
					<?php echo esc_html( $preco ); ?>
				</span>
			</div>
			<?php if ( $nota !== null ) : ?>
			<div class="gpa-card-info">
				<span class="gpa-card-info-label">NOTA PRADO AQUI</span>
				<span class="gpa-card-info-value">⭐ <?php echo esc_html( number_format( $nota, 1 ) ); ?>/10</span>
			</div>
			<?php endif; ?>
			<?php
			$nota_google = get_post_meta( $post_id, '_gpa_nota_google', true );
			if ( $nota_google ) : ?>
			<div class="gpa-card-info">
				<span class="gpa-card-info-label">NOTA GOOGLE</span>
				<span class="gpa-card-info-value">🔍 <?php echo esc_html( $nota_google ); ?></span>
			</div>
			<?php endif; ?>
		</div>

		<!-- Tags de pesca -->
		<?php if ( ! empty( $pesca_tags ) ) : ?>
			<div class="gpa-card-tags">
				<?php foreach ( $pesca_tags as $tag ) : ?>
					<span class="gpa-tag"><?php echo esc_html( $tag ); ?></span>
				<?php endforeach; ?>
				<?php if ( count( $pesca_list ) > 3 ) : ?>
					<span class="gpa-tag">+<?php echo count( $pesca_list ) - 3; ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Botões -->
		<div class="gpa-card-buttons">
			<a class="gpa-btn gpa-btn-view" href="<?php echo esc_url( $ranch_url ); ?>">
				🏡 Ver Rancho
			</a>
			<?php if ( $wa_url ) : ?>
				<a class="gpa-btn gpa-btn-green" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer">
					💬 WhatsApp
				</a>
			<?php endif; ?>
			<?php if ( $maps_url ) : ?>
				<a class="gpa-btn gpa-btn-blue" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">
					📍 Mapa
				</a>
			<?php endif; ?>
		</div>
	</div>
</article>

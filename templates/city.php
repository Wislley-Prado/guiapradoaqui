<?php
/**
 * Template: Página de cidade (taxonomy)
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Processa dados antes de get_header() para injetar SEO no <head>
$term      = get_queried_object();
$cidade_id = $term->term_id;
$estado    = get_term_meta( $cidade_id, '_gpa_cidade_estado', true );
$regiao    = get_term_meta( $cidade_id, '_gpa_cidade_regiao', true );
$og_image  = get_term_meta( $cidade_id, '_gpa_cidade_og_image', true );

// Query dos ranchos desta cidade
$query = new WP_Query( [
	'post_type'      => GPA_CPT,
	'post_status'    => 'publish',
	'posts_per_page' => 24,
	'tax_query'      => [
		[
			'taxonomy' => GPA_TAXONOMY,
			'field'    => 'term_id',
			'terms'    => $cidade_id,
		],
	],
	'meta_key'  => '_gpa_nota_final',
	'orderby'   => 'meta_value_num',
	'order'     => 'DESC',
] );

// SEO — injeta no <head> via hook, depois chama get_header()
$_gpa_c_title = $term->name . ( $estado ? ' - ' . $estado : '' ) . ' — Ranchos de Pesca — Guia Prado Aqui';
$_gpa_c_desc  = 'Encontre ranchos de pesca em ' . $term->name . '. ' . $query->found_posts . ' ranchos cadastrados no Guia Prado Aqui.';
$_gpa_c_img   = $og_image;

add_action( 'wp_head', function() use ( $_gpa_c_title, $_gpa_c_desc, $_gpa_c_img ) {
	echo '<meta name="description" content="' . esc_attr( $_gpa_c_desc ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $_gpa_c_title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $_gpa_c_desc ) . '">' . "\n";
	if ( $_gpa_c_img ) {
		echo '<meta property="og:image" content="' . esc_url( $_gpa_c_img ) . '">' . "\n";
	}
}, 5 );

get_header();
?>

<div class="gpa-city-page">

	<!-- Breadcrumb -->
	<nav class="gpa-breadcrumb" aria-label="Navegação">
		<a href="<?php echo esc_url( get_post_type_archive_link( GPA_CPT ) ); ?>">🎣 Ranchos</a>
		→ <?php echo esc_html( $term->name ); ?>
	</nav>

	<!-- Hero da cidade -->
	<div class="gpa-city-hero">
		<h1>
			🏙️ Ranchos em <?php echo esc_html( $term->name ); ?>
			<?php if ( $estado ) : ?>
				<small style="font-weight:400;font-size:0.5em;opacity:.7;"><?php echo esc_html( $estado ); ?></small>
			<?php endif; ?>
		</h1>
		<p>
			<?php echo esc_html( $query->found_posts ); ?> rancho<?php echo $query->found_posts !== 1 ? 's' : ''; ?> encontrado<?php echo $query->found_posts !== 1 ? 's' : ''; ?> nesta cidade.
			<?php if ( $regiao ) : ?>
				<?php echo esc_html( $regiao ); ?>.
			<?php endif; ?>
		</p>
	</div>

	<!-- Grid de ranchos -->
	<?php if ( $query->have_posts() ) : ?>
	<div class="gpa-grid">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<?php include GPA_PLUGIN_DIR . 'templates/parts/card.php'; ?>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>
	<?php else : ?>
	<div class="gpa-empty-state" style="text-align:center;padding:60px;color:var(--muted);">
		<span style="font-size:48px;display:block;margin-bottom:16px;">🎣</span>
		<p>Nenhum rancho publicado em <?php echo esc_html( $term->name ); ?> ainda.</p>
		<p><a href="<?php echo esc_url( get_post_type_archive_link( GPA_CPT ) ); ?>">Ver todos os ranchos</a></p>
	</div>
	<?php endif; ?>

	<!-- Outras cidades -->
	<?php
	$outras = get_terms( [
		'taxonomy'   => GPA_TAXONOMY,
		'hide_empty' => true,
		'exclude'    => [ $cidade_id ],
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 6,
	] );

	if ( $outras && ! is_wp_error( $outras ) ) : ?>
	<div class="gpa-info-block" style="margin-top:40px;">
		<h3 style="font-size:16px;font-weight:800;margin:0 0 16px;">Outras cidades</h3>
		<div style="display:flex;flex-wrap:wrap;gap:10px;">
			<?php foreach ( $outras as $c ) : ?>
			<a href="<?php echo esc_url( get_term_link( $c ) ); ?>" style="background:var(--g-l);color:var(--g);padding:8px 16px;border-radius:20px;text-decoration:none;font-weight:700;font-size:13px;">
				<?php echo esc_html( $c->name ); ?> <small style="opacity:.7;">(<?php echo esc_html( $c->count ); ?>)</small>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

</div>

<?php get_footer(); ?>

<?php
/**
 * Template: Catálogo público de ranchos (archive)
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'gpa_settings', [] );

// Cidades disponíveis
$cidades = get_terms( [
	'taxonomy'   => GPA_TAXONOMY,
	'hide_empty' => true,
	'orderby'    => 'name',
] );

// Query inicial (PHP — sem AJAX na primeira carga)
$exige_verificado = $settings['exige_verificado'] ?? false;

$initial_args = [
	'post_type'      => GPA_CPT,
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'meta_query'     => [
		[
			'key'     => '_gpa_status_verificacao',
			'value'   => $exige_verificado ? 'verificado' : 'desativado',
			'compare' => $exige_verificado ? '=' : '!=',
		],
	],
	'meta_key'  => '_gpa_nota_final',
	'orderby'   => 'meta_value_num',
	'order'     => 'DESC',
];

$initial_query = new WP_Query( $initial_args );
$total_ranchos = $initial_query->found_posts;

// SEO
$seo_title       = get_bloginfo( 'name' ) . ' — Ranchos de Pesca';
$seo_description = 'Encontre os melhores ranchos de pesca na região de Três Marias e Rio São Francisco. Pesquise por cidade, estrutura e tipo de pesca.';

// Injeta meta tags no <head> corretamente
$_gpa_seo_desc  = $seo_description;
$_gpa_seo_title = $seo_title;
add_action( 'wp_head', function() use ( $_gpa_seo_desc, $_gpa_seo_title ) {
	echo '<meta name="description" content="' . esc_attr( $_gpa_seo_desc ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $_gpa_seo_title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $_gpa_seo_desc ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
}, 5 );

// Se chamado via shortcode, não inclui header/footer do tema
$is_shortcode = defined( 'GPA_IS_SHORTCODE' ) && GPA_IS_SHORTCODE;

if ( ! $is_shortcode ) {
	get_header();
}




<!-- Header do Catálogo -->
<div class="gpa-header">
	<div class="gpa-header-inner">
		<div class="gpa-header-brand">
			<div class="gpa-header-icon">🎣</div>
			<div class="gpa-header-txt">
				<small>GUIA PRADO AQUI</small>
				<h1>Ranchos de Pesca</h1>
				<p class="gpa-header-sub">Rio São Francisco • Três Marias e Região</p>
			</div>
		</div>

		<!-- Busca integrada no header -->
		<div class="gpa-search-bar">
			<label for="gpa-search-input" class="gpa-seo-none">Buscar rancho</label>
			<input
				type="search"
				id="gpa-search-input"
				placeholder="🔎 Buscar rancho, cidade ou endereço..."
				autocomplete="off"
			>
		</div>
	</div>
</div>

<!-- Catálogo -->
<div class="gpa-catalog">

	<!-- Filtros -->
	<div class="gpa-filters" role="search" aria-label="Filtros de ranchos">
		<!-- Cidade -->
		<select id="gpa-filter-cidade" class="gpa-filter-select" aria-label="Filtrar por cidade">
			<option value="">🏙️ Todas as cidades</option>
			<?php if ( $cidades && ! is_wp_error( $cidades ) ) : ?>
				<?php foreach ( $cidades as $c ) : ?>
					<option value="<?php echo esc_attr( $c->term_id ); ?>"><?php echo esc_html( $c->name ); ?> (<?php echo esc_html( $c->count ); ?>)</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>

		<!-- Pesca -->
		<select id="gpa-filter-pesca" class="gpa-filter-select" aria-label="Filtrar por tipo de pesca">
			<option value="">🎣 Tipo de pesca</option>
			<option value="barranco">Pesca de barranco</option>
			<option value="barco">Pesca de barco</option>
			<option value="pesqueiro">Pesqueiro (lago próprio)</option>
			<option value="noturna">Pesca noturna</option>
			<option value="rampa">Com rampa</option>
		</select>

		<!-- Ordenação -->
		<select id="gpa-filter-ordenar" class="gpa-filter-select" aria-label="Ordenar por">
			<option value="nota">⭐ Melhor nota</option>
			<option value="preco_asc">💰 Menor preço</option>
			<option value="capacidade">👥 Maior capacidade</option>
			<option value="recentes">🆕 Mais recentes</option>
		</select>

		<!-- Chips -->
		<label class="gpa-filter-chip" for="gpa-chip-piscina">
			<input type="checkbox" id="gpa-chip-piscina" class="gpa-chip-input" data-filter="piscina">
			🏊 Piscina
		</label>
		<label class="gpa-filter-chip" for="gpa-chip-wifi">
			<input type="checkbox" id="gpa-chip-wifi" class="gpa-chip-input" data-filter="wifi">
			📶 Wi-Fi
		</label>

		<!-- Contador -->
		<div class="gpa-count" id="gpa-count" aria-live="polite">
			<?php echo esc_html( $total_ranchos ); ?> rancho<?php echo $total_ranchos !== 1 ? 's' : ''; ?> encontrado<?php echo $total_ranchos !== 1 ? 's' : ''; ?>
		</div>
	</div>

	<!-- Grid -->
	<div class="gpa-grid" id="gpa-grid" aria-label="Lista de ranchos">
		<!-- Loading -->
		<div class="gpa-loading" id="gpa-loading">
			<div class="gpa-spinner"></div>
			Carregando ranchos...
		</div>

		<?php if ( $initial_query->have_posts() ) : ?>
			<?php while ( $initial_query->have_posts() ) : $initial_query->the_post(); ?>
				<?php include GPA_PLUGIN_DIR . 'templates/parts/card.php'; ?>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<div class="gpa-empty-state">
				<span>🎣</span>
				<p>Nenhum rancho encontrado. <a href="<?php echo esc_url( admin_url( 'admin.php?page=gpa-importar' ) ); ?>">Importe a base de dados.</a></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Paginação -->
	<div class="gpa-pagination" id="gpa-pagination" aria-label="Navegação de páginas"></div>

</div>

<?php if ( ! $is_shortcode ) {
	get_footer();
}

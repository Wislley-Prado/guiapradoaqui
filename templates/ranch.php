<?php
/**
 * Template: Página individual do rancho
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Processa os dados ANTES do get_header() para poder injetar SEO no <head>
the_post();
$post_id = get_the_ID();

// Dados
$cidade       = gpa_get_cidade_term( $post_id );
$cidade_nome  = $cidade ? $cidade->name : '';
$nota         = gpa_get_nota_final( $post_id );
$nota_google  = get_post_meta( $post_id, '_gpa_nota_google', true );
$avl_google   = get_post_meta( $post_id, '_gpa_qtd_avaliacoes_google', true );
$preco        = gpa_format_preco( $post_id );
$capacidade   = gpa_format_capacidade( $post_id );
$quartos      = get_post_meta( $post_id, '_gpa_quartos', true );
$suites       = get_post_meta( $post_id, '_gpa_suites', true );
$banheiros    = get_post_meta( $post_id, '_gpa_banheiros', true );
$endereco     = get_post_meta( $post_id, '_gpa_endereco', true );
$estrutura    = gpa_get_estrutura_list( $post_id );
$pesca        = gpa_get_pesca_list( $post_id );
$wa_url       = gpa_get_whatsapp_url( $post_id );
$maps_url     = gpa_get_maps_url( $post_id );
$status       = get_post_meta( $post_id, '_gpa_status_verificacao', true ) ?: 'pendente';
$verificado   = $status === 'verificado';
$data_ver     = get_post_meta( $post_id, '_gpa_data_verificacao', true );
$instagram    = get_post_meta( $post_id, '_gpa_instagram', true );
$site         = get_post_meta( $post_id, '_gpa_site', true );
$obs_preco    = get_post_meta( $post_id, '_gpa_obs_preco', true );
$obs_pesca    = get_post_meta( $post_id, '_gpa_obs_pesca', true );
$tipo_preco   = get_post_meta( $post_id, '_gpa_tipo_preco', true );
$lat          = get_post_meta( $post_id, '_gpa_latitude', true );
$lng          = get_post_meta( $post_id, '_gpa_longitude', true );

// Galeria
$galeria_ids = gpa_get_gallery_ids( $post_id );
$foto_id     = get_post_thumbnail_id( $post_id );

// SEO — injeta no <head> via hook
$seo_title = get_the_title() . ' — Guia Prado Aqui';
if ( $cidade_nome ) {
	$seo_title = get_the_title() . ' em ' . $cidade_nome . ' — Guia Prado Aqui';
}
$seo_desc = gpa_get_meta_description( $post_id );
$og_image = $foto_id ? wp_get_attachment_image_src( $foto_id, 'large' )[0] : '';

$_gpa_r_title = $seo_title;
$_gpa_r_desc  = $seo_desc;
$_gpa_r_img   = $og_image;

add_action( 'wp_head', function() use ( $_gpa_r_title, $_gpa_r_desc, $_gpa_r_img ) {
	echo '<meta name="description" content="' . esc_attr( $_gpa_r_desc ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $_gpa_r_title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $_gpa_r_desc ) . '">' . "\n";
	echo '<meta property="og:type" content="local.business">' . "\n";
	if ( $_gpa_r_img ) {
		echo '<meta property="og:image" content="' . esc_url( $_gpa_r_img ) . '">' . "\n";
	}
}, 5 );

// Agora chama get_header() — as meta tags acima já estarão no <head>
get_header();
?>

<?php // ─── Conteúdo do Rancho ──────────────────────────────────── ?>
<div class="gpa-ranch-page" itemscope itemtype="https://schema.org/LodgingBusiness">
	<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>">
	<?php if ( $cidade_nome ) : ?>
	<meta itemprop="addressLocality" content="<?php echo esc_attr( $cidade_nome ); ?>">
	<?php endif; ?>


	<!-- Breadcrumb -->
		<nav class="gpa-breadcrumb" aria-label="Navegação">
			<a href="<?php echo esc_url( get_post_type_archive_link( GPA_CPT ) ); ?>">🎣 Ranchos</a>
			<?php if ( $cidade ) : ?>
				→ <a href="<?php echo esc_url( get_term_link( $cidade ) ); ?>"><?php echo esc_html( $cidade_nome ); ?></a>
			<?php endif; ?>
			→ <?php the_title(); ?>
		</nav>

		<!-- Hero -->
		<div class="gpa-ranch-hero">
			<?php if ( $foto_id ) : ?>
				<?php echo wp_get_attachment_image( $foto_id, 'large', false, [
					'class'   => '',
					'loading' => 'eager',
					'itemprop'=> 'image',
				] ); ?>
			<?php elseif ( ! empty( $galeria_ids ) ) : ?>
				<?php echo wp_get_attachment_image( $galeria_ids[0], 'large', false, [ 'class' => '' ] ); ?>
			<?php else : ?>
				<div style="height:380px;background:linear-gradient(135deg,#dcebe4,#c8dcd4);display:flex;align-items:center;justify-content:center;font-size:80px;opacity:.4;">🏡</div>
			<?php endif; ?>
			<div class="gpa-ranch-hero-overlay">
				<h1 class="gpa-ranch-hero-name" itemprop="name"><?php the_title(); ?></h1>
				<?php if ( $cidade_nome ) : ?>
					<div class="gpa-ranch-hero-city">📍 <?php echo esc_html( $cidade_nome ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Galeria -->
		<?php if ( ! empty( $galeria_ids ) ) : ?>
		<div class="gpa-gallery" aria-label="Galeria de fotos">
			<?php foreach ( array_slice( $galeria_ids, 0, 8 ) as $gid ) : ?>
				<?php echo wp_get_attachment_image( $gid, 'medium', false, [
					'loading' => 'lazy',
					'alt'     => esc_attr( get_the_title() . ' — foto' ),
				] ); ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<!-- CTAs principais -->
		<?php if ( $wa_url || $maps_url ) : ?>
		<div class="gpa-ranch-cta">
			<?php if ( $wa_url ) : ?>
				<a class="gpa-cta-wa" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" data-ranch="<?php echo esc_attr( get_the_title() ); ?>">
					🟢 FALAR COM O RANCHO
				</a>
			<?php endif; ?>
			<?php if ( $maps_url ) : ?>
				<a class="gpa-cta-maps" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">
					📍 COMO CHEGAR
				</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<!-- Verificação -->
		<?php if ( $verificado ) : ?>
		<div class="gpa-verified-seal" style="margin-top:20px;">
			<span class="seal-icon">🟢</span>
			<div>
				<div class="seal-text">INFORMAÇÕES VERIFICADAS PELO PRADO AQUI</div>
				<?php if ( $data_ver ) : ?>
					<div class="seal-date">Última atualização: <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data_ver ) ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Info Grid -->
		<div class="gpa-ranch-info-grid">
			<!-- Coluna principal -->
			<div>
				<!-- Descrição -->
				<?php if ( get_the_excerpt() ) : ?>
				<div class="gpa-info-block" style="margin-bottom:20px;">
					<h3>Sobre o Rancho</h3>
					<div itemprop="description"><?php the_excerpt(); ?></div>
					<?php if ( get_the_content() ) : ?>
						<div><?php the_content(); ?></div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<!-- Preço -->
				<div class="gpa-info-block" style="margin-bottom:20px;">
					<h3>💰 Preço</h3>
					<p style="font-size:20px;font-weight:800;color:var(--g);margin:0 0 8px;">
						<?php echo esc_html( $preco ); ?>
					</p>
					<?php if ( $tipo_preco && $tipo_preco !== 'a_confirmar' ) :
						$tipos = [
							'diaria'     => 'Valor por diária completa',
							'por_pessoa' => 'Valor por pessoa',
							'pacote'     => 'Preço de pacote',
						];
						$tipo_label = $tipos[ $tipo_preco ] ?? '';
						if ( $tipo_label ) : ?>
						<p style="font-size:12px;color:var(--muted);margin:0 0 6px;"><?php echo esc_html( $tipo_label ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( $obs_preco ) : ?>
						<p style="font-size:13px;color:var(--muted);margin:0;padding-top:8px;border-top:1px solid var(--line);"><?php echo esc_html( $obs_preco ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Capacidade e Estrutura -->
				<div class="gpa-info-block" style="margin-bottom:20px;">
					<h3>👥 Capacidade e Acomodações</h3>
					<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px;">
						<?php
						$cap_items = [
							[ 'HÓSPEDES', get_post_meta( $post_id, '_gpa_capacidade', true ), '👥' ],
							[ 'QUARTOS',  get_post_meta( $post_id, '_gpa_quartos', true ),    '🛏️' ],
							[ 'SUÍTES',   get_post_meta( $post_id, '_gpa_suites', true ),     '✨' ],
							[ 'BANHEIROS',get_post_meta( $post_id, '_gpa_banheiros', true ),  '🚿' ],
						];
						foreach ( $cap_items as $ci ) :
							if ( ! $ci[1] ) continue;
						?>
						<div style="background:var(--bg);border:1px solid var(--line);border-radius:10px;padding:12px;text-align:center;">
							<div style="font-size:20px;"><?php echo $ci[2]; ?></div>
							<div style="font-size:20px;font-weight:900;color:var(--ink);"><?php echo esc_html( $ci[1] ); ?></div>
							<div style="font-size:9px;font-weight:800;color:var(--muted);letter-spacing:.8px;"><?php echo esc_html( $ci[0] ); ?></div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Estrutura -->
				<?php if ( ! empty( $estrutura ) ) : ?>
				<div class="gpa-info-block" style="margin-bottom:20px;">
					<h3>🏠 Estrutura</h3>
					<ul class="gpa-feature-list">
						<?php foreach ( $estrutura as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>

				<!-- Pesca -->
				<?php if ( ! empty( $pesca ) || $obs_pesca ) : ?>
				<div class="gpa-info-block" style="margin-bottom:20px;">
					<h3>🎣 Pesca</h3>
					<?php if ( ! empty( $pesca ) ) : ?>
					<ul class="gpa-feature-list" style="margin-bottom:<?php echo $obs_pesca ? '14px' : '0'; ?>">
						<?php foreach ( $pesca as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					<?php if ( $obs_pesca ) : ?>
						<p style="font-size:13px;color:var(--muted);margin:0;"><?php echo esc_html( $obs_pesca ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<!-- Localização -->
				<?php if ( $endereco || $maps_url ) : ?>
				<div class="gpa-info-block">
					<h3>📍 Localização</h3>
					<?php if ( $endereco ) : ?>
						<p style="margin:0 0 12px;color:var(--ink);" itemprop="address"><?php echo esc_html( $endereco ); ?></p>
					<?php endif; ?>
					<?php if ( $maps_url ) : ?>
						<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="gpa-cta-maps" style="display:inline-flex;max-width:200px;">
							📍 Abrir no Google Maps
						</a>
					<?php endif; ?>
					<?php if ( $lat && $lng ) : ?>
						<p style="font-size:11px;color:var(--muted);margin:10px 0 0;">
							Coordenadas: <?php echo esc_html( $lat ); ?>, <?php echo esc_html( $lng ); ?>
						</p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Sidebar -->
			<div>
				<!-- Notas -->
				<div class="gpa-info-block" style="margin-bottom:16px;">
					<h3>⭐ Avaliações</h3>
					<?php if ( $nota !== null ) : ?>
					<div class="gpa-nota-destaque">
						<div class="gpa-nota-number"><?php echo esc_html( number_format( $nota, 1 ) ); ?></div>
						<div class="gpa-nota-label">Nota Prado Aqui</div>
					</div>
					<?php endif; ?>
					<?php if ( $nota_google ) : ?>
					<div style="display:flex;align-items:center;gap:8px;padding:10px;background:var(--bg);border-radius:10px;margin-top:10px;">
						<span style="font-size:20px;">🔍</span>
						<div>
							<div style="font-size:18px;font-weight:800;"><?php echo esc_html( $nota_google ); ?>/5</div>
							<div style="font-size:11px;color:var(--muted);">Google
								<?php if ( $avl_google ) : ?>
									• <?php echo esc_html( $avl_google ); ?> avaliações
								<?php endif; ?>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php if ( $nota === null && ! $nota_google ) : ?>
						<p style="color:var(--muted);font-size:13px;margin:0;">Avaliação em breve.</p>
					<?php endif; ?>
				</div>

				<!-- Contato -->
				<div class="gpa-info-block" style="margin-bottom:16px;">
					<h3>📱 Contato</h3>
					<?php if ( $wa_url ) : ?>
					<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="gpa-cta-wa" style="font-size:14px;padding:14px 16px;border-radius:12px;margin-bottom:10px;display:flex;">
						🟢 WhatsApp
					</a>
					<?php endif; ?>
					<?php if ( $instagram ) : ?>
					<a href="https://instagram.com/<?php echo esc_attr( ltrim( $instagram, '@' ) ); ?>" target="_blank" rel="noopener noreferrer" style="display:block;padding:10px;background:var(--bg);border-radius:10px;text-decoration:none;color:var(--ink);font-weight:700;font-size:13px;margin-bottom:8px;">
						📸 <?php echo esc_html( $instagram ); ?>
					</a>
					<?php endif; ?>
					<?php if ( $site ) : ?>
					<a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener noreferrer" style="display:block;padding:10px;background:var(--bg);border-radius:10px;text-decoration:none;color:var(--b);font-weight:700;font-size:13px;">
						🌐 Visitar site
					</a>
					<?php endif; ?>
					<?php if ( ! $wa_url && ! $instagram && ! $site ) : ?>
						<p style="color:var(--muted);font-size:13px;margin:0;">Informações de contato sendo confirmadas.</p>
					<?php endif; ?>
				</div>

				<!-- Cidade -->
				<?php if ( $cidade ) : ?>
				<div class="gpa-info-block">
					<h3>🏙️ Localização</h3>
					<a href="<?php echo esc_url( get_term_link( $cidade ) ); ?>" style="display:block;padding:12px;background:var(--g-l);border-radius:10px;text-decoration:none;color:var(--g);font-weight:800;font-size:14px;text-align:center;">
						<?php echo esc_html( $cidade->name ); ?>
						<span style="display:block;font-size:11px;color:var(--muted);font-weight:600;margin-top:3px;">Ver todos os ranchos desta cidade →</span>
					</a>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Nota: dados não verificados -->
		<?php if ( ! $verificado ) : ?>
		<div style="background:#fff9e8;border:1px solid #f1dda0;border-radius:12px;padding:14px 18px;margin-top:20px;font-size:13px;color:#66520b;">
			⚠️ <strong>Informações em processo de verificação.</strong> Os dados deste rancho ainda não foram confirmados pelo Guia Prado Aqui. Entre em contato diretamente para confirmar disponibilidade e valores.
		</div>
		<?php endif; ?>

</div><!-- .gpa-ranch-page -->

<?php
get_footer();

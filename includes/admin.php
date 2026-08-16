<?php
/**
 * Painel administrativo do plugin Guia Prado Aqui
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Menu Principal ───────────────────────────────────────────────────────────
add_action( 'admin_menu', 'gpa_register_admin_menu' );

function gpa_register_admin_menu(): void {
	// Menu principal
	add_menu_page(
		__( 'Guia Prado Aqui', 'guia-prado-aqui' ),
		__( 'Guia Prado Aqui', 'guia-prado-aqui' ),
		GPA_CAP,
		'guia-prado-aqui',
		'gpa_page_dashboard',
		'dashicons-location-alt'
	);

	// Dashboard
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Dashboard', 'guia-prado-aqui' ),
		__( '📊 Dashboard', 'guia-prado-aqui' ),
		GPA_CAP,
		'guia-prado-aqui',
		'gpa_page_dashboard'
	);

	// Ranchos (aponta para CPT)
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Ranchos', 'guia-prado-aqui' ),
		__( '🏡 Ranchos', 'guia-prado-aqui' ),
		GPA_CAP,
		'edit.php?post_type=' . GPA_CPT
	);

	// Adicionar Rancho
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Adicionar Rancho', 'guia-prado-aqui' ),
		__( '➕ Adicionar Rancho', 'guia-prado-aqui' ),
		GPA_CAP,
		'post-new.php?post_type=' . GPA_CPT
	);

	// Cidades
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Cidades', 'guia-prado-aqui' ),
		__( '🏙️ Cidades', 'guia-prado-aqui' ),
		GPA_CAP,
		'edit-tags.php?taxonomy=' . GPA_TAXONOMY . '&post_type=' . GPA_CPT
	);

	// Verificações
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Verificações', 'guia-prado-aqui' ),
		__( '✅ Verificações', 'guia-prado-aqui' ),
		GPA_CAP,
		'gpa-verificacoes',
		'gpa_page_verificacoes'
	);

	// Importar
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Importar Base', 'guia-prado-aqui' ),
		__( '⬆️ Importar Base', 'guia-prado-aqui' ),
		GPA_CAP,
		'gpa-importar',
		'gpa_page_importar'
	);

	// Configurações
	add_submenu_page(
		'guia-prado-aqui',
		__( 'Configurações', 'guia-prado-aqui' ),
		__( '⚙️ Configurações', 'guia-prado-aqui' ),
		GPA_CAP,
		'gpa-configuracoes',
		'gpa_page_configuracoes'
	);
}

// ─── Destacar menu pai para CPT e taxonomia ───────────────────────────────────
add_filter( 'parent_file',      'gpa_parent_file_fix' );
add_filter( 'submenu_file',     'gpa_submenu_file_fix' );

function gpa_parent_file_fix( string $parent_file ): string {
	global $current_screen;
	if ( ! isset( $current_screen ) || ! is_a( $current_screen, 'WP_Screen' ) ) {
		return $parent_file;
	}
	if ( in_array( $current_screen->post_type, [ GPA_CPT ], true ) ||
	     $current_screen->taxonomy === GPA_TAXONOMY ) {
		return 'guia-prado-aqui';
	}
	return $parent_file;
}

function gpa_submenu_file_fix( ?string $submenu_file ): ?string {
	global $current_screen, $pagenow;
	if ( ! isset( $current_screen ) || ! is_a( $current_screen, 'WP_Screen' ) ) {
		return $submenu_file;
	}
	if ( $current_screen->post_type === GPA_CPT ) {
		if ( $pagenow === 'post-new.php' ) {
			return 'post-new.php?post_type=' . GPA_CPT;
		}
		return 'edit.php?post_type=' . GPA_CPT;
	}
	if ( $current_screen->taxonomy === GPA_TAXONOMY ) {
		return 'edit-tags.php?taxonomy=' . GPA_TAXONOMY . '&post_type=' . GPA_CPT;
	}
	return $submenu_file;
}

// ─── Página: Dashboard ────────────────────────────────────────────────────────
function gpa_page_dashboard(): void {
	if ( ! current_user_can( GPA_CAP ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'guia-prado-aqui' ) );
	}

	// Estatísticas
	$total = wp_count_posts( GPA_CPT );
	$publicados = (int) ( $total->publish ?? 0 );
	$rascunhos  = (int) ( $total->draft ?? 0 );
	$privados   = (int) ( $total->private ?? 0 );
	$todos      = $publicados + $rascunhos + $privados;

	// Contagens por status de verificação
	$status_counts = [];
	foreach ( [ 'pendente', 'em_verificacao', 'verificado', 'precisa_atualizacao', 'desativado' ] as $s ) {
		$q = new WP_Query( [
			'post_type'      => GPA_CPT,
			'post_status'    => 'any',
			'meta_key'       => '_gpa_status_verificacao',
			'meta_value'     => $s,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		$status_counts[ $s ] = $q->found_posts;
	}

	// Sem status (pendentes implícitos)
	$sem_status_q = new WP_Query( [
		'post_type'      => GPA_CPT,
		'post_status'    => 'any',
		'meta_query'     => [
			[
				'key'     => '_gpa_status_verificacao',
				'compare' => 'NOT EXISTS',
			],
		],
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );
	$status_counts['pendente'] += $sem_status_q->found_posts;

	// Ranchos recentes
	$recentes = get_posts( [
		'post_type'      => GPA_CPT,
		'post_status'    => 'any',
		'posts_per_page' => 5,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	// Cidades
	$cidades = get_terms( [
		'taxonomy'   => GPA_TAXONOMY,
		'hide_empty' => false,
	] );
	$total_cidades = is_array( $cidades ) ? count( $cidades ) : 0;

	?>
	<div class="wrap gpa-wrap">
		<div class="gpa-page-header">
			<div class="gpa-page-header-inner">
				<div class="gpa-logo">🎣</div>
				<div>
					<h1 class="gpa-page-title">Guia Prado Aqui</h1>
					<p class="gpa-page-sub">Painel Administrativo — v<?php echo esc_html( GPA_VERSION ); ?></p>
				</div>
			</div>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . GPA_CPT ) ); ?>" class="gpa-btn-primary">
				➕ Cadastrar Rancho
			</a>
		</div>

		<!-- Stats Cards -->
		<div class="gpa-stats-grid">
			<div class="gpa-stat-card gpa-stat-total">
				<span class="gpa-stat-num"><?php echo esc_html( $todos ); ?></span>
				<span class="gpa-stat-label">Total de Ranchos</span>
			</div>
			<div class="gpa-stat-card gpa-stat-publicados">
				<span class="gpa-stat-num"><?php echo esc_html( $publicados ); ?></span>
				<span class="gpa-stat-label">Publicados</span>
			</div>
			<div class="gpa-stat-card gpa-stat-verificados">
				<span class="gpa-stat-num"><?php echo esc_html( $status_counts['verificado'] ?? 0 ); ?></span>
				<span class="gpa-stat-label">✅ Verificados</span>
			</div>
			<div class="gpa-stat-card gpa-stat-pendentes">
				<span class="gpa-stat-num"><?php echo esc_html( $status_counts['pendente'] ?? 0 ); ?></span>
				<span class="gpa-stat-label">🔴 Pendentes</span>
			</div>
			<div class="gpa-stat-card gpa-stat-verificando">
				<span class="gpa-stat-num"><?php echo esc_html( $status_counts['em_verificacao'] ?? 0 ); ?></span>
				<span class="gpa-stat-label">🟡 Em verificação</span>
			</div>
			<div class="gpa-stat-card gpa-stat-atualizacao">
				<span class="gpa-stat-num"><?php echo esc_html( $status_counts['precisa_atualizacao'] ?? 0 ); ?></span>
				<span class="gpa-stat-label">⚠️ Precisa atualização</span>
			</div>
			<div class="gpa-stat-card gpa-stat-cidades">
				<span class="gpa-stat-num"><?php echo esc_html( $total_cidades ); ?></span>
				<span class="gpa-stat-label">🏙️ Cidades</span>
			</div>
			<div class="gpa-stat-card gpa-stat-rascunhos">
				<span class="gpa-stat-num"><?php echo esc_html( $rascunhos ); ?></span>
				<span class="gpa-stat-label">📝 Rascunhos</span>
			</div>
		</div>

		<!-- Fluxo do Prado -->
		<div class="gpa-section">
			<h2 class="gpa-section-title">📋 Fluxo de Verificação</h2>
			<div class="gpa-fluxo">
				<?php
				$etapas = [
					[ '1', 'Encontrar', 'Identificar rancho novo' ],
					[ '2', 'Cadastrar', 'Criar post no sistema' ],
					[ '3', 'Ligar', 'Entrar em contato' ],
					[ '4', 'Confirmar', 'Telefone, preço, estrutura' ],
					[ '5', 'Fotos', 'Adicionar imagens' ],
					[ '6', 'Avaliar', 'Preencher nota Prado Aqui' ],
					[ '7', 'Verificar', 'Marcar como verificado' ],
					[ '8', 'Publicar', 'Publicar no catálogo' ],
				];
				foreach ( $etapas as $e ) {
					echo '<div class="gpa-etapa">';
					echo '<span class="gpa-etapa-num">' . esc_html( $e[0] ) . '</span>';
					echo '<strong>' . esc_html( $e[1] ) . '</strong>';
					echo '<small>' . esc_html( $e[2] ) . '</small>';
					echo '</div>';
				}
				?>
			</div>
		</div>

		<!-- Ranchos Recentes -->
		<div class="gpa-section">
			<div class="gpa-section-header">
				<h2 class="gpa-section-title">⏰ Ranchos Recentes</h2>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . GPA_CPT ) ); ?>" class="gpa-btn-outline">Ver todos</a>
			</div>
			<?php if ( $recentes ) : ?>
			<table class="gpa-table">
				<thead>
					<tr>
						<th>Rancho</th>
						<th>Cidade</th>
						<th>Verificação</th>
						<th>Status WP</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recentes as $r ) :
						$s       = get_post_meta( $r->ID, '_gpa_status_verificacao', true ) ?: 'pendente';
						$cor     = gpa_get_status_color( $s );
						$cidade  = gpa_get_cidade_nome( $r->ID );
					?>
					<tr>
						<td><strong><?php echo esc_html( $r->post_title ); ?></strong></td>
						<td><?php echo esc_html( $cidade ?: '—' ); ?></td>
						<td>
							<span style="background:<?php echo esc_attr( $cor['bg'] ); ?>;color:<?php echo esc_attr( $cor['text'] ); ?>;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700;">
								<?php echo esc_html( gpa_get_status_label( $s ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( get_post_status( $r->ID ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $r->ID ) ); ?>" class="gpa-btn-sm">✏️ Editar</a>
							<a href="<?php echo esc_url( get_permalink( $r->ID ) ); ?>" class="gpa-btn-sm" target="_blank">👁 Ver</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<div class="gpa-empty">
				<p>Nenhum rancho cadastrado ainda. <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . GPA_CPT ) ); ?>">Cadastre o primeiro!</a></p>
			</div>
			<?php endif; ?>
		</div>

		<!-- Atalhos -->
		<div class="gpa-atalhos">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gpa-verificacoes' ) ); ?>" class="gpa-atalho">
				<span>✅</span> Fila de Verificação
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gpa-importar' ) ); ?>" class="gpa-atalho">
				<span>⬆️</span> Importar Base
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . GPA_TAXONOMY . '&post_type=' . GPA_CPT ) ); ?>" class="gpa-atalho">
				<span>🏙️</span> Gerenciar Cidades
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gpa-configuracoes' ) ); ?>" class="gpa-atalho">
				<span>⚙️</span> Configurações
			</a>
		</div>
	</div>
	<?php
}

// ─── Página: Verificações ─────────────────────────────────────────────────────
function gpa_page_verificacoes(): void {
	if ( ! current_user_can( GPA_CAP ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'guia-prado-aqui' ) );
	}

	$filtro_status = sanitize_key( $_GET['status'] ?? '' );
	$filtro_cidade = absint( $_GET['cidade'] ?? 0 );
	$busca         = sanitize_text_field( wp_unslash( $_GET['busca'] ?? '' ) );

	$args = [
		'post_type'      => GPA_CPT,
		'post_status'    => 'any',
		'posts_per_page' => 50,
		'meta_query'     => [],
	];

	if ( $filtro_status ) {
		$args['meta_query'][] = [
			'key'   => '_gpa_status_verificacao',
			'value' => $filtro_status,
		];
	}

	if ( $filtro_cidade ) {
		$args['tax_query'] = [
			[
				'taxonomy' => GPA_TAXONOMY,
				'field'    => 'term_id',
				'terms'    => $filtro_cidade,
			],
		];
	}

	if ( $busca ) {
		$args['s'] = $busca;
	}

	$query = new WP_Query( $args );
	$cidades_all = get_terms( [ 'taxonomy' => GPA_TAXONOMY, 'hide_empty' => false ] );

	?>
	<div class="wrap gpa-wrap">
		<div class="gpa-page-header">
			<div class="gpa-page-header-inner">
				<div class="gpa-logo">✅</div>
				<div>
					<h1 class="gpa-page-title">Fila de Verificação</h1>
					<p class="gpa-page-sub">Acompanhe e atualize o status de verificação dos ranchos.</p>
				</div>
			</div>
		</div>

		<!-- Filtros -->
		<form method="GET" class="gpa-filter-form">
			<input type="hidden" name="page" value="gpa-verificacoes">
			<div class="gpa-filter-row">
				<input type="search" name="busca" placeholder="🔎 Buscar rancho..." value="<?php echo esc_attr( $busca ); ?>" class="gpa-input">
				<select name="status" class="gpa-select">
					<option value=""><?php esc_html_e( 'Todos os status', 'guia-prado-aqui' ); ?></option>
					<option value="pendente"            <?php selected( $filtro_status, 'pendente' ); ?>>🔴 Pendente</option>
					<option value="em_verificacao"      <?php selected( $filtro_status, 'em_verificacao' ); ?>>🟡 Em verificação</option>
					<option value="verificado"          <?php selected( $filtro_status, 'verificado' ); ?>>🟢 Verificado</option>
					<option value="precisa_atualizacao" <?php selected( $filtro_status, 'precisa_atualizacao' ); ?>>⚠️ Precisa atualização</option>
					<option value="desativado"          <?php selected( $filtro_status, 'desativado' ); ?>>⚫ Desativado</option>
				</select>
				<select name="cidade" class="gpa-select">
					<option value=""><?php esc_html_e( 'Todas as cidades', 'guia-prado-aqui' ); ?></option>
					<?php if ( ! is_wp_error( $cidades_all ) ) : ?>
						<?php foreach ( $cidades_all as $c ) : ?>
							<option value="<?php echo esc_attr( $c->term_id ); ?>"<?php selected( $filtro_cidade, $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
				<button type="submit" class="gpa-btn-primary">Filtrar</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=gpa-verificacoes' ) ); ?>" class="gpa-btn-outline">Limpar</a>
			</div>
		</form>

		<p class="gpa-result-count">
			<?php printf(
				esc_html( _n( '%d rancho encontrado', '%d ranchos encontrados', $query->found_posts, 'guia-prado-aqui' ) ),
				esc_html( $query->found_posts )
			); ?>
		</p>

		<?php if ( $query->have_posts() ) : ?>
		<table class="gpa-table gpa-table-verificacoes">
			<thead>
				<tr>
					<th>#</th>
					<th>Rancho</th>
					<th>Cidade</th>
					<th>Telefone</th>
					<th>Preço</th>
					<th>Confirmações</th>
					<th>Status</th>
					<th>Última verificação</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $query->have_posts() ) : $query->the_post();
					global $post;
					$id           = $post->ID;
					$status       = get_post_meta( $id, '_gpa_status_verificacao', true ) ?: 'pendente';
					$cor          = gpa_get_status_color( $status );
					$tel          = get_post_meta( $id, '_gpa_telefone', true );
					$preco        = get_post_meta( $id, '_gpa_preco', true );
					$tipo_preco   = get_post_meta( $id, '_gpa_tipo_preco', true );
					$data_ver     = get_post_meta( $id, '_gpa_data_verificacao', true );
					$tel_ok       = get_post_meta( $id, '_gpa_tel_confirmado', true );
					$preco_ok     = get_post_meta( $id, '_gpa_preco_confirmado', true );
					$struct_ok    = get_post_meta( $id, '_gpa_estrutura_confirmada', true );
					$fotos_ok     = get_post_meta( $id, '_gpa_fotos_confirmadas', true );
					$cidade_nome  = gpa_get_cidade_nome( $id );
					$id_original  = get_post_meta( $id, '_gpa_id_original', true );
				?>
				<tr class="gpa-ver-row" data-id="<?php echo esc_attr( $id ); ?>">
					<td class="gpa-cell-num">
						<?php echo $id_original ? '<small>#' . esc_html( $id_original ) . '</small>' : '<small>—</small>'; ?>
					</td>
					<td>
						<strong><?php the_title(); ?></strong>
					</td>
					<td><?php echo esc_html( $cidade_nome ?: '—' ); ?></td>
					<td>
						<?php if ( $tel ) :
							$tel_clean = preg_replace( '/\D/', '', $tel );
						?>
							<a href="https://wa.me/<?php echo esc_attr( $tel_clean ); ?>" target="_blank" style="color:#25d366;font-weight:700;">
								📱 <?php echo esc_html( $tel ); ?>
							</a>
						<?php else : ?>
							<span class="gpa-missing">Sem telefone</span>
						<?php endif; ?>
					</td>
					<td>
						<?php
						if ( $preco ) {
							echo 'R$ ' . esc_html( $preco );
							if ( $tipo_preco && $tipo_preco !== 'a_confirmar' ) {
								$tipos = [ 'diaria' => '/diária', 'por_pessoa' => '/pessoa', 'pacote' => '(pacote)' ];
								echo ' ' . esc_html( $tipos[ $tipo_preco ] ?? '' );
							}
						} else {
							echo '<span class="gpa-missing">A confirmar</span>';
						}
						?>
					</td>
					<td class="gpa-confirmacoes-cell">
						<span class="gpa-conf <?php echo $tel_ok ? 'ok' : 'no'; ?>" title="Telefone">📱</span>
						<span class="gpa-conf <?php echo $preco_ok ? 'ok' : 'no'; ?>" title="Preço">💰</span>
						<span class="gpa-conf <?php echo $struct_ok ? 'ok' : 'no'; ?>" title="Estrutura">🏠</span>
						<span class="gpa-conf <?php echo $fotos_ok ? 'ok' : 'no'; ?>" title="Fotos">📷</span>
					</td>
					<td>
						<select class="gpa-status-select gpa-quick-status" data-post-id="<?php echo esc_attr( $id ); ?>">
							<option value="pendente"            <?php selected( $status, 'pendente' ); ?>>🔴 Pendente</option>
							<option value="em_verificacao"      <?php selected( $status, 'em_verificacao' ); ?>>🟡 Em verificação</option>
							<option value="verificado"          <?php selected( $status, 'verificado' ); ?>>🟢 Verificado</option>
							<option value="precisa_atualizacao" <?php selected( $status, 'precisa_atualizacao' ); ?>>⚠️ Atualizar</option>
							<option value="desativado"          <?php selected( $status, 'desativado' ); ?>>⚫ Desativado</option>
						</select>
					</td>
					<td>
						<?php echo $data_ver ? esc_html( date_i18n( 'd/m/Y', strtotime( $data_ver ) ) ) : '<span class="gpa-missing">—</span>'; ?>
					</td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>" class="gpa-btn-sm">✏️</a>
						<?php if ( $tel ) : ?>
						<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $tel ) ); ?>" target="_blank" class="gpa-btn-sm gpa-btn-wa">💬</a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endwhile; wp_reset_postdata(); ?>
			</tbody>
		</table>
		<?php else : ?>
		<div class="gpa-empty">
			<p>Nenhum rancho encontrado com os filtros selecionados.</p>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

// ─── Página: Configurações ────────────────────────────────────────────────────
function gpa_page_configuracoes(): void {
	if ( ! current_user_can( GPA_CAP ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'guia-prado-aqui' ) );
	}

	// Salvar
	if ( isset( $_POST['gpa_save_settings'] ) && check_admin_referer( 'gpa_settings_save' ) ) {
		$settings = [
			'slug_rancho'       => sanitize_title( wp_unslash( $_POST['slug_rancho'] ?? 'rancho' ) ),
			'slug_cidade'       => sanitize_title( wp_unslash( $_POST['slug_cidade'] ?? 'cidade' ) ),
			'exige_verificado'  => ! empty( $_POST['exige_verificado'] ),
			'editor_can_manage' => ! empty( $_POST['editor_can_manage'] ),
			'wa_mensagem'       => sanitize_textarea_field( wp_unslash( $_POST['wa_mensagem'] ?? '' ) ),
			'pagina_catalogo'   => absint( $_POST['pagina_catalogo'] ?? 0 ),
		];
		update_option( 'gpa_settings', $settings );

		// Reconfigura permissões se habilitou editor
		gpa_add_capabilities();

		// Flush rewrite rules
		gpa_register_post_type();
		gpa_register_taxonomy();
		flush_rewrite_rules();

		echo '<div class="notice notice-success"><p>✅ Configurações salvas com sucesso!</p></div>';
	}

	$settings = get_option( 'gpa_settings', [] );
	$pages    = get_pages();

	?>
	<div class="wrap gpa-wrap">
		<div class="gpa-page-header">
			<div class="gpa-page-header-inner">
				<div class="gpa-logo">⚙️</div>
				<div>
					<h1 class="gpa-page-title">Configurações</h1>
					<p class="gpa-page-sub">Configure o comportamento do Guia Prado Aqui.</p>
				</div>
			</div>
		</div>

		<form method="POST" class="gpa-settings-form">
			<?php wp_nonce_field( 'gpa_settings_save' ); ?>

			<div class="gpa-settings-section">
				<h2>🔗 URLs e Slugs</h2>
				<p class="gpa-hint">⚠️ Alterar os slugs requer que você salve e depois vá em <strong>Configurações → Links Permanentes → Salvar</strong>.</p>
				<div class="gpa-settings-grid">
					<div class="gpa-field">
						<label for="slug_rancho">Slug do rancho</label>
						<input type="text" id="slug_rancho" name="slug_rancho" value="<?php echo esc_attr( $settings['slug_rancho'] ?? 'rancho' ); ?>" placeholder="rancho">
						<small>URL: /rancho/nome-do-rancho</small>
					</div>
					<div class="gpa-field">
						<label for="slug_cidade">Slug da cidade</label>
						<input type="text" id="slug_cidade" name="slug_cidade" value="<?php echo esc_attr( $settings['slug_cidade'] ?? 'cidade' ); ?>" placeholder="cidade">
						<small>URL: /cidade/tres-marias</small>
					</div>
				</div>
			</div>

			<div class="gpa-settings-section">
				<h2>📄 Página do Catálogo</h2>
				<div class="gpa-field">
					<label for="pagina_catalogo">Página onde o catálogo é exibido</label>
					<select id="pagina_catalogo" name="pagina_catalogo">
						<option value="">— Selecione uma página —</option>
						<?php foreach ( $pages as $p ) : ?>
							<option value="<?php echo esc_attr( $p->ID ); ?>"<?php selected( $settings['pagina_catalogo'] ?? 0, $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<small>Use o shortcode <code>[guia_prado_catalogo]</code> na página escolhida.</small>
				</div>
			</div>

			<div class="gpa-settings-section">
				<h2>🔍 Visibilidade Pública</h2>
				<label class="gpa-checkbox">
					<input type="checkbox" name="exige_verificado" value="1"<?php checked( ! empty( $settings['exige_verificado'] ) ); ?>>
					Exibir somente ranchos com status <strong>Verificado</strong> no catálogo público
				</label>
				<p class="gpa-hint">Se desmarcado, exibe todos os ranchos <em>publicados</em> (exceto desativados).</p>
			</div>

			<div class="gpa-settings-section">
				<h2>🔐 Permissões</h2>
				<label class="gpa-checkbox">
					<input type="checkbox" name="editor_can_manage" value="1"<?php checked( ! empty( $settings['editor_can_manage'] ) ); ?>>
					Permitir que usuários com papel <strong>Editor</strong> gerenciem ranchos
				</label>
			</div>

			<div class="gpa-settings-section">
				<h2>💬 Mensagem WhatsApp</h2>
				<div class="gpa-field gpa-field-full">
					<label for="wa_mensagem">Mensagem padrão ao abrir WhatsApp do rancho</label>
					<textarea id="wa_mensagem" name="wa_mensagem" rows="3"><?php echo esc_textarea( $settings['wa_mensagem'] ?? 'Olá! Encontrei o seu rancho no Guia Prado Aqui e gostaria de saber sobre disponibilidade e valores.' ); ?></textarea>
				</div>
			</div>

			<div class="gpa-settings-section gpa-settings-perms">
				<h2>👥 Usuários com Acesso</h2>
				<?php
				$users = get_users( [ 'number' => 50 ] );
				echo '<table class="gpa-table"><thead><tr><th>Usuário</th><th>E-mail</th><th>Papel</th><th>Acesso Guia</th><th>Ação</th></tr></thead><tbody>';
				foreach ( $users as $u ) {
					$has_cap = user_can( $u->ID, GPA_CAP );
					$roles   = implode( ', ', $u->roles );
					echo '<tr>';
					echo '<td><strong>' . esc_html( $u->display_name ) . '</strong></td>';
					echo '<td>' . esc_html( $u->user_email ) . '</td>';
					echo '<td>' . esc_html( $roles ) . '</td>';
					echo '<td>' . ( $has_cap ? '<span style="color:#087443;font-weight:700;">✅ Sim</span>' : '<span style="color:#aaa;">Não</span>' ) . '</td>';
					echo '<td>';
					if ( $has_cap && ! in_array( 'administrator', $u->roles ) ) {
						echo '<button type="button" class="gpa-btn-sm gpa-cap-toggle" data-user="' . esc_attr( $u->ID ) . '" data-action="revoke">Revogar acesso</button>';
					} elseif ( ! $has_cap ) {
						echo '<button type="button" class="gpa-btn-sm gpa-cap-toggle" data-user="' . esc_attr( $u->ID ) . '" data-action="grant">Conceder acesso</button>';
					}
					echo '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
				?>
			</div>

			<div class="gpa-settings-footer">
				<button type="submit" name="gpa_save_settings" class="gpa-btn-primary">
					💾 Salvar Configurações
				</button>
			</div>
		</form>
	</div>
	<?php
}

// ─── Página: Importar (delegar para includes/import.php) ──────────────────────
function gpa_page_importar(): void {
	if ( ! current_user_can( GPA_CAP ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'guia-prado-aqui' ) );
	}
	gpa_render_import_page();
}

// ─── Notices de boas-vindas ───────────────────────────────────────────────────
add_action( 'admin_notices', 'gpa_admin_notices' );

function gpa_admin_notices(): void {
	global $pagenow, $post_type;

	// Só mostra no contexto do plugin
	$current_page = sanitize_key( $_GET['page'] ?? '' );
	if ( $pagenow !== 'admin.php' || $current_page !== 'guia-prado-aqui' ) {
		return;
	}

	// Verifica se já importou
	$total = wp_count_posts( GPA_CPT );
	$todos = (int) ( $total->publish ?? 0 ) + (int) ( $total->draft ?? 0 );

	if ( $todos === 0 ) {
		echo '<div class="notice notice-warning"><p>';
		printf(
			esc_html__( '👋 Bem-vindo ao Guia Prado Aqui! Você ainda não tem ranchos cadastrados. %s', 'guia-prado-aqui' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=gpa-importar' ) ) . '"><strong>Importe a base de 67 ranchos agora</strong></a>'
		);
		echo '</p></div>';
	}
}

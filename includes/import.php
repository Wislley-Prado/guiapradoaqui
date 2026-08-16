<?php
/**
 * Ferramenta de importação dos 67 ranchos originais
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Renderiza a página de importação ────────────────────────────────────────
function gpa_render_import_page(): void {
	$json_file = GPA_PLUGIN_DIR . 'data/ranchos-base.json';
	$json_ok   = file_exists( $json_file );

	// Processa importação ao submeter
	$log = [];
	if ( isset( $_POST['gpa_run_import'] ) && check_admin_referer( 'gpa_run_import' ) ) {
		if ( $json_ok ) {
			$log = gpa_run_import_from_json( $json_file );
		}
	}

	// Processa importação de arquivo externo
	if ( isset( $_POST['gpa_import_file'] ) && check_admin_referer( 'gpa_import_file' ) ) {
		if ( ! empty( $_FILES['import_json']['tmp_name'] ) ) {
			$log = gpa_run_import_from_upload( $_FILES['import_json']['tmp_name'] );
		}
	}

	// Estatísticas atuais
	$total_wp = wp_count_posts( GPA_CPT );
	$todos    = (int) ( $total_wp->publish ?? 0 ) + (int) ( $total_wp->draft ?? 0 );

	?>
	<div class="wrap gpa-wrap">
		<div class="gpa-page-header">
			<div class="gpa-page-header-inner">
				<div class="gpa-logo">⬆️</div>
				<div>
					<h1 class="gpa-page-title">Importar Base</h1>
					<p class="gpa-page-sub">Importe os 67 ranchos da base original ou adicione novos via CSV/JSON.</p>
				</div>
			</div>
		</div>

		<!-- Log de resultado -->
		<?php if ( ! empty( $log ) ) : ?>
		<div class="gpa-import-log">
			<h3>📋 Resultado da Importação</h3>
			<div class="gpa-log-stats">
				<?php
				$criados    = count( array_filter( $log, function( $l ) { return $l['status'] === 'created'; } ) );
				$atualizados= count( array_filter( $log, function( $l ) { return $l['status'] === 'updated'; } ) );
				$erros      = count( array_filter( $log, function( $l ) { return $l['status'] === 'error'; } ) );
				$ignorados  = count( array_filter( $log, function( $l ) { return $l['status'] === 'skipped'; } ) );
				?>
				<span class="gpa-log-stat gpa-log-created">✅ <?php echo esc_html( $criados ); ?> criados</span>
				<span class="gpa-log-stat gpa-log-updated">🔄 <?php echo esc_html( $atualizados ); ?> atualizados</span>
				<span class="gpa-log-stat gpa-log-skipped">⏭️ <?php echo esc_html( $ignorados ); ?> ignorados</span>
				<span class="gpa-log-stat gpa-log-error">❌ <?php echo esc_html( $erros ); ?> erros</span>
			</div>
			<div class="gpa-log-items">
				<?php foreach ( $log as $l ) : ?>
				<div class="gpa-log-item gpa-log-<?php echo esc_attr( $l['status'] ); ?>">
					<span class="gpa-log-icon">
						<?php
						$status_icons = [
							'created'  => '✅',
							'updated'  => '🔄',
							'skipped'  => '⏭️',
							'error'    => '❌',
						];
						echo esc_html( $status_icons[ $l['status'] ] ?? '•' );
						?>
					</span>
					<span class="gpa-log-msg"><?php echo esc_html( $l['message'] ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Status Atual -->
		<div class="gpa-section">
			<h2 class="gpa-section-title">📊 Status Atual</h2>
			<div class="gpa-import-status">
				<div class="gpa-import-stat">
					<span class="gpa-stat-num"><?php echo esc_html( $todos ); ?></span>
					<span>Ranchos no WordPress</span>
				</div>
				<div class="gpa-import-stat">
					<span class="gpa-stat-num"><?php echo $json_ok ? '67' : '—'; ?></span>
					<span>Ranchos na base original</span>
				</div>
				<div class="gpa-import-stat">
					<span class="gpa-stat-num" style="color:<?php echo $json_ok ? '#087443' : '#c0392b'; ?>">
						<?php echo $json_ok ? '✅' : '❌'; ?>
					</span>
					<span>Arquivo base disponível</span>
				</div>
			</div>
		</div>

		<!-- Importar Base Padrão -->
		<div class="gpa-section">
			<h2 class="gpa-section-title">🗂️ Importar Base Padrão (67 ranchos)</h2>
			<?php if ( $json_ok ) : ?>
			<p>O arquivo <code>data/ranchos-base.json</code> contém os 67 ranchos originais. A importação:</p>
			<ul style="margin-left:24px;">
				<li>Cria ranchos ainda não existentes (baseado no campo <code>_gpa_id_original</code>)</li>
				<li>Ignora ranchos já importados (para não duplicar)</li>
				<li>Cria automaticamente as cidades como termos da taxonomia</li>
				<li>Preserva: nome, telefone, endereço, latitude, longitude</li>
				<li>Campos em branco ficam como "A confirmar" — <strong>nenhum dado inventado</strong></li>
			</ul>
			<form method="POST" style="margin-top:16px;">
				<?php wp_nonce_field( 'gpa_run_import' ); ?>
				<button type="submit" name="gpa_run_import" class="gpa-btn-primary" onclick="return confirm('Iniciar importação dos 67 ranchos da base padrão?')">
					🚀 Importar Base Padrão
				</button>
			</form>
			<?php else : ?>
			<div class="notice notice-error inline"><p>Arquivo <code>data/ranchos-base.json</code> não encontrado no plugin.</p></div>
			<?php endif; ?>
		</div>

		<!-- Importar arquivo externo -->
		<div class="gpa-section">
			<h2 class="gpa-section-title">📁 Importar Arquivo JSON Externo</h2>
			<p>Envie um arquivo JSON com o mesmo formato da base padrão para importar ranchos adicionais.</p>
			<form method="POST" enctype="multipart/form-data">
				<?php wp_nonce_field( 'gpa_import_file' ); ?>
				<div class="gpa-field" style="max-width:400px;">
					<label for="import_json">Arquivo JSON</label>
					<input type="file" id="import_json" name="import_json" accept=".json" required>
				</div>
				<button type="submit" name="gpa_import_file" class="gpa-btn-primary">
					⬆️ Importar Arquivo
				</button>
			</form>
		</div>

		<!-- Formato esperado -->
		<div class="gpa-section">
			<h2 class="gpa-section-title">📋 Formato do JSON</h2>
			<pre class="gpa-code"><?php echo esc_html( json_encode( [
				[
					'id'         => 1,
					'nome'       => 'Nome do Rancho',
					'cidade'     => 'Três Marias - MG',
					'endereco'   => 'Rua da Tilapia, 100',
					'telefone'   => '5538999000000',
					'latitude'   => '-18.1488564',
					'longitude'  => '-45.2259995',
					'imagem'     => '',
					'preco'      => '',
					'capacidade' => '',
					'nota'       => '',
					'pesca'      => '',
					'piscina'    => '',
					'rampa'      => '',
					'verificado' => false,
					'descricao'  => '',
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
		</div>

		<!-- Limpar importação -->
		<div class="gpa-section gpa-section-danger">
			<h2 class="gpa-section-title">⚠️ Área de Risco</h2>
			<p>Use com extremo cuidado. Esta ação não pode ser desfeita.</p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gpa-importar&gpa_action=reset_all' ), 'gpa_reset_all' ) ); ?>" class="gpa-btn-danger" onclick="return confirm('ATENÇÃO: Isso irá EXCLUIR TODOS os ranchos cadastrados. Tem certeza absoluta?')">
				🗑️ Excluir Todos os Ranchos
			</a>
		</div>
	</div>
	<?php
}

// ─── Processamento: Reset All ─────────────────────────────────────────────────
add_action( 'admin_init', 'gpa_handle_reset_all' );

function gpa_handle_reset_all(): void {
	if (
		! isset( $_GET['gpa_action'] ) || $_GET['gpa_action'] !== 'reset_all' ||
		! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gpa_reset_all' )
	) {
		return;
	}

	if ( ! current_user_can( 'administrator' ) ) {
		wp_die( 'Apenas administradores podem executar esta ação.' );
	}

	$posts = get_posts( [
		'post_type'      => GPA_CPT,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );

	foreach ( $posts as $id ) {
		wp_delete_post( $id, true );
	}

	wp_redirect( admin_url( 'admin.php?page=gpa-importar&reset=1' ) );
	exit;
}

// ─── Funções de importação ────────────────────────────────────────────────────

/**
 * Executa a importação a partir do arquivo JSON da base padrão.
 *
 * @param string $file Caminho do arquivo JSON.
 * @return array<int, array{status: string, message: string}> Log de resultado.
 */
function gpa_run_import_from_json( string $file ): array {
	$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $content ) {
		return [ [ 'status' => 'error', 'message' => 'Não foi possível ler o arquivo.' ] ];
	}
	$data = json_decode( $content, true );
	if ( ! is_array( $data ) ) {
		return [ [ 'status' => 'error', 'message' => 'JSON inválido.' ] ];
	}
	return gpa_process_import( $data );
}

/**
 * Executa a importação de um arquivo enviado via upload.
 *
 * @param string $tmp_file Caminho temporário do upload.
 * @return array Log.
 */
function gpa_run_import_from_upload( string $tmp_file ): array {
	$content = file_get_contents( $tmp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $content ) {
		return [ [ 'status' => 'error', 'message' => 'Arquivo inválido.' ] ];
	}
	$data = json_decode( $content, true );
	if ( ! is_array( $data ) ) {
		return [ [ 'status' => 'error', 'message' => 'JSON inválido ou mal formatado.' ] ];
	}
	return gpa_process_import( $data );
}

/**
 * Processa o array de ranchos e cria/atualiza posts.
 *
 * @param array $ranchos Array de ranchos.
 * @return array Log.
 */
function gpa_process_import( array $ranchos ): array {
	$log = [];

	// Limite de tempo de execução
	@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

	foreach ( $ranchos as $r ) {
		if ( empty( $r['nome'] ) ) {
			$log[] = [ 'status' => 'error', 'message' => 'Registro sem nome ignorado.' ];
			continue;
		}

		$id_original = absint( $r['id'] ?? 0 );
		$nome        = sanitize_text_field( $r['nome'] );

		// Verifica se já existe
		$post_id = gpa_find_by_original_id( $id_original );

		if ( $post_id ) {
			// Já importado — ignorar para não sobrescrever edições manuais
			$log[] = [
				'status'  => 'skipped',
				'message' => "#{$id_original} — {$nome} já existe (ignorado para preservar edições).",
			];
			continue;
		}

		// Prepara cidade
		$cidade_str = sanitize_text_field( $r['cidade'] ?? '' );
		$cidade_str = str_replace( 'Cidade não informada na base', '', $cidade_str );
		$cidade_str = trim( $cidade_str );

		// Cria o post
		$post_data = [
			'post_title'   => $nome,
			'post_status'  => 'draft', // Sempre começa como rascunho
			'post_type'    => GPA_CPT,
			'post_excerpt' => sanitize_textarea_field( $r['descricao'] ?? '' ),
		];

		$new_post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $new_post_id ) ) {
			$log[] = [
				'status'  => 'error',
				'message' => "#{$id_original} — {$nome}: " . $new_post_id->get_error_message(),
			];
			continue;
		}

		// Meta fields
		update_post_meta( $new_post_id, '_gpa_id_original',         $id_original );
		update_post_meta( $new_post_id, '_gpa_telefone',            sanitize_text_field( $r['telefone'] ?? '' ) );
		update_post_meta( $new_post_id, '_gpa_endereco',            sanitize_text_field( $r['endereco'] ?? '' ) );
		update_post_meta( $new_post_id, '_gpa_latitude',            sanitize_text_field( $r['latitude'] ?? '' ) );
		update_post_meta( $new_post_id, '_gpa_longitude',           sanitize_text_field( $r['longitude'] ?? '' ) );
		update_post_meta( $new_post_id, '_gpa_status_verificacao',  'pendente' );

		// Campos opcionais da base
		if ( ! empty( $r['preco'] ) ) {
			update_post_meta( $new_post_id, '_gpa_preco', sanitize_text_field( $r['preco'] ) );
		}
		if ( ! empty( $r['capacidade'] ) ) {
			update_post_meta( $new_post_id, '_gpa_capacidade', sanitize_text_field( $r['capacidade'] ) );
		}
		if ( ! empty( $r['nota'] ) ) {
			update_post_meta( $new_post_id, '_gpa_nota_manual', gpa_sanitize_decimal_0_10( $r['nota'] ) );
			update_post_meta( $new_post_id, '_gpa_nota_final',  gpa_sanitize_decimal_0_10( $r['nota'] ) );
		}

		// Estrutura: piscina e rampa do campo boolean
		if ( ! empty( $r['piscina'] ) && $r['piscina'] !== '' && $r['piscina'] !== 'Não' ) {
			update_post_meta( $new_post_id, '_gpa_piscina', '1' );
		}
		if ( ! empty( $r['rampa'] ) && $r['rampa'] !== '' && $r['rampa'] !== 'Não' ) {
			update_post_meta( $new_post_id, '_gpa_rampa', '1' );
		}

		// Auto-gerar link Maps
		if ( ! empty( $r['latitude'] ) && ! empty( $r['longitude'] ) ) {
			$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . $r['latitude'] . ',' . $r['longitude'];
			update_post_meta( $new_post_id, '_gpa_link_maps', $maps_url );
		}

		// Imagem (URL)
		if ( ! empty( $r['imagem'] ) ) {
			update_post_meta( $new_post_id, '_gpa_imagem_url_temp', esc_url_raw( $r['imagem'] ) );
		}

		// Cidade: cria o term se necessário e associa
		if ( $cidade_str ) {
			// Extrai só o nome da cidade (antes do " - MG")
			$cidade_nome = trim( preg_replace( '/\s*-\s*[A-Z]{2}$/', '', $cidade_str ) );
			$cidade_slug = sanitize_title( $cidade_nome );
			$estado      = '';

			// Extrai UF
			if ( preg_match( '/\s*-\s*([A-Z]{2})$/', $cidade_str, $m ) ) {
				$estado = $m[1];
			}

			$term = term_exists( $cidade_nome, GPA_TAXONOMY );
			if ( ! $term ) {
				$term = wp_insert_term( $cidade_nome, GPA_TAXONOMY, [ 'slug' => $cidade_slug ] );
			}

			if ( ! is_wp_error( $term ) ) {
				$term_id = is_array( $term ) ? $term['term_id'] : $term;
				wp_set_object_terms( $new_post_id, (int) $term_id, GPA_TAXONOMY );

				if ( $estado ) {
					update_term_meta( $term_id, '_gpa_cidade_estado', $estado );
				}
			}
		}

		$log[] = [
			'status'  => 'created',
			'message' => "#{$id_original} — {$nome}" . ( $cidade_str ? " ({$cidade_str})" : '' ) . ' criado como rascunho.',
		];
	}

	return $log;
}

/**
 * Busca um post de rancho pelo ID original da base.
 *
 * @param int $id_original ID original.
 * @return int|null Post ID ou null se não encontrado.
 */
function gpa_find_by_original_id( int $id_original ): ?int {
	if ( ! $id_original ) {
		return null;
	}

	$posts = get_posts( [
		'post_type'      => GPA_CPT,
		'post_status'    => 'any',
		'meta_key'       => '_gpa_id_original',
		'meta_value'     => $id_original,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	] );

	return ! empty( $posts ) ? (int) $posts[0] : null;
}

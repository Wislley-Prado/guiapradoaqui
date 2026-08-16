<?php
/**
 * Plugin Name:       Guia Prado Aqui
 * Plugin URI:        https://guia.pradoaqui.com.br
 * Description:       Sistema profissional de cadastro, edição, verificação e publicação de ranchos de pesca na região de Três Marias / Rio São Francisco.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Prado Aqui
 * Author URI:        https://pradoaqui.com.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       guia-prado-aqui
 * Domain Path:       /languages
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Nunca acessar diretamente.
}

// ─── Registro de Logs de Erros Temporário para Diagnóstico ─────────────────────
if ( ! defined( 'GPA_ERROR_LOG_PATH' ) ) {
	define( 'GPA_ERROR_LOG_PATH', __DIR__ . '/gpa-errors.log' );
}

// Inicia o buffer para capturar erros fatais silenciosos no shutdown
register_shutdown_function( function() {
	$error = error_get_last();
	if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
		$log_message = sprintf(
			"[%s] FATAL ERROR: %s in %s on line %d\n",
			date( 'Y-m-d H:i:s' ),
			$error['message'],
			$error['file'],
			$error['line']
		);
		error_log( $log_message, 3, GPA_ERROR_LOG_PATH );
	}
} );

// Handler para erros comuns
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
	if ( error_reporting() & $errno ) {
		$log_message = sprintf(
			"[%s] ERROR (%d): %s in %s on line %d\n",
			date( 'Y-m-d H:i:s' ),
			$errno,
			$errstr,
			$errfile,
			$errline
		);
		error_log( $log_message, 3, GPA_ERROR_LOG_PATH );
	}
	return false;
} );

// Handler para exceções não capturadas
set_exception_handler( function( $exception ) {
	$log_message = sprintf(
		"[%s] EXCEPTION: %s in %s on line %d\nStack trace:\n%s\n",
		date( 'Y-m-d H:i:s' ),
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine(),
		$exception->getTraceAsString()
	);
	error_log( $log_message, 3, GPA_ERROR_LOG_PATH );
} );

// ─── Constantes ────────────────────────────────────────────────────────────────
define( 'GPA_VERSION',     '1.0.0' );
define( 'GPA_PLUGIN_FILE', __FILE__ );
define( 'GPA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'GPA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'GPA_PLUGIN_SLUG', 'guia-prado-aqui' );
define( 'GPA_CPT',         'rancho' );
define( 'GPA_TAXONOMY',    'cidade' );
// Define a capability de forma robusta e dinâmica (evita bugs de cache de banco/objeto na Hostinger)
$gpa_settings_cap = get_option( 'gpa_settings', [] );
define( 'GPA_CAP', ! empty( $gpa_settings_cap['editor_can_manage'] ) ? 'edit_pages' : 'manage_options' );

// ─── Loader ────────────────────────────────────────────────────────────────────
require_once GPA_PLUGIN_DIR . 'includes/helpers.php';
require_once GPA_PLUGIN_DIR . 'includes/permissions.php';
require_once GPA_PLUGIN_DIR . 'includes/post-types.php';
require_once GPA_PLUGIN_DIR . 'includes/taxonomies.php';
require_once GPA_PLUGIN_DIR . 'includes/fields.php';
require_once GPA_PLUGIN_DIR . 'includes/admin.php';
require_once GPA_PLUGIN_DIR . 'includes/import.php';
require_once GPA_PLUGIN_DIR . 'includes/api.php';

// ─── Ativação / Desativação / Desinstalação ────────────────────────────────────
register_activation_hook( __FILE__, 'gpa_activate' );
register_deactivation_hook( __FILE__, 'gpa_deactivate' );
register_uninstall_hook( __FILE__, 'gpa_uninstall' );

/**
 * Executa na ativação do plugin.
 */
function gpa_activate(): void {
	gpa_add_capabilities();
	gpa_register_post_type();
	gpa_register_taxonomy();
	flush_rewrite_rules();

	// Salva a data de ativação
	if ( ! get_option( 'gpa_activated_at' ) ) {
		update_option( 'gpa_activated_at', current_time( 'mysql' ) );
	}
}

/**
 * Executa na desativação do plugin.
 */
function gpa_deactivate(): void {
	flush_rewrite_rules();
}

/**
 * Executa na desinstalação do plugin.
 * Remove opções mas preserva os posts (ranchos) para segurança.
 */
function gpa_uninstall(): void {
	// Apenas remove opções de configuração. Posts são preservados.
	delete_option( 'gpa_settings' );
	delete_option( 'gpa_activated_at' );
}

// ─── Enqueue Assets Público ────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'gpa_enqueue_public_assets' );

function gpa_enqueue_public_assets(): void {
	// Só carrega nas páginas relevantes
	if ( ! is_singular( GPA_CPT ) && ! is_tax( GPA_TAXONOMY ) && ! is_post_type_archive( GPA_CPT ) && ! gpa_is_catalog_page() ) {
		return;
	}

	wp_enqueue_style(
		'gpa-public',
		GPA_PLUGIN_URL . 'public/css/public.css',
		[],
		GPA_VERSION
	);

	wp_enqueue_script(
		'gpa-public',
		GPA_PLUGIN_URL . 'public/js/public.js',
		[ 'jquery' ],
		GPA_VERSION,
		true
	);

	wp_localize_script( 'gpa-public', 'gpaData', [
		'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
		'restUrl'    => rest_url( 'guia-prado/v1/' ),
		'nonce'      => wp_create_nonce( 'wp_rest' ),
		'ajaxNonce'  => wp_create_nonce( 'gpa_public_nonce' ),
		'pluginUrl'  => GPA_PLUGIN_URL,
		'whatsappMsg'=> __( 'Olá! Encontrei o seu rancho no Guia Prado Aqui e gostaria de saber sobre disponibilidade e valores.', 'guia-prado-aqui' ),
	] );
}

// ─── Enqueue Assets Admin ──────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'gpa_enqueue_admin_assets' );

function gpa_enqueue_admin_assets( string $hook ): void {
	global $post_type, $current_screen;

	$is_gpa_screen = (
		( isset( $post_type ) && $post_type === GPA_CPT ) ||
		( isset( $current_screen ) && strpos( $current_screen->id, 'guia-prado' ) !== false ) ||
		( isset( $current_screen ) && strpos( $current_screen->id, GPA_CPT ) !== false )
	);

	if ( ! $is_gpa_screen ) {
		return;
	}

	wp_enqueue_style(
		'gpa-admin',
		GPA_PLUGIN_URL . 'admin/css/admin.css',
		[],
		GPA_VERSION
	);

	// Media uploader
	wp_enqueue_media();

	wp_enqueue_script(
		'gpa-admin',
		GPA_PLUGIN_URL . 'admin/js/admin.js',
		[ 'jquery', 'wp-util' ],
		GPA_VERSION,
		true
	);

	wp_localize_script( 'gpa-admin', 'gpaAdmin', [
		'nonce'        => wp_create_nonce( 'gpa_admin_nonce' ),
		'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
		'mediaTitle'   => __( 'Selecionar Imagem', 'guia-prado-aqui' ),
		'mediaButton'  => __( 'Usar esta imagem', 'guia-prado-aqui' ),
		'galleryTitle' => __( 'Selecionar Imagens da Galeria', 'guia-prado-aqui' ),
		'galleryBtn'   => __( 'Adicionar à Galeria', 'guia-prado-aqui' ),
		'confirmReset' => __( 'Tem certeza? Esta ação não pode ser desfeita.', 'guia-prado-aqui' ),
	] );
}

// ─── Template Loader ───────────────────────────────────────────────────────────
add_filter( 'template_include', 'gpa_template_loader' );

function gpa_template_loader( string $template ): string {
	// Template para rancho individual
	if ( is_singular( GPA_CPT ) ) {
		$custom = locate_template( [ 'single-rancho.php' ] );
		if ( $custom ) {
			return $custom;
		}
		return GPA_PLUGIN_DIR . 'templates/ranch.php';
	}

	// Template para arquivo de ranchos
	if ( is_post_type_archive( GPA_CPT ) ) {
		$custom = locate_template( [ 'archive-rancho.php' ] );
		if ( $custom ) {
			return $custom;
		}
		return GPA_PLUGIN_DIR . 'templates/archive.php';
	}

	// Template para cidade
	if ( is_tax( GPA_TAXONOMY ) ) {
		$custom = locate_template( [ 'taxonomy-cidade.php' ] );
		if ( $custom ) {
			return $custom;
		}
		return GPA_PLUGIN_DIR . 'templates/city.php';
	}

	return $template;
}

// ─── Shortcode do Catálogo ─────────────────────────────────────────────────────
add_shortcode( 'guia_prado_catalogo', 'gpa_catalog_shortcode' );

function gpa_catalog_shortcode( array $atts ): string {
	$atts = shortcode_atts( [
		'cidade'   => '',
		'por_pag'  => 12,
		'ordenar'  => 'nota',
	], $atts, 'guia_prado_catalogo' );

	// Enqueue assets para shortcode
	wp_enqueue_style( 'gpa-public', GPA_PLUGIN_URL . 'public/css/public.css', [], GPA_VERSION );
	wp_enqueue_script( 'gpa-public', GPA_PLUGIN_URL . 'public/js/public.js', [ 'jquery' ], GPA_VERSION, true );
	wp_localize_script( 'gpa-public', 'gpaData', [
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'restUrl'   => rest_url( 'guia-prado/v1/' ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'ajaxNonce' => wp_create_nonce( 'gpa_public_nonce' ),
		'pluginUrl' => GPA_PLUGIN_URL,
		'whatsappMsg' => __( 'Olá! Encontrei o seu rancho no Guia Prado Aqui e gostaria de saber sobre disponibilidade e valores.', 'guia-prado-aqui' ),
	] );

	ob_start();
	if ( ! defined( 'GPA_IS_SHORTCODE' ) ) {
		define( 'GPA_IS_SHORTCODE', true );
	}
	include GPA_PLUGIN_DIR . 'templates/archive.php';
	return ob_get_clean();
}

// ─── AJAX — Busca/Filtro Público ───────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_gpa_filter', 'gpa_ajax_filter' );
add_action( 'wp_ajax_gpa_filter',        'gpa_ajax_filter' );

function gpa_ajax_filter(): void {
	check_ajax_referer( 'gpa_public_nonce', 'nonce' );

	$busca    = sanitize_text_field( wp_unslash( $_POST['busca'] ?? '' ) );
	$cidade   = absint( $_POST['cidade'] ?? 0 );
	$pesca    = sanitize_text_field( wp_unslash( $_POST['pesca'] ?? '' ) );
	$piscina  = (bool) ( $_POST['piscina'] ?? false );
	$wifi     = (bool) ( $_POST['wifi'] ?? false );
	$ordenar  = sanitize_key( $_POST['ordenar'] ?? 'nota' );
	$pagina   = max( 1, absint( $_POST['pagina'] ?? 1 ) );
	$por_pag  = absint( $_POST['por_pag'] ?? 12 );

	$args = [
		'post_type'      => GPA_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => $por_pag,
		'paged'          => $pagina,
		'meta_query'     => [ 'relation' => 'AND' ],
	];

	// Filtro: somente verificados OU todos publicados dependendo da config
	$settings = get_option( 'gpa_settings', [] );
	$exige_verificado = $settings['exige_verificado'] ?? false;
	if ( $exige_verificado ) {
		$args['meta_query'][] = [
			'key'   => '_gpa_status_verificacao',
			'value' => 'verificado',
		];
	} else {
		// Exclui os desativados
		$args['meta_query'][] = [
			'key'     => '_gpa_status_verificacao',
			'value'   => 'desativado',
			'compare' => '!=',
		];
	}

	// Busca por nome
	if ( $busca ) {
		$args['s'] = $busca;
	}

	// Filtro por cidade (taxonomia)
	if ( $cidade ) {
		$args['tax_query'] = [
			[
				'taxonomy' => GPA_TAXONOMY,
				'field'    => 'term_id',
				'terms'    => $cidade,
			],
		];
	}

	// Filtro por pesca
	if ( $pesca ) {
		$pesca_map = [
			'barranco' => '_gpa_pesca_barranco',
			'barco'    => '_gpa_pesca_barco',
			'pesqueiro'=> '_gpa_pesqueiro',
			'noturna'  => '_gpa_pesca_noturna',
			'rampa'    => '_gpa_rampa',
		];
		if ( isset( $pesca_map[ $pesca ] ) ) {
			$args['meta_query'][] = [
				'key'   => $pesca_map[ $pesca ],
				'value' => '1',
			];
		}
	}

	// Filtro piscina
	if ( $piscina ) {
		$args['meta_query'][] = [
			'key'   => '_gpa_piscina',
			'value' => '1',
		];
	}

	// Filtro wifi
	if ( $wifi ) {
		$args['meta_query'][] = [
			'key'   => '_gpa_wifi',
			'value' => '1',
		];
	}

	// Ordenação
	switch ( $ordenar ) {
		case 'nota':
			$args['meta_key'] = '_gpa_nota_final';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		case 'preco_asc':
			$args['meta_key'] = '_gpa_preco_num';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			break;
		case 'capacidade':
			$args['meta_key'] = '_gpa_capacidade';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		case 'recentes':
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
	}

	$query = new WP_Query( $args );
	$cards = '';

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			ob_start();
			include GPA_PLUGIN_DIR . 'templates/parts/card.php';
			$cards .= ob_get_clean();
		}
		wp_reset_postdata();
	}

	wp_send_json_success( [
		'html'      => $cards,
		'total'     => $query->found_posts,
		'paginas'   => $query->max_num_pages,
		'pagina'    => $pagina,
	] );
}

// ─── AJAX — Quick Verify (Admin) ───────────────────────────────────────────────
add_action( 'wp_ajax_gpa_quick_verify', 'gpa_ajax_quick_verify' );

function gpa_ajax_quick_verify(): void {
	check_ajax_referer( 'gpa_admin_nonce', 'nonce' );

	if ( ! current_user_can( GPA_CAP ) ) {
		wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
	}

	$post_id = absint( $_POST['post_id'] ?? 0 );
	$status  = sanitize_key( $_POST['status'] ?? 'pendente' );

	$valid = [ 'pendente', 'em_verificacao', 'verificado', 'precisa_atualizacao', 'desativado' ];
	if ( ! in_array( $status, $valid, true ) ) {
		wp_send_json_error( [ 'message' => 'Status inválido.' ] );
	}

	update_post_meta( $post_id, '_gpa_status_verificacao', $status );
	if ( $status === 'verificado' ) {
		update_post_meta( $post_id, '_gpa_data_verificacao', current_time( 'Y-m-d' ) );
	}

	wp_send_json_success( [
		'status'  => $status,
		'label'   => gpa_get_status_label( $status ),
		'message' => 'Status atualizado com sucesso.',
	] );
}

// ─── Helpers de página ─────────────────────────────────────────────────────────
/**
 * Verifica se a página atual é uma página de catálogo (shortcode).
 */
function gpa_is_catalog_page(): bool {
	global $post;
	if ( ! isset( $post ) ) {
		return false;
	}
	return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'guia_prado_catalogo' );
}

<?php
/**
 * Permissões e capabilities do plugin Guia Prado Aqui
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adiciona a capability manage_guia_prado ao role Administrator na ativação.
 */
function gpa_add_capabilities(): void {
	$admin_role = get_role( 'administrator' );
	if ( $admin_role ) {
		$admin_role->add_cap( GPA_CAP );
	}

	// Suporte ao Editor se configurado
	$settings = get_option( 'gpa_settings', [] );
	if ( ! empty( $settings['editor_can_manage'] ) ) {
		$editor_role = get_role( 'editor' );
		if ( $editor_role ) {
			$editor_role->add_cap( GPA_CAP );
		}
	}
}

/**
 * Remove a capability manage_guia_prado dos roles na desinstalação.
 */
function gpa_remove_capabilities(): void {
	foreach ( [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ] as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( GPA_CAP );
		}
	}
}

/**
 * Verifica se o usuário atual pode gerenciar o Guia Prado Aqui.
 *
 * @param int|null $user_id ID do usuário (null = atual).
 */
function gpa_current_user_can_manage( ?int $user_id = null ): bool {
	if ( $user_id ) {
		return user_can( $user_id, GPA_CAP );
	}
	return current_user_can( GPA_CAP );
}

/**
 * Hook para verificar permissão antes de salvar meta.
 * Retorna true se pode salvar, false caso contrário.
 *
 * @param int $post_id ID do post.
 */
function gpa_can_save_meta( int $post_id ): bool {
	// Não salva em auto-save
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	// Verifica capability
	if ( ! current_user_can( GPA_CAP ) ) {
		return false;
	}

	// Verifica se é o post type correto
	if ( get_post_type( $post_id ) !== GPA_CPT ) {
		return false;
	}

	return true;
}

// ─── Proteção das rotas admin ─────────────────────────────────────────────────
add_action( 'admin_init', 'gpa_protect_admin_pages' );

function gpa_protect_admin_pages(): void {
	$page = sanitize_key( $_GET['page'] ?? '' );

	$gpa_pages = [
		'guia-prado-aqui',
		'gpa-ranchos',
		'gpa-cidades',
		'gpa-verificacoes',
		'gpa-configuracoes',
		'gpa-importar',
	];

	if ( in_array( $page, $gpa_pages, true ) && ! current_user_can( GPA_CAP ) ) {
		wp_die(
			esc_html__( 'Você não tem permissão para acessar esta área.', 'guia-prado-aqui' ),
			esc_html__( 'Acesso negado', 'guia-prado-aqui' ),
			[ 'response' => 403 ]
		);
	}
}

// ─── Gerenciamento de capabilities via Admin ──────────────────────────────────
add_action( 'wp_ajax_gpa_toggle_user_cap', 'gpa_ajax_toggle_user_cap' );

function gpa_ajax_toggle_user_cap(): void {
	check_ajax_referer( 'gpa_admin_nonce', 'nonce' );

	// Apenas admins podem conceder ou revogar capabilities
	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json_error( [ 'message' => 'Apenas administradores podem alterar permissões.' ] );
	}

	$user_id = absint( $_POST['user_id'] ?? 0 );
	$action  = sanitize_key( $_POST['cap_action'] ?? '' );

	if ( ! $user_id || ! in_array( $action, [ 'grant', 'revoke' ], true ) ) {
		wp_send_json_error( [ 'message' => 'Parâmetros inválidos.' ] );
	}

	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		wp_send_json_error( [ 'message' => 'Usuário não encontrado.' ] );
	}

	if ( $action === 'grant' ) {
		$user->add_cap( GPA_CAP );
		$message = 'Acesso concedido ao Guia Prado Aqui.';
	} else {
		$user->remove_cap( GPA_CAP );
		$message = 'Acesso revogado do Guia Prado Aqui.';
	}

	wp_send_json_success( [ 'message' => $message ] );
}

// ─── Garante que o administrador sempre tenha acesso (fallback) ──────────────
add_action( 'admin_init', 'gpa_ensure_admin_capabilities' );

function gpa_ensure_admin_capabilities(): void {
	if ( current_user_can( 'administrator' ) && ! current_user_can( GPA_CAP ) ) {
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( GPA_CAP );
		}
	}
}

<?php
/**
 * Registro do Custom Post Type: rancho
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gpa_register_post_type' );

/**
 * Registra o CPT rancho.
 */
function gpa_register_post_type(): void {
	$labels = [
		'name'                  => _x( 'Ranchos', 'Post type general name', 'guia-prado-aqui' ),
		'singular_name'         => _x( 'Rancho', 'Post type singular name', 'guia-prado-aqui' ),
		'menu_name'             => _x( 'Ranchos', 'Admin Menu text', 'guia-prado-aqui' ),
		'name_admin_bar'        => _x( 'Rancho', 'Add New on Toolbar', 'guia-prado-aqui' ),
		'add_new'               => __( 'Adicionar Rancho', 'guia-prado-aqui' ),
		'add_new_item'          => __( 'Adicionar Novo Rancho', 'guia-prado-aqui' ),
		'new_item'              => __( 'Novo Rancho', 'guia-prado-aqui' ),
		'edit_item'             => __( 'Editar Rancho', 'guia-prado-aqui' ),
		'view_item'             => __( 'Ver Rancho', 'guia-prado-aqui' ),
		'all_items'             => __( 'Todos os Ranchos', 'guia-prado-aqui' ),
		'search_items'          => __( 'Buscar Ranchos', 'guia-prado-aqui' ),
		'parent_item_colon'     => __( 'Rancho pai:', 'guia-prado-aqui' ),
		'not_found'             => __( 'Nenhum rancho encontrado.', 'guia-prado-aqui' ),
		'not_found_in_trash'    => __( 'Nenhum rancho na lixeira.', 'guia-prado-aqui' ),
		'featured_image'        => _x( 'Foto Principal', 'Overrides the "Featured Image" phrase', 'guia-prado-aqui' ),
		'set_featured_image'    => _x( 'Definir foto principal', 'Overrides the "Set featured image" phrase', 'guia-prado-aqui' ),
		'remove_featured_image' => _x( 'Remover foto principal', 'Overrides the "Remove featured image" phrase', 'guia-prado-aqui' ),
		'use_featured_image'    => _x( 'Usar como foto principal', 'Overrides the "Use as featured image" phrase', 'guia-prado-aqui' ),
		'archives'              => _x( 'Catálogo de Ranchos', 'The post type archive label used in nav menus', 'guia-prado-aqui' ),
		'insert_into_item'      => _x( 'Inserir no rancho', 'Overrides the "Insert into post" phrase', 'guia-prado-aqui' ),
		'uploaded_to_this_item' => _x( 'Enviado para este rancho', 'Overrides the "Uploaded to this post" phrase', 'guia-prado-aqui' ),
		'filter_items_list'     => _x( 'Filtrar lista de ranchos', 'Screen reader text', 'guia-prado-aqui' ),
		'items_list_navigation' => _x( 'Navegação da lista de ranchos', 'Screen reader text', 'guia-prado-aqui' ),
		'items_list'            => _x( 'Lista de ranchos', 'Screen reader text', 'guia-prado-aqui' ),
	];

	// Capabilities customizadas
	$capabilities = [
		'edit_post'              => GPA_CAP,
		'read_post'              => 'read',
		'delete_post'            => GPA_CAP,
		'edit_posts'             => GPA_CAP,
		'edit_others_posts'      => GPA_CAP,
		'publish_posts'          => GPA_CAP,
		'read_private_posts'     => GPA_CAP,
		'delete_posts'           => GPA_CAP,
		'delete_private_posts'   => GPA_CAP,
		'delete_published_posts' => GPA_CAP,
		'delete_others_posts'    => GPA_CAP,
		'edit_private_posts'     => GPA_CAP,
		'edit_published_posts'   => GPA_CAP,
		'create_posts'           => GPA_CAP,
	];

	$settings = get_option( 'gpa_settings', [] );
	$slug_rancho = sanitize_title( $settings['slug_rancho'] ?? 'rancho' );

	$args = [
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => false, // Controlado manualmente via admin.php
		'query_var'          => true,
		'rewrite'            => [
			'slug'       => $slug_rancho,
			'with_front' => false,
		],
		'capability_type'    => 'post',
		'capabilities'       => $capabilities,
		'map_meta_cap'       => true,
		'has_archive'        => 'ranchos',
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-location-alt',
		'supports'           => [
			'title',
			'editor',
			'thumbnail',
			'excerpt',
			'revisions',
			'custom-fields',
		],
		'show_in_rest'       => true,
		'rest_base'          => 'ranchos-wp',
	];

	register_post_type( GPA_CPT, $args );
}

// ─── Colunas Personalizadas na Lista Admin ─────────────────────────────────────
add_filter( 'manage_rancho_posts_columns',       'gpa_set_columns' );
add_action( 'manage_rancho_posts_custom_column', 'gpa_render_columns', 10, 2 );
add_filter( 'manage_edit-rancho_sortable_columns', 'gpa_sortable_columns' );

function gpa_set_columns( array $columns ): array {
	$new = [];
	$new['cb']          = $columns['cb'];
	$new['foto']        = '📷';
	$new['title']       = __( 'Nome do Rancho', 'guia-prado-aqui' );
	$new['cidade']      = __( 'Cidade', 'guia-prado-aqui' );
	$new['telefone']    = __( 'Telefone / WhatsApp', 'guia-prado-aqui' );
	$new['nota']        = __( '⭐ Nota', 'guia-prado-aqui' );
	$new['status_ver']  = __( '🔍 Verificação', 'guia-prado-aqui' );
	$new['acoes']       = __( 'Ações Rápidas', 'guia-prado-aqui' );
	$new['date']        = $columns['date'];
	return $new;
}

function gpa_render_columns( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'foto':
			$img_id = get_post_thumbnail_id( $post_id );
			if ( $img_id ) {
				echo wp_get_attachment_image( $img_id, [ 50, 50 ], false, [ 'style' => 'border-radius:6px;object-fit:cover;' ] );
			} else {
				echo '<span style="font-size:24px;opacity:.4;">🏡</span>';
			}
			break;

		case 'cidade':
			$cidades = get_the_terms( $post_id, GPA_TAXONOMY );
			if ( $cidades && ! is_wp_error( $cidades ) ) {
				$links = array_map( function( $c ) {
					return '<a href="' . esc_url( get_edit_term_link( $c->term_id, GPA_TAXONOMY ) ) . '">' . esc_html( $c->name ) . '</a>';
				}, $cidades );
				echo implode( ', ', $links );
			} else {
				echo '<span style="color:#aaa;">—</span>';
			}
			break;

		case 'telefone':
			$tel = get_post_meta( $post_id, '_gpa_telefone', true );
			if ( $tel ) {
				$tel_clean = preg_replace( '/\D/', '', $tel );
				echo '<a href="https://wa.me/' . esc_attr( $tel_clean ) . '" target="_blank" style="color:#25d366;font-weight:700;" title="Abrir WhatsApp">📱 ' . esc_html( $tel ) . '</a>';
			} else {
				echo '<span style="color:#aaa;">—</span>';
			}
			break;

		case 'nota':
			$nota = get_post_meta( $post_id, '_gpa_nota_final', true );
			if ( $nota !== '' && $nota !== null ) {
				$color = $nota >= 8 ? '#087443' : ( $nota >= 6 ? '#e6a817' : '#c0392b' );
				echo '<strong style="color:' . esc_attr( $color ) . ';font-size:16px;">' . esc_html( number_format( (float) $nota, 1 ) ) . '</strong>';
			} else {
				echo '<span style="color:#aaa;">—</span>';
			}
			break;

		case 'status_ver':
			$status = get_post_meta( $post_id, '_gpa_status_verificacao', true ) ?: 'pendente';
			$label  = gpa_get_status_label( $status );
			$color  = gpa_get_status_color( $status );
			echo '<span class="gpa-status-badge" style="background:' . esc_attr( $color['bg'] ) . ';color:' . esc_attr( $color['text'] ) . ';padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;" data-post-id="' . esc_attr( $post_id ) . '" data-status="' . esc_attr( $status ) . '">';
			echo esc_html( $label );
			echo '</span>';
			break;

		case 'acoes':
			$tel   = get_post_meta( $post_id, '_gpa_telefone', true );
			$lat   = get_post_meta( $post_id, '_gpa_latitude', true );
			$lng   = get_post_meta( $post_id, '_gpa_longitude', true );
			$edit  = get_edit_post_link( $post_id );
			$view  = get_permalink( $post_id );

			echo '<div class="gpa-quick-actions">';
			if ( $tel ) {
				$tel_clean = preg_replace( '/\D/', '', $tel );
				echo '<a href="https://wa.me/' . esc_attr( $tel_clean ) . '" target="_blank" class="gpa-btn gpa-btn-green" title="WhatsApp">💬</a>';
			}
			if ( $lat && $lng ) {
				echo '<a href="https://www.google.com/maps/search/?api=1&query=' . esc_attr( $lat ) . ',' . esc_attr( $lng ) . '" target="_blank" class="gpa-btn gpa-btn-blue" title="Google Maps">📍</a>';
			}
			if ( current_user_can( GPA_CAP ) ) {
				echo '<a href="' . esc_url( $edit ) . '" class="gpa-btn gpa-btn-edit" title="Editar">✏️</a>';
				echo '<a href="' . esc_url( $view ) . '" target="_blank" class="gpa-btn gpa-btn-view" title="Visualizar">👁</a>';
			}
			echo '</div>';
			break;
	}
}

function gpa_sortable_columns( array $columns ): array {
	$columns['nota']   = 'nota';
	$columns['cidade'] = 'cidade';
	return $columns;
}

// ─── Filtro de lista por status de verificação ─────────────────────────────────
add_action( 'restrict_manage_posts', 'gpa_admin_filter_ui' );
add_action( 'pre_get_posts',         'gpa_admin_filter_query' );

function gpa_admin_filter_ui( string $post_type ): void {
	if ( $post_type !== GPA_CPT ) {
		return;
	}

	$current = sanitize_key( $_GET['gpa_status_ver'] ?? '' );
	$options = [
		''                   => __( 'Todos os status', 'guia-prado-aqui' ),
		'pendente'           => __( '🔴 Pendente', 'guia-prado-aqui' ),
		'em_verificacao'     => __( '🟡 Em verificação', 'guia-prado-aqui' ),
		'verificado'         => __( '🟢 Verificado', 'guia-prado-aqui' ),
		'precisa_atualizacao'=> __( '⚠️ Precisa atualização', 'guia-prado-aqui' ),
		'desativado'         => __( '⚫ Desativado', 'guia-prado-aqui' ),
	];

	echo '<select name="gpa_status_ver" id="gpa_status_ver">';
	foreach ( $options as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function gpa_admin_filter_query( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->get( 'post_type' ) !== GPA_CPT ) {
		return;
	}

	$status = sanitize_key( $_GET['gpa_status_ver'] ?? '' );
	if ( $status ) {
		$meta_query = $query->get( 'meta_query' ) ?: [];
		$meta_query[] = [
			'key'   => '_gpa_status_verificacao',
			'value' => $status,
		];
		$query->set( 'meta_query', $meta_query );
	}
}

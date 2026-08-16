<?php
/**
 * REST API endpoints do plugin Guia Prado Aqui
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'gpa_register_rest_routes' );

function gpa_register_rest_routes(): void {
	$namespace = 'guia-prado/v1';

	// GET /wp-json/guia-prado/v1/ranchos (público)
	register_rest_route( $namespace, '/ranchos', [
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'gpa_api_get_ranchos',
		'permission_callback' => '__return_true',
		'args'                => [
			'page'     => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1 ],
			'per_page' => [ 'type' => 'integer', 'default' => 12, 'maximum' => 100 ],
			'search'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			'cidade'   => [ 'type' => 'integer', 'default' => 0 ],
			'ordenar'  => [ 'type' => 'string', 'default' => 'nota', 'enum' => [ 'nota', 'preco_asc', 'capacidade', 'recentes' ] ],
			'pesca'    => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
			'piscina'  => [ 'type' => 'boolean', 'default' => false ],
			'wifi'     => [ 'type' => 'boolean', 'default' => false ],
		],
	] );

	// GET /wp-json/guia-prado/v1/ranchos/{id} (público)
	register_rest_route( $namespace, '/ranchos/(?P<id>[\d]+)', [
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'gpa_api_get_rancho',
		'permission_callback' => '__return_true',
		'args'                => [
			'id' => [ 'type' => 'integer', 'required' => true ],
		],
	] );

	// GET /wp-json/guia-prado/v1/cidades (público)
	register_rest_route( $namespace, '/cidades', [
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'gpa_api_get_cidades',
		'permission_callback' => '__return_true',
	] );

	// GET /wp-json/guia-prado/v1/cidades/{id} (público)
	register_rest_route( $namespace, '/cidades/(?P<id>[\d]+)', [
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'gpa_api_get_cidade',
		'permission_callback' => '__return_true',
		'args'                => [
			'id' => [ 'type' => 'integer', 'required' => true ],
		],
	] );

	// POST /wp-json/guia-prado/v1/ranchos (privado — autenticação + capability)
	register_rest_route( $namespace, '/ranchos', [
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'gpa_api_create_rancho',
		'permission_callback' => 'gpa_api_permission_check',
	] );
}

// ─── Permission Check ─────────────────────────────────────────────────────────

function gpa_api_permission_check(): bool | WP_Error {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'rest_forbidden', 'Autenticação necessária.', [ 'status' => 401 ] );
	}
	if ( ! current_user_can( GPA_CAP ) ) {
		return new WP_Error( 'rest_forbidden', 'Você não tem permissão para esta ação.', [ 'status' => 403 ] );
	}
	return true;
}

// ─── GET /ranchos ─────────────────────────────────────────────────────────────

function gpa_api_get_ranchos( WP_REST_Request $request ): WP_REST_Response | WP_Error {
	$page     = $request->get_param( 'page' );
	$per_page = $request->get_param( 'per_page' );
	$search   = $request->get_param( 'search' );
	$cidade   = $request->get_param( 'cidade' );
	$ordenar  = $request->get_param( 'ordenar' );
	$pesca    = $request->get_param( 'pesca' );
	$piscina  = $request->get_param( 'piscina' );
	$wifi     = $request->get_param( 'wifi' );

	$settings = get_option( 'gpa_settings', [] );

	$args = [
		'post_type'      => GPA_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'meta_query'     => [ 'relation' => 'AND' ],
	];

	// Visibilidade
	$exige_verificado = $settings['exige_verificado'] ?? false;
	if ( $exige_verificado ) {
		$args['meta_query'][] = [ 'key' => '_gpa_status_verificacao', 'value' => 'verificado' ];
	} else {
		$args['meta_query'][] = [ 'key' => '_gpa_status_verificacao', 'value' => 'desativado', 'compare' => '!=' ];
	}

	if ( $search ) {
		$args['s'] = $search;
	}

	if ( $cidade ) {
		$args['tax_query'] = [ [ 'taxonomy' => GPA_TAXONOMY, 'field' => 'term_id', 'terms' => $cidade ] ];
	}

	if ( $pesca ) {
		$map = [
			'barranco'  => '_gpa_pesca_barranco',
			'barco'     => '_gpa_pesca_barco',
			'pesqueiro' => '_gpa_pesqueiro',
			'noturna'   => '_gpa_pesca_noturna',
			'rampa'     => '_gpa_rampa',
		];
		if ( isset( $map[ $pesca ] ) ) {
			$args['meta_query'][] = [ 'key' => $map[ $pesca ], 'value' => '1' ];
		}
	}

	if ( $piscina ) {
		$args['meta_query'][] = [ 'key' => '_gpa_piscina', 'value' => '1' ];
	}
	if ( $wifi ) {
		$args['meta_query'][] = [ 'key' => '_gpa_wifi', 'value' => '1' ];
	}

	switch ( $ordenar ) {
		case 'nota':
			$args['meta_key'] = '_gpa_nota_final';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		case 'preco_asc':
			$args['meta_key'] = '_gpa_preco';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			break;
		case 'capacidade':
			$args['meta_key'] = '_gpa_capacidade';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	$query   = new WP_Query( $args );
	$ranchos = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$ranchos[] = gpa_format_rancho_public( get_the_ID() );
	}
	wp_reset_postdata();

	$response = new WP_REST_Response( $ranchos, 200 );
	$response->header( 'X-WP-Total',      $query->found_posts );
	$response->header( 'X-WP-TotalPages', $query->max_num_pages );

	return $response;
}

// ─── GET /ranchos/{id} ────────────────────────────────────────────────────────

function gpa_api_get_rancho( WP_REST_Request $request ): WP_REST_Response | WP_Error {
	$id   = $request->get_param( 'id' );
	$post = get_post( $id );

	if ( ! $post || $post->post_type !== GPA_CPT || $post->post_status !== 'publish' ) {
		return new WP_Error( 'rest_not_found', 'Rancho não encontrado.', [ 'status' => 404 ] );
	}

	$status = get_post_meta( $id, '_gpa_status_verificacao', true );
	if ( $status === 'desativado' ) {
		return new WP_Error( 'rest_not_found', 'Rancho não disponível.', [ 'status' => 404 ] );
	}

	return new WP_REST_Response( gpa_format_rancho_public( $id ), 200 );
}

// ─── GET /cidades ─────────────────────────────────────────────────────────────

function gpa_api_get_cidades( WP_REST_Request $request ): WP_REST_Response | WP_Error {
	$terms = get_terms( [
		'taxonomy'   => GPA_TAXONOMY,
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	$cidades = array_map( function( WP_Term $t ) {
		return [
			'id'     => $t->term_id,
			'nome'   => $t->name,
			'slug'   => $t->slug,
			'estado' => get_term_meta( $t->term_id, '_gpa_cidade_estado', true ) ?: '',
			'regiao' => get_term_meta( $t->term_id, '_gpa_cidade_regiao', true ) ?: '',
			'total'  => $t->count,
			'url'    => get_term_link( $t ),
		];
	}, $terms );

	return new WP_REST_Response( $cidades, 200 );
}

// ─── GET /cidades/{id} ────────────────────────────────────────────────────────

function gpa_api_get_cidade( WP_REST_Request $request ): WP_REST_Response | WP_Error {
	$id   = $request->get_param( 'id' );
	$term = get_term( $id, GPA_TAXONOMY );

	if ( ! $term || is_wp_error( $term ) ) {
		return new WP_Error( 'rest_not_found', 'Cidade não encontrada.', [ 'status' => 404 ] );
	}

	return new WP_REST_Response( [
		'id'      => $term->term_id,
		'nome'    => $term->name,
		'slug'    => $term->slug,
		'estado'  => get_term_meta( $term->term_id, '_gpa_cidade_estado', true ) ?: '',
		'regiao'  => get_term_meta( $term->term_id, '_gpa_cidade_regiao', true ) ?: '',
		'total'   => $term->count,
		'url'     => get_term_link( $term ),
	], 200 );
}

// ─── POST /ranchos (privado) ──────────────────────────────────────────────────

function gpa_api_create_rancho( WP_REST_Request $request ): WP_REST_Response | WP_Error {
	$nome = sanitize_text_field( $request->get_param( 'nome' ) ?? '' );

	if ( ! $nome ) {
		return new WP_Error( 'rest_bad_request', 'Nome é obrigatório.', [ 'status' => 400 ] );
	}

	$post_id = wp_insert_post( [
		'post_title'  => $nome,
		'post_status' => 'draft',
		'post_type'   => GPA_CPT,
	] );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Campos opcionais
	$campos = [ 'telefone', 'endereco', 'latitude', 'longitude', 'preco', 'capacidade' ];
	foreach ( $campos as $campo ) {
		$val = $request->get_param( $campo );
		if ( $val !== null ) {
			update_post_meta( $post_id, '_gpa_' . $campo, sanitize_text_field( $val ) );
		}
	}

	update_post_meta( $post_id, '_gpa_status_verificacao', 'pendente' );

	return new WP_REST_Response( [
		'id'      => $post_id,
		'message' => 'Rancho criado como rascunho.',
		'edit_url'=> get_edit_post_link( $post_id, 'raw' ),
	], 201 );
}

// ─── Formatar rancho para API pública ─────────────────────────────────────────

/**
 * Formata os dados públicos de um rancho para a API.
 * NUNCA retorna campos internos (obs_interna, nome_contato, etc.)
 *
 * @param int $post_id ID do post.
 * @return array Dados públicos formatados.
 */
function gpa_format_rancho_public( int $post_id ): array {
	$post   = get_post( $post_id );
	$status = get_post_meta( $post_id, '_gpa_status_verificacao', true ) ?: 'pendente';

	// Foto principal
	$foto_url = '';
	$foto_id  = get_post_thumbnail_id( $post_id );
	if ( $foto_id ) {
		$foto = wp_get_attachment_image_src( $foto_id, 'large' );
		$foto_url = $foto ? $foto[0] : '';
	}

	// Galeria
	$galeria_ids  = gpa_get_gallery_ids( $post_id );
	$galeria_urls = [];
	foreach ( array_slice( $galeria_ids, 0, 10 ) as $gid ) {
		$img = wp_get_attachment_image_src( $gid, 'medium_large' );
		if ( $img ) {
			$galeria_urls[] = $img[0];
		}
	}

	// Cidade
	$cidades    = get_the_terms( $post_id, GPA_TAXONOMY );
	$cidade     = null;
	if ( $cidades && ! is_wp_error( $cidades ) ) {
		$c = $cidades[0];
		$cidade = [
			'id'   => $c->term_id,
			'nome' => $c->name,
			'slug' => $c->slug,
			'url'  => get_term_link( $c ),
		];
	}

	// Nota Prado Aqui
	$nota_final = gpa_get_nota_final( $post_id );

	// Estrutura e pesca (somente itens ativos)
	$estrutura = gpa_get_estrutura_list( $post_id );
	$pesca     = gpa_get_pesca_list( $post_id );

	// WhatsApp e Maps
	$wa_url   = gpa_get_whatsapp_url( $post_id );
	$maps_url = gpa_get_maps_url( $post_id );

	// Data de verificação (pública)
	$data_ver = '';
	if ( $status === 'verificado' ) {
		$data_raw = get_post_meta( $post_id, '_gpa_data_verificacao', true );
		if ( $data_raw ) {
			$data_ver = date_i18n( 'd/m/Y', strtotime( $data_raw ) );
		}
	}

	return [
		// Identificação
		'id'          => $post_id,
		'nome'        => $post->post_title,
		'slug'        => $post->post_name,
		'url'         => get_permalink( $post_id ),
		'descricao'   => wp_strip_all_tags( $post->post_excerpt ),

		// Mídia
		'foto'        => $foto_url,
		'galeria'     => $galeria_urls,

		// Cidade
		'cidade'      => $cidade,

		// Contato
		'telefone'    => get_post_meta( $post_id, '_gpa_telefone', true ) ?: '',
		'instagram'   => get_post_meta( $post_id, '_gpa_instagram', true ) ?: '',
		'site'        => get_post_meta( $post_id, '_gpa_site', true ) ?: '',
		'whatsapp_url'=> $wa_url,

		// Localização
		'endereco'    => get_post_meta( $post_id, '_gpa_endereco', true ) ?: '',
		'latitude'    => get_post_meta( $post_id, '_gpa_latitude', true ) ?: '',
		'longitude'   => get_post_meta( $post_id, '_gpa_longitude', true ) ?: '',
		'maps_url'    => $maps_url,

		// Preço
		'preco'       => gpa_format_preco( $post_id ),
		'preco_raw'   => get_post_meta( $post_id, '_gpa_preco', true ) ?: '',
		'tipo_preco'  => get_post_meta( $post_id, '_gpa_tipo_preco', true ) ?: '',

		// Capacidade
		'capacidade'  => gpa_format_capacidade( $post_id ),
		'cap_max'     => (int) ( get_post_meta( $post_id, '_gpa_capacidade', true ) ?: 0 ),
		'quartos'     => (int) ( get_post_meta( $post_id, '_gpa_quartos', true ) ?: 0 ),
		'suites'      => (int) ( get_post_meta( $post_id, '_gpa_suites', true ) ?: 0 ),
		'banheiros'   => (int) ( get_post_meta( $post_id, '_gpa_banheiros', true ) ?: 0 ),

		// Estrutura e Pesca
		'estrutura'   => $estrutura,
		'pesca'       => $pesca,

		// Avaliação Prado Aqui
		'nota_final'  => $nota_final,
		'nota_google' => get_post_meta( $post_id, '_gpa_nota_google', true ) ?: '',
		'avaliacoes_google' => (int) ( get_post_meta( $post_id, '_gpa_qtd_avaliacoes_google', true ) ?: 0 ),

		// Verificação (somente campo público)
		'verificado'         => $status === 'verificado',
		'data_verificacao'   => $data_ver,

		// Metadados
		'publicado_em' => get_the_date( 'Y-m-d', $post_id ),
		'atualizado_em'=> get_the_modified_date( 'Y-m-d', $post_id ),
	];

	// NUNCA retornar: _gpa_obs_interna, _gpa_nome_contato, _gpa_tel_confirmado,
	// _gpa_preco_confirmado, _gpa_estrutura_confirmada, _gpa_fotos_confirmadas
}

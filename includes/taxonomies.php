<?php
/**
 * Registro da Taxonomia: cidade
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gpa_register_taxonomy' );

/**
 * Registra a taxonomia cidade para o CPT rancho.
 */
function gpa_register_taxonomy(): void {
	$labels = [
		'name'                       => _x( 'Cidades', 'Taxonomy general name', 'guia-prado-aqui' ),
		'singular_name'              => _x( 'Cidade', 'Taxonomy singular name', 'guia-prado-aqui' ),
		'search_items'               => __( 'Buscar Cidades', 'guia-prado-aqui' ),
		'popular_items'              => __( 'Cidades Populares', 'guia-prado-aqui' ),
		'all_items'                  => __( 'Todas as Cidades', 'guia-prado-aqui' ),
		'parent_item'                => __( 'Cidade Pai', 'guia-prado-aqui' ),
		'parent_item_colon'          => __( 'Cidade Pai:', 'guia-prado-aqui' ),
		'edit_item'                  => __( 'Editar Cidade', 'guia-prado-aqui' ),
		'update_item'                => __( 'Atualizar Cidade', 'guia-prado-aqui' ),
		'add_new_item'               => __( 'Adicionar Nova Cidade', 'guia-prado-aqui' ),
		'new_item_name'              => __( 'Nome da Nova Cidade', 'guia-prado-aqui' ),
		'separate_items_with_commas' => __( 'Separe cidades com vírgulas', 'guia-prado-aqui' ),
		'add_or_remove_items'        => __( 'Adicionar ou remover cidades', 'guia-prado-aqui' ),
		'choose_from_most_used'      => __( 'Escolher das mais usadas', 'guia-prado-aqui' ),
		'not_found'                  => __( 'Nenhuma cidade encontrada.', 'guia-prado-aqui' ),
		'no_terms'                   => __( 'Nenhuma cidade', 'guia-prado-aqui' ),
		'items_list_navigation'      => __( 'Navegação da lista de cidades', 'guia-prado-aqui' ),
		'items_list'                 => __( 'Lista de cidades', 'guia-prado-aqui' ),
		'back_to_items'              => __( '← Voltar para Cidades', 'guia-prado-aqui' ),
		'menu_name'                  => __( 'Cidades', 'guia-prado-aqui' ),
	];

	$settings   = get_option( 'gpa_settings', [] );
	$slug_cidade = sanitize_title( $settings['slug_cidade'] ?? 'cidade' );

	$args = [
		'hierarchical'          => true, // Como categorias (permite cidades filhas / regiões)
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => false, // Gerenciamos nas colunas próprias
		'query_var'             => true,
		'rewrite'               => [
			'slug'         => $slug_cidade,
			'with_front'   => false,
			'hierarchical' => false,
		],
		'show_in_rest'          => true,
		'rest_base'             => 'cidades-wp',
		'capabilities'          => [
			'manage_terms' => GPA_CAP,
			'edit_terms'   => GPA_CAP,
			'delete_terms' => GPA_CAP,
			'assign_terms' => GPA_CAP,
		],
	];

	register_taxonomy( GPA_TAXONOMY, [ GPA_CPT ], $args );
}

// ─── Campos extras na tela de edição de cidade ─────────────────────────────────
add_action( GPA_TAXONOMY . '_add_form_fields',  'gpa_cidade_add_fields' );
add_action( GPA_TAXONOMY . '_edit_form_fields', 'gpa_cidade_edit_fields', 10, 2 );
add_action( 'created_' . GPA_TAXONOMY,          'gpa_cidade_save_fields', 10, 2 );
add_action( 'edited_' . GPA_TAXONOMY,           'gpa_cidade_save_fields', 10, 2 );

/**
 * Campos extras ao adicionar nova cidade.
 */
function gpa_cidade_add_fields(): void {
	wp_nonce_field( 'gpa_cidade_meta', 'gpa_cidade_nonce' );
	?>
	<div class="form-field">
		<label for="gpa_cidade_estado"><?php esc_html_e( 'Estado (UF)', 'guia-prado-aqui' ); ?></label>
		<input type="text" name="gpa_cidade_estado" id="gpa_cidade_estado" value="" maxlength="2" style="width:60px;" placeholder="MG">
		<p class="description"><?php esc_html_e( 'Sigla do estado. Ex: MG', 'guia-prado-aqui' ); ?></p>
	</div>
	<div class="form-field">
		<label for="gpa_cidade_regiao"><?php esc_html_e( 'Região / Referência', 'guia-prado-aqui' ); ?></label>
		<input type="text" name="gpa_cidade_regiao" id="gpa_cidade_regiao" value="" placeholder="Ex: Região do Alto São Francisco">
	</div>
	<div class="form-field">
		<label for="gpa_cidade_og_image"><?php esc_html_e( 'URL da Imagem OG (para SEO)', 'guia-prado-aqui' ); ?></label>
		<input type="url" name="gpa_cidade_og_image" id="gpa_cidade_og_image" value="" placeholder="https://...">
		<p class="description"><?php esc_html_e( 'Imagem exibida quando a página da cidade é compartilhada nas redes sociais.', 'guia-prado-aqui' ); ?></p>
	</div>
	<?php
}

/**
 * Campos extras ao editar cidade.
 */
function gpa_cidade_edit_fields( WP_Term $term ): void {
	wp_nonce_field( 'gpa_cidade_meta', 'gpa_cidade_nonce' );
	$estado   = get_term_meta( $term->term_id, '_gpa_cidade_estado', true );
	$regiao   = get_term_meta( $term->term_id, '_gpa_cidade_regiao', true );
	$og_image = get_term_meta( $term->term_id, '_gpa_cidade_og_image', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="gpa_cidade_estado"><?php esc_html_e( 'Estado (UF)', 'guia-prado-aqui' ); ?></label></th>
		<td>
			<input type="text" name="gpa_cidade_estado" id="gpa_cidade_estado" value="<?php echo esc_attr( $estado ); ?>" maxlength="2" style="width:60px;" placeholder="MG">
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="gpa_cidade_regiao"><?php esc_html_e( 'Região / Referência', 'guia-prado-aqui' ); ?></label></th>
		<td>
			<input type="text" name="gpa_cidade_regiao" id="gpa_cidade_regiao" value="<?php echo esc_attr( $regiao ); ?>" placeholder="Ex: Região do Alto São Francisco">
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="gpa_cidade_og_image"><?php esc_html_e( 'URL da Imagem OG', 'guia-prado-aqui' ); ?></label></th>
		<td>
			<input type="url" name="gpa_cidade_og_image" id="gpa_cidade_og_image" value="<?php echo esc_attr( $og_image ); ?>" placeholder="https://">
			<p class="description"><?php esc_html_e( 'Imagem OG para SEO e redes sociais.', 'guia-prado-aqui' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Salva os campos extras da cidade.
 */
function gpa_cidade_save_fields( int $term_id ): void {
	if (
		! isset( $_POST['gpa_cidade_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gpa_cidade_nonce'] ) ), 'gpa_cidade_meta' )
	) {
		return;
	}

	if ( isset( $_POST['gpa_cidade_estado'] ) ) {
		update_term_meta( $term_id, '_gpa_cidade_estado', strtoupper( sanitize_text_field( wp_unslash( $_POST['gpa_cidade_estado'] ) ) ) );
	}

	if ( isset( $_POST['gpa_cidade_regiao'] ) ) {
		update_term_meta( $term_id, '_gpa_cidade_regiao', sanitize_text_field( wp_unslash( $_POST['gpa_cidade_regiao'] ) ) );
	}

	if ( isset( $_POST['gpa_cidade_og_image'] ) ) {
		update_term_meta( $term_id, '_gpa_cidade_og_image', esc_url_raw( wp_unslash( $_POST['gpa_cidade_og_image'] ) ) );
	}
}

// ─── Colunas da lista de cidades ───────────────────────────────────────────────
add_filter( 'manage_edit-' . GPA_TAXONOMY . '_columns',      'gpa_cidade_columns' );
add_filter( 'manage_' . GPA_TAXONOMY . '_custom_column',     'gpa_cidade_column_content', 10, 3 );

function gpa_cidade_columns( array $columns ): array {
	$new = [];
	$new['cb']       = $columns['cb'];
	$new['name']     = __( 'Cidade', 'guia-prado-aqui' );
	$new['estado']   = __( 'UF', 'guia-prado-aqui' );
	$new['ranchos']  = __( 'Ranchos', 'guia-prado-aqui' );
	$new['slug']     = __( 'Slug', 'guia-prado-aqui' );
	return $new;
}

function gpa_cidade_column_content( string $content, string $column, int $term_id ): string {
	switch ( $column ) {
		case 'estado':
			$estado = get_term_meta( $term_id, '_gpa_cidade_estado', true );
			return $estado ? '<strong>' . esc_html( $estado ) . '</strong>' : '<span style="color:#aaa;">—</span>';

		case 'ranchos':
			$count = get_term( $term_id )->count;
			return '<strong>' . esc_html( $count ) . '</strong>';

		case 'slug':
			$term = get_term( $term_id );
			return '<code>' . esc_html( $term->slug ) . '</code>';
	}
	return $content;
}

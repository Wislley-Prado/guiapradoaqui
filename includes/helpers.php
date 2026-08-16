<?php
/**
 * Helpers e funções utilitárias do plugin Guia Prado Aqui
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── WhatsApp ──────────────────────────────────────────────────────────────────

/**
 * Retorna a URL completa do WhatsApp para um rancho.
 *
 * @param int    $post_id  ID do post.
 * @param string $mensagem Mensagem opcional.
 */
function gpa_get_whatsapp_url( int $post_id, string $mensagem = '' ): string {
	$tel = get_post_meta( $post_id, '_gpa_whatsapp', true );
	if ( ! $tel ) {
		$tel = get_post_meta( $post_id, '_gpa_telefone', true );
	}
	if ( ! $tel ) {
		return '';
	}

	$tel_clean = preg_replace( '/\D/', '', $tel );

	if ( ! $mensagem ) {
		$mensagem = sprintf(
			__( 'Olá! Encontrei o %s no Guia Prado Aqui e gostaria de saber sobre disponibilidade e valores.', 'guia-prado-aqui' ),
			get_the_title( $post_id )
		);
	}

	return 'https://wa.me/' . $tel_clean . '?text=' . rawurlencode( $mensagem );
}

/**
 * Retorna a URL do Google Maps para um rancho.
 *
 * @param int $post_id ID do post.
 */
function gpa_get_maps_url( int $post_id ): string {
	$lat = get_post_meta( $post_id, '_gpa_latitude', true );
	$lng = get_post_meta( $post_id, '_gpa_longitude', true );

	if ( ! $lat || ! $lng ) {
		// Tenta o link manual
		$link = get_post_meta( $post_id, '_gpa_link_maps', true );
		return $link ?: '';
	}

	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $lat . ',' . $lng );
}

// ─── Nota Prado Aqui ──────────────────────────────────────────────────────────

/**
 * Calcula e retorna a nota final do Prado Aqui.
 * Usa override manual se preenchido, caso contrário calcula pela média.
 *
 * @param int $post_id ID do post.
 * @return float|null Nota calculada ou null se não houver dados.
 */
function gpa_calc_nota_final( int $post_id ): ?float {
	// Verifica se há override manual
	$nota_manual = get_post_meta( $post_id, '_gpa_nota_manual', true );
	if ( $nota_manual !== '' && $nota_manual !== null ) {
		return (float) $nota_manual;
	}

	// Calcula pela média dos critérios
	$criterios = [
		'_gpa_nota_pesca',
		'_gpa_nota_estrutura',
		'_gpa_nota_localizacao',
		'_gpa_nota_acesso',
		'_gpa_nota_custo_beneficio',
	];

	$soma  = 0.0;
	$count = 0;

	foreach ( $criterios as $key ) {
		$val = get_post_meta( $post_id, $key, true );
		if ( $val !== '' && $val !== null ) {
			$soma += (float) $val;
			$count++;
		}
	}

	if ( $count === 0 ) {
		return null;
	}

	return round( $soma / $count, 1 );
}

/**
 * Retorna a nota final formatada.
 *
 * @param int  $post_id    ID do post.
 * @param bool $calcular   Se true, calcula se não houver valor armazenado.
 */
function gpa_get_nota_final( int $post_id, bool $calcular = true ): ?float {
	$nota = get_post_meta( $post_id, '_gpa_nota_final', true );

	if ( $nota !== '' && $nota !== null ) {
		return (float) $nota;
	}

	if ( $calcular ) {
		return gpa_calc_nota_final( $post_id );
	}

	return null;
}

// ─── Preço ────────────────────────────────────────────────────────────────────

/**
 * Retorna o preço formatado para exibição.
 *
 * @param int $post_id ID do post.
 */
function gpa_format_preco( int $post_id ): string {
	$preco      = get_post_meta( $post_id, '_gpa_preco', true );
	$tipo_preco = get_post_meta( $post_id, '_gpa_tipo_preco', true );

	if ( ! $preco ) {
		return __( 'Preço a confirmar', 'guia-prado-aqui' );
	}

	$tipos = [
		'por_pessoa' => __( 'por pessoa', 'guia-prado-aqui' ),
		'diaria'     => __( 'por diária', 'guia-prado-aqui' ),
		'pacote'     => __( '(pacote)', 'guia-prado-aqui' ),
		'outro'      => '',
		'a_confirmar'=> '',
	];

	$sufixo = $tipos[ $tipo_preco ] ?? '';
	return 'R$ ' . $preco . ( $sufixo ? ' ' . $sufixo : '' );
}

// ─── Capacidade ───────────────────────────────────────────────────────────────

/**
 * Retorna a capacidade formatada.
 *
 * @param int $post_id ID do post.
 */
function gpa_format_capacidade( int $post_id ): string {
	$cap = get_post_meta( $post_id, '_gpa_capacidade', true );
	if ( ! $cap ) {
		return __( 'Capacidade a confirmar', 'guia-prado-aqui' );
	}
	return sprintf( __( 'até %d pessoas', 'guia-prado-aqui' ), (int) $cap );
}

// ─── Status de Verificação ────────────────────────────────────────────────────

/**
 * Retorna o label de um status de verificação.
 *
 * @param string $status Slug do status.
 */
function gpa_get_status_label( string $status ): string {
	$labels = [
		'pendente'            => '🔴 Pendente',
		'em_verificacao'      => '🟡 Em verificação',
		'verificado'          => '🟢 Verificado',
		'precisa_atualizacao' => '⚠️ Precisa atualização',
		'desativado'          => '⚫ Desativado',
	];
	return $labels[ $status ] ?? '🔴 Pendente';
}

/**
 * Retorna as cores de um status de verificação.
 *
 * @param string $status Slug do status.
 * @return array{ bg: string, text: string }
 */
function gpa_get_status_color( string $status ): array {
	$colors = [
		'pendente'            => [ 'bg' => '#fce8e8', 'text' => '#c0392b' ],
		'em_verificacao'      => [ 'bg' => '#fff9e0', 'text' => '#b7860b' ],
		'verificado'          => [ 'bg' => '#e8f8ee', 'text' => '#087443' ],
		'precisa_atualizacao' => [ 'bg' => '#fff3cd', 'text' => '#856404' ],
		'desativado'          => [ 'bg' => '#ebebeb', 'text' => '#555' ],
	];
	return $colors[ $status ] ?? [ 'bg' => '#fce8e8', 'text' => '#c0392b' ];
}

// ─── Estrutura e Pesca ────────────────────────────────────────────────────────

/**
 * Retorna lista de características de estrutura do rancho.
 *
 * @param int $post_id ID do post.
 * @return string[] Lista de características ativas.
 */
function gpa_get_estrutura_list( int $post_id ): array {
	$campos = [
		'_gpa_piscina'        => '🏊 Piscina',
		'_gpa_churrasqueira'  => '🔥 Churrasqueira',
		'_gpa_area_gourmet'   => '🍽️ Área Gourmet',
		'_gpa_wifi'           => '📶 Wi-Fi',
		'_gpa_ar_condicionado'=> '❄️ Ar-condicionado',
		'_gpa_freezer'        => '🧊 Freezer',
		'_gpa_estacionamento' => '🚗 Estacionamento',
	];

	$ativos = [];
	foreach ( $campos as $key => $label ) {
		if ( get_post_meta( $post_id, $key, true ) == '1' ) {
			$ativos[] = $label;
		}
	}
	return $ativos;
}

/**
 * Retorna lista de características de pesca do rancho.
 *
 * @param int $post_id ID do post.
 * @return string[] Lista de características ativas.
 */
function gpa_get_pesca_list( int $post_id ): array {
	$campos = [
		'_gpa_pesca_barranco' => '🎣 Pesca de barranco',
		'_gpa_acesso_rio'     => '🌊 Acesso ao rio',
		'_gpa_pesqueiro'      => '🐟 Pesqueiro',
		'_gpa_pesca_noturna'  => '🌙 Pesca noturna',
		'_gpa_pesca_barco'    => '⛵ Pesca de barco',
		'_gpa_rampa'          => '🛥️ Rampa para barco',
		'_gpa_acesso_barco'   => '⚓ Acesso para barco',
		'_gpa_piloteiro'      => '👨‍✈️ Piloteiro',
	];

	$ativos = [];
	foreach ( $campos as $key => $label ) {
		if ( get_post_meta( $post_id, $key, true ) == '1' ) {
			$ativos[] = $label;
		}
	}
	return $ativos;
}

// ─── Galeria ──────────────────────────────────────────────────────────────────

/**
 * Retorna IDs das imagens da galeria de um rancho.
 *
 * @param int $post_id ID do post.
 * @return int[] Array de IDs de attachment.
 */
function gpa_get_gallery_ids( int $post_id ): array {
	$galeria = get_post_meta( $post_id, '_gpa_galeria', true );
	if ( ! $galeria ) {
		return [];
	}
	if ( is_string( $galeria ) ) {
		$galeria = maybe_unserialize( $galeria );
	}
	if ( ! is_array( $galeria ) ) {
		return [];
	}
	return array_filter( array_map( 'absint', $galeria ) );
}

// ─── Cidade ───────────────────────────────────────────────────────────────────

/**
 * Retorna o nome da cidade de um rancho.
 *
 * @param int $post_id ID do post.
 */
function gpa_get_cidade_nome( int $post_id ): string {
	$cidades = get_the_terms( $post_id, GPA_TAXONOMY );
	if ( ! $cidades || is_wp_error( $cidades ) ) {
		return '';
	}
	return $cidades[0]->name;
}

/**
 * Retorna o objeto do primeiro term cidade de um rancho.
 *
 * @param int $post_id ID do post.
 */
function gpa_get_cidade_term( int $post_id ): ?WP_Term {
	$cidades = get_the_terms( $post_id, GPA_TAXONOMY );
	if ( ! $cidades || is_wp_error( $cidades ) ) {
		return null;
	}
	return $cidades[0];
}

// ─── SEO ──────────────────────────────────────────────────────────────────────

/**
 * Gera meta description para rancho.
 *
 * @param int $post_id ID do post.
 */
function gpa_get_meta_description( int $post_id ): string {
	$excerpt = get_the_excerpt( $post_id );
	if ( $excerpt ) {
		return wp_strip_all_tags( $excerpt );
	}

	$cidade = gpa_get_cidade_nome( $post_id );
	$preco  = get_post_meta( $post_id, '_gpa_preco', true );
	$cap    = get_post_meta( $post_id, '_gpa_capacidade', true );
	$nome   = get_the_title( $post_id );

	$desc = $nome;
	if ( $cidade ) {
		$desc .= ' em ' . $cidade;
	}
	$desc .= ' — Rancho de pesca no Rio São Francisco. ';
	if ( $cap ) {
		$desc .= 'Capacidade para ' . $cap . ' pessoas. ';
	}
	if ( $preco ) {
		$desc .= 'A partir de R$ ' . $preco . '. ';
	}
	$desc .= 'Encontrado no Guia Prado Aqui.';

	return $desc;
}

// ─── Sanitização ──────────────────────────────────────────────────────────────

/**
 * Sanitiza um número decimal (0-10).
 *
 * @param mixed $value Valor de entrada.
 * @return string Valor sanitizado ou string vazia.
 */
function gpa_sanitize_decimal_0_10( $value ): string {
	if ( $value === '' || $value === null ) {
		return '';
	}
	$float = (float) str_replace( ',', '.', (string) $value );
	$float = max( 0, min( 10, $float ) );
	return (string) $float;
}

/**
 * Sanitiza telefone (somente números e sinal +).
 *
 * @param string $tel Telefone de entrada.
 */
function gpa_sanitize_telefone( string $tel ): string {
	return preg_replace( '/[^0-9+\s\-\(\)]/', '', $tel );
}

// ─── Template Parts ───────────────────────────────────────────────────────────

/**
 * Inclui um template part do plugin, com fallback para o tema.
 *
 * @param string $slug  Nome do arquivo (sem .php).
 * @param array  $data  Dados passados ao template.
 */
function gpa_get_template_part( string $slug, array $data = [] ): void {
	$theme_file  = get_theme_file_path( 'guia-prado-aqui/' . $slug . '.php' );
	$plugin_file = GPA_PLUGIN_DIR . 'templates/parts/' . $slug . '.php';

	$file = file_exists( $theme_file ) ? $theme_file : $plugin_file;

	if ( file_exists( $file ) ) {
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		include $file;
	}
}

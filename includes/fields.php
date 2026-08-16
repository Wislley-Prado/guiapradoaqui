<?php
/**
 * Meta boxes e campos customizados do rancho
 *
 * @package GuiaPradoAqui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'gpa_register_meta_boxes' );
add_action( 'save_post_' . GPA_CPT, 'gpa_save_meta_boxes', 10, 2 );

/**
 * Registra todos os meta boxes do rancho.
 */
function gpa_register_meta_boxes(): void {
	$boxes = [
		[
			'id'       => 'gpa_contato',
			'title'    => '📱 Contato',
			'callback' => 'gpa_mb_contato',
			'context'  => 'normal',
			'priority' => 'high',
		],
		[
			'id'       => 'gpa_localizacao',
			'title'    => '📍 Localização',
			'callback' => 'gpa_mb_localizacao',
			'context'  => 'normal',
			'priority' => 'high',
		],
		[
			'id'       => 'gpa_preco',
			'title'    => '💰 Preço',
			'callback' => 'gpa_mb_preco',
			'context'  => 'normal',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_capacidade',
			'title'    => '👥 Capacidade e Acomodações',
			'callback' => 'gpa_mb_capacidade',
			'context'  => 'normal',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_estrutura',
			'title'    => '🏠 Estrutura',
			'callback' => 'gpa_mb_estrutura',
			'context'  => 'normal',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_pesca',
			'title'    => '🎣 Pesca',
			'callback' => 'gpa_mb_pesca',
			'context'  => 'normal',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_galeria',
			'title'    => '🖼️ Galeria de Fotos',
			'callback' => 'gpa_mb_galeria',
			'context'  => 'normal',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_avaliacao',
			'title'    => '⭐ Avaliação Prado Aqui',
			'callback' => 'gpa_mb_avaliacao',
			'context'  => 'side',
			'priority' => 'high',
		],
		[
			'id'       => 'gpa_google',
			'title'    => '🔍 Google',
			'callback' => 'gpa_mb_google',
			'context'  => 'side',
			'priority' => 'default',
		],
		[
			'id'       => 'gpa_verificacao',
			'title'    => '🔍 Verificação (interno)',
			'callback' => 'gpa_mb_verificacao',
			'context'  => 'side',
			'priority' => 'default',
		],
	];

	foreach ( $boxes as $box ) {
		add_meta_box(
			$box['id'],
			$box['title'],
			$box['callback'],
			GPA_CPT,
			$box['context'],
			$box['priority']
		);
	}
}

// ─── Helper para render de campo ──────────────────────────────────────────────

function gpa_field_text( string $key, string $label, string $placeholder = '', string $type = 'text', string $extra = '' ): void {
	$value = get_post_meta( get_the_ID(), $key, true );
	$id    = 'gpa_' . ltrim( $key, '_' );
	?>
	<div class="gpa-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		>
	</div>
	<?php
}

function gpa_field_textarea( string $key, string $label, string $placeholder = '' ): void {
	$value = get_post_meta( get_the_ID(), $key, true );
	$id    = 'gpa_' . ltrim( $key, '_' );
	?>
	<div class="gpa-field gpa-field-full">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<textarea
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			rows="3"
		><?php echo esc_textarea( $value ); ?></textarea>
	</div>
	<?php
}

function gpa_field_select( string $key, string $label, array $options ): void {
	$value = get_post_meta( get_the_ID(), $key, true );
	$id    = 'gpa_' . ltrim( $key, '_' );
	?>
	<div class="gpa-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>">
			<?php foreach ( $options as $opt_val => $opt_label ) : ?>
				<option value="<?php echo esc_attr( $opt_val ); ?>"<?php selected( $value, $opt_val ); ?>><?php echo esc_html( $opt_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

function gpa_field_checkbox( string $key, string $label ): void {
	$value = get_post_meta( get_the_ID(), $key, true );
	?>
	<label class="gpa-checkbox">
		<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"<?php checked( $value, '1' ); ?>>
		<?php echo esc_html( $label ); ?>
	</label>
	<?php
}

function gpa_field_nota( string $key, string $label ): void {
	$value = get_post_meta( get_the_ID(), $key, true );
	$id    = 'gpa_' . ltrim( $key, '_' );
	?>
	<div class="gpa-field gpa-field-nota">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<div class="gpa-nota-wrap">
			<input
				type="number"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				min="0" max="10" step="0.1"
				class="gpa-nota-input"
				data-nota-key="<?php echo esc_attr( $key ); ?>"
			>
			<span class="gpa-nota-display"><?php echo $value ? esc_html( number_format( (float) $value, 1 ) ) : '—'; ?></span>
		</div>
	</div>
	<?php
}

// ─── Meta Boxes ───────────────────────────────────────────────────────────────

function gpa_mb_contato( WP_Post $post ): void {
	wp_nonce_field( 'gpa_save_meta_' . $post->ID, 'gpa_meta_nonce' );
	echo '<div class="gpa-meta-grid">';
	gpa_field_text( '_gpa_telefone',  'Telefone / WhatsApp', 'Ex.: 5538999000000' );
	gpa_field_text( '_gpa_whatsapp',  'WhatsApp (se diferente)', '5538999000000 (somente números)' );
	gpa_field_text( '_gpa_instagram', 'Instagram', '@ranchosagarana' );
	gpa_field_text( '_gpa_site',      'Site / Link', 'https://', 'url' );
	echo '</div>';
	echo '<p class="gpa-hint">💡 Se o WhatsApp for igual ao telefone, deixe o campo WhatsApp em branco.</p>';
}

function gpa_mb_localizacao( WP_Post $post ): void {
	echo '<div class="gpa-meta-grid">';
	gpa_field_text( '_gpa_endereco',   'Endereço completo', 'Rua, número, bairro', 'text', 'style="grid-column:1/-1"' );
	gpa_field_text( '_gpa_latitude',   'Latitude',  '-18.1488564', 'text', 'class="gpa-latlong"' );
	gpa_field_text( '_gpa_longitude',  'Longitude', '-45.2259995', 'text', 'class="gpa-latlong"' );
	gpa_field_text( '_gpa_link_maps',  'Link Google Maps (manual)', 'https://maps.google.com/...', 'url' );
	echo '</div>';

	$lat = get_post_meta( $post->ID, '_gpa_latitude', true );
	$lng = get_post_meta( $post->ID, '_gpa_longitude', true );
	if ( $lat && $lng ) {
		echo '<div class="gpa-maps-preview"><a href="' . esc_url( 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng ) . '" target="_blank" class="button">📍 Abrir no Google Maps</a>';
		echo '<span class="gpa-hint"> Link gerado automaticamente pelas coordenadas.</span></div>';
	}
	echo '<p class="gpa-hint">💡 Preencha Latitude e Longitude para gerar o link automaticamente. Use o <a href="https://maps.google.com" target="_blank">Google Maps</a> para encontrar as coordenadas do rancho.</p>';
}

function gpa_mb_preco( WP_Post $post ): void {
	echo '<div class="gpa-meta-grid">';
	gpa_field_text( '_gpa_preco', 'Preço (somente números)', 'Ex.: 890', 'text' );
	gpa_field_select( '_gpa_tipo_preco', 'Tipo de preço', [
		'a_confirmar' => 'A confirmar',
		'diaria'      => 'Por diária',
		'por_pessoa'  => 'Por pessoa',
		'pacote'      => 'Pacote',
		'outro'       => 'Outro',
	] );
	gpa_field_text( '_gpa_data_conf_preco', 'Data da última confirmação', '', 'date' );
	gpa_field_textarea( '_gpa_obs_preco', 'Observações sobre preço', 'Ex.: Preço pode variar em feriados. Inclui café da manhã.' );
	echo '</div>';
	echo '<p class="gpa-hint">⚠️ Nunca invente preços. Somente informe dados confirmados diretamente com o rancho.</p>';
}

function gpa_mb_capacidade( WP_Post $post ): void {
	echo '<div class="gpa-meta-grid">';
	gpa_field_text( '_gpa_capacidade', 'Capacidade máxima (pessoas)', 'Ex.: 12', 'number' );
	gpa_field_text( '_gpa_quartos',    'Quartos', 'Ex.: 3', 'number' );
	gpa_field_text( '_gpa_suites',     'Suítes', 'Ex.: 1', 'number' );
	gpa_field_text( '_gpa_banheiros',  'Banheiros', 'Ex.: 2', 'number' );
	echo '</div>';
}

function gpa_mb_estrutura( WP_Post $post ): void {
	echo '<div class="gpa-checkbox-grid">';
	gpa_field_checkbox( '_gpa_piscina',         '🏊 Piscina' );
	gpa_field_checkbox( '_gpa_churrasqueira',   '🔥 Churrasqueira' );
	gpa_field_checkbox( '_gpa_area_gourmet',    '🍽️ Área Gourmet' );
	gpa_field_checkbox( '_gpa_wifi',            '📶 Wi-Fi' );
	gpa_field_checkbox( '_gpa_ar_condicionado', '❄️ Ar-condicionado' );
	gpa_field_checkbox( '_gpa_freezer',         '🧊 Freezer' );
	gpa_field_checkbox( '_gpa_estacionamento',  '🚗 Estacionamento' );
	echo '</div>';
	echo '<p class="gpa-hint">⚠️ Marque somente o que foi confirmado diretamente com o rancho.</p>';
}

function gpa_mb_pesca( WP_Post $post ): void {
	echo '<div class="gpa-checkbox-grid">';
	gpa_field_checkbox( '_gpa_pesca_barranco', '🎣 Pesca de barranco' );
	gpa_field_checkbox( '_gpa_acesso_rio',     '🌊 Acesso ao rio' );
	gpa_field_checkbox( '_gpa_pesqueiro',      '🐟 Pesqueiro (lago próprio)' );
	gpa_field_checkbox( '_gpa_pesca_noturna',  '🌙 Pesca noturna' );
	gpa_field_checkbox( '_gpa_pesca_barco',    '⛵ Pesca de barco' );
	gpa_field_checkbox( '_gpa_rampa',          '🛥️ Rampa para barco' );
	gpa_field_checkbox( '_gpa_acesso_barco',   '⚓ Acesso para barco' );
	gpa_field_checkbox( '_gpa_piloteiro',      '👨‍✈️ Piloteiro disponível' );
	echo '</div>';
	gpa_field_text( '_gpa_obs_pesca', 'Observações de pesca', 'Ex.: espécies disponíveis, regras, etc.' );
}

function gpa_mb_galeria( WP_Post $post ): void {
	$galeria_ids = gpa_get_gallery_ids( $post->ID );
	?>
	<div class="gpa-gallery-container">
		<div id="gpa-gallery-preview" class="gpa-gallery-preview">
			<?php foreach ( $galeria_ids as $img_id ) : ?>
				<div class="gpa-gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
					<?php echo wp_get_attachment_image( $img_id, [ 80, 80 ], false, [ 'class' => 'gpa-gallery-thumb' ] ); ?>
					<button type="button" class="gpa-gallery-remove" data-id="<?php echo esc_attr( $img_id ); ?>" title="Remover">✕</button>
				</div>
			<?php endforeach; ?>
		</div>
		<input type="hidden" id="gpa_galeria_ids" name="_gpa_galeria" value="<?php echo esc_attr( implode( ',', $galeria_ids ) ); ?>">
		<div class="gpa-gallery-actions">
			<button type="button" id="gpa-add-gallery" class="button button-secondary">
				➕ Adicionar Imagens à Galeria
			</button>
			<button type="button" id="gpa-clear-gallery" class="button button-link-delete" style="<?php echo empty( $galeria_ids ) ? 'display:none' : ''; ?>">
				🗑️ Limpar Galeria
			</button>
		</div>
		<p class="gpa-hint">💡 Use a Biblioteca de Mídia do WordPress. Imagens são servidas em tamanhos responsivos automaticamente.</p>
	</div>
	<?php
}

function gpa_mb_avaliacao( WP_Post $post ): void {
	$nota_final = get_post_meta( $post->ID, '_gpa_nota_final', true );
	?>
	<div class="gpa-avaliacao-final">
		<div class="gpa-nota-display-large">
			<span id="gpa-nota-calculada"><?php echo $nota_final ? esc_html( number_format( (float) $nota_final, 1 ) ) : '—'; ?></span>
			<small>Nota Prado Aqui</small>
		</div>
	</div>
	<p class="gpa-avaliacao-desc">Calculada automaticamente. Preencha os critérios abaixo.</p>
	<div class="gpa-notas-criterios">
		<?php
		gpa_field_nota( '_gpa_nota_pesca',         '🎣 Pesca' );
		gpa_field_nota( '_gpa_nota_estrutura',      '🏠 Estrutura' );
		gpa_field_nota( '_gpa_nota_localizacao',    '📍 Localização' );
		gpa_field_nota( '_gpa_nota_acesso',         '🛣️ Acesso' );
		gpa_field_nota( '_gpa_nota_custo_beneficio','💰 Custo-benefício' );
		?>
	</div>
	<hr>
	<div class="gpa-nota-override">
		<?php gpa_field_nota( '_gpa_nota_manual', '✏️ Nota Manual (override)' ); ?>
		<p class="gpa-hint">Preencha apenas se quiser sobrescrever o cálculo automático.</p>
	</div>
	<input type="hidden" id="gpa_nota_final_hidden" name="_gpa_nota_final" value="<?php echo esc_attr( $nota_final ); ?>">
	<?php
}

function gpa_mb_google( WP_Post $post ): void {
	echo '<div class="gpa-meta-sidebar">';
	gpa_field_text( '_gpa_nota_google',           'Nota no Google', 'Ex.: 4.8' );
	gpa_field_text( '_gpa_qtd_avaliacoes_google',  'Quantidade de avaliações', 'Ex.: 127', 'number' );
	echo '</div>';
	echo '<p class="gpa-hint">Informações inseridas manualmente. Não são buscadas automaticamente na V1.</p>';
}

function gpa_mb_verificacao( WP_Post $post ): void {
	$status = get_post_meta( $post->ID, '_gpa_status_verificacao', true ) ?: 'pendente';
	$color  = gpa_get_status_color( $status );
	?>
	<div class="gpa-status-atual" style="background:<?php echo esc_attr( $color['bg'] ); ?>;color:<?php echo esc_attr( $color['text'] ); ?>;padding:10px;border-radius:8px;margin-bottom:12px;text-align:center;font-weight:700;">
		<?php echo esc_html( gpa_get_status_label( $status ) ); ?>
	</div>
	<div class="gpa-meta-sidebar">
		<?php
		gpa_field_select( '_gpa_status_verificacao', 'Status de verificação', [
			'pendente'            => '🔴 Pendente',
			'em_verificacao'      => '🟡 Em verificação',
			'verificado'          => '🟢 Verificado',
			'precisa_atualizacao' => '⚠️ Precisa atualização',
			'desativado'          => '⚫ Desativado',
		] );
		gpa_field_text( '_gpa_data_verificacao', 'Data da verificação', '', 'date' );
		gpa_field_text( '_gpa_nome_contato',     'Nome da pessoa contatada', 'Ex.: João (proprietário)' );
		?>
	</div>
	<hr>
	<strong style="display:block;margin:8px 0 6px;font-size:12px;color:#666;">Confirmações:</strong>
	<div class="gpa-confirmacoes">
		<?php
		gpa_field_checkbox( '_gpa_tel_confirmado',      '✅ Telefone confirmado' );
		gpa_field_checkbox( '_gpa_preco_confirmado',    '✅ Preço confirmado' );
		gpa_field_checkbox( '_gpa_estrutura_confirmada','✅ Estrutura confirmada' );
		gpa_field_checkbox( '_gpa_fotos_confirmadas',   '✅ Fotos confirmadas' );
		?>
	</div>
	<hr>
	<?php gpa_field_textarea( '_gpa_obs_interna', 'Observações internas', 'Histórico de contato, negociações, observações...' ); ?>
	<p class="gpa-hint" style="color:#c0392b;">🔒 Estas informações são internas e NUNCA aparecem para visitantes.</p>
	<?php
}

// ─── Salvar Campos ────────────────────────────────────────────────────────────

function gpa_save_meta_boxes( int $post_id, WP_Post $post ): void {
	// Verifica nonce
	if (
		! isset( $_POST['gpa_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gpa_meta_nonce'] ) ), 'gpa_save_meta_' . $post_id )
	) {
		return;
	}

	if ( ! gpa_can_save_meta( $post_id ) ) {
		return;
	}

	// ── Campos de texto simples ──────────────────────────────────────────────
	$text_fields = [
		'_gpa_telefone', '_gpa_whatsapp', '_gpa_instagram',
		'_gpa_endereco', '_gpa_latitude', '_gpa_longitude',
		'_gpa_preco', '_gpa_tipo_preco', '_gpa_data_conf_preco',
		'_gpa_capacidade', '_gpa_quartos', '_gpa_suites', '_gpa_banheiros',
		'_gpa_obs_pesca',
		'_gpa_nota_google', '_gpa_qtd_avaliacoes_google',
		'_gpa_status_verificacao', '_gpa_data_verificacao', '_gpa_nome_contato',
		'_gpa_obs_interna', '_gpa_obs_preco',
	];

	foreach ( $text_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			update_post_meta( $post_id, $field, $value );
		}
	}

	// ── URLs ─────────────────────────────────────────────────────────────────
	$url_fields = [ '_gpa_site', '_gpa_link_maps' ];
	foreach ( $url_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	// ── Campos de nota (0-10) ─────────────────────────────────────────────────
	$nota_fields = [
		'_gpa_nota_pesca', '_gpa_nota_estrutura', '_gpa_nota_localizacao',
		'_gpa_nota_acesso', '_gpa_nota_custo_beneficio', '_gpa_nota_manual',
	];
	foreach ( $nota_fields as $field ) {
		if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
			update_post_meta( $post_id, $field, gpa_sanitize_decimal_0_10( wp_unslash( $_POST[ $field ] ) ) );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	// ── Calcular nota final e salvar ──────────────────────────────────────────
	$nota_final = gpa_calc_nota_final( $post_id );
	if ( $nota_final !== null ) {
		update_post_meta( $post_id, '_gpa_nota_final', $nota_final );
	} else {
		// Se vier via JS (campo hidden)
		if ( isset( $_POST['_gpa_nota_final'] ) && $_POST['_gpa_nota_final'] !== '' ) {
			update_post_meta( $post_id, '_gpa_nota_final', gpa_sanitize_decimal_0_10( wp_unslash( $_POST['_gpa_nota_final'] ) ) );
		} else {
			delete_post_meta( $post_id, '_gpa_nota_final' );
		}
	}

	// ── Checkboxes ────────────────────────────────────────────────────────────
	$checkbox_fields = [
		// Estrutura
		'_gpa_piscina', '_gpa_churrasqueira', '_gpa_area_gourmet',
		'_gpa_wifi', '_gpa_ar_condicionado', '_gpa_freezer', '_gpa_estacionamento',
		// Pesca
		'_gpa_pesca_barranco', '_gpa_acesso_rio', '_gpa_pesqueiro',
		'_gpa_pesca_noturna', '_gpa_pesca_barco', '_gpa_rampa',
		'_gpa_acesso_barco', '_gpa_piloteiro',
		// Verificação
		'_gpa_tel_confirmado', '_gpa_preco_confirmado',
		'_gpa_estrutura_confirmada', '_gpa_fotos_confirmadas',
	];

	foreach ( $checkbox_fields as $field ) {
		update_post_meta( $post_id, $field, isset( $_POST[ $field ] ) ? '1' : '0' );
	}

	// ── Galeria ───────────────────────────────────────────────────────────────
	if ( isset( $_POST['_gpa_galeria'] ) ) {
		$galeria_raw = sanitize_text_field( wp_unslash( $_POST['_gpa_galeria'] ) );
		if ( $galeria_raw ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $galeria_raw ) ) );
			update_post_meta( $post_id, '_gpa_galeria', $ids );
		} else {
			delete_post_meta( $post_id, '_gpa_galeria' );
		}
	}

	// ── Auto-gerar link Maps ──────────────────────────────────────────────────
	$lat = get_post_meta( $post_id, '_gpa_latitude', true );
	$lng = get_post_meta( $post_id, '_gpa_longitude', true );
	if ( $lat && $lng && ! get_post_meta( $post_id, '_gpa_link_maps', true ) ) {
		$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
		update_post_meta( $post_id, '_gpa_link_maps', $maps_url );
	}

	// ── Guardar ID original da base (para não perder na importação) ───────────
	if ( isset( $_POST['_gpa_id_original'] ) ) {
		update_post_meta( $post_id, '_gpa_id_original', absint( $_POST['_gpa_id_original'] ) );
	}
}

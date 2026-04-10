<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * KEENON ROBOTICS ECUADOR — BACKEND WORDPRESS
 * ═══════════════════════════════════════════════════════════════════
 * 
 * INSTRUCCIONES DE INSTALACIÓN:
 * 
 * 1. CREAR UNA PÁGINA EN WORDPRESS:
 *    - Ve a Páginas > Añadir nueva
 *    - Titulo: "KEENON Robotics Ecuador"
 *    - Cambia al editor de código/HTML
 *    - Pega TODO el contenido del archivo keenon-ecuador.html
 *    - Publica la página
 *    
 *    NOTA: Si usas Elementor, WPBakery u otro page builder:
 *    - Usa un widget de "HTML personalizado" o "Código"
 *    - Pega el HTML completo dentro del widget
 *
 * 2. AGREGAR ESTE ARCHIVO PHP:
 *    - Copia este archivo a: wp-content/themes/TU-TEMA/
 *    - O agrega el código al functions.php de tu tema hijo
 *
 * 3. CONFIGURAR LAS IMÁGENES:
 *    - Sube las imágenes de los robots a la biblioteca de medios
 *    - Reemplaza los placeholders en el HTML con las URLs de las imágenes
 *    - Los placeholders dicen "Reemplazar con imagen del producto"
 *
 * 4. CONFIGURAR DATOS DE CONTACTO:
 *    - Busca y reemplaza "XXX-XXXX" con el teléfono real
 *    - Busca y reemplaza "9XXXXXXXX" con el WhatsApp real
 *    - Actualiza el email "robotics@soyoda.com" si es diferente
 *
 * ═══════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════
// 1. REGISTRAR CUSTOM POST TYPE PARA LEADS
// ═══════════════════════════════════════════
function keenon_register_leads_cpt() {
    register_post_type('keenon_lead', array(
        'labels' => array(
            'name'               => 'Leads KEENON',
            'singular_name'      => 'Lead KEENON',
            'menu_name'          => 'Leads KEENON',
            'add_new'            => 'Añadir Lead',
            'add_new_item'       => 'Añadir Nuevo Lead',
            'edit_item'          => 'Editar Lead',
            'view_item'          => 'Ver Lead',
            'all_items'          => 'Todos los Leads',
            'search_items'       => 'Buscar Leads',
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array('title', 'custom-fields'),
        'capability_type'    => 'post',
    ));
}
add_action('init', 'keenon_register_leads_cpt');


// ═══════════════════════════════════════════
// 2. AJAX HANDLER — PROCESAR FORMULARIO
// ═══════════════════════════════════════════
function keenon_process_lead() {
    // Verificar nonce de seguridad
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'keenon_lead_nonce')) {
        wp_send_json_error(array('message' => 'Error de seguridad'));
        wp_die();
    }

    // Sanitizar datos
    $nombre    = sanitize_text_field($_POST['nombre'] ?? '');
    $empresa   = sanitize_text_field($_POST['empresa'] ?? '');
    $email     = sanitize_email($_POST['email'] ?? '');
    $telefono  = sanitize_text_field($_POST['telefono'] ?? '');
    $industria = sanitize_text_field($_POST['industria'] ?? '');
    $robots    = sanitize_text_field($_POST['robots'] ?? '');
    $mensaje   = sanitize_textarea_field($_POST['mensaje'] ?? '');
    $source    = sanitize_text_field($_POST['source'] ?? 'web_form');

    // Validar campos requeridos
    if (empty($nombre) || empty($email) || empty($telefono)) {
        wp_send_json_error(array('message' => 'Campos requeridos faltantes'));
        wp_die();
    }

    // Crear el post del lead
    $lead_id = wp_insert_post(array(
        'post_type'   => 'keenon_lead',
        'post_title'  => $nombre . ' — ' . $empresa . ' (' . date('d/m/Y H:i') . ')',
        'post_status' => 'publish',
    ));

    if (is_wp_error($lead_id)) {
        wp_send_json_error(array('message' => 'Error al guardar'));
        wp_die();
    }

    // Guardar meta datos
    update_post_meta($lead_id, '_keenon_nombre', $nombre);
    update_post_meta($lead_id, '_keenon_empresa', $empresa);
    update_post_meta($lead_id, '_keenon_email', $email);
    update_post_meta($lead_id, '_keenon_telefono', $telefono);
    update_post_meta($lead_id, '_keenon_industria', $industria);
    update_post_meta($lead_id, '_keenon_robots', $robots);
    update_post_meta($lead_id, '_keenon_mensaje', $mensaje);
    update_post_meta($lead_id, '_keenon_source', $source);
    update_post_meta($lead_id, '_keenon_fecha', current_time('mysql'));
    update_post_meta($lead_id, '_keenon_estado', 'nuevo');

    // ═══════════════════════════════════════
    // 3. ENVIAR EMAIL DE NOTIFICACIÓN
    // ═══════════════════════════════════════
    $admin_email = get_option('admin_email');
    // También puedes agregar emails adicionales:
    // $admin_email = 'robotics@soyoda.com, gerencia@soyoda.com';

    $subject = '🤖 Nuevo Lead KEENON — ' . $nombre . ' (' . $empresa . ')';
    
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: #0066FF; color: white; padding: 20px; border-radius: 8px 8px 0 0;'>
            <h2 style='margin:0;'>🤖 Nuevo Lead KEENON Ecuador</h2>
            <p style='margin:5px 0 0;opacity:0.8;'>Solicitud recibida desde la página web</p>
        </div>
        <div style='background: #f8f9fa; padding: 24px; border: 1px solid #dee2e6; border-top:none;'>
            <table style='width:100%; border-collapse:collapse;'>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold; width:140px;'>Nombre:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'>{$nombre}</td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Empresa:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'>{$empresa}</td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Email:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'><a href='mailto:{$email}'>{$email}</a></td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Teléfono:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'><a href='tel:{$telefono}'>{$telefono}</a></td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Industria:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'>{$industria}</td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Producto:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'>{$robots}</td>
                </tr>
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6; font-weight:bold;'>Mensaje:</td>
                    <td style='padding:10px; border-bottom:1px solid #dee2e6;'>{$mensaje}</td>
                </tr>
                <tr>
                    <td style='padding:10px; font-weight:bold;'>Fuente:</td>
                    <td style='padding:10px;'>{$source}</td>
                </tr>
            </table>
        </div>
        <div style='background: #fff; padding: 16px; border: 1px solid #dee2e6; border-top:none; border-radius: 0 0 8px 8px; text-align:center;'>
            <p style='margin:0; font-size:12px; color:#999;'>
                Lead capturado el " . date('d/m/Y') . " a las " . date('H:i') . " | 
                <a href='" . admin_url('edit.php?post_type=keenon_lead') . "'>Ver en WordPress</a>
            </p>
        </div>
    </div>
    </body>
    </html>";

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: KEENON Ecuador <noreply@soyoda.com>',
    );

    wp_mail($admin_email, $subject, $body, $headers);

    // ═══════════════════════════════════════
    // 4. EMAIL DE CONFIRMACIÓN AL LEAD
    // ═══════════════════════════════════════
    if (!empty($email)) {
        $lead_subject = '¡Gracias por tu interés en KEENON Robotics! 🤖';
        $lead_body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #0066FF; color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align:center;'>
                <h1 style='margin:0; font-size:24px;'>KEENON Robotics Ecuador</h1>
                <p style='margin:8px 0 0; opacity:0.8;'>Distribuido por Soyoda</p>
            </div>
            <div style='background: #ffffff; padding: 30px; border: 1px solid #dee2e6; border-top:none;'>
                <h2 style='color:#0066FF;'>¡Hola, {$nombre}!</h2>
                <p>Hemos recibido tu solicitud y estamos emocionados de poder ayudarte a transformar <strong>{$empresa}</strong> con nuestros robots de servicio inteligentes.</p>
                <p>Un asesor especializado te contactará en las <strong>próximas 24 horas</strong> para coordinar tu demostración gratuita.</p>
                <div style='background:#f0f4ff; padding:20px; border-radius:8px; margin:20px 0;'>
                    <p style='margin:0 0 10px; font-weight:bold; color:#0066FF;'>Mientras tanto, ¿sabías que...</p>
                    <ul style='padding-left:20px; margin:0;'>
                        <li>KEENON tiene más de 100,000 robots desplegados en 60+ países</li>
                        <li>Nuestros clientes reducen hasta un 40% sus costos operativos</li>
                        <li>Garantía de hasta 48 meses con soporte local en Ecuador</li>
                    </ul>
                </div>
                <p style='text-align:center; margin-top:24px;'>
                    <a href='https://wa.me/5939XXXXXXXX' style='display:inline-block; background:#25D366; color:white; padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:bold;'>💬 Contáctanos por WhatsApp</a>
                </p>
            </div>
            <div style='padding:16px; text-align:center; border: 1px solid #dee2e6; border-top:none; border-radius:0 0 8px 8px;'>
                <p style='margin:0; font-size:12px; color:#999;'>
                    KEENON Robotics Ecuador — Distribuido por Soyoda<br>
                    Guayaquil, Ecuador | robotics@soyoda.com
                </p>
            </div>
        </div>
        </body>
        </html>";

        wp_mail($email, $lead_subject, $lead_body, $headers);
    }

    // Respuesta exitosa
    wp_send_json_success(array(
        'message' => 'Lead registrado exitosamente',
        'lead_id' => $lead_id
    ));
    wp_die();
}

// Registrar AJAX handlers (para usuarios logueados y no logueados)
add_action('wp_ajax_keenon_lead', 'keenon_process_lead');
add_action('wp_ajax_nopriv_keenon_lead', 'keenon_process_lead');


// ═══════════════════════════════════════════
// 5. AGREGAR NONCE AL FRONTEND
// ═══════════════════════════════════════════
function keenon_enqueue_scripts() {
    // Solo en la página de KEENON
    if (is_page('keenon-robotics-ecuador') || is_page('keenon')) {
        wp_localize_script('jquery', 'keenon_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('keenon_lead_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'keenon_enqueue_scripts');


// ═══════════════════════════════════════════
// 6. COLUMNAS PERSONALIZADAS EN ADMIN
// ═══════════════════════════════════════════
function keenon_lead_columns($columns) {
    return array(
        'cb'            => '<input type="checkbox" />',
        'title'         => 'Lead',
        'empresa'       => 'Empresa',
        'industria'     => 'Industria',
        'telefono'      => 'Teléfono',
        'producto'      => 'Producto',
        'source'        => 'Fuente',
        'estado'        => 'Estado',
        'date'          => 'Fecha',
    );
}
add_filter('manage_keenon_lead_posts_columns', 'keenon_lead_columns');

function keenon_lead_column_data($column, $post_id) {
    switch ($column) {
        case 'empresa':
            echo esc_html(get_post_meta($post_id, '_keenon_empresa', true));
            break;
        case 'industria':
            echo esc_html(get_post_meta($post_id, '_keenon_industria', true));
            break;
        case 'telefono':
            $tel = get_post_meta($post_id, '_keenon_telefono', true);
            echo '<a href="tel:' . esc_attr($tel) . '">' . esc_html($tel) . '</a>';
            break;
        case 'producto':
            echo esc_html(get_post_meta($post_id, '_keenon_robots', true));
            break;
        case 'source':
            $source = get_post_meta($post_id, '_keenon_source', true);
            $badge_color = $source === 'chatbot' ? '#9333ea' : '#0066FF';
            echo '<span style="background:' . $badge_color . ';color:white;padding:2px 8px;border-radius:10px;font-size:11px;">' . esc_html($source) . '</span>';
            break;
        case 'estado':
            $estado = get_post_meta($post_id, '_keenon_estado', true) ?: 'nuevo';
            $colors = array(
                'nuevo' => '#0066FF',
                'contactado' => '#F59E0B',
                'demo_agendada' => '#10B981',
                'cerrado' => '#6B7280',
                'convertido' => '#059669',
            );
            $color = $colors[$estado] ?? '#6B7280';
            echo '<span style="background:' . $color . ';color:white;padding:2px 8px;border-radius:10px;font-size:11px;">' . esc_html(ucfirst(str_replace('_', ' ', $estado))) . '</span>';
            break;
    }
}
add_action('manage_keenon_lead_posts_custom_column', 'keenon_lead_column_data', 10, 2);


// ═══════════════════════════════════════════
// 7. META BOX EN EL EDITOR DE LEADS
// ═══════════════════════════════════════════
function keenon_lead_meta_box() {
    add_meta_box(
        'keenon_lead_details',
        '📋 Detalles del Lead',
        'keenon_lead_meta_box_callback',
        'keenon_lead',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'keenon_lead_meta_box');

function keenon_lead_meta_box_callback($post) {
    $fields = array(
        'nombre'    => 'Nombre',
        'empresa'   => 'Empresa',
        'email'     => 'Email',
        'telefono'  => 'Teléfono',
        'industria' => 'Industria',
        'robots'    => 'Producto de Interés',
        'mensaje'   => 'Mensaje',
        'source'    => 'Fuente',
        'fecha'     => 'Fecha de Registro',
        'estado'    => 'Estado',
    );

    echo '<table style="width:100%; border-collapse:collapse;">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, '_keenon_' . $key, true);
        echo '<tr style="border-bottom:1px solid #eee;">';
        echo '<td style="padding:10px; font-weight:bold; width:150px;">' . esc_html($label) . '</td>';
        
        if ($key === 'estado') {
            echo '<td style="padding:10px;">';
            echo '<select name="keenon_estado" style="padding:5px;">';
            $estados = array('nuevo', 'contactado', 'demo_agendada', 'negociando', 'cerrado', 'convertido');
            foreach ($estados as $estado) {
                $selected = ($value === $estado) ? 'selected' : '';
                echo '<option value="' . $estado . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $estado)) . '</option>';
            }
            echo '</select>';
            echo '</td>';
        } elseif ($key === 'email') {
            echo '<td style="padding:10px;"><a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a></td>';
        } elseif ($key === 'telefono') {
            echo '<td style="padding:10px;"><a href="tel:' . esc_attr($value) . '">' . esc_html($value) . '</a> | <a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $value) . '" target="_blank">WhatsApp</a></td>';
        } else {
            echo '<td style="padding:10px;">' . esc_html($value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';

    // Nonce para guardar
    wp_nonce_field('keenon_save_lead_meta', 'keenon_lead_meta_nonce');
}

function keenon_save_lead_meta($post_id) {
    if (!isset($_POST['keenon_lead_meta_nonce']) || !wp_verify_nonce($_POST['keenon_lead_meta_nonce'], 'keenon_save_lead_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['keenon_estado'])) {
        update_post_meta($post_id, '_keenon_estado', sanitize_text_field($_POST['keenon_estado']));
    }
}
add_action('save_post_keenon_lead', 'keenon_save_lead_meta');


// ═══════════════════════════════════════════
// 8. WIDGET DE DASHBOARD — RESUMEN DE LEADS
// ═══════════════════════════════════════════
function keenon_dashboard_widget() {
    wp_add_dashboard_widget(
        'keenon_leads_dashboard',
        '🤖 Leads KEENON Ecuador — Resumen',
        'keenon_dashboard_widget_callback'
    );
}
add_action('wp_dashboard_setup', 'keenon_dashboard_widget');

function keenon_dashboard_widget_callback() {
    $total = wp_count_posts('keenon_lead')->publish;
    
    // Leads de esta semana
    $this_week = new WP_Query(array(
        'post_type'      => 'keenon_lead',
        'post_status'    => 'publish',
        'date_query'     => array(array('after' => '1 week ago')),
        'posts_per_page' => -1,
    ));

    // Leads nuevos (sin gestionar)
    $nuevos = new WP_Query(array(
        'post_type'      => 'keenon_lead',
        'post_status'    => 'publish',
        'meta_key'       => '_keenon_estado',
        'meta_value'     => 'nuevo',
        'posts_per_page' => -1,
    ));

    echo '<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px;">';
    echo '<div style="text-align:center; padding:16px; background:#f0f4ff; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#0066FF;">' . $total . '</div><div style="font-size:12px; color:#666;">Total Leads</div></div>';
    echo '<div style="text-align:center; padding:16px; background:#f0fdf4; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#10B981;">' . $this_week->found_posts . '</div><div style="font-size:12px; color:#666;">Esta Semana</div></div>';
    echo '<div style="text-align:center; padding:16px; background:#fef3cd; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#F59E0B;">' . $nuevos->found_posts . '</div><div style="font-size:12px; color:#666;">Sin Gestionar</div></div>';
    echo '</div>';
    
    echo '<a href="' . admin_url('edit.php?post_type=keenon_lead') . '" style="display:block; text-align:center; padding:10px; background:#0066FF; color:white; border-radius:6px; text-decoration:none; font-weight:bold;">Ver Todos los Leads →</a>';
    
    wp_reset_postdata();
}


// ═══════════════════════════════════════════
// 9. EXPORTAR LEADS A CSV
// ═══════════════════════════════════════════
function keenon_export_leads_csv() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['keenon_export_leads'])) return;

    $leads = get_posts(array(
        'post_type'      => 'keenon_lead',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=keenon_leads_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    // BOM para Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, array('Fecha', 'Nombre', 'Empresa', 'Email', 'Teléfono', 'Industria', 'Producto', 'Mensaje', 'Fuente', 'Estado'));

    foreach ($leads as $lead) {
        fputcsv($output, array(
            get_post_meta($lead->ID, '_keenon_fecha', true),
            get_post_meta($lead->ID, '_keenon_nombre', true),
            get_post_meta($lead->ID, '_keenon_empresa', true),
            get_post_meta($lead->ID, '_keenon_email', true),
            get_post_meta($lead->ID, '_keenon_telefono', true),
            get_post_meta($lead->ID, '_keenon_industria', true),
            get_post_meta($lead->ID, '_keenon_robots', true),
            get_post_meta($lead->ID, '_keenon_mensaje', true),
            get_post_meta($lead->ID, '_keenon_source', true),
            get_post_meta($lead->ID, '_keenon_estado', true),
        ));
    }
    fclose($output);
    exit;
}
add_action('admin_init', 'keenon_export_leads_csv');

// Agregar botón de exportar en la lista de leads
function keenon_export_button() {
    global $typenow;
    if ($typenow === 'keenon_lead') {
        $export_url = admin_url('edit.php?post_type=keenon_lead&keenon_export_leads=1');
        echo '<script>
            jQuery(function($) {
                $(".wrap h1").append(\'<a href="' . $export_url . '" class="page-title-action" style="background:#10B981;color:white;border:none;">📊 Exportar CSV</a>\');
            });
        </script>';
    }
}
add_action('admin_footer', 'keenon_export_button');


/**
 * ═══════════════════════════════════════════════════════════════════
 * CÓDIGO JAVASCRIPT PARA REEMPLAZAR EN EL HTML
 * ═══════════════════════════════════════════════════════════════════
 * 
 * Reemplaza la función handleFormSubmit() en el HTML con esta versión
 * que se conecta al backend de WordPress:
 * 
 * function handleFormSubmit(e) {
 *     e.preventDefault();
 *     const formData = new FormData(e.target);
 *     formData.append('action', 'keenon_lead');
 *     formData.append('nonce', keenon_ajax.nonce);
 *     formData.append('source', 'web_form');
 *     
 *     const btn = e.target.querySelector('.form-submit');
 *     btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
 *     btn.disabled = true;
 *     
 *     fetch(keenon_ajax.ajax_url, {
 *         method: 'POST',
 *         body: formData
 *     })
 *     .then(res => res.json())
 *     .then(data => {
 *         if (data.success) {
 *             btn.innerHTML = '<i class="fas fa-check-circle"></i> ¡Solicitud Enviada!';
 *             btn.style.background = '#10B981';
 *             setTimeout(() => {
 *                 btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';
 *                 btn.style.background = '';
 *                 btn.disabled = false;
 *                 e.target.reset();
 *             }, 3000);
 *         } else {
 *             btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error, intenta de nuevo';
 *             btn.style.background = '#EF4444';
 *             setTimeout(() => {
 *                 btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';
 *                 btn.style.background = '';
 *                 btn.disabled = false;
 *             }, 2000);
 *         }
 *     })
 *     .catch(() => {
 *         btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';
 *         btn.style.background = '';
 *         btn.disabled = false;
 *         alert('Error de conexión. Intenta de nuevo.');
 *     });
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════
 */

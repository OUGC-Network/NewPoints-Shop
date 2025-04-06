<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/languages/english/newpoints_shop.lang.php)
 *    Author: Diogo Parrinha
 *    Copyright: © 2009 Diogo Parrinha
 *    Copyright: © 2024 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Integrates a shop system with NewPoints.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

$l['newpoints_shop'] = 'Tienda';

$l['newpoints_shop_icon'] = 'Ícono';
$l['newpoints_shop_categories'] = 'Categorías';
$l['newpoints_shop_items'] = 'Artículos';
$l['newpoints_shop_purchase'] = 'Comprar';
$l['newpoints_shop_price'] = 'Precio';
$l['newpoints_shop_stock'] = 'Inventario';
$l['newpoints_shop_name'] = 'Nombre';
$l['newpoints_shop_description'] = 'Descripción';
$l['newpoints_shop_myitems'] = 'Mis Artículos';
$l['newpoints_shop_items_username'] = 'Artículos de {1}';
$l['newpoints_shop_send'] = 'Enviar';
$l['newpoints_shop_sell'] = 'Vender';
$l['newpoints_shop_infinite'] = 'Infinito';
$l['newpoints_shop_options'] = 'Opciones';
$l['newpoints_shop_quantity'] = 'Cantidad';
$l['newpoints_shop_limit_user'] = 'Límite por Usuario';
$l['newpoints_shop_confirm'] = 'Confirmar';
$l['newpoints_shop_send_item'] = 'Enviar Artículo';
$l['newpoints_shop_send_item_username'] = 'Nombre de Usuario';
$l['newpoints_shop_sell_item'] = 'Vender Artículo';
$l['newpoints_shop_yes'] = 'Sí';
$l['newpoints_shop_no'] = 'No';
$l['newpoints_shop_view_item'] = 'Ver Artículo';
$l['newpoints_shop_sendable'] = 'Enviable';
$l['newpoints_shop_sellable'] = 'Vendible';

// Profile
$l['newpoints_shop_edit_inventory'] = 'Editar Inventario';

// Error messages
$l['newpoints_shop_invalid_item'] = 'Artículo inválido';
$l['newpoints_shop_invalid_cat'] = 'Categoría inválida';
$l['newpoints_shop_outofstock'] = 'El artículo que seleccionaste está agotado.';
$l['newpoints_shop_not_enough'] = 'No tienes suficientes puntos para comprar el artículo seleccionado.';
$l['newpoints_shop_inline_errors'] = 'Han ocurrido los siguientes errores:';
$l['newpoints_shop_inventory_empty'] = 'Tu inventario está vacío.';
$l['newpoints_shop_selected_item_not_owned'] = 'No eres dueño del artículo seleccionado.';
$l['newpoints_shop_invalid_user'] = 'Has seleccionado un usuario inválido.';
$l['newpoints_shop_out_of_stock'] = 'Artículo agotado';
$l['newpoints_shop_user_limit_reached'] = 'No puedes comprar más artículos de este tipo.';

// Success messages
$l['newpoints_shop_item_bought'] = 'Has comprado el artículo seleccionado con éxito.<br />Ahora serás redirigido a la página anterior.';
$l['newpoints_shop_item_bought_title'] = 'Artículo comprado';
$l['newpoints_shop_item_sent'] = 'Has enviado el artículo seleccionado con éxito.<br />Ahora serás redirigido a la página anterior.';
$l['newpoints_shop_item_sent_title'] = 'Artículo enviado';
$l['newpoints_shop_item_sell'] = 'Has vendido el artículo seleccionado con éxito.<br />Ahora serás redirigido a la página anterior.';
$l['newpoints_shop_item_sell_title'] = 'Artículo vendido';

// Other messages
$l['newpoints_shop_no_items'] = 'No se encontraron artículos.';
$l['newpoints_shop_no_cats'] = 'No se encontraron categorías.';
$l['newpoints_shop_no_options'] = 'No hay opciones disponibles.';
$l['newpoints_shop_send_item_message'] = 'Ingresa el nombre del usuario al que deseas enviar el artículo.';
$l['newpoints_shop_sell_item_confirm'] = '¿Estás seguro de que deseas vender el artículo "{1}" por {2}?';
$l['newpoints_shop_bought_item_pm_subject'] = 'Has comprado un artículo';
$l['newpoints_shop_bought_item_pmadmin_subject'] = 'El usuario compró un artículo';
$l['newpoints_shop_confirm_purchase'] = '¿Estás seguro de que deseas comprar este artículo?';

// Log
$l['newpoints_shop_purchased_log'] = '{1}-{2}-(primer número = id del artículo, segundo número = precio)';
$l['newpoints_shop_sent_log'] = '{1}-{2}-{3}-(primer número = id del artículo, segundo número = id del usuario y tercero = nombre del usuario)';
$l['newpoints_shop_sell_log'] = '{1}-{2}-(primer número = id del artículo, segundo número = precio por el que se vendió el artículo)';

// Statistics
$l['newpoints_shop_item'] = 'Artículo';
$l['newpoints_shop_username'] = 'Usuario';
$l['newpoints_shop_user'] = 'Usuario';
$l['newpoints_shop_price'] = 'Precio';
$l['newpoints_shop_date'] = 'Fecha';
$l['newpoints_shop_lastpurchases'] = 'Últimas Compras';
$l['newpoints_shop_no_purchases'] = 'No se encontraron compras.';

// Private Messages
$l['newpoints_shop_item_received_title'] = 'Artículo Recibido';
$l['newpoints_shop_item_received'] = '{1} te ha enviado el artículo {2}.';

// Profile
$l['newpoints_shop_view_all_items'] = 'Ver Todos los Artículos';
$l['newpoints_shop_user_no_items'] = 'Este usuario no tiene artículos.';

$l['newpoints_shop_cant_send_item_self'] = 'No puedes enviarte un artículo a ti mismo.';

$l = array_merge($l, [
    'newpoints_shop_menu_title' => 'Tienda',

    'newpoints_buttons_my_items' => 'Mis Artículos',
    'newpoints_buttons_add_category' => 'Agregar Categoría',

    'newpoints_shop_profile_items' => 'Artículos',
    'newpoints_shop_profile_items_empty' => 'Este usuario no tiene artículos.',
    'newpoints_shop_profile_items_view_all' => 'Ver Todos los Artículos',

    'newpoints_shop_post_items' => 'Artículos',
    'newpoints_shop_post_items_empty' => 'Este usuario no tiene artículos.',
    'newpoints_shop_post_items_view_all' => 'Ver Todos los Artículos',

    'newpoints_shop_quick_edit' => 'Editar Artículos',
    'newpoints_shop_quick_edit_description' => 'Selecciona los artículos que deseas eliminar de este usuario.',

    'newpoints_shop_stats_empty' => 'No se encontraron compras.',

    'newpoints_shop_confirm_purchase_description' => '¿Estás seguro de que deseas comprar el artículo seleccionado?',
    'newpoints_shop_confirm_purchase_item' => 'Artículo',
    'newpoints_shop_confirm_purchase_price' => 'Precio',

    'newpoints_shop_confirm_send_title' => 'Enviar Artículo',
    'newpoints_shop_confirm_send_description' => '¿Estás seguro de que deseas enviar el artículo seleccionado?',
    'newpoints_shop_confirm_send_button' => 'Enviar Artículo',

    'newpoints_shop_confirm_sell_title' => 'Vender Artículo',
    'newpoints_shop_confirm_sell_description' => '¿Estás seguro de que deseas vender el artículo seleccionado?',
    'newpoints_shop_confirm_sell_button' => 'Vender Artículo',

    'newpoints_shop_category_empty' => 'Actualmente no hay artículos en esta categoría.',
    'newpoints_shop_category_edit_category' => 'Editar Categoría',
    'newpoints_shop_category_delete_category' => 'Eliminar Categoría',
    'newpoints_shop_category_add_item' => 'Agregar Artículo',
    'newpoints_shop_category_thead_icon' => 'Ícono',
    'newpoints_shop_category_thead_name' => 'Nombre',
    'newpoints_shop_category_thead_price' => 'Precio',
    'newpoints_shop_category_thead_stock' => 'Inventario',
    'newpoints_shop_category_thead_purchase' => 'Comprar',
    'newpoints_shop_category_thead_options' => 'Opciones',
    'newpoints_shop_category_thead_options_edit' => 'Editar',
    'newpoints_shop_category_thead_options_delete' => 'Eliminar',

    'newpoints_shop_add_category' => 'Agregar Categoría',
    'newpoints_shop_add_category_table_title' => 'Agregar Categoría',
    'newpoints_shop_add_category_button_create' => 'Crear Categoría',

    'newpoints_shop_edit_category' => 'Editar Categoría',
    'newpoints_shop_edit_category_table_title' => 'Editar Categoría',
    'newpoints_shop_edit_category_table_description' => 'Usa el formulario a continuación para actualizar la categoría seleccionada.',
    'newpoints_shop_edit_category_table_category_name' => 'Nombre de la Categoría',
    'newpoints_shop_edit_category_table_category_name_description' => 'Establece un nombre corto para esta categoría.',
    'newpoints_shop_edit_category_table_category_description' => 'Descripción de la Categoría',
    'newpoints_shop_edit_category_table_category_description_description' => 'Establece una descripción corta para esta categoría.',
    'newpoints_shop_edit_category_table_category_category' => 'Grupos Permitidos',
    'newpoints_shop_edit_category_table_category_category_description' => 'Selecciona los grupos permitidos para navegar (comprar, enviar, vender y ver artículos) en esta categoría.',
    'newpoints_shop_edit_category_table_category_category_all' => 'Todos los Grupos',
    'newpoints_shop_edit_category_table_category_icon_file' => 'Ícono de la Categoría',
    'newpoints_shop_edit_category_table_category_icon_file_description' => 'Sube una imagen para esta categoría.',
    'newpoints_shop_edit_category_table_category_icon_file_description_note' => 'El ícono del archivo actual será reemplazado si se sube un nuevo archivo.',
    'newpoints_shop_edit_category_table_category_display_order' => 'Orden de Visualización',
    'newpoints_shop_edit_category_table_category_display_order_description' => 'Establece el orden de visualización para esta categoría.',
    'newpoints_shop_edit_category_table_category_is_visible' => '¿Es Visible?',
    'newpoints_shop_edit_category_table_category_is_visible_description' => '¿Es esta categoría visible para los usuarios?',
    'newpoints_shop_edit_category_button_update' => 'Actualizar Categoría',

    'newpoints_shop_add_item' => 'Crear Artículo',
    'newpoints_shop_add_item_table_title' => 'Crear Artículo',
    'newpoints_shop_edit_item_button_create' => 'Crear Artículo',

    'newpoints_shop_edit_item' => 'Editar Artículo',
    'newpoints_shop_edit_item_table_title' => 'Editar Artículo',
    'newpoints_shop_edit_item_table_description' => 'Usa el formulario a continuación para actualizar el artículo seleccionado.',
    'newpoints_shop_edit_item_table_item_name' => 'Nombre del Artículo',
    'newpoints_shop_edit_item_table_item_name_description' => 'Establece un nombre corto para este artículo.',
    'newpoints_shop_edit_item_table_item_description' => 'Descripción del Artículo',
    'newpoints_shop_edit_item_table_item_description_description' => 'Establece una descripción corta para este artículo.',
    'newpoints_shop_edit_item_table_item_category' => 'Categoría del Artículo',
    'newpoints_shop_edit_item_table_item_category_description' => 'Selecciona la categoría para este artículo.',
    'newpoints_shop_edit_item_table_item_icon_file' => 'Ícono del Artículo',
    'newpoints_shop_edit_item_table_item_icon_file_description' => 'Sube una imagen para este artículo.',
    'newpoints_shop_edit_item_table_item_icon_file_description_note' => 'El ícono actual será reemplazado si se sube un nuevo archivo.',
    'newpoints_shop_edit_item_table_item_price' => 'Precio del Artículo',
    'newpoints_shop_edit_item_table_item_price_description' => 'Establece un precio para este artículo.',
    'newpoints_shop_edit_item_table_item_private_message' => 'Contenido del Mensaje Privado',
    'newpoints_shop_edit_item_table_item_private_message_description' => 'Si no está vacío, se enviará un mensaje privado cada vez que se compre este artículo.',
    'newpoints_shop_edit_item_table_item_private_message_note' => '{user_name} = Nombre de usuario
{item_name} = Nombre del artículo
{item_id} = ID del artículo
{item_image} = Imagen',
    'newpoints_shop_edit_item_table_item_private_message_admin' => 'Contenido del Mensaje Privado',
    'newpoints_shop_edit_item_table_item_private_message_admin_description' => 'Si no está vacío, se enviará un mensaje privado a los administradores de la tienda cada vez que se compre este artículo.',
    'newpoints_shop_edit_item_table_item_private_message_admin_note' => '{user_name} = Nombre de usuario
{item_name} = Nombre del artículo
{item_id} = ID del artículo
{item_image} = Imagen',
    'newpoints_shop_edit_item_table_item_display_order' => 'Orden de Visualización',
    'newpoints_shop_edit_item_table_item_display_order_description' => 'Establece el orden de visualización para este artículo.',
    'newpoints_shop_edit_item_table_item_stock' => 'Inventario del Artículo',
    'newpoints_shop_edit_item_table_item_stock_description' => 'Establece el stock para este artículo.',
    'newpoints_shop_edit_item_table_item_infinite' => 'Stock Infinito',
    'newpoints_shop_edit_item_table_item_infinite_description' => 'Stock Infinito',
    'newpoints_shop_edit_item_table_item_limit' => 'Límite por Usuario',
    'newpoints_shop_edit_item_table_item_limit_description' => 'Establece un límite para los usuarios para que tengan al mismo tiempo.',
    'newpoints_shop_edit_item_table_item_is_visible' => '¿Es Visible?',
    'newpoints_shop_edit_item_table_item_is_visible_description' => '¿Es este artículo visible para los usuarios?',
    'newpoints_shop_edit_item_table_item_can_be_sent' => 'Puede Ser Enviado',
    'newpoints_shop_edit_item_table_item_can_be_sent_description' => '¿Pueden los usuarios enviar este artículo a otros usuarios?',
    'newpoints_shop_edit_item_table_item_can_be_sold' => 'Puede Ser Vendido',
    'newpoints_shop_edit_item_table_item_can_be_sold_description' => '¿Pueden los usuarios vender este artículo de nuevo a otros usuarios?',
    'newpoints_shop_edit_item_button_update' => 'Actualizar Artículo',

    'newpoints_shop_redirect_category_add' => 'La categoría seleccionada se ha agregado correctamente.<br/>Ahora serás redirigido a la página anterior.',
    'newpoints_shop_redirect_category_update' => 'La categoría seleccionada se ha actualizado correctamente.<br/>Ahora serás redirigido a la página anterior.',
    'newpoints_shop_redirect_category_delete' => 'La categoría seleccionada se ha eliminado correctamente.<br/>Ahora serás redirigido a la página anterior.',
    'newpoints_shop_redirect_item_add' => 'El artículo seleccionado se ha agregado correctamente.<br/>Ahora serás redirigido a la página anterior.',
    'newpoints_shop_redirect_item_update' => 'El artículo seleccionado se ha actualizado correctamente.<br/>Ahora serás redirigido a la página anterior.',
    'newpoints_shop_redirect_item_delete' => 'El artículo seleccionado se ha eliminado correctamente.<br/>Ahora serás redirigido a la página anterior.',

    'newpoints_shop_error_invalid_item_name' => 'El nombre seleccionado no es válido.',
    'newpoints_shop_error_invalid_item_icon' => 'El archivo de ícono seleccionado no es válido.',
    'newpoints_shop_error_invalid_item_category' => 'La categoría seleccionada no es válida.',

    'newpoints_shop_confirm_category_delete_title' => 'Confirmar Eliminación de Categoría',
    'newpoints_page_confirm_category_delete_text' => '¿Estás seguro de que quieres eliminar la categoría seleccionada?',
    'newpoints_shop_confirm_category_delete_button' => 'Confirmar Eliminación',

    'newpoints_shop_confirm_item_delete_title' => 'Confirmar Eliminación de Artículo',
    'newpoints_page_confirm_item_delete_text' => '¿Estás seguro de que quieres eliminar el artículo seleccionado?',
    'newpoints_shop_confirm_item_delete_button' => 'Confirmar Eliminación',

    'newpoints_shop_yes' => 'Sí',
    'newpoints_shop_no' => 'No',

    'newpoints_alert_text_shop_item_received' => 'Has recibido el artículo "{1}" de la tienda de {2}.',
    'newpoints_alert_text_shop_item_deleted' => 'El artículo "{1}" de la tienda fue eliminado de tu cuenta.',
    'newpoints_alert_text_shop_item_received_fallback' => 'Has recibido un artículo de la tienda de.',
    'newpoints_alert_text_shop_item_deleted_fallback' => 'Un artículo de la tienda fue eliminado de tu cuenta.',

    'myalerts_setting_newpoints_shop_item_received' => '¿Recibir alerta cuando recibas un artículo de la tienda?',
    'myalerts_setting_newpoints_shop_item_deleted' => '¿Recibir alerta cuando un artículo de la tienda sea eliminado de mi cuenta?',

    'newpoints_shop_dvz_stream_purchases' => 'Compras en la Tienda',
    'newpoints_shop_dvz_stream_sends' => 'Envíos de la Tienda',
    'newpoints_shop_dvz_stream_sales' => 'Ventas en la Tienda',
    'newpoints_shop_dvz_stream_event_purchase' => 'Artículo de la Tienda Comprado',
    'newpoints_shop_dvz_stream_event_send' => 'Artículo de la Tienda Enviado',
    'newpoints_shop_dvz_stream_event_sell' => 'Artículo de la Tienda Vendido',
    'newpoints_shop_dvz_stream_purchased' => 'Compraste el artículo {1} por {2} {3}.',
    'newpoints_shop_dvz_stream_sent' => 'Enviaste el artículo {1} a {2}.',
    'newpoints_shop_dvz_stream_sold' => 'Vendiste el artículo {1} por {2} {3}.',

    'newpoints_shop_wol_location' => 'Viendo la página <a href="{1}/{2}">Tienda</a>.',

    'newpoints_shop_page_logs_shop_purchase' => 'Compra en la Tienda',
    'newpoints_shop_page_logs_shop_send' => 'Artículo de la Tienda Enviado',
    'newpoints_shop_page_logs_shop_item_received' => 'Artículo de la Tienda Recibido',
    'newpoints_shop_page_logs_shop_sell' => 'Venta en la Tienda',
    'newpoints_shop_page_logs_shop_quick_item_delete' => 'Artículo de la Tienda Eliminado',
]);
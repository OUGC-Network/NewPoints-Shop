<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/languages/english/admin/newpoints_shop.lang.php)
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
$l['newpoints_shop_canmanage'] = '¿Puede gestionar la tienda?';

$l['setting_group_newpoints_shop'] = 'Tienda';
$l['setting_group_newpoints_shop_desc'] = 'Integra un sistema de tienda con NewPoints.';

$l['setting_newpoints_shop_action_name'] = 'Nombre de la acción';
$l['setting_newpoints_shop_action_name_desc'] = 'Selecciona el nombre de entrada de la acción a utilizar para esta función.';
$l['setting_newpoints_shop_manage_groups'] = 'Gestionar Grupos';
$l['setting_newpoints_shop_manage_groups_desc'] = 'Selecciona los grupos que pueden gestionar la tienda.';
$l['setting_newpoints_shop_per_page'] = 'Paginación de artículos por página';
$l['setting_newpoints_shop_per_page_desc'] = 'Número de artículos a mostrar por página en el mercado de firmas.';
$l['setting_newpoints_shop_menu_order'] = 'Orden del menú';
$l['setting_newpoints_shop_menu_order_desc'] = 'Orden en el ítem del menú de NewPoints';
$l['setting_newpoints_shop_enable_dvz_stream'] = 'Habilitar integración con DVZ Stream';
$l['setting_newpoints_shop_enable_dvz_stream_desc'] = 'Habilita la integración con DVZ Stream para las compras, ventas y envíos de la tienda.';
$l['setting_newpoints_shop_lastpurchases'] = 'Últimas compras';
$l['setting_newpoints_shop_lastpurchases_desc'] = 'Número de últimas compras para mostrar en las estadísticas.';
$l['setting_newpoints_shop_itemsprofile'] = 'Artículos en el perfil';
$l['setting_newpoints_shop_itemsprofile_desc'] = 'Número de artículos a mostrar en la página del perfil. Pon 0 para desactivar esta función.';
$l['setting_newpoints_shop_itemspostbit'] = 'Artículos en el postbit';
$l['setting_newpoints_shop_itemspostbit_desc'] = 'Número de artículos a mostrar en el postbit. Pon 0 para desactivar esta función.';
$l['setting_newpoints_shop_pmadmins'] = 'Enviar PM a los administradores';
$l['setting_newpoints_shop_pmadmins_desc'] = 'Ingresa los ID de usuario de los usuarios que recibirán PMs cuando un artículo sea comprado (separados por coma).';
$l['setting_newpoints_shop_pm_default'] = 'PM predeterminado';
$l['setting_newpoints_shop_pm_default_desc'] = 'Ingresa el contenido del cuerpo del mensaje que se envía por defecto a los usuarios cuando compran un artículo (nota: este PM puede ser personalizado para cada artículo; esto se usa en caso de que no haya uno presente). Puedes usar {item_name} y {item_id}.';
$l['setting_newpoints_shop_pmadmin_default'] = 'PM predeterminado para administradores';
$l['setting_newpoints_shop_pmadmin_default_desc'] = 'Ingresa el contenido del cuerpo del mensaje que se envía por defecto a los administradores cuando un usuario compra un artículo (nota: este PM puede ser personalizado para cada artículo; esto se usa en caso de que no haya uno presente). Puedes usar {item_name} y {item_id}.';
$l['setting_newpoints_shop_upload_path'] = 'Ruta de carga';
$l['setting_newpoints_shop_upload_path_desc'] = 'Escribe la ruta donde se cargarán las imágenes de categoría y artículo.';
$l['setting_newpoints_shop_upload_dimensions'] = 'Dimensiones de carga';
$l['setting_newpoints_shop_upload_dimensions_desc'] = 'Escribe las dimensiones máximas para las imágenes de categoría y artículo. Por defecto, 32|32.';
$l['setting_newpoints_shop_upload_size'] = 'Tamaño de carga';
$l['setting_newpoints_shop_upload_size_desc'] = 'Escribe el tamaño máximo en bytes para las imágenes de categoría y artículo. Por defecto, 50.';

// Other messages
$l['newpoints_shop_no_items'] = 'No se encontraron artículos.';
$l['newpoints_shop_no_cats'] = 'No se encontraron categorías.';

// Statistics
$l['newpoints_shop_item'] = 'Artículo';
$l['newpoints_shop_username'] = 'Usuario';
$l['newpoints_shop_price'] = 'Precio';
$l['newpoints_shop_date'] = 'Fecha';
$l['newpoints_stats_lastpurchases'] = 'Últimas Compras';

$l = array_merge($l, [
    'newpoints_recount_shop_user_items' => 'Reconstruir los artículos de usuario de NewPoints Shop',
    'newpoints_recount_shop_user_items_desc' => 'Cuando esto se ejecute, el almacenamiento legado de artículos de usuarios en NewPoints Shop será convertido al nuevo sistema de almacenamiento en la base de datos.',

    'setting_newpoints_quick_edit_shop_delete_refund' => 'Reembolso por eliminación de artículo en la tienda',
    'setting_newpoints_quick_edit_shop_delete_refund_desc' => 'Cuando se elimine un artículo de la tienda, se reembolsará al usuario el precio del artículo.',
    'setting_newpoints_quick_edit_shop_delete_stock_increase' => 'Aumento del stock por eliminación de artículo',
    'setting_newpoints_quick_edit_shop_delete_stock_increase_desc' => 'Pon "sí" si deseas que los artículos aumenten en 1 al eliminar los artículos de los usuarios.',

    'newpoints_user_groups_shop_can_view' => '¿Puede ver la tienda?',
    'newpoints_user_groups_shop_can_view_inventories' => '¿Puede ver los inventarios?',
    'newpoints_user_groups_shop_can_send' => '¿Puede enviar artículos?',
    'newpoints_user_groups_shop_can_purchase' => '¿Puede comprar artículos?',
    'newpoints_user_groups_shop_can_sell' => '¿Puede vender artículos?',
    'newpoints_user_groups_rate_shop_purchase' => 'Porcentaje de tasa de compra en la tienda <code style="color: darkorange;">El más bajo de todos los grupos.</code><br /><small class="input">La tasa para comprar artículos en la tienda. El valor predeterminado es <code>100</code>.</small><br />',
    'newpoints_user_groups_rate_shop_sell' => 'Porcentaje de tasa de venta en la tienda <br /><small class="input">La tasa para vender artículos de vuelta a la tienda. El valor predeterminado es <code>90</code>.</small><br />',
]);
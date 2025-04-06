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

// Tabs
$l['newpoints_shop_categories'] = 'Categories';
$l['newpoints_shop_categories_desc'] = 'Manage categories.';
$l['newpoints_shop_items'] = 'Items';
$l['newpoints_shop_items_desc'] = 'Manage items in the selected category.';

$l['newpoints_shop_addcat'] = 'Add Category';
$l['newpoints_shop_addcat_desc'] = 'Add a new category to the shop.';
$l['newpoints_shop_editcat'] = 'Edit Category';
$l['newpoints_shop_editcat_desc'] = 'Edit an existing category.';

$l['newpoints_shop_additem'] = 'Add Item';
$l['newpoints_shop_additem_desc'] = 'Add a new item to the shop.';
$l['newpoints_shop_edititem'] = 'Edit Item';
$l['newpoints_shop_edititem_desc'] = 'Edit an existing item.';

$l['newpoints_shop_inventory'] = 'Inventory';
$l['newpoints_shop_inventory_desc'] = 'Browsing a user\'s inventory.';

// Tcat
$l['newpoints_shop_cat_name'] = 'Name';
$l['newpoints_shop_cat_description'] = 'Description';
$l['newpoints_shop_cat_disporder'] = 'Display Order';
$l['newpoints_shop_cat_items'] = 'Items';
$l['newpoints_shop_cat_action'] = 'Action';

$l['newpoints_shop_item_action'] = 'Action';
$l['newpoints_shop_item_name'] = 'Name';
$l['newpoints_shop_item_description'] = 'Description';
$l['newpoints_shop_item_price'] = 'Price';
$l['newpoints_shop_item_stock'] = 'Stock';
$l['newpoints_shop_item_icon'] = 'Icon';
$l['newpoints_shop_item_disporder'] = 'Display Order';

// Add/Edit Cat
$l['newpoints_shop_addedit_cat_name'] = 'Name';
$l['newpoints_shop_addedit_cat_name_desc'] = 'Name of the category.';
$l['newpoints_shop_addedit_cat_description'] = 'Description';
$l['newpoints_shop_addedit_cat_description_desc'] = 'Description of the category.';
$l['newpoints_shop_addedit_cat_usergroups'] = 'Visible to Usergroups';
$l['newpoints_shop_addedit_cat_usergroups_desc'] = 'Select the user groups that can view this category.';
$l['newpoints_shop_addedit_cat_icon'] = 'Icon';
$l['newpoints_shop_addedit_cat_icon_desc'] = 'Path to a small icon image.';
$l['newpoints_shop_addedit_cat_visible'] = 'Visible';
$l['newpoints_shop_addedit_cat_visible_desc'] = 'Set to no if you do not want this category to be visible.';
$l['newpoints_shop_addedit_cat_disporder'] = 'Display Order';
$l['newpoints_shop_addedit_cat_disporder_desc'] = 'Display order of the category.';
$l['newpoints_shop_addedit_cat_expanded'] = 'Expanded by default';
$l['newpoints_shop_addedit_cat_expanded_desc'] = 'Do you want the category to be expanded by default?';

// Add/Edit Item
$l['newpoints_shop_addedit_item_name'] = 'Name';
$l['newpoints_shop_addedit_item_name_desc'] = 'Name of the item.';
$l['newpoints_shop_addedit_item_description'] = 'Description';
$l['newpoints_shop_addedit_item_description_desc'] = 'Description of the item.';
$l['newpoints_shop_addedit_item_price'] = 'Price';
$l['newpoints_shop_addedit_item_price_desc'] = 'Price of the item.';
$l['newpoints_shop_addedit_item_icon'] = 'Icon';
$l['newpoints_shop_addedit_item_icon_desc'] = 'Path to a small icon image.';
$l['newpoints_shop_addedit_item_visible'] = 'Visible';
$l['newpoints_shop_addedit_item_visible_desc'] = 'Set to no if you do not want this item to be visible.';
$l['newpoints_shop_addedit_item_disporder'] = 'Display Order';
$l['newpoints_shop_addedit_item_disporder_desc'] = 'Display order of the item.';
$l['newpoints_shop_addedit_item_stock'] = 'Stock';
$l['newpoints_shop_addedit_item_stock_desc'] = 'Number of items in stock.';
$l['newpoints_shop_addedit_item_limit'] = 'Limit per User';
$l['newpoints_shop_addedit_item_limit_desc'] = 'Maximum number of items of this type one user can purchase. Leave empty/zero for infinite.';
$l['newpoints_shop_addedit_item_infinite'] = 'Infinite Stock';
$l['newpoints_shop_addedit_item_infinite_desc'] = 'Set to yes if you want this item to have an infinite stock.';
$l['newpoints_shop_addedit_item_sendable'] = 'Users can send';
$l['newpoints_shop_addedit_item_sendable_desc'] = 'Users can send this item to other users?';
$l['newpoints_shop_addedit_item_sellable'] = 'Users can sell';
$l['newpoints_shop_addedit_item_sellable_desc'] = 'Users can sell this item to other users?';
$l['newpoints_shop_addedit_item_category'] = 'Category';
$l['newpoints_shop_addedit_item_category_desc'] = 'The category where this item is going to be placed.';
$l['newpoints_shop_addedit_item_pm'] = 'Private Message';
$l['newpoints_shop_addedit_item_pm_desc'] = 'Enter the private message received by users when they purchase this item. Leave blank to use the default PM.';
$l['newpoints_shop_addedit_item_pmadmin'] = 'Admin Private Message';
$l['newpoints_shop_addedit_item_pmadmin_desc'] = 'Enter the private message received by admins when users purchase this item. Leave to use the default PM.';
$l['newpoints_shop_infinite'] = 'Infinite';
$l['newpoints_shop_select_cat'] = 'Select a category';

// Inventory
$l['newpoints_shop_inventory_of'] = 'Viewing Inventory of ';

// Success messages
$l['newpoints_shop_cat_added'] = 'A new category has been added';
$l['newpoints_shop_cat_edited'] = 'The selected category has been edited.';
$l['newpoints_shop_cat_deleted'] = 'The selected category has been deleted.';

$l['newpoints_shop_item_added'] = 'A new item has been added';
$l['newpoints_shop_item_edited'] = 'The selected item has been edited.';
$l['newpoints_shop_item_deleted'] = 'The selected item has been deleted.';

$l['newpoints_shop_item_removed'] = 'The item has been removed from the user\'s inventory.';

// Error messages
$l['newpoints_shop_invalid_cat'] = 'Invalid category';
$l['newpoints_shop_invalid_item'] = 'Invalid item';
$l['newpoints_shop_missing_field'] = 'There\'s at least one field missing.';
$l['newpoints_shop_invalid_user'] = 'You have selected an invalid user.';
$l['newpoints_shop_inventory_empty'] = 'The inventory is empty.';
$l['newpoints_shop_selected_item_not_owned'] = 'The user does not own the selected item.';

// Confirm messages
$l['newpoints_shop_confirm_deletecat'] = 'Are you sure you want to delete the selected category?';
$l['newpoints_shop_confirm_deleteitem'] = 'Are you sure you want to delete the selected item?';
$l['newpoints_shop_confirm_removeitem'] = 'Are you sure you want to remove the selected item from the user\'s inventory?';

// Other messages
$l['newpoints_shop_no_items'] = 'No se encontraron artículos.';
$l['newpoints_shop_no_cats'] = 'No se encontraron categorías.';

// Buttons
$l['newpoints_shop_edit'] = 'Edit';
$l['newpoints_shop_delete'] = 'Delete';
$l['newpoints_shop_remove'] = 'Remove';
$l['newpoints_shop_submit'] = 'Submit';
$l['newpoints_shop_reset'] = 'Reset';

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
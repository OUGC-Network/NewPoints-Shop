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

$l['newpoints_shop'] = 'Shop';
$l['newpoints_shop_canmanage'] = 'Can manage Shop?';

$l['setting_group_newpoints_shop'] = 'Shop';
$l['setting_group_newpoints_shop_desc'] = 'Integrates a shop system with NewPoints.';

$l['setting_newpoints_shop_action_name'] = 'Action Name';
$l['setting_newpoints_shop_action_name_desc'] = 'Select the action input name to use for this feature.';
$l['setting_newpoints_shop_manage_groups'] = 'Manage Groups';
$l['setting_newpoints_shop_manage_groups_desc'] = 'Select the groups that can manage the shop.';
$l['setting_newpoints_shop_per_page'] = 'Pagination Per Page Items';
$l['setting_newpoints_shop_per_page_desc'] = 'Number of items to display per page in the signature market.';
$l['setting_newpoints_shop_menu_order'] = 'Menu Order';
$l['setting_newpoints_shop_menu_order_desc'] = 'Order in the Newpoints menu item';
$l['setting_newpoints_shop_enable_dvz_stream'] = 'Enable DVZ Stream Integration';
$l['setting_newpoints_shop_enable_dvz_stream_desc'] = 'Enable DVZ Stream integration for the shop purchases, sales, and sends.';
$l['setting_newpoints_shop_lastpurchases'] = 'Last Purchases';
$l['setting_newpoints_shop_lastpurchases_desc'] = 'Number of last purchases to show in statistics.';
$l['setting_newpoints_shop_itemsprofile'] = 'Items on profile';
$l['setting_newpoints_shop_itemsprofile_desc'] = 'Number of items to show in profile page. Set to 0 to disable this feature.';
$l['setting_newpoints_shop_itemspostbit'] = 'Items on postbit';
$l['setting_newpoints_shop_itemspostbit_desc'] = 'Number of items to show in postbit. Set to 0 to disable this feature.';
$l['setting_newpoints_shop_pmadmins'] = 'PM Admins';
$l['setting_newpoints_shop_pmadmins_desc'] = 'Enter the user IDs of the users that get PMs whenever an item is bought (separated by a comma).';
$l['setting_newpoints_shop_pm_default'] = 'Default PM';
$l['setting_newpoints_shop_pm_default_desc'] = 'Enter the content of the message body that is sent by default to users when they purchase an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {item_name} and {item_id}.';
$l['setting_newpoints_shop_pmadmin_default'] = 'Default Admin PM';
$l['setting_newpoints_shop_pmadmin_default_desc'] = 'Enter the content of the message body that is sent by default to admins when a user purchases an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {item_name} and {item_id}.';
$l['setting_newpoints_shop_upload_path'] = 'Uploads Path';
$l['setting_newpoints_shop_upload_path_desc'] = 'Type the path where the category  and item images will be uploaded.';
$l['setting_newpoints_shop_upload_dimensions'] = 'Uploads Dimensions';
$l['setting_newpoints_shop_upload_dimensions_desc'] = 'Type the maximum dimensions for the category and item images. Default 32|32.';
$l['setting_newpoints_shop_upload_size'] = 'Uploads Size';
$l['setting_newpoints_shop_upload_size_desc'] = 'Type the maximum size in bytes for the category and item images. Default 50.';

// Other messages
$l['newpoints_shop_no_items'] = 'No items found.';
$l['newpoints_shop_no_cats'] = 'No categories found.';

// Statistics
$l['newpoints_shop_item'] = 'Item';
$l['newpoints_shop_username'] = 'User';
$l['newpoints_shop_price'] = 'Price';
$l['newpoints_shop_date'] = 'Date';
$l['newpoints_stats_lastpurchases'] = 'Last Purchases';

$l = array_merge($l, [
    'newpoints_recount_shop_user_items' => 'Rebuild NewPoints Shop User Items',
    'newpoints_recount_shop_user_items_desc' => 'When this is run, the NewPoints Shop users items legacy storage will be converted to the new database storage system.',

    'setting_newpoints_quick_edit_shop_delete_refund' => 'Shop Item Delete Refund',
    'setting_newpoints_quick_edit_shop_delete_refund_desc' => 'When a shop item is deleted, the user will be refunded the price of the item.',
    'setting_newpoints_quick_edit_shop_delete_stock_increase' => 'Shop Item Delete Stock Increase',
    'setting_newpoints_quick_edit_shop_delete_stock_increase_desc' => 'Set to yes if you want items to be increased by 1 when removing users items.',

    'newpoints_user_groups_shop_can_view' => 'Can view Shop?',
    'newpoints_user_groups_shop_can_view_inventories' => 'Can view inventories?',
    'newpoints_user_groups_shop_can_send' => 'Can send items?',
    'newpoints_user_groups_shop_can_purchase' => 'Can purchase items?',
    'newpoints_user_groups_shop_can_sell' => 'Can sell items?',
    'newpoints_user_groups_rate_shop_purchase' => 'Shop Purchase Rate Percentage <code style="color: darkorange;">Lowest from all groups.</code><br /><small class="input">The rate for purchasing items from the shop. Default is <code>100</code>.</small><br />',
    'newpoints_user_groups_rate_shop_sell' => 'Shop Sell Rate Percentage <br /><small class="input">The rate for selling items back to the shop. Default is <code>90</code>.</small><br />',
]);
<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/languages/english/newpoints_shop.lang.php)
 *    Author: Diogo Parrinha
 *    Copyright: © 2009 Diogo Parrinha
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

$l['newpoints_shop_icon'] = 'Icon';
$l['newpoints_shop_categories'] = 'Categories';
$l['newpoints_shop_items'] = 'Items';
$l['newpoints_shop_purchase'] = 'Purchase';
$l['newpoints_shop_price'] = 'Price';
$l['newpoints_shop_stock'] = 'Stock';
$l['newpoints_shop_name'] = 'Name';
$l['newpoints_shop_description'] = 'Description';
$l['newpoints_shop_myitems'] = 'My Items';
$l['newpoints_shop_items_username'] = '{1}\'s Items';
$l['newpoints_shop_send'] = 'Send';
$l['newpoints_shop_sell'] = 'Sell';
$l['newpoints_shop_infinite'] = 'Infinite';
$l['newpoints_shop_options'] = 'Options';
$l['newpoints_shop_quantity'] = 'Quantity';
$l['newpoints_shop_limit_user'] = 'Limit per User';
$l['newpoints_shop_confirm'] = 'Confirm';
$l['newpoints_shop_send_item'] = 'Send Item';
$l['newpoints_shop_send_item_username'] = 'Username';
$l['newpoints_shop_sell_item'] = 'Sell Item';
$l['newpoints_shop_yes'] = 'Yes';
$l['newpoints_shop_no'] = 'No';
$l['newpoints_shop_view_item'] = 'View Item';
$l['newpoints_shop_sendable'] = 'Sendable';
$l['newpoints_shop_sellable'] = 'Sellable';

// Profile
$l['newpoints_shop_edit_inventory'] = 'Edit Inventory';

// Error messages
$l['newpoints_shop_invalid_item'] = 'Invalid item';
$l['newpoints_shop_invalid_cat'] = 'Invalid category';
$l['newpoints_shop_outofstock'] = 'The item you selected is out of stock.';
$l['newpoints_shop_not_enough'] = 'You do not have enough points to purchase the selected item.';
$l['newpoints_shop_inline_errors'] = 'The following errors have occurred:';
$l['newpoints_shop_inventory_empty'] = 'Your inventory is empty.';
$l['newpoints_shop_selected_item_not_owned'] = 'You do not own the selected item.';
$l['newpoints_shop_invalid_user'] = 'You have selected an invalid user.';
$l['newpoints_shop_out_of_stock'] = 'Item out of stock';
$l['newpoints_shop_user_limit_reached'] = 'You cannot purchase anymore items of this type.';

// Success messages
$l['newpoints_shop_item_bought'] = 'You have successfully bought the selected item.<br />You will now be redirected back to the previous page.';
$l['newpoints_shop_item_bought_title'] = 'Item bought';
$l['newpoints_shop_item_sent'] = 'You have successfully sent the selected item.<br />You will now be redirected back to the previous page.';
$l['newpoints_shop_item_sent_title'] = 'Item sent';
$l['newpoints_shop_item_sell'] = 'You have successfully sold the selected item.<br />You will now be redirected back to the previous page.';
$l['newpoints_shop_item_sell_title'] = 'Item sold';

// Other messages
$l['newpoints_shop_no_items'] = 'No items found.';
$l['newpoints_shop_no_cats'] = 'No categories found.';
$l['newpoints_shop_no_options'] = 'No options available.';
$l['newpoints_shop_send_item_message'] = 'Enter the name of the user you want to send the item to.';
$l['newpoints_shop_sell_item_confirm'] = 'Are you sure you want to sell the item "{1}" for {2}?';
$l['newpoints_shop_bought_item_pm_subject'] = 'You have bought an item';
$l['newpoints_shop_bought_item_pmadmin_subject'] = 'User bought an item';
$l['newpoints_shop_confirm_purchase'] = 'Are you sure you want to purchase this item?';

// Log
$l['newpoints_shop_purchased_log'] = '{1}-{2}-(first number = item id, second number = price)';
$l['newpoints_shop_sent_log'] = '{1}-{2}-{3}-(first number = item id, second number = user id and third = user name)';
$l['newpoints_shop_sell_log'] = '{1}-{2}-(first number = item id, second number = price the item was sold for)';

// Statistics
$l['newpoints_shop_item'] = 'Item';
$l['newpoints_shop_username'] = 'User';
$l['newpoints_shop_user'] = 'User';
$l['newpoints_shop_price'] = 'Price';
$l['newpoints_shop_date'] = 'Date';
$l['newpoints_shop_lastpurchases'] = 'Last Purchases';
$l['newpoints_shop_no_purchases'] = 'No purchases were found.';

// Private Messages
$l['newpoints_shop_item_received_title'] = 'Item Received';
$l['newpoints_shop_item_received'] = '{1} has sent you the item {2}.';

// Profile
$l['newpoints_shop_view_all_items'] = 'View All Items';
$l['newpoints_shop_user_no_items'] = 'This user has no items.';

$l['newpoints_shop_cant_send_item_self'] = 'You cannot send an item to yourself.';

$l = array_merge($l, [
    'newpoints_shop_menu_title' => 'Shop',

    'newpoints_buttons_my_items' => 'My Items',
    'newpoints_buttons_add_category' => 'Add Category',

    'newpoints_shop_profile_items' => 'Items',
    'newpoints_shop_profile_items_empty' => 'This user has no items.',
    'newpoints_shop_profile_items_view_all' => 'View All Items',

    'newpoints_shop_post_items' => 'Items',
    'newpoints_shop_post_items_empty' => 'This user has no items.',
    'newpoints_shop_post_items_view_all' => 'View All Items',

    'newpoints_shop_quick_edit' => 'Shop Items',
    'newpoints_shop_quick_edit_description' => 'Select the items you want to remove from this user.',

    'newpoints_shop_stats_empty' => 'No purchases were found.',

    'newpoints_shop_confirm_purchase_description' => ' Are you sure you want to purchase the selected item? ',
    'newpoints_shop_confirm_purchase_item' => 'Item',
    'newpoints_shop_confirm_purchase_price' => 'Price',

    'newpoints_shop_confirm_send_title' => 'Send Item',
    'newpoints_shop_confirm_send_description' => ' Are you sure you want to send the selected item? ',
    'newpoints_shop_confirm_send_button' => 'Send Item',

    'newpoints_shop_confirm_sell_title' => 'Sell Item',
    'newpoints_shop_confirm_sell_description' => ' Are you sure you want to sell the selected item? ',
    'newpoints_shop_confirm_sell_button' => 'Sell Item',

    'newpoints_shop_category_empty' => 'There are currently no items in this category.',
    'newpoints_shop_category_edit_category' => 'Edit Category',
    'newpoints_shop_category_delete_category' => 'Delete Category',
    'newpoints_shop_category_add_item' => 'Add Item',
    'newpoints_shop_category_thead_icon' => 'Icon',
    'newpoints_shop_category_thead_name' => 'Name',
    'newpoints_shop_category_thead_price' => 'Price',
    'newpoints_shop_category_thead_stock' => 'Stock',
    'newpoints_shop_category_thead_purchase' => 'Purchase',
    'newpoints_shop_category_thead_options' => 'Options',
    'newpoints_shop_category_thead_options_edit' => 'Edit',
    'newpoints_shop_category_thead_options_delete' => 'Delete',

    'newpoints_shop_add_category' => 'Add Category',
    'newpoints_shop_add_category_table_title' => 'Add Category',

    'newpoints_shop_edit_category' => 'Edit Category',
    'newpoints_shop_edit_category_table_title' => 'Edit Category',
    'newpoints_shop_edit_category_table_description' => 'Use the form below to update the selected category.',
    'newpoints_shop_edit_category_table_category_name' => 'Category Name',
    'newpoints_shop_edit_category_table_category_name_description' => 'Set a short name for this category. ',
    'newpoints_shop_edit_category_table_category_description' => 'Category Description',
    'newpoints_shop_edit_category_table_category_description_description' => 'Set a short description for this category.',
    'newpoints_shop_edit_category_table_category_category' => 'Allowed Groups',
    'newpoints_shop_edit_category_table_category_category_description' => 'Select the groups allowed to navigate (purchase, send, sell, and view items) this category.',
    'newpoints_shop_edit_category_table_category_icon_file' => 'Category Icon',
    'newpoints_shop_edit_category_table_category_icon_file_description' => 'Upload an image for this category.',
    'newpoints_shop_edit_category_table_category_icon_file_description_note' => 'Current file icon will be replaced if a new file is uploaded.',
    'newpoints_shop_edit_category_table_category_display_order' => 'Display Order',
    'newpoints_shop_edit_category_table_category_display_order_description' => 'Set the display order for this category.',
    'newpoints_shop_edit_category_table_category_is_visible' => 'Is Visible',
    'newpoints_shop_edit_category_table_category_is_visible_description' => 'Is this category visible to users?',
    'newpoints_shop_edit_category_button_update' => 'Update Category',

    'newpoints_shop_add_item' => 'Add Item',
    'newpoints_shop_add_item_table_title' => 'Add Item',

    'newpoints_shop_edit_item' => 'Edit Item',
    'newpoints_shop_edit_item_table_title' => 'Edit Item',
    'newpoints_shop_edit_item_table_description' => 'Use the form below to update the selected item.',
    'newpoints_shop_edit_item_table_item_name' => 'Item Name',
    'newpoints_shop_edit_item_table_item_name_description' => 'Set a short name for this item. ',
    'newpoints_shop_edit_item_table_item_description' => 'Item Description',
    'newpoints_shop_edit_item_table_item_description_description' => 'Set a short description for this item.',
    'newpoints_shop_edit_item_table_item_category' => 'Item Category',
    'newpoints_shop_edit_item_table_item_category_description' => 'Select the category for this item.',
    'newpoints_shop_edit_item_table_item_icon_file' => 'Item Icon',
    'newpoints_shop_edit_item_table_item_icon_file_description' => 'Upload an image for this item.',
    'newpoints_shop_edit_item_table_item_icon_file_description_note' => 'Current file icon will be replaced if a new file is uploaded.',
    'newpoints_shop_edit_item_table_item_price' => 'Item Price',
    'newpoints_shop_edit_item_table_item_price_description' => 'Set a price for this item.',
    'newpoints_shop_edit_item_table_item_private_message' => 'Private Message Content',
    'newpoints_shop_edit_item_table_item_private_message_description' => 'If not empty, will send a Private Message whenever this item is purchased.',
    'newpoints_shop_edit_item_table_item_private_message_note' => '{user_name} = Username
{item_name} = Item name
{item_id} = Item ID
{item_image} = Image',
    'newpoints_shop_edit_item_table_item_private_message_admin' => 'Private Message Content',
    'newpoints_shop_edit_item_table_item_private_message_admin_description' => 'If not empty, will send a Private Message to shop managers whenever this item is purchased.',
    'newpoints_shop_edit_item_table_item_private_message_admin_note' => '{user_name} = Username
{item_name} = Item name
{item_id} = Item ID
{item_image} = Image',
    'newpoints_shop_edit_item_table_item_display_order' => 'Display Order',
    'newpoints_shop_edit_item_table_item_display_order_description' => 'Set the display order for this item.',
    'newpoints_shop_edit_item_table_item_stock' => 'Item Stock',
    'newpoints_shop_edit_item_table_item_stock_description' => 'Set a stock for this item.',
    'newpoints_shop_edit_item_table_item_infinite' => 'Infinite Stock',
    'newpoints_shop_edit_item_table_item_infinite_description' => 'Infinite Stock',
    'newpoints_shop_edit_item_table_item_limit' => 'User Limit',
    'newpoints_shop_edit_item_table_item_limit_description' => 'Set a limit for users to own at the same time.',
    'newpoints_shop_edit_item_table_item_is_visible' => 'Is Visible',
    'newpoints_shop_edit_item_table_item_is_visible_description' => 'Is this item visible to users?',
    'newpoints_shop_edit_item_table_item_can_be_sent' => 'Can Be Sent',
    'newpoints_shop_edit_item_table_item_can_be_sent_description' => 'Users can send this item to other users?',
    'newpoints_shop_edit_item_table_item_can_be_sold' => 'Can Be Sold',
    'newpoints_shop_edit_item_table_item_can_be_sold_description' => 'Users can sell this item back to other users?',
    'newpoints_shop_edit_item_button_update' => 'Update Item',

    'newpoints_shop_redirect_category_add' => 'The selected category has been added successfully.<br/>You will now be redirected back to the previous page.',
    'newpoints_shop_redirect_category_update' => 'The selected category has been updated successfully.<br/>You will now be redirected back to the previous page.',
    'newpoints_shop_redirect_category_delete' => 'The selected category has been deleted successfully.<br/>You will now be redirected back to the previous page.',
    'newpoints_shop_redirect_item_add' => 'The selected item has been added successfully.<br/>You will now be redirected back to the previous page.',
    'newpoints_shop_redirect_item_update' => 'The selected item has been updated successfully.<br/>You will now be redirected back to the previous page.',
    'newpoints_shop_redirect_item_delete' => 'The selected item has been deleted successfully.<br/>You will now be redirected back to the previous page.',

    'newpoints_shop_error_invalid_item_name' => 'The selected name is invalid.',
    'newpoints_shop_error_invalid_item_icon' => 'The selected icon file is invalid.',
    'newpoints_shop_error_invalid_item_category' => 'The selected category is invalid.',

    'newpoints_shop_confirm_category_delete_title' => 'Confirm Category Delete',
    'newpoints_page_confirm_category_delete_text' => 'Are you sure you want to delete the selected category?',
    'newpoints_shop_confirm_category_delete_button' => 'Confirm Delete',

    'newpoints_shop_confirm_item_delete_title' => 'Confirm Item Delete',
    'newpoints_page_confirm_item_delete_text' => 'Are you sure you want to delete the selected item?',
    'newpoints_shop_confirm_item_delete_button' => 'Confirm Delete',

    'newpoints_shop_yes' => 'Yes',
    'newpoints_shop_no' => 'No',

    'newpoints_shop_dvz_stream' => 'Shop',
    'newpoints_shop_dvz_stream_event_purchase' => 'Shop Item Purchased',
    'newpoints_shop_dvz_stream_event_send' => 'Shop Item Sent',
    'newpoints_shop_dvz_stream_purchased' => 'Purchased item {1} for {2} {3}.',
    'newpoints_shop_dvz_stream_sent' => 'Sent item {1} to {2}.',
]);
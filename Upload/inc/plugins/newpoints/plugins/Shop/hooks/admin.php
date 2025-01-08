<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/hooks/admin.php)
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

declare(strict_types=1);

namespace Newpoints\Shop\Hooks\Admin;

use Form;
use FormContainer;
use MyBB;
use Table;

use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\points_add_simple;
use function Newpoints\Core\points_format;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\users_get_group_permissions;
use function Newpoints\Shop\Admin\recount_rebuild_legacy_storage;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\item_get;

use const Newpoints\Shop\Admin\FIELDS_DATA;
use const Newpoints\Shop\ROOT;

function newpoints_settings_rebuild_start(array &$hook_arguments): array
{
    language_load('shop');

    $hook_arguments['settings_directories'][] = ROOT . '/settings';

    return $hook_arguments;
}

function newpoints_templates_rebuild_start(array &$hook_arguments): array
{
    $hook_arguments['templates_directories']['shop'] = ROOT . '/templates';

    return $hook_arguments;
}

function newpoints_admin_user_groups_edit_graph_start(array &$hook_arguments): array
{
    language_load('shop');

    $hook_arguments['data_fields'] = array_merge(
        $hook_arguments['data_fields'],
        FIELDS_DATA['usergroups']
    );

    return $hook_arguments;
}

function newpoints_admin_user_groups_edit_commit_start(array &$hook_arguments): array
{
    return newpoints_admin_user_groups_edit_graph_start($hook_arguments);
}

function newpoints_admin_stats_noaction_end(): bool
{
    global $db, $lang, $mybb;

    language_load('shop');

    echo '<br />';

    // table
    $table = new Table();

    $table->construct_header($lang->newpoints_shop_item, ['width' => '30%']);

    $table->construct_header($lang->newpoints_shop_username, ['width' => '30%']);

    $table->construct_header($lang->newpoints_shop_price, ['width' => '20%', 'class' => 'align_center']);

    $table->construct_header($lang->newpoints_shop_date, ['width' => '20%', 'class' => 'align_center']);

    $query = $db->simple_select(
        'newpoints_log',
        '*',
        'action=\'shop_purchase\'',
        [
            'order_by' => 'date',
            'order_dir' => 'DESC',
            'limit' => (int)get_setting('shop_lastpurchases')
        ]
    );

    while ($stats = $db->fetch_array($query)) {
        $data = explode('-', $stats['data']);

        $item = item_get(["iid='{$data[0]}'"]);

        $table->construct_cell(htmlspecialchars_uni($item['name']));

        $link = build_profile_link(htmlspecialchars_uni($stats['username']), (int)$stats['uid']);

        $table->construct_cell($link);

        $table->construct_cell(points_format((float)$data[1]), ['class' => 'align_center']);

        $table->construct_cell(
            my_date($mybb->settings['dateformat'], (int)$stats['date'], '', false) . ', ' . my_date(
                $mybb->settings['timeformat'],
                (int)$stats['date']
            ),
            ['class' => 'align_center']
        );

        $table->construct_row();
    }

    if ($table->num_rows() == 0) {
        $table->construct_cell($lang->newpoints_error_gathering, ['colspan' => 4]);

        $table->construct_row();
    }

    $table->output($lang->newpoints_stats_lastpurchases);

    return true;
}

function newpoints_admin_load()
{
    global $db, $lang, $mybb, $page, $run_module, $action_file, $plugins;

    language_load('shop');

    if ($run_module !== 'newpoints' || $action_file !== 'newpoints_shop') {
        return false;
    }

    $cid = $mybb->get_input('cid', MyBB::INPUT_INT);

    if ($mybb->request_method == 'post') {
        switch ($mybb->get_input('action')) {
            case 'do_addcat':
                if ($mybb->get_input('name') == '') {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_missing_field, true);
                }

                $name = $db->escape_string($mybb->get_input('name'));

                $description = $db->escape_string($mybb->get_input('description'));

                // get visible to user groups options
                if ($mybb->get_input('usergroups', MyBB::INPUT_ARRAY)) {
                    foreach ($mybb->get_input('usergroups', MyBB::INPUT_ARRAY) as $gid) {
                        if (isset($mybb->input['usergroups'][$gid])) {
                            unset($mybb->input['usergroups'][$gid]);
                        }
                    }
                    $usergroups = implode(',', $mybb->get_input('usergroups', MyBB::INPUT_ARRAY));
                } else {
                    $usergroups = '';
                }

                $usergroups = $db->escape_string($usergroups);

                $visible = $mybb->get_input('visible', MyBB::INPUT_INT);

                $icon = '';

                if (isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '') {
                    $icon = basename($_FILES['icon']['name']);

                    if ($icon) {
                        $icon = 'icon_' . TIME_NOW . '_' . md5(uniqid((string)rand(), true)) . '.' . get_extension(
                                $icon
                            );
                    }

                    // Already exists?
                    if (file_exists(MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error2, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    if (!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    $icon = $db->escape_string('uploads/shop/' . $icon);
                }

                $disporder = $mybb->get_input('disporder', MyBB::INPUT_INT);

                $expanded = $mybb->get_input('expanded', MyBB::INPUT_INT);

                $insert_query = [
                    'name' => $name,
                    'description' => $description,
                    'usergroups' => $usergroups,
                    'visible' => $visible,
                    'disporder' => $disporder,
                    'icon' => $icon,
                    'expanded' => $expanded
                ];

                $db->insert_query('newpoints_shop_categories', $insert_query);

                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_cat_added);
                break;
            case 'do_editcat':

                if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
                }

                if ($mybb->get_input('name') == '') {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_missing_field, true);
                }

                $name = $db->escape_string($mybb->get_input('name'));

                $description = $db->escape_string($mybb->get_input('description'));

                // get visible to user groups options
                if ($mybb->get_input('usergroups', MyBB::INPUT_ARRAY)) {
                    foreach ($mybb->get_input('usergroups', MyBB::INPUT_ARRAY) as $gid) {
                        if (isset($mybb->input['usergroups'][$gid])) {
                            unset($mybb->input['usergroups'][$gid]);
                        }
                    }
                    $usergroups = implode(',', $mybb->get_input('usergroups', MyBB::INPUT_ARRAY));
                } else {
                    $usergroups = '';
                }

                $usergroups = $db->escape_string($usergroups);

                $visible = $mybb->get_input('visible', MyBB::INPUT_INT);

                $icon = '';

                if (isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '') {
                    $icon = basename($_FILES['icon']['name']);

                    if ($icon) {
                        $icon = 'icon_' . TIME_NOW . '_' . md5(uniqid((string)rand(), true)) . '.' . get_extension(
                                $icon
                            );
                    }

                    // Already exists?
                    if (file_exists(MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error2, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    if (!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    $icon = $db->escape_string('uploads/shop/' . $icon);
                }

                $disporder = $mybb->get_input('disporder', MyBB::INPUT_INT);

                $expanded = $mybb->get_input('expanded', MyBB::INPUT_INT);

                $update_query = [
                    'name' => $name,
                    'description' => $description,
                    'usergroups' => $usergroups,
                    'visible' => $visible,
                    'disporder' => $disporder,
                    'icon' => $icon,
                    'expanded' => $expanded
                ];
                $db->update_query('newpoints_shop_categories', $update_query, 'cid=\'' . $cid . '\'');

                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_cat_edited);

                break;

            case 'do_additem':
                if ($mybb->get_input('name') == '' || !$mybb->get_input('cid', MyBB::INPUT_INT)) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_missing_field, true);
                }

                $name = $db->escape_string($mybb->get_input('name'));

                $description = $db->escape_string($mybb->get_input('description'));

                $icon = '';

                if (isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '') {
                    $icon = basename($_FILES['icon']['name']);
                    if ($icon) {
                        $icon = 'icon_' . TIME_NOW . '_' . md5(uniqid((string)rand(), true)) . '.' . get_extension(
                                $icon
                            );
                    }

                    // Already exists?
                    if (file_exists(MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error2, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    if (!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error, 'error');

                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    $icon = $db->escape_string('uploads/shop/' . $icon);
                }

                $pm = $db->escape_string($mybb->get_input('pm'));

                $pmadmin = $db->escape_string($mybb->get_input('pmadmin'));

                $price = $mybb->get_input('price', MyBB::INPUT_FLOAT);

                $infinite = $mybb->get_input('infinite', MyBB::INPUT_INT);

                if ($infinite == 1) {
                    $stock = 0;
                } else {
                    $stock = $mybb->get_input('stock', MyBB::INPUT_INT);
                }

                $limit = $mybb->get_input('limit', MyBB::INPUT_INT);

                $visible = $mybb->get_input('visible', MyBB::INPUT_INT);

                $disporder = $mybb->get_input('disporder', MyBB::INPUT_INT);

                $sendable = $mybb->get_input('sendable', MyBB::INPUT_INT);

                $sellable = $mybb->get_input('sellable', MyBB::INPUT_INT);

                if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
                }

                $insert_array = [
                    'name' => $name,
                    'description' => $description,
                    'icon' => $icon,
                    'visible' => $visible,
                    'disporder' => $disporder,
                    'price' => $price,
                    'infinite' => $infinite,
                    'stock' => $stock,
                    'limit' => $limit,
                    'sendable' => $sendable,
                    'sellable' => $sellable,
                    'cid' => $cid,
                    'pm' => $pm,
                    'pmadmin' => $pmadmin
                ];

                $insert_array = run_hooks('shop_commit', $insert_array);

                $db->insert_query('newpoints_shop_items', $insert_array);

                $db->write_query(
                    'UPDATE ' . TABLE_PREFIX . 'newpoints_shop_categories SET items = items+1 WHERE cid=\'' . $cid . '\''
                );

                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_item_added, false, 'items&amp;cid=' . $cid);
                break;
            case 'do_edititem':
                $iid = $mybb->get_input('item_id', MyBB::INPUT_INT);
                if ($iid <= 0 || (!($item = item_get(["iid='{$iid}'"])))) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_item, true, 'items');
                }

                if ($mybb->get_input('name') == '' || !$mybb->get_input('cid', MyBB::INPUT_INT)) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_missing_field, true);
                }

                $name = $db->escape_string($mybb->get_input('name'));

                $description = $db->escape_string($mybb->get_input('description'));

                $icon = '';

                if (isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '') {
                    $icon = basename($_FILES['icon']['name']);
                    if ($icon) {
                        $icon = 'icon_' . TIME_NOW . '_' . md5(uniqid((string)rand(), true)) . '.' . get_extension(
                                $icon
                            );
                    }

                    // Already exists?
                    if (file_exists(MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error2, 'error');
                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    if (!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT . 'uploads/shop/' . $icon)) {
                        flash_message($lang->mydownloads_background_upload_error, 'error');
                        admin_redirect('index.php?module=newpoints-newpoints_shop');
                    }

                    $icon = $db->escape_string('uploads/shop/' . $icon);
                }

                $price = $mybb->get_input('price', MyBB::INPUT_FLOAT);

                $pm = $db->escape_string($mybb->get_input('pm'));

                $pmadmin = $db->escape_string($mybb->get_input('pmadmin'));

                $infinite = $mybb->get_input('infinite', MyBB::INPUT_INT);

                if ($infinite == 1) {
                    $stock = 0;
                } else {
                    $stock = $mybb->get_input('stock', MyBB::INPUT_INT);
                }

                $limit = $mybb->get_input('limit', MyBB::INPUT_INT);

                $visible = $mybb->get_input('visible', MyBB::INPUT_INT);

                $disporder = $mybb->get_input('disporder', MyBB::INPUT_INT);

                $sendable = $mybb->get_input('sendable', MyBB::INPUT_INT);

                $sellable = $mybb->get_input('sellable', MyBB::INPUT_INT);

                if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
                    \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
                }

                $update_array = [
                    'name' => $name,
                    'description' => $description,
                    'icon' => ($icon != '' ? $icon : $db->escape_string($item['icon'])),
                    'visible' => $visible,
                    'disporder' => $disporder,
                    'price' => $price,
                    'infinite' => $infinite,
                    'stock' => $stock,
                    'limit' => $limit,
                    'sendable' => $sendable,
                    'sellable' => $sellable,
                    'cid' => $cid,
                    'pm' => $pm,
                    'pmadmin' => $pmadmin
                ];

                $update_array = run_hooks('shop_commit', $update_array);

                $db->update_query('newpoints_shop_items', $update_array, 'iid=\'' . $iid . '\'');

                if ($cid != $item['cid']) {
                    $db->write_query(
                        'UPDATE ' . TABLE_PREFIX . 'newpoints_shop_categories SET items = items-1 WHERE cid=\'' . $item['cid'] . '\''
                    );
                    $db->write_query(
                        'UPDATE ' . TABLE_PREFIX . 'newpoints_shop_categories SET items = items+1 WHERE cid=\'' . $cid . '\''
                    );
                }

                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_item_edited, false, 'items&amp;cid=' . $cid);
                break;
        }
    }

    if ($mybb->get_input('action') === 'do_deletecat') {
        $page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

        $page->output_header($lang->newpoints_shop);

        if (isset($mybb->input['no'])) {
            admin_redirect('index.php?module=newpoints-shop');
        }

        if ($mybb->request_method == 'post') {
            if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
            }

            $db->delete_query('newpoints_shop_categories', "cid = $cid");

            // unassign items from this category
            $db->update_query('newpoints_shop_items', ['cid' => 0], "cid = $cid");

            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_cat_deleted);
        } else {
            $form = new Form(
                "index.php?module=newpoints-shop&amp;action=do_deletecat&amp;cid={$mybb->get_input('cid', MyBB::INPUT_INT)}&amp;my_post_key={$mybb->post_code}",
                'post'
            );

            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->newpoints_shop_confirm_deletecat}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->yes, ['class' => 'button_yes']);
            echo $form->generate_submit_button($lang->no, ['name' => 'no', 'class' => 'button_no']);
            echo "</p>\n";
            echo "</div>\n";

            $form->end();
        }
    } elseif ($mybb->get_input('action') === 'do_deleteitem') {
        $page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

        $page->output_header($lang->newpoints_shop);

        $iid = $mybb->get_input('item_id', MyBB::INPUT_INT);

        if (isset($mybb->input['no'])) {
            admin_redirect('index.php?module=newpoints-shop', 0, 'items&amp;cid=' . $cid);
        }

        if ($mybb->request_method == 'post') {
            if ($iid <= 0 || (!($item = item_get(["iid='{$iid}'"])))) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_item, true, 'items&amp;cid=' . $cid);
            }

            $db->delete_query('newpoints_shop_items', "iid = $iid");

            // remove one from the items count
            $db->write_query(
                'UPDATE ' . TABLE_PREFIX . 'newpoints_shop_categories SET items = items-1 WHERE cid=\'' . $item['cid'] . '\''
            );

            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_item_deleted, false, 'items&amp;cid=' . $cid);
        } else {
            $form = new Form(
                "index.php?module=newpoints-shop&amp;action=do_deleteitem&amp;iid={$mybb->get_input('item_id', MyBB::INPUT_INT)}&amp;my_post_key={$mybb->post_code}",
                'post'
            );

            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->newpoints_shop_confirm_deleteitem}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->yes, ['class' => 'button_yes']);
            echo $form->generate_submit_button($lang->no, ['name' => 'no', 'class' => 'button_no']);
            echo "</p>\n";
            echo "</div>\n";

            $form->end();
        }
    } elseif ($mybb->get_input('action') === 'remove') {
        $page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

        $page->output_header($lang->newpoints_shop);

        $iid = $mybb->get_input('item_id', MyBB::INPUT_INT);

        if (isset($mybb->input['no'])) {
            admin_redirect('index.php?module=newpoints-shop', 0, 'items&amp;cid=' . $cid);
        }

        if ($mybb->request_method == 'post') {
            if ($iid <= 0 || (!($item = item_get(["iid='{$iid}'"])))) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_item, true, 'items&amp;cid=' . $cid);
            }

            $uid = $mybb->get_input('uid', MyBB::INPUT_INT);

            if ($uid <= 0) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_user, true);
            }

            $user = get_user($uid);

            $user_group_permissions = users_get_group_permissions($uid);

            // we're viewing someone else's inventory
            if (empty($user)) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_user, true);
            }

            $inventory = my_unserialize($user['newpoints_items']);

            if (!$inventory) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_inventory_empty, true);
            }

            // make sure we own the item
            $key = array_search($item['iid'], $inventory);

            if ($key === false) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_selected_item_not_owned, true);
            }

            // remove item from our inventory
            unset($inventory[$key]);

            sort($inventory);

            $db->update_query('users', ['newpoints_items' => my_serialize($inventory)], 'uid=\'' . $uid . '\'');

            // update stock
            if ($item['infinite'] != 1) {
                $db->update_query(
                    'newpoints_shop_items',
                    ['stock' => $item['stock'] + 1],
                    'iid=\'' . $item['iid'] . '\''
                );
            }

            $item_price = (float)$item['price'];

            if (!empty($user_group_permissions['newpoints_rate_shop_sell'])) {
                $item_price = $item_price * ($user_group_permissions['newpoints_rate_shop_sell'] / 100);
            }

            points_add_simple(
                $uid,
                $item_price
            );

            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_item_removed, false, 'inventory&amp;uid=' . $uid);
        } else {
            $form = new Form(
                "index.php?module=newpoints-shop&amp;action=remove&amp;iid={$mybb->get_input('item_id', MyBB::INPUT_INT)}&amp;uid={$mybb->get_input('uid', MyBB::INPUT_INT)}&amp;my_post_key={$mybb->post_code}",
                'post'
            );

            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->newpoints_shop_confirm_removeitem}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->yes, ['class' => 'button_yes']);
            echo $form->generate_submit_button($lang->no, ['name' => 'no', 'class' => 'button_no']);
            echo "</p>\n";
            echo "</div>\n";

            $form->end();
        }
    }

    if (!$mybb->get_input('action') || $mybb->get_input('action') === 'categories' || $mybb->get_input(
            'action'
        ) === 'inventory' || $mybb->get_input('action') === 'addcat' || $mybb->get_input(
            'action'
        ) === 'editcat') {
        $page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

        $page->output_header($lang->newpoints_shop);

        $sub_tabs['newpoints_shop_categories'] = [
            'title' => $lang->newpoints_shop_categories,
            'link' => 'index.php?module=newpoints-shop',
            'description' => $lang->newpoints_shop_categories_desc
        ];

        if (!$mybb->get_input('action') || $mybb->get_input('action') === 'categories' || $mybb->get_input(
                'action'
            ) === 'addcat' || $mybb->get_input('action') === 'editcat') {
            $sub_tabs['newpoints_shop_categories_add'] = [
                'title' => $lang->newpoints_shop_addcat,
                'link' => 'index.php?module=newpoints-shop&amp;action=addcat',
                'description' => $lang->newpoints_shop_addcat_desc
            ];

            $sub_tabs['newpoints_shop_categories_edit'] = [
                'title' => $lang->newpoints_shop_editcat,
                'link' => 'index.php?module=newpoints-shop&amp;action=editcat',
                'description' => $lang->newpoints_shop_editcat_desc
            ];
        }
    }

    if ($mybb->get_input('action') === 'inventory') {
        $sub_tabs['newpoints_shop_inventory'] = [
            'title' => $lang->newpoints_shop_inventory,
            'link' => 'index.php?module=newpoints-shop&amp;action=inventory&amp;uid=' . $mybb->get_input(
                    'uid',
                    MyBB::INPUT_INT
                ),
            'description' => $lang->newpoints_shop_inventory_desc
        ];
    }

    if ($mybb->get_input('action') === 'items' || $mybb->get_input(
            'action'
        ) === 'additem' || $mybb->get_input('action') === 'edititem') {
        $page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

        $page->output_header($lang->newpoints_shop);

        $sub_tabs['newpoints_shop_categories'] = [
            'title' => $lang->newpoints_shop_categories,
            'link' => 'index.php?module=newpoints-shop',
            'description' => $lang->newpoints_shop_categories_desc
        ];

        $sub_tabs['newpoints_shop_items'] = [
            'title' => $lang->newpoints_shop_items,
            'link' => 'index.php?module=newpoints-shop&amp;action=items&amp;cid=' . $mybb->get_input(
                    'cid',
                    MyBB::INPUT_INT
                ),
            'description' => $lang->newpoints_shop_items_desc
        ];

        if ($mybb->get_input('action') === 'items' || $mybb->get_input(
                'action'
            ) === 'additem' || $mybb->get_input('action') === 'edititem') {
            $sub_tabs['newpoints_shop_items_add'] = [
                'title' => $lang->newpoints_shop_additem,
                'link' => 'index.php?module=newpoints-shop&amp;action=additem&amp;cid=' . intval(
                        $mybb->get_input('cid', MyBB::INPUT_INT)
                    ),
                'description' => $lang->newpoints_shop_additem_desc
            ];

            $sub_tabs['newpoints_shop_items_edit'] = [
                'title' => $lang->newpoints_shop_edititem,
                'link' => 'index.php?module=newpoints-shop&amp;action=edititem',
                'description' => $lang->newpoints_shop_edititem_desc
            ];
        }
    }

    if (!$mybb->get_input('action') || $mybb->get_input('action') === 'categories') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories');

        // table
        $table = new Table();

        $table->construct_header($lang->newpoints_shop_item_icon, ['width' => '1%']);

        $table->construct_header($lang->newpoints_shop_cat_name, ['width' => '30%']);

        $table->construct_header($lang->newpoints_shop_cat_description, ['width' => '35%']);

        $table->construct_header($lang->newpoints_shop_cat_items, ['width' => '10%', 'class' => 'align_center']);

        $table->construct_header($lang->newpoints_shop_cat_disporder, ['width' => '10%', 'class' => 'align_center']
        );

        $table->construct_header($lang->newpoints_shop_cat_action, ['width' => '25%', 'class' => 'align_center']);

        $query = $db->simple_select(
            'newpoints_shop_categories',
            '*',
            '',
            ['order_by' => 'disporder', 'order_dir' => 'ASC']
        );
        while ($cat = $db->fetch_array($query)) {
            $table->construct_cell(
                htmlspecialchars_uni(
                    $cat['icon']
                ) ? '<img src="' . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni(
                        $cat['icon']
                    ) . '" style="max-width: 24px; max-height: 24px">' : '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">',
                ['class' => 'align_center']
            );

            $table->construct_cell(
                "<a href=\"index.php?module=newpoints-shop&amp;action=items&amp;cid={$cat['cid']}\">" . htmlspecialchars_uni(
                    $cat['name']
                ) . '</a>'
            );

            $table->construct_cell(htmlspecialchars_uni($cat['description']));

            $table->construct_cell((int)$cat['items'], ['class' => 'align_center']);

            $table->construct_cell((int)$cat['disporder'], ['class' => 'align_center']);

            // actions column
            $table->construct_cell(
                "<a href=\"index.php?module=newpoints-shop&amp;action=editcat&amp;cid=" . intval(
                    $cat['cid']
                ) . "\">" . $lang->newpoints_shop_edit . "</a> - <a href=\"index.php?module=newpoints-shop&amp;action=do_deletecat&amp;cid=" . intval(
                    $cat['cid']
                ) . "\">" . $lang->newpoints_shop_delete . '</a>',
                ['class' => 'align_center']
            );

            $table->construct_row();
        }

        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->newpoints_shop_no_cats, ['colspan' => 5]);

            $table->construct_row();
        }

        $table->output($lang->newpoints_shop_categories);
    } elseif ($mybb->get_input('action') === 'addcat') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories_add');

        $query = $db->simple_select('usergroups', 'gid, title', "gid != '1'", ['order_by' => 'title']);

        while ($usergroup = $db->fetch_array($query)) {
            $options[$usergroup['gid']] = $usergroup['title'];
        }

        $form = new Form('index.php?module=newpoints-shop&amp;action=do_addcat', 'post', 'newpoints_shop', 1);

        $form_container = new FormContainer($lang->newpoints_shop_addcat);

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_name . '<em>*</em>',
            $lang->newpoints_shop_addedit_cat_name_desc,
            $form->generate_text_box('name', '', ['id' => 'name']
            ),
            'name'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_description,
            $lang->newpoints_shop_addedit_cat_description_desc,
            $form->generate_text_box('description', '', ['id' => 'description']
            ),
            'description'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_visible,
            $lang->newpoints_shop_addedit_cat_visible_desc,
            $form->generate_yes_no_radio('visible', 1),
            'visible'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_icon,
            $lang->newpoints_shop_addedit_cat_icon_desc,
            $form->generate_file_upload_box(
                'icon',
                ['style' => 'width: 200px;']
            ),
            'icon'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_usergroups,
            $lang->newpoints_shop_addedit_cat_usergroups_desc,
            $form->generate_select_box(
                'usergroups[]',
                $options,
                '',
                ['id' => 'usergroups', 'multiple' => true, 'size' => 5]
            ),
            'groups'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_disporder,
            $lang->newpoints_shop_addedit_cat_disporder_desc,
            $form->generate_text_box('disporder', '0', ['id' => 'disporder']
            ),
            'disporder'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_expanded,
            $lang->newpoints_shop_addedit_cat_expanded_desc,
            $form->generate_yes_no_radio('expanded', 1),
            'expanded'
        );

        $form_container->end();

        $buttons = [];

        $buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);

        $buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);

        $form->output_submit_wrapper($buttons);

        $form->end();
    } elseif ($mybb->get_input('action') === 'editcat') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories_edit');

        if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
        }

        $query = $db->simple_select('usergroups', 'gid, title', "gid != '1'", ['order_by' => 'title']);

        while ($usergroup = $db->fetch_array($query)) {
            $options[$usergroup['gid']] = $usergroup['title'];
        }

        $form = new Form('index.php?module=newpoints-shop&amp;action=do_editcat', 'post', 'newpoints_shop', 1);

        echo $form->generate_hidden_field('cid', $cat['cid']);

        $form_container = new FormContainer($lang->newpoints_shop_addcat);

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_name . '<em>*</em>',
            $lang->newpoints_shop_addedit_cat_name_desc,
            $form->generate_text_box('name', htmlspecialchars_uni($cat['name']), ['id' => 'name']
            ),
            'name'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_description,
            $lang->newpoints_shop_addedit_cat_description_desc,
            $form->generate_text_box(
                'description',
                htmlspecialchars_uni($cat['description']),
                ['id' => 'description']
            ),
            'description'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_visible,
            $lang->newpoints_shop_addedit_cat_visible_desc,
            $form->generate_yes_no_radio('visible', (int)$cat['visible']),
            'visible'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_icon,
            $lang->newpoints_shop_addedit_cat_icon_desc,
            $form->generate_file_upload_box(
                'icon',
                ['style' => 'width: 200px;']
            ),
            'icon'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_usergroups,
            $lang->newpoints_shop_addedit_cat_usergroups_desc,
            $form->generate_select_box(
                'usergroups[]',
                $options,
                explode(',', $cat['usergroups']),
                ['id' => 'usergroups', 'multiple' => true, 'size' => 5]
            ),
            'groups'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_disporder,
            $lang->newpoints_shop_addedit_cat_disporder_desc,
            $form->generate_text_box('disporder', (int)$cat['disporder'], ['id' => 'disporder']
            ),
            'disporder'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_cat_expanded,
            $lang->newpoints_shop_addedit_cat_expanded_desc,
            $form->generate_yes_no_radio('expanded', (int)$cat['expanded']),
            'expanded'
        );

        $form_container->end();

        $buttons = [];

        $buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);

        $buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);

        $form->output_submit_wrapper($buttons);

        $form->end();
    } elseif ($mybb->get_input('action') === 'items') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_items');

        if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
        }

        // table
        $table = new Table();

        $table->construct_header($lang->newpoints_shop_item_icon, ['width' => '1%', 'class' => 'align_center']);

        $table->construct_header($lang->newpoints_shop_item_name, ['width' => '30%']);

        $table->construct_header($lang->newpoints_shop_item_price, ['width' => '15%', 'class' => 'align_center']
        );

        $table->construct_header(
            $lang->newpoints_shop_item_disporder,
            ['width' => '15%', 'class' => 'align_center']
        );

        $table->construct_header(
            $lang->newpoints_shop_item_action,
            ['width' => '20%', 'class' => 'align_center']
        );

        $query = $db->simple_select(
            'newpoints_shop_items',
            '*',
            'cid=\'' . $cid . '\'',
            ['order_by' => 'disporder', 'order_dir' => 'ASC']
        );

        while ($item = $db->fetch_array($query)) {
            if ($item['infinite']) {
                $item['stock'] = $lang->newpoints_shop_infinite;
            }

            if (!$item['visible']) {
                $visible_info = ' (<span style="color: #FF0000;">hidden</span>)';
            } else {
                $visible_info = '';
            }

            $table->construct_cell(
                htmlspecialchars_uni(
                    $item['icon']
                ) ? '<img src="' . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni(
                        $item['icon']
                    ) . '" style="max-width: 24px; max-height: 24px">' : '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">',
                ['class' => 'align_center']
            );

            $table->construct_cell(
                htmlspecialchars_uni($item['name']) . ' (' . (intval(
                    $item['infinite']
                ) ? $lang->newpoints_shop_infinite : intval(
                    $item['stock']
                )) . ')' . $visible_info . '<br /><small>' . htmlspecialchars_uni(
                    $item['description']
                ) . '</small>'
            );

            $table->construct_cell(points_format((float)$item['price']), ['class' => 'align_center']
            );

            $table->construct_cell((int)$item['disporder'], ['class' => 'align_center']);

            // actions column
            $table->construct_cell(
                "<a href=\"index.php?module=newpoints-shop&amp;action=edititem&amp;iid=" . intval(
                    $item['iid']
                ) . "\">" . $lang->newpoints_shop_edit . "</a> - <a href=\"index.php?module=newpoints-shop&amp;action=do_deleteitem&amp;iid=" . intval(
                    $item['iid']
                ) . "\">" . $lang->newpoints_shop_delete . '</a>',
                ['class' => 'align_center']
            );

            $table->construct_row();
        }

        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->newpoints_shop_no_items, ['colspan' => 6]);

            $table->construct_row();
        }

        $table->output($lang->newpoints_shop_items);
    } elseif ($mybb->get_input('action') === 'additem') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_items_add');

        if ($cid > 0) {
            if ($cid <= 0 || (!($cat = category_get(["cid='{$cid}'"])))) {
                \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_cat, true);
            }
        } else {
            $cid = 0;
        }

        $categories[0] = $lang->newpoints_shop_select_cat;

        $query = $db->simple_select('newpoints_shop_categories');

        while ($cat = $db->fetch_array($query)) {
            $categories[$cat['cid']] = $cat['name'];
        }

        $form = new Form('index.php?module=newpoints-shop&amp;action=do_additem', 'post', 'newpoints_shop', 1);

        $form_container = new FormContainer($lang->newpoints_shop_additem);

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_name . '<em>*</em>',
            $lang->newpoints_shop_addedit_item_name_desc,
            $form->generate_text_box('name', '', ['id' => 'name']
            ),
            'name'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_description,
            $lang->newpoints_shop_addedit_item_description_desc,
            $form->generate_text_box('description', '', ['id' => 'description']
            ),
            'description'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_price,
            $lang->newpoints_shop_addedit_item_price_desc,
            $form->generate_text_box('price', '0', ['id' => 'price']
            ),
            'price'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_icon,
            $lang->newpoints_shop_addedit_item_icon_desc,
            $form->generate_file_upload_box(
                'icon',
                ['style' => 'width: 200px;']
            ),
            'icon'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_disporder,
            $lang->newpoints_shop_addedit_item_disporder_desc,
            $form->generate_text_box('disporder', '0', ['id' => 'disporder']
            ),
            'disporder'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_stock,
            $lang->newpoints_shop_addedit_item_stock_desc,
            $form->generate_text_box('stock', '0', ['id' => 'stock']
            ),
            'stock'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_infinite,
            $lang->newpoints_shop_addedit_item_infinite_desc,
            $form->generate_yes_no_radio('infinite', 1),
            'infinite'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_limit,
            $lang->newpoints_shop_addedit_item_limit_desc,
            $form->generate_text_box('limit', '0', ['id' => 'limit']
            ),
            'limit'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_visible,
            $lang->newpoints_shop_addedit_item_visible_desc,
            $form->generate_yes_no_radio('visible', 1),
            'visible'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_sendable,
            $lang->newpoints_shop_addedit_item_sendable_desc,
            $form->generate_yes_no_radio('sendable', 1),
            'sendable'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_sellable,
            $lang->newpoints_shop_addedit_item_sellable_desc,
            $form->generate_yes_no_radio('sellable', 1),
            'sellable'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_pm,
            $lang->newpoints_shop_addedit_item_pm_desc,
            $form->generate_text_area('pm', ''),
            'pm'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_pmadmin,
            $lang->newpoints_shop_addedit_item_pmadmin_desc,
            $form->generate_text_area('pmadmin', ''),
            'pmadmin'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_category . '<em>*</em>',
            $lang->newpoints_shop_addedit_item_category_desc,
            $form->generate_select_box('cid', $categories, $cid, ['id' => 'cid']
            ),
            'cid'
        );

        $args = [
            'form_container' => &$form_container,
            'form' => &$form,
            'item' => []
        ];

        $args = run_hooks('shop_row', $args);

        $form_container->end();

        $buttons = [];

        $buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);

        $buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);

        $form->output_submit_wrapper($buttons);

        $form->end();
    } elseif ($mybb->get_input('action') === 'edititem') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_items_edit');

        $iid = $mybb->get_input('item_id', MyBB::INPUT_INT);

        if ($iid <= 0 || (!($item = item_get(["iid='{$iid}'"])))) {
            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_item, true, 'items');
        }

        $categories[0] = $lang->newpoints_shop_select_cat;

        $query = $db->simple_select('newpoints_shop_categories');

        while ($cat = $db->fetch_array($query)) {
            $categories[$cat['cid']] = $cat['name'];
        }

        $form = new Form('index.php?module=newpoints-shop&amp;action=do_edititem', 'post', 'newpoints_shop', 1);

        echo $form->generate_hidden_field('item_id', $iid);

        $form_container = new FormContainer($lang->newpoints_shop_additem);

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_name . '<em>*</em>',
            $lang->newpoints_shop_addedit_item_name_desc,
            $form->generate_text_box('name', htmlspecialchars_uni($item['name']), ['id' => 'name']
            ),
            'name'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_description,
            $lang->newpoints_shop_addedit_item_description_desc,
            $form->generate_text_box(
                'description',
                htmlspecialchars_uni($item['description']),
                ['id' => 'description']
            ),
            'description'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_price,
            $lang->newpoints_shop_addedit_item_price_desc,
            $form->generate_text_box('price', (float)$item['price'], ['id' => 'price']
            ),
            'price'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_icon,
            $lang->newpoints_shop_addedit_item_icon_desc,
            $form->generate_file_upload_box(
                'icon',
                ['style' => 'width: 200px;']
            ),
            'icon'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_disporder,
            $lang->newpoints_shop_addedit_item_disporder_desc,
            $form->generate_text_box('disporder', (int)$item['disporder'], ['id' => 'disporder']
            ),
            'disporder'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_stock,
            $lang->newpoints_shop_addedit_item_stock_desc,
            $form->generate_text_box('stock', (int)$item['stock'], ['id' => 'stock']
            ),
            'stock'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_infinite,
            $lang->newpoints_shop_addedit_item_infinite_desc,
            $form->generate_yes_no_radio('infinite', (int)$item['infinite']),
            'infinite'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_limit,
            $lang->newpoints_shop_addedit_item_limit_desc,
            $form->generate_text_box('limit', (int)$item['limit'], ['id' => 'limit']
            ),
            'limit'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_visible,
            $lang->newpoints_shop_addedit_item_visible_desc,
            $form->generate_yes_no_radio('visible', (int)$item['visible']),
            'visible'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_sendable,
            $lang->newpoints_shop_addedit_item_sendable_desc,
            $form->generate_yes_no_radio('sendable', (int)$item['sendable']),
            'sendable'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_sellable,
            $lang->newpoints_shop_addedit_item_sellable_desc,
            $form->generate_yes_no_radio('sellable', (int)$item['sellable']),
            'sellable'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_pm,
            $lang->newpoints_shop_addedit_item_pm_desc,
            $form->generate_text_area('pm', htmlspecialchars_uni($item['pm']), ['id' => 'pm_text']
            ),
            'pm'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_pmadmin,
            $lang->newpoints_shop_addedit_item_pmadmin_desc,
            $form->generate_text_area('pmadmin', htmlspecialchars_uni($item['pmadmin'])),
            'pmadmin'
        );

        $form_container->output_row(
            $lang->newpoints_shop_addedit_item_category . '<em>*</em>',
            $lang->newpoints_shop_addedit_item_category_desc,
            $form->generate_select_box('cid', $categories, (int)$item['cid'], ['id' => 'cid']
            ),
            'cid'
        );

        $args = [
            'form_container' => &$form_container,
            'form' => &$form,
            'item' => &$item
        ];

        $args = run_hooks('shop_row', $args);

        $form_container->end();

        $buttons = [];

        $buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);

        $buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);

        $form->output_submit_wrapper($buttons);

        $form->end();
    } elseif ($mybb->get_input('action') === 'inventory') {
        $page->output_nav_tabs($sub_tabs, 'newpoints_shop_inventory');

        $uid = $mybb->get_input('uid', MyBB::INPUT_INT);

        if ($uid <= 0) {
            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_user, true);
        }

        $user = get_user($uid);

        // we're viewing someone else's inventory
        if (empty($user)) {
            \Newpoints\Shop\Admin\redirect($lang->newpoints_shop_invalid_user, true);
        }

        $inventory = my_unserialize($user['newpoints_items']);

        if (!$inventory) {
            $inventory = [0];
        } // Item id is 0 because it doesn't exist, this when we use it in the query we won't show anything

        // table
        $table = new Table();

        $table->construct_header(
            $lang->newpoints_shop_item_icon,
            ['width' => '10%', 'class' => 'align_center']
        );

        $table->construct_header($lang->newpoints_shop_item_name, ['width' => '30%']);

        $table->construct_header(
            $lang->newpoints_shop_item_price,
            ['width' => '15%', 'class' => 'align_center']
        );

        $table->construct_header(
            $lang->newpoints_shop_item_disporder,
            ['width' => '15%', 'class' => 'align_center']
        );

        $table->construct_header(
            $lang->newpoints_shop_item_action,
            ['width' => '20%', 'class' => 'align_center']
        );

        $query = $db->simple_select(
            'newpoints_shop_items',
            '*',
            'iid IN (' . implode(',', array_unique($inventory)) . ')',
            ['order_by' => 'disporder', 'order_dir' => 'ASC']
        );

        while ($item = $db->fetch_array($query)) {
            if ($item['infinite']) {
                $item['stock'] = $lang->newpoints_shop_infinite;
            }

            if (!$item['visible']) {
                $visible_info = ' (<span style="color: #FF0000;">hidden</span>)';
            } else {
                $visible_info = '';
            }

            $table->construct_cell(
                htmlspecialchars_uni(
                    $item['icon']
                ) ? '<img src="' . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni(
                        $item['icon']
                    ) . '" style="max-width: 24px; max-height: 24px">' : '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">',
                ['class' => 'align_center']
            );

            $table->construct_cell(
                htmlspecialchars_uni($item['name']) . ' (' . count(
                    array_keys($inventory, $item['iid'])
                ) . ')' . $visible_info . '<br /><small>' . htmlspecialchars_uni(
                    $item['description']
                ) . '</small>'
            );

            $table->construct_cell(points_format((float)$item['price']), ['class' => 'align_center']
            );

            $table->construct_cell((int)$item['disporder'], ['class' => 'align_center']);

            // actions column
            $table->construct_cell(
                "<a href=\"index.php?module=newpoints-shop&amp;action=remove&amp;iid=" . intval(
                    $item['iid']
                ) . '&amp;uid=' . (int)$user['uid'] . "\">" . $lang->newpoints_shop_remove . '</a>',
                ['class' => 'align_center']
            );

            $table->construct_row();
        }

        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->newpoints_shop_no_items, ['colspan' => 5]);

            $table->construct_row();
        }

        $table->output($lang->newpoints_shop_inventory_of . ' ' . htmlspecialchars_uni($user['username']));
    }

    $page->output_footer();

    exit;
}

function newpoints_admin_newpoints_menu(array &$sub_menu): array
{
    global $lang;

    language_load('shop');

    $sub_menu[] = ['id' => 'shop', 'title' => $lang->newpoints_shop, 'link' => 'index.php?module=newpoints-shop'];

    return $sub_menu;
}

function newpoints_admin_newpoints_action_handler(array &$actions): array
{
    $actions['shop'] = ['active' => 'shop', 'file' => 'newpoints_shop'];

    return $actions;
}

function newpoints_admin_newpoints_permissions(array &$admin_permissions): array
{
    global $lang;

    language_load('shop');

    $admin_permissions['shop'] = $lang->newpoints_shop_canmanage;

    return $admin_permissions;
}

function admin_tools_recount_rebuild_output_list(): bool
{
    global $lang;
    global $form_container, $form;

    $form_container->output_cell(
        "<label>{$lang->newpoints_recount_shop_user_items}</label><div class=\"description\">{$lang->newpoints_recount_shop_user_items_desc}</div>"
    );
    $form_container->output_cell(
        $form->generate_numeric_field('newpoints_recount_shop_user_items', 50, ['style' => 'width: 150px;', 'min' => 0])
    );
    $form_container->output_cell(
        $form->generate_submit_button($lang->go, ['name' => 'do_rebuild_newpoints_shop_user_items'])
    );
    $form_container->construct_row();

    return true;
}

function admin_tools_do_recount_rebuild(): bool
{
    global $mybb;

    if (isset($mybb->input['do_rebuild_newpoints_shop_user_items'])) {
        if ($mybb->input['page'] == 1) {
            log_admin_action('recount');
        }

        $per_page = $mybb->get_input('newpoints_recount_shop_user_items', MyBB::INPUT_INT);

        if (!$per_page || $per_page <= 0) {
            $mybb->input['newpoints_recount_shop_user_items'] = 50;
        }

        recount_rebuild_legacy_storage();
    }

    return true;
}
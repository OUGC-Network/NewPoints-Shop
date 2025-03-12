<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/hooks/admin.php)
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

declare(strict_types=1);

namespace Newpoints\Shop\Hooks\Admin;

use MyBB;
use Table;

use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\points_format;
use function Newpoints\Shop\Admin\recount_rebuild_legacy_storage;
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

function newpoints_admin_settings_intermediate(array &$hook_arguments): array
{
    language_load('shop');

    //unset($hook_arguments['active_plugins']['shop']);

    $hook_arguments['shop'] = [];

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

        $item = item_get(["iid='{$data[0]}'"], ['name']);

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

    language_load('shop');

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

function newpoints_my_alerts_install(array &$hook_arguments): array
{
    $hook_arguments['newpoints_my_alerts_formatters'][] = [
        'plugin_code' => 'shop',
        'alert_types' => ['item_received', 'item_deleted'],
    ];

    return $hook_arguments;
}

function newpoints_my_alerts_uninstall(array &$hook_arguments): array
{
    return newpoints_my_alerts_install($hook_arguments);
}
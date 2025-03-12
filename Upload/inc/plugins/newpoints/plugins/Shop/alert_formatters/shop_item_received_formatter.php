<?php

/***************************************************************************
 *
 *    NewPoints plugin (/inc/plugins/newpoints/plugins/Shop/alert_formatters/shop_item_received_formatter.php)
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

namespace Newpoints\Shop\MyAlerts\Formatters;

use MybbStuff_MyAlerts_Entity_Alert;
use MybbStuff_MyAlerts_Formatter_AbstractFormatter;

use function Newpoints\Core\language_load;
use function Newpoints\Core\log_get;
use function Newpoints\Core\main_file_name;
use function Newpoints\Core\points_format;
use function Newpoints\Core\get_setting;
use function Newpoints\Core\url_handler_build;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\item_get;

class newpoints_shop_item_received_formatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function init(): bool
    {
        return language_load('shop');
    }

    /**
     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
     *
     * @return string The formatted alert string.
     */
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert): string
    {
        $details = $alert->toArray();

        $log_id = (int)$details['object_id'];

        $log_data = log_get($log_id);

        if (empty($log_data)) {
            return $this->lang->newpoints_alert_text_shop_item_deleted_fallback;
        }

        $item_id = (int)$log_data['log_primary_id'];

        $item_data = item_get(["iid='{$item_id}'", "visible='1'"], ['cid', 'name']);

        if (empty($item_data)) {
            return $this->lang->newpoints_alert_text_shop_item_deleted_fallback;
        }

        $category_id = (int)$item_data['cid'];

        $category_data = category_get(["cid='{$category_id}'", "visible='1'"]);

        if (empty($category_data)) {
            return $this->lang->newpoints_alert_text_shop_item_deleted_fallback;
        }

        $item_name = htmlspecialchars_uni($item_data['name']);

        return $this->lang->sprintf(
            $this->lang->newpoints_alert_text_shop_item_deleted,
            $item_name
        );
    }

    /**
     * Build a link to an alert's content so that the system can redirect to it.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
     *
     * @return string The built alert, preferably an absolute link.
     */
    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert): string
    {
        global $settings;

        $action_name = get_setting('shop_action_name');

        $my_items_url = url_handler_build([
            'action' => $action_name,
            'view' => 'my_items'
        ]);

        return $settings['bburl'] . '/' . $my_items_url;
    }
}
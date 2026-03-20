<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
namespace Presta_Shop\Module\Block_Wish_List\Grid\Data;

use Presta_Shop\Presta_Shop\Core\Grid\Data\Factory\Grid_Data_Factory_Interface;
use Presta_Shop\Presta_Shop\Core\Grid\Data\Grid_Data;
use Presta_Shop\Presta_Shop\Core\Grid\Record\Record_Collection;
use Presta_Shop\Presta_Shop\Core\Grid\Search\Search_Criteria_Interface;
class All_Time_Statistics_Grid_Data_Factory extends Base_Grid_Data_Factory implements Grid_Data_Factory_Interface
{
    // 1 month
    public const CACHE_LIFETIME_SECONDS = 2629746;
    public function get_data(Search_Criteria_Interface $search_criteria)
    {
        if ($this->cache->contains(self::CACHE_KEY_STATS_ALL_TIME . $this->shop_id)) {
            $results = $this->cache->fetch(self::CACHE_KEY_STATS_ALL_TIME . $this->shop_id);
        } else {
            $results = $this->calculator->compute_stats_for('allTime');
            $this->cache->save(self::CACHE_KEY_STATS_ALL_TIME . $this->shop_id, $results, self::CACHE_LIFETIME_SECONDS);
        }
        return new Grid_Data(new Record_Collection($results), count($results));
    }
}
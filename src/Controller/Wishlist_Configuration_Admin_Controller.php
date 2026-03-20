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
namespace Presta_Shop\Module\Block_Wish_List\Controller;

use Configuration;
use Doctrine\Common\Cache\Cache_Provider;
use Language;
use Presta_Shop\Module\Block_Wish_List\Grid\Data\Base_Grid_Data_Factory;
use Presta_Shop\Module\Block_Wish_List\Type\Configuration_Type;
use Presta_Shop\Presta_Shop\Core\Grid\Search\Search_Criteria;
use Presta_Shop_Bundle\Controller\Admin\Framework_Bundle_Admin_Controller;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Request;
class Wishlist_Configuration_Admin_Controller extends Framework_Bundle_Admin_Controller
{
    /**
     * @var CacheProvider
     */
    private $cache;
    /**
     * @var int|null
     */
    private $shop_id;
    public function __construct(Cache_Provider $cache, $shop_id)
    {
        $this->cache = $cache;
        $this->shop_id = $shop_id;
    }
    public function configuration_action(Request $request)
    {
        $datas = $this->get_wishlist_configuration_datas();
        $configuration_form = $this->create_form(Configuration_Type::class, $datas);
        $configuration_form->handle_request($request);
        $result_handle_form = null;
        if ($configuration_form->is_submitted() && $configuration_form->is_valid()) {
            $result_handle_form = $this->handle_form($configuration_form->get_data());
            if ($result_handle_form) {
                return $this->redirect_to_route('blockwishlist_configuration');
            }
        }
        return $this->render('@Modules/blockwishlist/views/templates/admin/home.html.twig', ['configurationForm' => $configuration_form->create_view(), 'resultHandleForm' => $result_handle_form, 'enableSidebar' => true, 'help_link' => $this->generate_sidebar_link('WishlistConfigurationAdminController')]);
    }
    public function statistics_action()
    {
        $search_criteria = new Search_Criteria();
        $all_time_stats_grid_factory = $this->get('prestashop.module.blockwishlist.grid.all_time_stastistics_grid_factory');
        $current_year_grid_factory = $this->get('prestashop.module.blockwishlist.grid.current_year_stastistics_grid_factory');
        $current_month_grid_factory = $this->get('prestashop.module.blockwishlist.grid.current_month_stastistics_grid_factory');
        $current_day_grid_factory = $this->get('prestashop.module.blockwishlist.grid.current_day_stastistics_grid_factory');
        $all_time_statistics_grid = $all_time_stats_grid_factory->get_grid($search_criteria);
        $current_year_grid = $current_year_grid_factory->get_grid($search_criteria);
        $current_month_grid = $current_month_grid_factory->get_grid($search_criteria);
        $current_day_grid = $current_day_grid_factory->get_grid($search_criteria);
        return $this->render('@Modules/blockwishlist/views/templates/admin/statistics.html.twig', ['allTimeStatisticsGrid' => $this->present_grid($all_time_statistics_grid), 'currentYearStatisticsGrid' => $this->present_grid($current_year_grid), 'currentMonthStatisticsGrid' => $this->present_grid($current_month_grid), 'currentDayStatisticsGrid' => $this->present_grid($current_day_grid), 'shopId' => $this->shop_id, 'enableSidebar' => true, 'help_link' => $this->generate_sidebar_link('WishlistConfigurationAdminController')]);
    }
    public function reset_statistics_cache_action()
    {
        $result = $this->cache->delete(Base_Grid_Data_Factory::CACHE_KEY_STATS_ALL_TIME . $this->shop_id) && $this->cache->delete(Base_Grid_Data_Factory::CACHE_KEY_STATS_CURRENT_DAY . $this->shop_id) && $this->cache->delete(Base_Grid_Data_Factory::CACHE_KEY_STATS_CURRENT_MONTH . $this->shop_id) && $this->cache->delete(Base_Grid_Data_Factory::CACHE_KEY_STATS_CURRENT_YEAR . $this->shop_id);
        return new Json_Response(['success' => $result]);
    }
    /**
     * handleForm
     *
     *
     * @return bool
     */
    private function handle_form(array $datas)
    {
        $result = true;
        $default_language_id = (int) Configuration::get('PS_LANG_DEFAULT');
        if (isset($datas['WishlistPageName'])) {
            foreach ($datas['WishlistPageName'] as $lang_id => $value) {
                if (empty($value) && $lang_id != $default_language_id) {
                    $value = $datas['WishlistPageName'][$default_language_id];
                }
                $result = $result && Configuration::update_value('blockwishlist_WishlistPageName', [$lang_id => $value]);
            }
        }
        if (isset($datas['WishlistDefaultTitle'])) {
            foreach ($datas['WishlistDefaultTitle'] as $lang_id => $value) {
                if (empty($value) && $lang_id != $default_language_id) {
                    $value = $datas['WishlistDefaultTitle'][$default_language_id];
                }
                $result = $result && Configuration::update_value('blockwishlist_WishlistDefaultTitle', [$lang_id => $value]);
            }
        }
        if (isset($datas['CreateButtonLabel'])) {
            foreach ($datas['CreateButtonLabel'] as $lang_id => $value) {
                if (empty($value) && $lang_id != $default_language_id) {
                    $value = $datas['CreateButtonLabel'][$default_language_id];
                }
                $result = $result && Configuration::update_value('blockwishlist_CreateButtonLabel', [$lang_id => $value]);
            }
        }
        if ($result === true) {
            $this->add_flash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        }
        return $result;
    }
    /**
     * getWishlistConfigurationDatas
     */
    private function get_wishlist_configuration_datas(): array
    {
        $languages = Language::get_languages(true);
        $wishlist_names = $wishlist_default_titles = $wishlist_create_new_buttons_label = [];
        foreach ($languages as $lang) {
            $wishlist_names[$lang['id_lang']] = Configuration::get('blockwishlist_WishlistPageName', $lang['id_lang']);
            $wishlist_default_titles[$lang['id_lang']] = Configuration::get('blockwishlist_WishlistDefaultTitle', $lang['id_lang']);
            $wishlist_create_new_buttons_label[$lang['id_lang']] = Configuration::get('blockwishlist_CreateButtonLabel', $lang['id_lang']);
        }
        return ['WishlistPageName' => $wishlist_names, 'WishlistDefaultTitle' => $wishlist_default_titles, 'CreateButtonLabel' => $wishlist_create_new_buttons_label];
    }
}
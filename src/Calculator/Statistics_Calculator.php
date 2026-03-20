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
namespace Presta_Shop\Module\Block_Wish_List\Calculator;

use Customer;
use DateTime;
use Db;
use Db_Query;
use Presta_Shop\Presta_Shop\Adapter\Image\Image_Retriever;
use Presta_Shop\Presta_Shop\Adapter\Legacy_Context;
use Presta_Shop\Presta_Shop\Adapter\Presenter\Product\Product_Lazy_Array;
use Presta_Shop\Presta_Shop\Adapter\Presenter\Product\Product_Presenter;
use Presta_Shop\Presta_Shop\Adapter\Product\Price_Formatter;
use Presta_Shop\Presta_Shop\Adapter\Product\Product_Colors_Retriever;
use Presta_Shop\Presta_Shop\Core\Localization\Locale;
use Product_Assembler;
use Product_Presenter_Factory;
class Statistics_Calculator
{
    public const ARRAY_KEYS_STATS = ['allTime', 'currentYear', 'currentMonth', 'currentDay'];
    private $context;
    private $product_assembler;
    /**
     * @var Locale
     */
    private $locale;
    public function __construct(Legacy_Context $context, Locale $locale)
    {
        $this->context = $context->get_context();
        $this->context->customer = new Customer();
        $this->product_assembler = new Product_Assembler($this->context);
        $this->locale = $locale;
    }
    /**
     * computeStatsFor
     *
     * @param string|null $statsRange
     */
    public function compute_stats_for($stats_range = null): array
    {
        $query = new Db_Query();
        $query->select('id_product');
        $query->select('id_product_attribute');
        $query->select('date_add');
        $query->select('id_statistics');
        $query->from('blockwishlist_statistics');
        $query->where('id_shop = "' . (int) $this->context->shop->id . '"');
        switch ($stats_range) {
            case 'currentYear':
                $date_start = (new DateTime('now'))->modify('-1 year')->format('Y-m-d H:i:s');
                break;
            case 'currentMonth':
                $date_start = (new DateTime('now'))->modify('-1 month')->format('Y-m-d H:i:s');
                break;
            case 'currentDay':
                $date_start = (new DateTime('now'))->modify('-1 day')->format('Y-m-d H:i:s');
                break;
            case 'allTime':
            default:
                $date_start = null;
                break;
        }
        if (null !== $date_start) {
            $query->where('date_add >= "' . $date_start . '"');
        }
        $results = Db::get_instance()->execute_s($query);
        $stats = [];
        foreach ($results as $result) {
            $product_attribute_key = $result['id_product'] . '.' . $result['id_product_attribute'];
            if (isset($stats[$product_attribute_key])) {
                $stats[$product_attribute_key] = $stats[$product_attribute_key] + 1;
            } else {
                $stats[$product_attribute_key] = 1;
            }
        }
        arsort($stats);
        $stats = array_slice($stats, 0, 10);
        $this->compute_conversion_rate($stats, $date_start);
        return $stats;
    }
    /**
     * computeconversionRate
     *
     * @param string|null $dateStart
     *
     */
    public function compute_conversion_rate(array &$stats, $date_start = null): void
    {
        $position = 0;
        foreach ($stats as $id_product_and_attribute => $count) {
            // first ID is product, second one is product_attribute
            $combination = '';
            $ids = explode('.', $id_product_and_attribute);
            $id_product = $ids[0];
            $id_product_attribute = $ids[1];
            $product_details = $this->product_assembler->assemble_product(['id_product' => $id_product, 'id_product_attribute' => $id_product_attribute]);
            if (!empty($product_details['attributes'])) {
                $combination_arr = [];
                foreach ($product_details['attributes'] as $attribute) {
                    $combination_arr[] = $attribute['group'] . ' : ' . $attribute['name'];
                }
                $combination = implode(',', $combination_arr);
            }
            $presented_product = $this->get_presented_product($product_details);
            $img_details = $this->get_product_image($presented_product);
            $stats[$id_product_and_attribute] = ['position' => $position, 'count' => $count, 'id_product' => $id_product, 'id_product_attribute' => $id_product_attribute, 'name' => $product_details['name'], 'combination' => $combination, 'category_name' => $presented_product['category_name'], 'image_small_url' => $img_details['small']['url'], 'link' => $presented_product['link'], 'reference' => $product_details['reference'], 'price' => $this->locale->format_price($product_details['price'], $this->context->currency->iso_code), 'quantity' => $product_details['quantity'], 'conversionRate' => $this->compute_conversion_by_product($id_product, $id_product_attribute, $date_start) . '%'];
            ++$position;
        }
    }
    private function get_presented_product($product_details)
    {
        $presenter_factory = new Product_Presenter_Factory($this->context);
        $presentation_settings = $presenter_factory->get_presentation_settings();
        $image_retriever = new Image_Retriever($this->context->link);
        $presenter = new Product_Presenter($image_retriever, $this->context->link, new Price_Formatter(), new Product_Colors_Retriever(), $this->context->get_translator());
        return $presenter->present($presentation_settings, $product_details, $this->context->language);
    }
    /**
     * getProductImage
     *
     * @param mixed|ProductLazyArray $presentedProduct
     *
     * @return array
     */
    public function get_product_image($presented_product)
    {
        $img_details = [];
        foreach ($presented_product as $key => $value) {
            if ($key == 'embedded_attributes') {
                $img_details = $value['cover'];
            }
        }
        if (!$img_details) {
            $image_retriever = new Image_Retriever($this->context->link);
            $img_details = $image_retriever->get_no_picture_image($this->context->language);
        }
        return $img_details;
    }
    /**
     * computeConversionByProduct
     *
     * @param string $dateStart (Y-m-d H:i:s)
     *
     * @return float
     */
    public function compute_conversion_by_product(string $id_product, string $id_product_attribute, $date_start = null)
    {
        $nb_order_paid_and_shipped = [];
        $query_orders = '
            SELECT count(distinct(o.id_order)) as nb
            FROM ' . _DB_PREFIX_ . 'orders o
            INNER JOIN ' . _DB_PREFIX_ . 'blockwishlist_statistics bws ON (o.id_cart = bws.id_cart )
            LEFT JOIN ' . _DB_PREFIX_ . 'order_history oh ON (o.`id_order` = oh.`id_order`)
            LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON (os.`id_order_state` = oh.`id_order_state` AND os.`paid` = 1 AND os.`shipped` = 1)
            LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON (od.`id_order` = o.`id_order` AND od.`product_id` = bws.`id_product` AND od.`product_attribute_id` = bws.`id_product_attribute`)
            WHERE bws.`id_cart` <> 0 AND bws.`id_product` = ' . (int) $id_product . ' AND bws.`id_product_attribute` = ' . (int) $id_product_attribute . '
            AND bws.`id_shop` = ' . (int) $this->context->shop->id . '
            ';
        if (null != $date_start) {
            $query_orders .= 'AND bws.date_add >= "' . $date_start . '"';
        }
        $nb_order_paid_and_shipped = Db::get_instance()->get_row($query_orders);
        if (empty($nb_order_paid_and_shipped['nb'])) {
            return 0;
        }
        $query_count_all = new Db_Query();
        $query_count_all->select('COUNT(id_statistics)');
        $query_count_all->from('blockwishlist_statistics');
        $query_count_all->where('id_product = ' . $id_product);
        $query_count_all->where('id_product_attribute = ' . $id_product_attribute);
        $query_count_all->where('id_shop = ' . (int) $this->context->shop->id);
        if (null != $date_start) {
            $query_count_all->where('date_add >= "' . $date_start . '"');
        }
        $count_added_to_wishlist = Db::get_instance()->get_value($query_count_all);
        if (0 != $count_added_to_wishlist) {
            return round($nb_order_paid_and_shipped['nb'] / $count_added_to_wishlist * 100, 2);
        }
        return 0;
    }
}
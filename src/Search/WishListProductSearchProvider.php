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
namespace Presta_Shop\Module\Block_Wish_List\Search;

use Combination;
use Configuration;
use Db;
use Db_Query;
use Front_Controller;
use Group;
use Presta_Shop\Presta_Shop\Core\Product\Search\Product_Search_Context;
use Presta_Shop\Presta_Shop\Core\Product\Search\Product_Search_Provider_Interface;
use Presta_Shop\Presta_Shop\Core\Product\Search\Product_Search_Query;
use Presta_Shop\Presta_Shop\Core\Product\Search\Product_Search_Result;
use Presta_Shop\Presta_Shop\Core\Product\Search\Sort_Order;
use Presta_Shop\Presta_Shop\Core\Product\Search\Sort_Orders_Collection;
use Product;
use Shop;
use Symfony\Contracts\Translation\Translator_Interface;
use Validate;
use Wish_List;
/**
 * Responsible of getting products for specific wishlist.
 */
class Wish_List_Product_Search_Provider implements Product_Search_Provider_Interface
{
    /**
     * @var Db
     */
    private $db;
    /**
     * @var WishList
     */
    private $wish_list;
    /**
     * @var SortOrdersCollection
     */
    private $sort_orders_collection;
    /**
     * @var TranslatorInterface the translator
     */
    private $translator;
    public function __construct(Db $db, Wish_List $wish_list, Sort_Orders_Collection $sort_orders_collection, Translator_Interface $translator)
    {
        $this->db = $db;
        $this->wish_list = $wish_list;
        $this->sort_orders_collection = $sort_orders_collection;
        $this->translator = $translator;
    }
    public function run_query(Product_Search_Context $context, Product_Search_Query $query): \Presta_Shop\Presta_Shop\Core\Product\Search\Product_Search_Result
    {
        $result = new Product_Search_Result();
        $result->set_products($this->get_products_or_count($context, $query, 'products'));
        $result->set_total_products_count($this->get_products_or_count($context, $query, 'count'));
        $sort_orders = $this->sort_orders_collection->get_defaults();
        $sort_orders[] = (new Sort_Order('wishlist_product', 'id_wishlist_product', 'DESC'))->set_label($this->translator->trans('Last added', [], 'Modules.Blockwishlist.Shop'));
        $result->set_available_sort_orders($sort_orders);
        return $result;
    }
    /**
     *
     * @return array|int
     */
    private function get_products_or_count(Product_Search_Context $context, Product_Search_Query $query, string $type = 'products')
    {
        $query_search = new Db_Query();
        if ('products' === $type) {
            $query_search->select('p.*');
            $query_search->select('wp.quantity AS wishlist_quantity');
            $query_search->select('product_shop.*');
            $query_search->select('stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity');
            $query_search->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
            pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`');
            $query_search->select('image_shop.`id_image` AS id_image');
            $query_search->select('il.`legend`');
            $query_search->select('
            DATEDIFF(
                product_shop.`date_add`,
                DATE_SUB(
                    "' . date('Y-m-d') . ' 00:00:00",
                    INTERVAL ' . (0 <= (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
                )
            ) > 0 AS new');
            if (Combination::is_feature_active()) {
                $query_search->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.`id_product_attribute`,0) AS id_product_attribute');
            }
        } else {
            $query_search->select('COUNT(DISTINCT wp.id_product)');
        }
        $query_search->from('product', 'p');
        $query_search->join(Shop::add_sql_association('product', 'p'));
        $query_search->inner_join('wishlist_product', 'wp', 'wp.`id_product` = p.`id_product`');
        $query_search->left_join('category_product', 'cp', 'p.id_product = cp.id_product AND cp.id_category = product_shop.id_category_default');
        if (Combination::is_feature_active()) {
            $query_search->left_join('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`id_product_attribute` = wp.id_product_attribute AND product_attribute_shop.id_shop=' . (int) $context->get_id_shop());
        }
        if ('products' === $type) {
            $query_search->left_join('stock_available', 'stock', 'stock.id_product = `p`.id_product AND stock.id_product_attribute = wp.id_product_attribute' . \Stock_Available::add_sql_shop_restriction(null, (int) $context->get_id_shop(), 'stock'));
            $query_search->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int) $context->get_id_lang() . \Shop::add_sql_restriction_on_lang('pl'));
            $query_search->left_join('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop = ' . (int) $context->get_id_shop());
            $query_search->left_join('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $context->get_id_lang());
            $query_search->left_join('category', 'ca', 'cp.`id_category` = ca.`id_category` AND ca.`active` = 1');
        }
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups = false === empty($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::get_current()->id;
            $query_search->left_join('category_group', 'cg', 'cp.`id_category` = cg.`id_category` AND cg.`id_group`' . $sql_groups);
        }
        $query_search->where('wp.id_wishlist = ' . (int) $this->wish_list->id);
        $query_search->where('product_shop.active = 1');
        $query_search->where('product_shop.visibility IN ("both", "catalog")');
        if ('products' === $type) {
            $sort_order = $query->get_sort_order()->to_legacy_order_by(true);
            $sort_way = $query->get_sort_order()->to_legacy_order_way();
            if (Validate::is_order_by($sort_order) && Validate::is_order_way($sort_way)) {
                $query_search->order_by($sort_order . ' ' . $sort_way);
            }
            $query_search->limit((int) $query->get_results_per_page(), ((int) $query->get_page() - 1) * (int) $query->get_results_per_page());
            $query_search->group_by('p.id_product');
            $products = $this->db->execute_s($query_search);
            if (empty($products)) {
                return [];
            }
            return Product::get_products_properties((int) $context->get_id_lang(), $products);
        }
        return (int) $this->db->get_value($query_search);
    }
}
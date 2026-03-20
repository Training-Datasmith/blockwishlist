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
namespace Presta_Shop\Module\Block_Wish_List\Grid\Definition;

use Presta_Shop\Presta_Shop\Core\Grid\Column\Column_Collection;
use Presta_Shop\Presta_Shop\Core\Grid\Column\Type\Common\Image_Column;
use Presta_Shop\Presta_Shop\Core\Grid\Column\Type\Common\Link_Column;
use Presta_Shop\Presta_Shop\Core\Grid\Column\Type\Common\Position_Column;
use Presta_Shop\Presta_Shop\Core\Grid\Column\Type\Data_Column;
use Presta_Shop\Presta_Shop\Core\Grid\Definition\Factory\Abstract_Grid_Definition_Factory;
class Base_Statistics_Grid_Definition_Factory extends Abstract_Grid_Definition_Factory
{
    protected function get_id()
    {
        return 'statistics';
    }
    protected function get_name()
    {
        return $this->trans('Statistics', [], 'Admin.Advparameters.Feature');
    }
    protected function get_columns()
    {
        return (new Column_Collection())->add((new Position_Column('position'))->set_name($this->trans('Product', [], 'Modules.Blockwishlist.Admin'))->set_options(['id_field' => 'position', 'position_field' => 'position', 'update_route' => '']))->add((new Image_Column('image'))->set_options(['src_field' => 'image_small_url']))->add((new Link_Column('name'))->set_options(['field' => 'name', 'route' => 'admin_product_form', 'route_param_name' => 'id', 'route_param_field' => 'id_product']))->add((new Data_Column('reference'))->set_name($this->trans('Reference', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'reference']))->add((new Data_Column('combination'))->set_name($this->trans('Combination', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'combination']))->add((new Data_Column('category_name'))->set_name($this->trans('Category', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'category_name']))->add((new Data_Column('price'))->set_name($this->trans('Price (tax excl.)', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'price']))->add((new Data_Column('quantity'))->set_name($this->trans('Available Qty', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'quantity']))->add((new Data_Column('conversionRate'))->set_name($this->trans('Conversion rate', [], 'Modules.Blockwishlist.Admin'))->set_options(['field' => 'conversionRate']));
    }
}
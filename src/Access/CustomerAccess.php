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
namespace Presta_Shop\Module\Block_Wish_List\Access;

use Customer;
use Tools;
use Validate;
use Wish_List;
class Customer_Access
{
    /**
     * @var Customer
     */
    private $customer;
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }
    /**
     * @return bool
     */
    public function has_read_access_to_wishlist(Wish_List $wishlist)
    {
        // Wishlist is shared
        if (!empty($wishlist->token) && Tools::get_isset('token')) {
            return true;
        }
        return $this->has_write_access_to_wishlist($wishlist);
    }
    /**
     * @return bool
     */
    public function has_write_access_to_wishlist(Wish_List $wishlist)
    {
        if (false === Validate::is_loaded_object($this->customer)) {
            return false;
        }
        return (int) $wishlist->id_customer === $this->customer->id;
    }
}
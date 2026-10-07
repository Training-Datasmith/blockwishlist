<?php
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

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\BlockWishList\Access\CustomerAccess;

class CustomerAccessTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
    }

    public function testGuestCannotWriteOrReadAPrivateWishlist()
    {
        $access = new CustomerAccess(new Customer());
        $wishlist = $this->wishlist(5, 'SHARE');

        $this->assertFalse($access->hasWriteAccessToWishlist($wishlist));
        $this->assertFalse($access->hasReadAccessToWishlist($wishlist));
    }

    public function testOwnerCanReadAndWriteWithoutAShareToken()
    {
        $customer = new Customer();
        $customer->id = 5;
        $access = new CustomerAccess($customer);
        $wishlist = $this->wishlist(5, '');

        $this->assertTrue($access->hasWriteAccessToWishlist($wishlist));
        $this->assertTrue($access->hasReadAccessToWishlist($wishlist));
    }

    public function testAnotherCustomerCannotManageTheWishlist()
    {
        $customer = new Customer();
        $customer->id = 8;
        $access = new CustomerAccess($customer);
        $wishlist = $this->wishlist(5, 'SHARE');

        $this->assertFalse($access->hasWriteAccessToWishlist($wishlist));
        $this->assertFalse($access->hasReadAccessToWishlist($wishlist));
    }

    public function testASharedWishlistIsReadableWhenAnyTokenParameterIsPresent()
    {
        $guest = new CustomerAccess(new Customer());
        $wishlist = $this->wishlist(5, 'SHARE');

        $_GET['token'] = '';
        $this->assertTrue($guest->hasReadAccessToWishlist($wishlist));
        $this->assertFalse($guest->hasWriteAccessToWishlist($wishlist));

        $_GET = [];
        $_POST['token'] = 'unrelated';
        $this->assertTrue($guest->hasReadAccessToWishlist($wishlist));
    }

    public function testAnEmptyOrZeroWishlistTokenDoesNotGrantSharedReadAccess()
    {
        $guest = new CustomerAccess(new Customer());
        $_GET['token'] = 'shared';

        $this->assertFalse($guest->hasReadAccessToWishlist($this->wishlist(5, '')));
        $this->assertFalse($guest->hasReadAccessToWishlist($this->wishlist(5, '0')));
        $this->assertFalse($guest->hasReadAccessToWishlist($this->wishlist(5, null)));
    }

    public function testWriteAccessComparesTheCustomerIdStrictly()
    {
        $customer = new Customer();
        $customer->id = '5';
        $access = new CustomerAccess($customer);

        $this->assertFalse($access->hasWriteAccessToWishlist($this->wishlist(5, '')));

        $customer->id = 5;
        $this->assertTrue($access->hasWriteAccessToWishlist($this->wishlist(5, '')));
    }

    public function testACustomerWithIdZeroIsTreatedAsNotLoaded()
    {
        $customer = new Customer();
        $customer->id = 0;
        $access = new CustomerAccess($customer);

        $this->assertFalse($access->hasWriteAccessToWishlist($this->wishlist(0, '')));
    }

    private function wishlist($customerId, $token)
    {
        $wishlist = new WishList();
        $wishlist->id = 4;
        $wishlist->id_customer = $customerId;
        $wishlist->token = $token;

        return $wishlist;
    }
}

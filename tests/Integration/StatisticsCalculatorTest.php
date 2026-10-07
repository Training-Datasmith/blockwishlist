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

use PrestaShop\Module\BlockWishList\Calculator\StatisticsCalculator;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Localization\Locale;

class StatisticsCalculatorTest extends WishlistDatabaseTestCase
{
    public function testComputeStatsForKeepsTheTopTenOrderedByCount()
    {
        for ($productId = 1; $productId <= 11; ++$productId) {
            for ($copy = 0; $copy < $productId; ++$copy) {
                $this->insertStatistic($productId, 0, 1, $productId);
            }
        }
        $this->insertStatistic(11, 0, 2, 11);

        $stats = $this->calculator()->computeStatsFor('allTime');

        $this->assertCount(10, $stats);
        $this->assertArrayNotHasKey('1.0', $stats);
        $first = reset($stats);
        $this->assertSame(0, $first['position']);
        $this->assertSame(11, $first['count']);
        $this->assertSame('11', (string) $first['id_product']);
        $this->assertSame('0', (string) $first['id_product_attribute']);
        $this->assertSame('Product 11', $first['name']);
        $this->assertSame('', $first['combination']);
        $this->assertSame('Home', $first['category_name']);
        $this->assertSame('https://example.test/11.jpg', $first['image_small_url']);
        $this->assertSame('https://example.test/product/11', $first['link']);
        $this->assertSame('REF-11', $first['reference']);
        $this->assertSame('19.90 EUR', $first['price']);
        $this->assertSame(4, $first['quantity']);
        $this->assertSame('0%', $first['conversionRate']);
        $this->assertSame(9, $stats['2.0']['position']);
    }

    public function testComputeStatsForDescribesCombinationsAndIgnoresUnknownRangesAsAllTime()
    {
        $this->insertStatistic(8, 4, 1, 80);
        $this->insertStatistic(8, 0, 1, 81);

        $stats = $this->calculator()->computeStatsFor('not-a-range');

        $this->assertSame('Color : Red', $stats['8.4']['combination']);
        $this->assertSame('', $stats['8.0']['combination']);
        $this->assertSame('Product 8', $stats['8.4']['name']);
    }

    public function testComputeStatsForAppliesTheRequestedDateWindowAndShop()
    {
        $this->insertStatistic(1, 0, 1, 1, 'NOW()');
        $this->insertStatistic(1, 0, 1, 1, 'DATE_SUB(NOW(), INTERVAL 2 DAY)');
        $this->insertStatistic(1, 0, 1, 1, 'DATE_SUB(NOW(), INTERVAL 2 MONTH)');
        $this->insertStatistic(1, 0, 1, 1, 'DATE_SUB(NOW(), INTERVAL 2 YEAR)');
        $this->insertStatistic(2, 0, 2, 2, 'NOW()');

        $calculator = $this->calculator();

        $this->assertSame(1, $calculator->computeStatsFor('currentDay')['1.0']['count']);
        $this->assertSame(2, $calculator->computeStatsFor('currentMonth')['1.0']['count']);
        $this->assertSame(3, $calculator->computeStatsFor('currentYear')['1.0']['count']);
        $this->assertSame(4, $calculator->computeStatsFor('allTime')['1.0']['count']);
        $this->assertSame(4, $calculator->computeStatsFor(null)['1.0']['count']);
        $this->assertArrayNotHasKey('2.0', $calculator->computeStatsFor('allTime'));
    }

    public function testComputeConversionByProductRoundsAndHonorsCartShopAndDate()
    {
        $this->insertStatistic(5, 1, 1, 0, 'NOW()');
        $this->insertStatistic(5, 1, 1, 0, 'NOW()');
        $this->insertStatistic(5, 1, 1, 50, 'NOW()');
        $this->insertStatistic(5, 1, 1, 51, 'DATE_SUB(NOW(), INTERVAL 3 DAY)');
        $this->insertStatistic(5, 1, 2, 60, 'NOW()');
        $this->insertOrder(50, 0, 0, 99, 0);
        $this->insertOrder(51, 1, 1, 5, 1);

        $calculator = $this->calculator();
        $yesterday = (new DateTime('now'))->modify('-1 day')->format('Y-m-d H:i:s');
        $tomorrow = (new DateTime('now'))->modify('+1 day')->format('Y-m-d H:i:s');

        $this->assertSame(0, $calculator->computeConversionByProduct(5, 9));
        $this->assertEquals(50.0, $calculator->computeConversionByProduct(5, 1));
        $this->assertEquals(33.33, $calculator->computeConversionByProduct(5, 1, $yesterday));
        $this->assertSame(0, $calculator->computeConversionByProduct(5, 1, $tomorrow));
    }

    private function calculator()
    {
        return new StatisticsCalculator(new LegacyContext(), new Locale());
    }

    public function testGetProductImageUsesTheCoverOrTheNoPictureFallback()
    {
        $calculator = $this->calculator();

        $cover = $calculator->getProductImage([
            'name' => 'Mug',
            'embedded_attributes' => [
                'cover' => ['small' => ['url' => 'https://example.test/cover.jpg']],
            ],
        ]);
        $this->assertSame('https://example.test/cover.jpg', $cover['small']['url']);

        $fallback = $calculator->getProductImage(['name' => 'Mug']);
        $this->assertSame('https://example.test/no-picture.jpg', $fallback['small']['url']);
    }
}

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

use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use PrestaShop\Module\BlockWishList\Calculator\StatisticsCalculator;
use PrestaShop\Module\BlockWishList\Grid\Data\AllTimeStatisticsGridDataFactory;
use PrestaShop\Module\BlockWishList\Grid\Data\CurrentDayStatisticsGridDataFactory;
use PrestaShop\Module\BlockWishList\Grid\Data\CurrentMonthStatisticsGridDataFactory;
use PrestaShop\Module\BlockWishList\Grid\Data\CurrentYearStatisticsGridDataFactory;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class StatisticsGridDataFactoryTest extends TestCase
{
    /**
     * @dataProvider factoryProvider
     */
    public function testCacheMissComputesStatsAndStoresThemForTheShop($className, $range, $cacheKey, $lifetime)
    {
        $cache = new ArrayStatisticsCache();
        $calculator = new FixedStatisticsCalculator();
        $factory = new $className($cache, $calculator, 7);

        $data = $factory->getData(new StatisticsSearchCriteria());

        $this->assertSame([$range], $calculator->ranges);
        $this->assertSame($lifetime, $cache->savedLifetime);
        $this->assertArrayHasKey($cacheKey . '7', $cache->store);
        $this->assertSame(1, $data->getRecordsTotal());
        $this->assertSame([['count' => 2]], $data->getRecords()->all());
    }

    /**
     * @dataProvider factoryProvider
     */
    public function testCacheHitSkipsTheCalculator($className, $range, $cacheKey, $lifetime)
    {
        $cache = new ArrayStatisticsCache();
        $cache->store[$cacheKey . '4'] = [];
        $calculator = new FixedStatisticsCalculator();
        $factory = new $className($cache, $calculator, 4);

        $data = $factory->getData(new StatisticsSearchCriteria());

        $this->assertSame([], $calculator->ranges);
        $this->assertSame(0, $data->getRecordsTotal());
        $this->assertSame([], $data->getRecords()->all());
        $this->assertNull($cache->savedLifetime);
    }

    public function factoryProvider()
    {
        return [
            [AllTimeStatisticsGridDataFactory::class, 'allTime', 'blockwishlist.stats.allTime', AllTimeStatisticsGridDataFactory::CACHE_LIFETIME_SECONDS],
            [CurrentYearStatisticsGridDataFactory::class, 'currentYear', 'blockwishlist.stats.currentYear', CurrentYearStatisticsGridDataFactory::CACHE_LIFETIME_SECONDS],
            [CurrentMonthStatisticsGridDataFactory::class, 'currentMonth', 'blockwishlist.stats.currentMonth', CurrentMonthStatisticsGridDataFactory::CACHE_LIFETIME_SECONDS],
            [CurrentDayStatisticsGridDataFactory::class, 'currentDay', 'blockwishlist.stats.currentDay', CurrentDayStatisticsGridDataFactory::CACHE_LIFETIME_SECONDS],
        ];
    }
}

class FixedStatisticsCalculator extends StatisticsCalculator
{
    public $ranges = [];

    public function __construct()
    {
    }

    public function computeStatsFor($statsRange = null)
    {
        $this->ranges[] = $statsRange;

        return [['count' => 2]];
    }
}

class ArrayStatisticsCache extends CacheProvider
{
    public $store = [];
    public $savedLifetime;

    public function contains($id)
    {
        return array_key_exists($id, $this->store);
    }

    public function fetch($id)
    {
        return $this->store[$id];
    }

    public function save($id, $data, $lifeTime = 0)
    {
        $this->store[$id] = $data;
        $this->savedLifetime = $lifeTime;

        return true;
    }
}

class StatisticsSearchCriteria implements SearchCriteriaInterface
{
}

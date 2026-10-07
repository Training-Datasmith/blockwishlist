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

namespace Symfony\Contracts\Translation {
    interface TranslatorInterface
    {
        public function trans($id, array $parameters = [], $domain = null, $locale = null);
    }
}

namespace PrestaShop\PrestaShop\Adapter {
    class LegacyContext
    {
        public function getContext()
        {
            return \Context::getContext();
        }
    }
}

namespace PrestaShop\PrestaShop\Adapter\Image {
    class ImageRetriever
    {
        public function __construct($link)
        {
        }

        public function getNoPictureImage($language)
        {
            return [
                'small' => ['url' => 'https://example.test/no-picture.jpg'],
            ];
        }
    }
}

namespace PrestaShop\PrestaShop\Adapter\Presenter\Product {
    class ProductPresenter
    {
        public function __construct($imageRetriever, $link, $priceFormatter, $colorsRetriever, $translator)
        {
        }

        public function present($settings, array $product, $language)
        {
            return [
                'category_name' => 'Home',
                'link' => 'https://example.test/product/' . $product['id_product'],
                'embedded_attributes' => [
                    'cover' => [
                        'small' => ['url' => 'https://example.test/' . $product['id_product'] . '.jpg'],
                    ],
                ],
            ];
        }
    }

    class ProductLazyArray
    {
    }
}

namespace PrestaShop\PrestaShop\Adapter\Product {
    class PriceFormatter
    {
    }

    class ProductColorsRetriever
    {
    }
}

namespace PrestaShop\PrestaShop\Core\Localization {
    class Locale
    {
        public function formatPrice($price, $currencyCode)
        {
            return $price . ' ' . $currencyCode;
        }
    }
}

namespace Doctrine\Common\Cache {
    abstract class CacheProvider
    {
        abstract public function contains($id);

        abstract public function fetch($id);

        abstract public function save($id, $data, $lifeTime = 0);
    }
}

namespace PrestaShop\PrestaShop\Core\Grid\Search {
    interface SearchCriteriaInterface
    {
    }
}

namespace PrestaShop\PrestaShop\Core\Grid\Record {
    class RecordCollection implements \Countable, \IteratorAggregate
    {
        private $records;

        public function __construct(array $records = [])
        {
            $this->records = $records;
        }

        public function count(): int
        {
            return count($this->records);
        }

        public function all()
        {
            return $this->records;
        }

        #[\ReturnTypeWillChange]
        public function getIterator(): \Traversable
        {
            return new \ArrayIterator($this->records);
        }
    }
}

namespace PrestaShop\PrestaShop\Core\Grid\Data {
    use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;

    class GridData
    {
        private $records;
        private $recordsTotal;

        public function __construct(RecordCollection $records, $recordsTotal)
        {
            $this->records = $records;
            $this->recordsTotal = $recordsTotal;
        }

        public function getRecords()
        {
            return $this->records;
        }

        public function getRecordsTotal()
        {
            return $this->recordsTotal;
        }
    }
}

namespace PrestaShop\PrestaShop\Core\Grid\Data\Factory {
    use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

    interface GridDataFactoryInterface
    {
        public function getData(SearchCriteriaInterface $searchCriteria);
    }
}

namespace {
    use Symfony\Contracts\Translation\TranslatorInterface;

    class PrestaShopException extends \Exception
    {
    }

    class ObjectModel
    {
        const TYPE_INT = 1;
        const TYPE_BOOL = 2;
        const TYPE_STRING = 3;
        const TYPE_FLOAT = 4;
        const TYPE_DATE = 5;
        const TYPE_HTML = 6;
        const TYPE_NOTHING = 7;
        const TYPE_SQL = 8;

        public $id;

        public function __construct($id = null)
        {
            if ($id) {
                $this->id = (int) $id;
            }
        }
    }

    class Module
    {
        public $name;
        public $tab;
        public $version;
        public $author;
        public $need_instance;
        public $context;
        public $displayName;
        public $description;
        public $ps_versions_compliancy;

        public function trans($id, array $parameters = [], $domain = null, $locale = null)
        {
            return $id;
        }
    }

    class Customer extends ObjectModel
    {
        public function isLogged()
        {
            return Validate::isLoadedObject($this);
        }
    }

    class TestTranslator implements TranslatorInterface
    {
        public function trans($id, array $parameters = [], $domain = null, $locale = null)
        {
            return (string) $id;
        }
    }

    class ProductAssembler
    {
        public function __construct($context)
        {
        }

        public function assembleProduct(array $rawProduct)
        {
            $attributes = [];
            if ((int) $rawProduct['id_product_attribute'] > 0) {
                $attributes[] = [
                    'group' => 'Color',
                    'name' => 'Red',
                ];
            }

            return [
                'id_product' => $rawProduct['id_product'],
                'id_product_attribute' => $rawProduct['id_product_attribute'],
                'name' => 'Product ' . $rawProduct['id_product'],
                'attributes' => $attributes,
                'reference' => 'REF-' . $rawProduct['id_product'],
                'price' => '19.90',
                'quantity' => 4,
            ];
        }
    }

    class ProductPresenterFactory
    {
        public function __construct($context)
        {
        }

        public function getPresentationSettings()
        {
            return [];
        }
    }

    class Language
    {
        public static $languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
            ['id_lang' => 2, 'locale' => 'fr-FR'],
        ];

        public static function getLanguages($active = true, $idShop = false, $idLang = false)
        {
            return self::$languages;
        }
    }

    class Configuration
    {
        public static $values = [];

        public static function reset()
        {
            self::$values = [];
        }

        public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
        {
            self::$values[$key] = $values;

            return true;
        }

        public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
        {
            if (!isset(self::$values[$key])) {
                return false;
            }

            if (null === $idLang) {
                return self::$values[$key];
            }

            return isset(self::$values[$key][$idLang]) ? self::$values[$key][$idLang] : false;
        }
    }

    class Tab extends ObjectModel
    {
        public static $ids = [];
        public static $added = [];
        public static $deleted = [];
        public static $addCount = 0;
        public $class_name;
        public $active;
        public $name = [];
        public $id_parent;
        public $module;

        public static function reset()
        {
            self::$ids = [];
            self::$added = [];
            self::$deleted = [];
            self::$addCount = 0;
        }

        public static function getIdFromClassName($className)
        {
            return isset(self::$ids[$className]) ? self::$ids[$className] : 0;
        }

        public function add()
        {
            ++self::$addCount;
            self::$ids[$this->class_name] = self::$addCount;
            self::$added[] = [
                'class_name' => $this->class_name,
                'active' => $this->active,
                'module' => $this->module,
                'name' => $this->name,
                'id_parent' => $this->id_parent,
            ];

            return true;
        }

        public function delete()
        {
            self::$deleted[] = $this->id;

            return true;
        }
    }

    class Validate
    {
        public static function isLoadedObject($object)
        {
            return is_object($object) && !empty($object->id);
        }

        public static function isMessage($message)
        {
            return !preg_match('/[<>{}]/i', (string) $message);
        }

        public static function isUnsignedInt($value)
        {
            return (string) (int) $value === (string) $value && $value < 4294967296 && $value >= 0;
        }

        public static function isGenericName($name)
        {
            return is_string($name) && !preg_match('/[<>={}]/i', $name);
        }
    }

    class Tools
    {
        public static function getIsset($key)
        {
            if (!is_string($key)) {
                return false;
            }

            return isset($_POST[$key]) || isset($_GET[$key]);
        }

        public static function getValue($key, $defaultValue = false)
        {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }

            if (isset($_GET[$key])) {
                return $_GET[$key];
            }

            return $defaultValue;
        }
    }

    class Cache
    {
        private static $store = [];

        public static function isStored($key)
        {
            return array_key_exists($key, self::$store);
        }

        public static function store($key, $value)
        {
            self::$store[$key] = $value;

            return $value;
        }

        public static function retrieve($key)
        {
            return isset(self::$store[$key]) ? self::$store[$key] : null;
        }

        public static function clean($key = null)
        {
            if (null === $key) {
                self::$store = [];

                return;
            }

            unset(self::$store[$key]);
        }
    }

    class Context
    {
        public $shop;
        public $customer;
        public $link;
        public $currency;
        public $language;
        public $cart;

        private static $instance;

        public static function getContext()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function getTranslator()
        {
            return new TestTranslator();
        }
    }

    class Shop
    {
        public static $contextShopId = 1;
        public static $contextShopGroupId = 1;

        public static function getContextShopID()
        {
            return self::$contextShopId;
        }

        public static function getContextShopGroupID()
        {
            return self::$contextShopGroupId;
        }

        public static function addSqlAssociation($table, $alias, $innerJoin = true, $on = null, $forceNotDefault = false)
        {
            $joinAlias = $table . '_shop';
            $sql = ($innerJoin ? ' INNER JOIN ' : ' LEFT JOIN ');
            $sql .= '`' . _DB_PREFIX_ . $table . '_shop` ' . $joinAlias;
            $sql .= ' ON (' . $joinAlias . '.id_' . $table . ' = ' . $alias . '.id_' . $table;

            if (self::getContextShopID()) {
                $sql .= ' AND ' . $joinAlias . '.id_shop = ' . (int) self::getContextShopID();
            }

            if ($on) {
                $sql .= ' AND ' . $on;
            }

            $sql .= ')';

            return $sql;
        }

        public static function addSqlRestrictionOnLang($alias = null, $idShop = null)
        {
            if (null === $idShop) {
                $shop = Context::getContext()->shop;
                $idShop = is_object($shop) ? (int) $shop->id : 0;
            }

            if (!$idShop) {
                return '';
            }

            return ' AND ' . ($alias ? $alias . '.' : '') . 'id_shop = ' . (int) $idShop;
        }
    }

    class Db
    {
        private static $instance;
        private $pdo;
        private $lastError = '';
        private $lastRowCount = 0;

        public static function getInstance($master = true)
        {
            if (!self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct()
        {
            $host = getenv('BLOCKWISHLIST_DB_HOST') ?: '127.0.0.1';
            $port = getenv('BLOCKWISHLIST_DB_PORT') ?: '3306';
            $name = getenv('BLOCKWISHLIST_DB_NAME') ?: 'blockwishlist_test';
            $user = getenv('BLOCKWISHLIST_DB_USER') ?: 'blockwishlist';
            $password = getenv('BLOCKWISHLIST_DB_PASSWORD');
            if (false === $password) {
                $password = 'blockwishlist';
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

            try {
                $this->pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                // Same session mode as PrestaShop DbPDO::connect() / DbMySQLi::connect().
                $this->pdo->exec('SET SESSION sql_mode = \'\'');
            } catch (PDOException $exception) {
                throw new RuntimeException('Cannot connect to the blockwishlist test database "' . $name . '". ' . $exception->getMessage(), 0, $exception);
            }
        }

        public function getLastError()
        {
            return $this->lastError;
        }

        public function escape($string, $htmlOK = false)
        {
            if (is_int($string) || is_float($string) || (is_string($string) && is_numeric($string))) {
                return (string) $string;
            }

            $quoted = $this->pdo->quote((string) $string);

            return substr($quoted, 1, -1);
        }

        public function Insert_ID()
        {
            return (int) $this->pdo->lastInsertId();
        }

        public function NumRows()
        {
            return $this->lastRowCount;
        }

        public function execute($sql)
        {
            $sql = $this->normalizeSql($sql);

            try {
                $this->pdo->exec($sql);

                return true;
            } catch (PDOException $exception) {
                $this->lastError = $exception->getMessage() . ' | SQL: ' . $sql;

                return false;
            }
        }

        public function executeS($sql)
        {
            $sql = $this->normalizeSql($sql);

            try {
                $statement = $this->pdo->query($sql);
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
                $this->lastRowCount = count($rows);

                return $rows;
            } catch (PDOException $exception) {
                $this->lastError = $exception->getMessage() . ' | SQL: ' . $sql;
                $this->lastRowCount = 0;

                return false;
            }
        }

        public function getRow($sql)
        {
            $rows = $this->executeS($sql);
            if (empty($rows)) {
                return false;
            }

            return $rows[0];
        }

        public function getValue($sql)
        {
            $row = $this->getRow($sql);
            if (!is_array($row) || !$row) {
                return false;
            }

            return reset($row);
        }

        public function insert($table, $data, $nullValues = false, $useCache = true, $type = 'INSERT', $addPrefix = true)
        {
            if ($addPrefix) {
                $table = _DB_PREFIX_ . $table;
            }

            $keys = [];
            $values = [];
            foreach ($data as $key => $value) {
                $keys[] = '`' . $this->escapeIdentifier($key) . '`';
                if (null === $value || '' === $value) {
                    $values[] = $nullValues ? 'NULL' : "''";
                } else {
                    $values[] = '\'' . pSQL($value) . '\'';
                }
            }

            $sql = 'INSERT INTO `' . $this->escapeIdentifier($table) . '` (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';

            return $this->execute($sql);
        }

        public function update($table, $data, $where = '', $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
        {
            if ($addPrefix) {
                $table = _DB_PREFIX_ . $table;
            }

            $sets = [];
            foreach ($data as $key => $value) {
                if (null === $value || '' === $value) {
                    $sets[] = '`' . $this->escapeIdentifier($key) . '` = ' . ($nullValues ? 'NULL' : "''");
                } else {
                    $sets[] = '`' . $this->escapeIdentifier($key) . '` = \'' . pSQL($value) . '\'';
                }
            }

            $sql = 'UPDATE `' . $this->escapeIdentifier($table) . '` SET ' . implode(', ', $sets);
            if ($where) {
                $sql .= ' WHERE ' . $where;
            }
            if ($limit) {
                $sql .= ' LIMIT ' . (int) $limit;
            }

            return $this->execute($sql);
        }

        public function delete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
        {
            if ($addPrefix) {
                $table = _DB_PREFIX_ . $table;
            }

            $sql = 'DELETE FROM `' . $this->escapeIdentifier($table) . '`';
            if ($where) {
                $sql .= ' WHERE ' . $where;
            }
            if ($limit) {
                $sql .= ' LIMIT ' . (int) $limit;
            }

            return $this->execute($sql);
        }

        private function normalizeSql($sql)
        {
            if ($sql instanceof DbQuery) {
                return $sql->build();
            }

            return (string) $sql;
        }

        private function escapeIdentifier($identifier)
        {
            return str_replace('`', '', (string) $identifier);
        }
    }

    class DbQuery
    {
        private $query;

        public function __construct()
        {
            $this->query = [
                'select' => [],
                'from' => [],
                'join' => [],
                'where' => [],
                'group' => [],
                'having' => [],
                'order' => [],
                'limit' => ['offset' => 0, 'limit' => 0],
            ];
        }

        public function select($fields)
        {
            if (!empty($fields)) {
                $this->query['select'][] = $fields;
            }

            return $this;
        }

        public function from($table, $alias = null)
        {
            if (!empty($table)) {
                $this->query['from'][] = '`' . _DB_PREFIX_ . bqSQL($table) . '`' . ($alias ? ' ' . $alias : '');
            }

            return $this;
        }

        public function join($join)
        {
            if (!empty($join)) {
                $this->query['join'][] = $join;
            }

            return $this;
        }

        public function leftJoin($table, $alias = null, $on = null)
        {
            return $this->join(
                'LEFT JOIN `' . _DB_PREFIX_ . bqSQL($table) . '`'
                . ($alias ? ' `' . pSQL($alias) . '`' : '')
                . ($on ? ' ON ' . $on : '')
            );
        }

        public function innerJoin($table, $alias = null, $on = null)
        {
            return $this->join(
                'INNER JOIN `' . _DB_PREFIX_ . bqSQL($table) . '`'
                . ($alias ? ' `' . pSQL($alias) . '`' : '')
                . ($on ? ' ON ' . $on : '')
            );
        }

        public function where($restriction)
        {
            if (!empty($restriction)) {
                $this->query['where'][] = $restriction;
            }

            return $this;
        }

        public function groupBy($fields)
        {
            if (!empty($fields)) {
                $this->query['group'][] = $fields;
            }

            return $this;
        }

        public function orderBy($fields)
        {
            if (!empty($fields)) {
                $this->query['order'][] = $fields;
            }

            return $this;
        }

        public function limit($limit, $offset = 0)
        {
            $offset = (int) $offset;
            if ($offset < 0) {
                $offset = 0;
            }

            $this->query['limit'] = [
                'offset' => $offset,
                'limit' => (int) $limit,
            ];

            return $this;
        }

        public function build()
        {
            if (!$this->query['from']) {
                throw new RuntimeException('DbQuery is missing a from clause');
            }

            $sql = 'SELECT ' . ($this->query['select'] ? implode(",\n", $this->query['select']) : '*') . "\n";
            $sql .= 'FROM ' . implode(', ', $this->query['from']) . "\n";

            if ($this->query['join']) {
                $sql .= implode("\n", $this->query['join']) . "\n";
            }

            if ($this->query['where']) {
                $sql .= 'WHERE (' . implode(') AND (', $this->query['where']) . ")\n";
            }

            if ($this->query['group']) {
                $sql .= 'GROUP BY ' . implode(', ', $this->query['group']) . "\n";
            }

            if ($this->query['having']) {
                $sql .= 'HAVING (' . implode(') AND (', $this->query['having']) . ")\n";
            }

            if ($this->query['order']) {
                $sql .= 'ORDER BY ' . implode(', ', $this->query['order']) . "\n";
            }

            if ($this->query['limit']['limit']) {
                $limit = $this->query['limit'];
                $sql .= 'LIMIT ' . ($limit['offset'] ? $limit['offset'] . ', ' : '') . $limit['limit'];
            }

            return $sql;
        }

        public function __toString()
        {
            return $this->build();
        }
    }

    function pSQL($string, $htmlOK = false)
    {
        return Db::getInstance()->escape($string, $htmlOK);
    }

    function bqSQL($string)
    {
        return str_replace('`', '\`', pSQL($string));
    }
}

# DbHandler

DbHandler is a PHP library designed to simplify interactions with MySQL databases. It offers a comprehensive set of tools for building and executing queries, managing transactions, and handling database schema through PDO connections.

## Features

- **Query Building**: Easily construct SQL queries using a fluent, intuitive syntax.
- **Transaction Management**: Efficiently handle database transactions with integrated support.
- **Schema Management**: Conveniently manage database schema, including tables and stored procedures.
- **Cache Integration**: Leverage caching for query results to enhance performance.
- **Logging and Error Handling**: Integrated logging and error handling for better debugging and monitoring.
- **PSR Standards Compliance**: Adheres to PSR standards, ensuring high-quality, interoperable PHP code.

## Installation

Install the package via Composer:

```bash
composer require tribal2/db-handler
```

## Usage

Begin by creating a `Db` instance:

```php
use Tribal2\DbHandler\Db;
use Tribal2\DbHandler\DbConfig;

$config = DbConfig::create('my_database')
  ->withUser('username')
  ->withPassword('password')
  ->withHost('localhost')
  ->withPort(3306)
  ->withCharset('utf8mb4');

$db = new Db($config);
```

### Creating Queries

Build and execute SELECT, INSERT, UPDATE, DELETE, and stored procedure queries:

```php
// SELECT Query
$select = $db->select()
  ->columns(['column1', 'column2'])
  ->column('column3')
  ->from('table_name')
  ->where(Where::equals('column2', 1))  // See "Where Clauses" section below
  ->groupBy('column1')
  ->having(Where::equals('sum(column2)', 5))
  ->orderBy('column3', 'ASC')
  ->limit(10)
  ->offset(5);

// Execute query
$allResults = $select->fetchAll();
$countResults = $select->fetchCount();
$firstResult = $select->fetchFirst();
$column1Values = $select->fetchColumn('column1');
$column3DistinctValues = $select->fetchDistincts('column3');

# @todo 1 Add more examples...
```

### Where Clauses

To provide examples of using the `Where` class, I'll create various scenarios that demonstrate how to use different methods of this class to construct SQL conditions.

### Example 1: Using `equals` Method

```php
$where = Where::equals('status', 'active');
echo $where->getSql();
// Output: `status` = :status___1
```

### Example 2: Using `notEquals` Method

```php
$where = Where::notEquals('category', 'archived');
echo $where->getSql();
// Output: `category` <> :category___1
```

### Example 3: Using `greaterThan` Method

```php
$where = Where::greaterThan('price', 100);
echo $where->getSql();
// Output: `price` > :price___1
```

### Example 4: Using `lessThan` Method

```php
$where = Where::lessThan('price', 50);
echo $where->getSql();
// Output: `price` < :price___1
```

### Example 5: Using `like` Method

```php
$where = Where::like('name', '%Apple%');
echo $where->getSql();
// Output: `name` LIKE :name___1
```

### Example 6: Using `between` Method

```php
$where = Where::between('date', '2021-01-01', '2021-12-31');
echo $where->getSql();
// Output: `date` BETWEEN :date___1 AND :date___2
```

### Example 7: Using `in` Method

```php
$where = Where::in('status', ['active', 'pending', 'on-hold']);
echo $where->getSql();
// Output: `status` IN (:status___1, :status___2, :status___3)
```

### Example 8: Using `or` and `and` Methods

```php
$where1 = Where::equals('status', 'active');
$where2 = Where::greaterThan('price', 100);
$combinedWhere = Where::or($where1, $where2);
echo $combinedWhere->getSql();
// Output: (`status` = :status___1 OR `price` > :price___1)
```

### Example 9: Using `isNull` and `isNotNull` Methods

```php
$where = Where::isNull('description');
echo $where->getSql();
// Output: `description` IS NULL

$whereNotNull = Where::isNotNull('description');
echo $whereNotNull->getSql();
// Output: `description` IS NOT NULL
```

### Managing Transactions

Handle transactions with ease:

```php
$db->transaction()->begin();
$db->transaction()->commit();
$db->transaction()->rollback();
```

## Contributing

Contributions are welcome. Please refer to the repository's issues page on GitHub for more information.

## License

This library is licensed under the MIT License. See the LICENSE file for more details.

## Support

For support, please visit the issues page on the GitHub repository:
[GitHub Issues](https://github.com/tribal2/php-dbhandler/issues)

<h1 align="center">PHP-DbHandler</h1>

<p align="center">
  <a href="https://packagist.org/packages/tribal2/db-handler" title="Latest Version"><img alt="Latest Version" src="https://img.shields.io/packagist/v/tribal2/db-handler"></a>
  <a href="https://github.com/tribal2/php-dbhandler" title="CI GitHub action"><img alt="CI GitHub action status" src="https://github.com/tribal2/php-dbhandler/actions/workflows/ci.yml/badge.svg"></a>
  <a href="https://codecov.io/gh/tribal2/php-dbhandler" title="Code coverage"><img alt="Codecov Code Coverage" src="https://img.shields.io/codecov/c/github/tribal2/php-dbhandler?logo=codecov"></a>
  <a href="https://github.com/tribal2/php-dbhandler/blob/main/LICENSE" title="license"><img alt="LICENSE" src="https://img.shields.io/badge/license-MIT-428f7e.svg?logo=open%20source%20initiative&logoColor=white&labelColor=555555"></a>
  <a href="#tada-php-support" title="PHP Versions Supported"><img alt="PHP Versions Supported" src="https://img.shields.io/badge/php-8.2%20to%208.4-777bb3.svg?logo=php&logoColor=white&labelColor=555555"></a>
</p>

PHP-DbHandler is a PHP library designed to simplify interactions with MySQL databases. It offers a comprehensive set of tools for building and executing queries, managing transactions, and handling database schema through PDO connections.

## Contents

* [Features](#features)
* [Installation](#installation)
* [Setup](#setup)
* [Creating `Where` and `Having` clauses](#creating--where--and--having--clauses)
  + [Comparison operators](#comparison-operators)
  + [Logical operators](#logical-operators)
  + [`or` and `and` operators](#-or--and--and--operators)
* [Creating and executing queries](#creating-and-executing-queries)
  + [SELECT](#select)
  + [INSERT](#insert)
  + [UPDATE](#update)
  + [DELETE](#delete)
  + [STORED PROCEDURES](#stored-procedures)
* [Transactions](#transactions)
  + [Global Transaction Management](#global-transaction-management)
* [Contributing](#contributing)
  + [Contribution Process](#contribution-process)
  + [Using the Development Container](#using-the-development-container)
  + [Testing with Pest](#testing-with-pest)
  + [Submitting Contributions](#submitting-contributions)
  + [Review Process](#review-process)
  + [Questions and Discussions](#questions-and-discussions)
* [License](#license)
* [Support](#support)

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

## Setup

Begin by creating a `Db` instance:

```php
use Tribal2\DbHandler\Core\PDOWrapper;
use Tribal2\DbHandler\Db;
use Tribal2\DbHandler\DbConfig;

$config = DbConfig::create('my_database')
  ->withUser('username')
  ->withPassword('password')
  ->withHost('localhost')   // Optional. Default: 'localhost'
  ->withPort(3306)          // Optional. Default: 3306
  ->withCharset('utf8mb4'); // Optional. Default: 'utf8mb4'

$pdoWrapper = new PDOWrapper(
  $config,
  // Optional Psr\Log\LoggerInterface instance.
  // $logger, // Default: Psr\Log\NullLogger
);

$db = new Db(
  $pdoWrapper,
  // Optional Psr\SimpleCache\CacheInterface instance.
  // $cache,  // Default: NULL
);
```

## Creating `Where` and `Having` clauses

The Where class provide a flexible and intuitive way to construct query conditions. It support a variety of comparison and logical operators, allowing you to precisely define the criteria for selecting or filtering data from your database.

> The methods return a Where object encapsulating the condition, along with a parameterized value for secure and efficient querying.

The Where clauses not only simplify the construction of query syntax but also enhance security by internally managing the risks associated with SQL injection. This library automatically replaces values with PDO named parameters and performs binding using the appropriate PDO data types. By handling these crucial aspects, it ensures that your queries are not only clean and maintainable but also secure.

You no longer need to worry about manually sanitizing your inputs for database queries. The library takes care of preparing the statements in a way that guards against SQL injection, one of the most common security vulnerabilities in database-driven applications. This approach allows you to focus on the business logic of your application, trusting that the database interactions are handled safely and efficiently.

### Comparison operators

```php
$where = Where::equals('status', 'active');
// Output: `status` = :status___1

$where = Where::notEquals('category', 'archived');
// Output: `category` <> :category___1

$where = Where::greaterThan('price', 100);
// Output: `price` > :price___1

$where = Where::greaterThanOrEquals('price', 100);
// Output: `price` >= :price___1

$where = Where::lessThan('price', 50);
// Output: `price` < :price___1

$where = Where::lessThanOrEquals('price', 50);
// Output: `price` <= :price___1

$where = Where::isNull('description');
// Output: `description` IS NULL

$whereNotNull = Where::isNotNull('description');
// Output: Output: `description` IS NOT NULL
```

### Logical operators

```php
$where = Where::like('name', '%Apple%');
// Output: `name` LIKE :name___1

$where = Where::notLike('name', '%Apple%');
// Output: `name` NOT LIKE :name___1

$where = Where::between('date', '2021-01-01', '2021-12-31');
// Output: `date` BETWEEN :date___1 AND :date___2

$where = Where::notBetween('date', '2021-01-01', '2021-12-31');
// Output: `date` NOT BETWEEN :date___1 AND :date___2

$where = Where::in('status', ['active', 'pending', 'on-hold']);
// Output: `status` IN (:status___1, :status___2, :status___3)

$where = Where::notIn('status', ['active', 'pending', 'on-hold']);
// Output: `status` NOT IN (:status___1, :status___2, :status___3)
```

### `or` and `and` operators

```php
$where1 = Where::equals('status', 'active');
$where2 = Where::greaterThan('price', 100);
$orWhere = Where::or($where1, $where2);
// Output: (`status` = :status___1 OR `price` > :price___1)

$andWhere = Where::and($where1, $where2);
// Output: (`status` = :status___1 AND `price` > :price___1)
```

> You can also nest `or` and `and` operators:

```php
$where3 = Where::equals('category', 'archived');
$combinedWhere = Where::and($where3, $orWhere);
// Output: (`category` = :category___1 AND (`status` = :status___1 OR `price` > :price___1))
```

## Creating and executing queries

In the following subsections, we will explore how to create and execute queries using this library. For the sake of simplicity, we will assume that the `$db` variable is an instance of the `Db` class.

In all the examples below, we separated the query construction from the execution. This approach allows you to reuse the query object and execute it multiple times with different parameters, but you can also chain the methods to create and execute the query in a single statement like this:

```php
$results = $db
  ->select()
  ->columns(['column1', 'column2'])
  ->from('table_name')
  ->where(Where::equals('column2', 1))
  ->fethAll();
```

### SELECT

```php
$select = $db->select()
  ->columns(['column1', 'column2'])
  ->column('column3')
  ->from('table_name')
  ->where(Where::equals('column2', 1))  // See "Where Clauses" section above
  ->groupBy('column1')
  ->having(Where::equals('sum(column2)', 5))
  ->orderBy('column3', 'ASC')
  ->limit(10)
  ->offset(5);

$sql = $select->getSql();
// $sql:
// SELECT
//     `column1`,
//     `column2`,
//     `column3`
// FROM
//     `table_name`
// WHERE
//     `column2` = :column2___1
// GROUP BY
//     `column1`
// HAVING
//     `sum(column2)` = :sum_column2____1
// ORDER BY
//     `column3` ASC
// LIMIT
//     10
// OFFSET
//     5;
```

**Fetching results:**

By default, the `fetchAll()` method returns an array of objects (using `PDO::FETCH_OBJ` by default), where each object represents a row of data. You can also fetch the results as an array of associative arrays by passing the `PDO::FETCH_ASSOC` constant as an argument to the `fetchMethod()` builder method before executing the query.

```php
$allResults = $select->fetchAll();
$firstResult = $select->fetchFirst();
$column1Values = $select->fetchColumn('column1');
$column3DistinctValues = $select->fetchDistincts('column3');

// Output: object(FetchResult) {
//     data => array(n) {
//         [0]...
//         [1]...
//         [n-1]...
//     },
//     count => int(n)
// }
```

You can also fetch the count of results with:

```php
$countResults = $select->fetchCount();
// Output: 5
```

**Pagination:**

Efficiently handling large datasets and providing a user-friendly interface for data navigation are essential for any robust application. The pagination feature in PHP-DbHandler addresses these needs elegantly. It simplifies the process of dividing your data into manageable chunks, or "pages", making it easier to work with large datasets without overwhelming the system or the user.

_Setting Up Pagination_

There are two ways to set up pagination for your queries:

- Using the paginate Method: This method allows you to define the number of items per page in a concise manner. It's an efficient way to prepare your query for pagination.

  ```php
  $select = $db->select()
    ->from('table_name')
    // ...
    ->paginate(itemsPerPage: 10);
  ```

- Manually Setting limit and offset: For more control, you can manually specify the limit (number of items per page) and offset (starting point in the dataset) for your query.

  ```php
  $select = $db->select()
    ->from('table_name')
    // ...
    ->limit(10)
    ->offset(0);
  ```

_Fetching Results with Pagination_

Once pagination is set up, you can fetch results in various ways, navigating through your dataset with ease:

- `fetchPage(?int $page)`: Fetch a current or specific page.
- `fetchNextPage()`: Fetch results for the next page.
- `fetchPreviousPage()`: Fetch results for the previous page.
- `fetchFirstPage()`: Fetch results for the first page.
- `fetchLastPage()`: Fetch results for the last page.

Each of these methods returns a `FetchPaginatedResult` object, which contains the following properties:

- `data`: An array of the records on the current page.
- `count`: The total number of records in the dataset.
- `page`: The current page number.
- `perPage`: The number of records per page.
- `totalPages`: The total number of pages.

```php
// Example output structure of FetchPaginatedResult
object(FetchPaginatedResult) {
    data => array(n) {
        [0]...
        [1]...
        [n-1]...
    },
    count => int(n),
    page => int(10),
    perPage => int(10),
    totalPages => int(23)
}
```

This pagination system in PHP-DbHandler ensures that you can effectively manage and navigate through large datasets, enhancing the overall performance and user experience of your application.

**Caching:**

In today's data-driven applications, efficiency and performance are key. To enhance these aspects in database interactions, the library includes a caching feature within its `Select` queries. This feature boosts performance by caching query results, thereby reducing database load and enhancing response times for frequently executed queries. Importantly, it is designed to be fully compliant with the PSR-16 (Simple Cache) standard, ensuring broad compatibility and flexibility.

> _PSR-16 Compliant Caching_
>
> The caching functionality within Select queries accepts any cache instance that implements the Psr\SimpleCache\CacheInterface. This compliance with PSR-16 standards means you can seamlessly integrate a wide range of caching libraries that adhere to this interface, offering you the flexibility to choose the caching solution that best fits your application's needs.

1. **Setting Up Cache**: If provided an instance of `Psr\SimpleCache\CacheInterface` when initializing the `Db` class, you can skip this step. I you did not, you can use the `setCache` method:

```php
$select = $db->select()->setCache($simpleCacheInstance);
```

> _Notes:_
> - If you did not provide a cache instance when initializing the `Db` class, you must set it for each `Select` query that you want to cache.
> - You can also use this method if you want to set an specific cache instance for a `Select` query. This allows you to use different caching solutions for different queries, depending on your application's needs.

2. **Configuring Cache**: Enable and configure caching for your query using the `withCache` method. You can specify a default return value for missing cache entries and a TTL (Time To Live) for the cached data.

```php
$select->withCache(defaultValue, ttl);
```

> _Notes:_
> - The `defaultValue` argument is optional. If not provided, the library will return `NULL` for missing cache entries.
> - The `ttl` argument is optional. If not provided, the library will use the TTL value set by the Psr\SimpleCache instance.

3. **Automated Cache Handling**: When executing a query with caching enabled, the library first checks for the presence of cached results. If available, it fetches from the cache, skipping the database query. Otherwise, it executes the query, caches the result, and then returns it.

```php
$allResults = $select->fetchAll();
$firstResult = $select->fetchFirst();
$column1Values = $select->fetchColumn('column1');
$column3DistinctValues = $select->fetchDistincts('column3');
```

**Key Benefits**

- **Flexibility**: Choose from a variety of caching implementations that comply with PSR-16.
- **Performance**: Lessen the frequency of database queries by utilizing cached data for repeated queries.
- **Ease of Use**: Implementing caching is straightforward and integrates smoothly with existing query structures.
- **Reliable Cache Key Generation**: The library generates unique cache keys for each query, ensuring the accuracy and relevance of cached data.

### INSERT

The `Insert` class in the PHP-DbHandler library streamlines the process of creating and executing insert queries in a database. This class, equipped with multiple traits and interfaces, offers a sophisticated approach to handling insert operations with various advanced features.

**Query generation**

1. **Dynamic Value Assignment**: The `Insert` class allows you to dynamically assign values to columns for insertion. You can add a single value or multiple values at once:

```php
$insert = $db->insert()
  ->into('table_name')
  ->value('column1', 'value1')
  ->values(['column2' => 'value2', 'column3' => 'value3']);
```

> The class will check if the column exists in the table before adding the value, and will also take care of the necessary PDO binding.

2. **Inserting Multiple Rows**: Effortlessly insert multiple rows at once by providing an array of values:

```php
$rows = [
  ['column1' => 'value1', 'column2' => 'value2'],
  ['column1' => 'value3', 'column2' => 'value4'],
  // ...
];
$insert->rows($rows);
```

**Execution**

```php
$success = $insert->execute();
```

> **Checks**
>
> Before executing an insert operation, the class will automatically check:
> - If the database is in a **read-only mode**, preventing unintended write operations.
> - If there are **collisions in non-autoincrement primary keys**, ensuring data integrity.

The `Insert` class is an all-encompassing solution for handling insert operations in a database, offering both ease of use and advanced features to manage complex insertion tasks efficiently.

### UPDATE

The `Update` class in the PHP-DbHandler library provides a sophisticated and flexible way to construct and execute update queries in a database. It's designed to seamlessly integrate with the existing database structure while offering robust features to manage update operations effectively.

**Query generation**

1. **Setting Update Values**: Easily specify the columns to be updated and their new values. The class ensures that only existing columns are updated, preventing errors and maintaining data integrity.

```php
$update = $db->update()
  ->table('table_name')
  ->set('column1', 'newValue1')
  ->set('column2', 'newValue2');
    ```

2. **Conditional Updates**: Incorporate conditions into your update queries using the `where` method. This allows for precise targeting of records to be updated.

```php
$update->where(Where::equals('column3', 'conditionValue'));
```

**Execution**

```php
$success = $update->execute();
```

> **Read-Only Mode Check**: Prior to execution, the class checks if the database is in read-only mode, thus preventing unintended write operations.

The `Update` class represents a comprehensive solution for constructing and executing update operations in a database. Its combination of flexibility, robustness, and ease of use makes it an ideal choice for managing database updates in PHP applications.


### DELETE

The `Delete` class in the PHP-DbHandler library offers a sophisticated approach to constructing and executing delete queries in databases. This class ensures that delete operations are conducted with precision and safety, integrating essential checks and features for optimal query handling.

**Query generation**

The class allows for precise targeting of records to be deleted using conditional expressions. This is achieved through the `where` method, enabling specific rows to be selected for deletion based on the given criteria.

```php
$delete = $db->delete()
  ->from('table_name')
  ->where(Where::equals('column', 'value'));
```

> **Mandatory Where Clause**: To avoid accidental deletion of all records in a table, the class requires a `WHERE` clause to be specified. This serves as a safeguard against unintentional bulk deletions.

**Execution**

```php
$success = $delete->execute();
```

> The class performs essential checks before executing the delete operation, including verifying the table's existence and ensuring the database is not in read-only mode.

The `Delete` class is designed to handle delete operations with a high degree of control and safety. It ensures that deletions are performed accurately, respecting the database's structure and constraints. Whether you're performing simple or complex deletion tasks, this class provides the necessary tools to execute them reliably and securely.

### STORED PROCEDURES

The `StoredProcedure` class in the PHP-DbHandler library offers a streamlined and efficient approach to executing stored procedures in databases. This class provides a robust way to interact with stored procedures, handling parameter management, execution, and result fetching with ease.

**Query generation**

**Setting Up Stored Procedure Calls**: Easily set up calls to stored procedures with dynamic parameter management. Specify the procedure name and the parameters it requires.

```php
$procedure = $db->storedProcedure()
  ->call('procedure_name')
  ->with('paramName', $value)
  // ...
  ->with('paramName2', $value);
```

**Execution**

```php
$results = $procedure->execute();
```

> **Read-Only Mode Checks**: Prior to execution, the class verifies if the database is in read-only mode, ensuring that write operations are not unintentionally performed.

The `StoredProcedure` class is an indispensable tool for handling stored procedure calls within PHP applications. It simplifies the interaction with stored procedures, making the process more intuitive and less error-prone, especially in applications that heavily rely on complex database operations.

## Transactions

Managing database transactions is a crucial aspect of ensuring data integrity, especially in applications dealing with complex data manipulation. PHP-DbHandler simplifies this process, offering an intuitive and straightforward way to handle transactions.

With the provided transaction management capabilities, you can easily start, commit, or roll back transactions, giving you complete control over your database operations. This ensures that a series of database operations can be treated as a single atomic unit, either completing entirely or not at all, thus maintaining the consistency and reliability of your data.

```php
$db->transaction->begin();
$db->transaction->commit();
$db->transaction->rollback();
```
This feature is particularly useful in scenarios where multiple related database operations need to be executed together. If any operation within the transaction fails, the rollback method can be used to revert all changes made from the beginning of the transaction, thereby preventing partial updates that could lead to data inconsistencies. Conversely, if all operations are successful, the commit method will save all changes to the database.

Utilizing these transaction controls, PHP-DbHandler ensures that your application's data management is robust, consistent, and error-resilient. Whether you are dealing with complex data entries, updates, or batch processes, these transactional capabilities provide the necessary tools to manage your database operations effectively.

### Global Transaction Management

The `Transaction` class also introduces a powerful feature for managing complex transaction scenarios. This feature allows you to globally control transaction commits, especially useful when you want to encompass multiple methods that use transactions under a single, overarching transactional context.

**Handling Transactions Globally**

You can manage multiple transactional operations as part of a larger transaction by disabling automatic commits. This is particularly useful in scenarios where several operations, each capable of handling transactions independently, need to be executed as a part of a single atomic transaction.

```php
// Begin a transaction
$db->transaction->begin();

// Disable automatic commits
$db->transaction->setCommitsModeOff();

// Execute other methods that use transactions
// $db->transaction->begin();
// ...
// $db->transaction->commit();

// Re-enable automatic commits
$db->transaction->setCommitsModeOn();

// Commit the transaction
$db->transaction->commit();
```

This feature enhances the control over transactional operations, allowing for more complex and reliable data manipulation scenarios. It ensures that all changes made within the scope of the global transaction are either committed together or rolled back, maintaining data integrity and consistency.

## Contributing

We highly value and welcome contributions to the project! If you're interested in contributing, please read our [CONTRIBUTING.md](CONTRIBUTING.md) file for detailed information on how to get started, guidelines for submitting contributions, and tips for making the process as easy and effective as possible.

Whether you're fixing a bug, adding a feature, or improving documentation, your contributions are greatly appreciated and make a significant impact on the project.


### Questions and Discussions

If you have questions or want to discuss ideas before coding, feel free to open an issue on our [GitHub Issues](https://github.com/tribal2/php-dbhandler/issues) page for discussion.

We appreciate your willingness to contribute and look forward to your submissions!

## License

This library is licensed under the MIT License. See the LICENSE file for more details.

## Support

For support, please visit the issues page on the GitHub repository:
[GitHub Issues](https://github.com/tribal2/php-dbhandler/issues)

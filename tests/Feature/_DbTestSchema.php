<?php

use Psr\Log\NullLogger;
use Tribal2\DbHandler\Core\PDOWrapper;
use Tribal2\DbHandler\DbConfig;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\PDOSingleton;

class DbTestSchema {


  public static function getConfig(): DbConfig {
    $dbConfig = new DbConfig(
      $_ENV['MYSQL_DATABASE'],
      $_ENV['MYSQL_USER'],
      $_ENV['MYSQL_PASSWORD'],
      $_ENV['MYSQL_HOST'],
      $_ENV['MYSQL_PORT'],
      $_ENV['MYSQL_ENCODING'],
    );

    return $dbConfig;
  }


  public static function usePdoSingleton(): void {
    $cfg = self::getConfig();
    PDOSingleton::configure($cfg);
  }


  public static function getPdoWrapper(): PDOWrapperInterface {
    $cfg = self::getConfig();
    $logger = new NullLogger();

    $myPdo = new PDOWrapper($cfg, $logger);

    return $myPdo;
  }


  public static function getPdo(): PDO {
    $cfg = self::getConfig();

    $pdo = new PDO($cfg->getConnString(), $cfg->getUser(), $cfg->getPassword());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    return $pdo;
  }


  public static function up() {
    self::down();

    /*
    * DB Dummy data setup
    */
    $pdo = self::getPdo();

    $query = 'CREATE TABLE IF NOT EXISTS `test_table` (
      `test_table_id` int(11) NOT NULL AUTO_INCREMENT,
      `key` varchar(255) NOT NULL,
      `value` varchar(255),
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`test_table_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $sth = $pdo->prepare($query);
    $sth->execute();

    // Insert some data
    $query = 'INSERT INTO `test_table` (`key`, `value`) VALUES (:key, :value)';
    $sth = $pdo->prepare($query);
    $sth->execute([
      ':key' => 'test1',
      ':value' => 'Test value 1',
    ]);
    $sth->execute([
      ':key' => 'test2',
      ':value' => 'Test value 2',
    ]);

    // Create another table without auto increment primary key
    $query = 'CREATE TABLE IF NOT EXISTS `test_table_no_auto_increment` (
      `test_table_id` int(11) NOT NULL,
      `key` varchar(255) NOT NULL,
      `value` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`test_table_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $sth = $pdo->prepare($query);
    $sth->execute();

    // Insert some data
    $query = 'INSERT INTO `test_table_no_auto_increment` (`test_table_id`, `key`, `value`) VALUES (:id, :key, :value)';
    $sth = $pdo->prepare($query);
    $sth->execute([
      ':id' => 1,
      ':key' => 'test1',
      ':value' => 'Test value 1',
    ]);
    $sth->execute([
      ':id' => 2,
      ':key' => 'test2',
      ':value' => 'Test value 2',
    ]);

    // Create a test stored procedure with params
    $query = 'DROP PROCEDURE IF EXISTS `get_test_rows`';
    $sth = $pdo->prepare($query);
    $sth->execute();

    $query = "
      CREATE PROCEDURE `get_test_rows`(
        IN keyInput VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        IN valueInput VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
      )
      BEGIN
          SET keyInput = CONCAT('%', keyInput, '%');

          IF valueInput IS NULL THEN
              SELECT * FROM test_table WHERE `key` LIKE keyInput;
          ELSE
              SET valueInput = CONCAT('%', valueInput, '%');
              SELECT * FROM test_table WHERE `key` LIKE keyInput OR `value` LIKE valueInput;
          END IF;
      END;
    ";
    $sth = $pdo->prepare($query);
    $sth->execute();

    $pdo = NULL;
  }


  public static function down() {
    $pdo = self::getPdo();

    // Drop the test table
    $query = 'DROP TABLE IF EXISTS `test_table`';
    $sth = $pdo->prepare($query);
    $sth->execute();

    // Drop the test table without auto increment primary key
    $query = 'DROP TABLE IF EXISTS `test_table_no_auto_increment`';
    $sth = $pdo->prepare($query);
    $sth->execute();

    // Drop the test stored procedure
    $query = 'DROP PROCEDURE IF EXISTS `get_test_rows`';
    $sth = $pdo->prepare($query);
    $sth->execute();

    $pdo = NULL;
  }


}

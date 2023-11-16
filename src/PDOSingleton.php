<?php

namespace Tribal2\DbHandler;

use Exception;
use PDO;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;

class PDOSingleton {
  /**
   * Objeto PDO
   * @var PDO
   */
  private static $instance;

  private static ?DbConfig $dbConfig = NULL;


  final public static function configure(DbConfig $dbConfig): void {
    self::$dbConfig = $dbConfig;
  }


  /**
   * Singleton
   * @return PDO
   */
  final public static function get(): PDO {
    if (!is_null(self::$instance)) {
      return self::$instance;
    }

    if (is_null(self::$dbConfig)) {
      throw new Exception(static::class . ' is not configured. Call configure() method first.');
    }

    $cfg = self::$dbConfig;
    $pdo = new PDO($cfg->getConnString(), $cfg->user, $cfg->password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    self::$instance = $pdo;

    return self::$instance;
  }


  final public static function getDbName(): string {
    return self::$dbConfig->dbName;
  }


  final public static function destroy(): void {
    self::$instance = NULL;
  }


  private function __construct() {}


  private function __clone() {}


  final public function __wakeup() {}


}

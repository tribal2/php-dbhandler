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

    self::set();

    return self::$instance;
  }


  final public static function getDbName(): string {
    return self::$dbConfig->dbName;
  }


  final public static function destroy(): void {
    self::$instance = NULL;
  }


  public static function set(?PDO $pdo = NULL): void {
    self::$instance = $pdo ?? self::getDefaultPdo();
  }


  private static function getDefaultPdo(): PDO {
    if (is_null(self::$dbConfig)) {
      throw new Exception(static::class . ' is not configured. Call configure() method first.');
    }

    $cfg = self::$dbConfig;
    $pdo = new PDO($cfg->getConnString(), $cfg->user, $cfg->password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    return $pdo;
  }


  private function __construct() {}


  private function __clone() {}


  final public function __wakeup() {}


}

<?php

namespace Tribal2\DbHandler;

use Exception;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;

class DbTransaction {


  public static bool $throw = FALSE;


  public static function begin(): bool {
    $dbh = PDOSingleton::get();

    if ($dbh->inTransaction()) {
      return self::errorHandler('Ya existe una transacción activa.');
    }

    return $dbh->beginTransaction();
  }


  public static function commit(): bool {
    $dbh = PDOSingleton::get();

    if (!$dbh->inTransaction()) {
      return self::errorHandler('No hay ninguna transacción activa.');
    }

    if (PDOSingleton::getCommitsMode() === PDOCommitModeEnum::OFF) {
      return self::errorHandler('Los commits están desabilitados.');
    }

    return $dbh->commit();
  }


  public static function rollback(): bool {
    $dbh = PDOSingleton::get();

    if (!$dbh->inTransaction()) {
      return self::errorHandler('No hay ninguna transacción activa.');
    }

    return $dbh->rollBack();
  }


  public static function check() {
    $dbh = PDOSingleton::get();

    return $dbh->inTransaction();
  }


  private static function errorHandler($msg): bool {
    try {
      if (self::$throw) {
        throw new Exception($msg);
      }

      return FALSE;
    }

    catch (Exception $e) {
      throw $e;
    }
  }


}

<?php

use Tribal2\DbHandler\Factories\ColumnsFactory;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;

describe('ColumnsFactory', function () {

  describe('->make()', function () {

    it('should return ColumnsInterface', function () {
      $mockPdo = Mockery::mock(PDOWrapperInterface::class, [
        'getDbName' => 'test_db',
        'execute' => Mockery::mock(PDOStatement::class, [
          'fetchAll' => [
            (object)[
              'COLUMN_NAME' => 'test_table_id',
              'COLUMN_KEY' => 'PRI',
              'EXTRA' => 'auto_increment',
            ],
          ],
        ]),
      ]);

      $factory = new ColumnsFactory($mockPdo);
      $columns = $factory->make('users');
      expect($columns)->toBeInstanceOf(ColumnsInterface::class);
    });

  });

});

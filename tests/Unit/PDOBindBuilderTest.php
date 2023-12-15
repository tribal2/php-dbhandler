<?php

use Tribal2\DbHandler\PDOBindBuilder;

describe('Private methods', function () {

  test('checkType()', function() {
    $pdoBuilder = new PDOBindBuilder();

    $reflection = new ReflectionClass(PDOBindBuilder::class);
    $method = $reflection->getMethod('checkType');
    $method->setAccessible(TRUE);

    $pdoTypes = [
      PDO::PARAM_BOOL,
      PDO::PARAM_NULL,
      PDO::PARAM_INT,
      PDO::PARAM_STR,
      PDO::PARAM_LOB,
      PDO::PARAM_STMT,
      PDO::PARAM_INPUT_OUTPUT,
      PDO::PARAM_STR_CHAR,
      PDO::PARAM_STR_NATL,
    ];

    foreach ($pdoTypes as $pdoType) {
      expect($method->invokeArgs($pdoBuilder, [$pdoType]))
        ->not()->toThrow(Exception::class);
    }
  });

  test('checkType() - should throw', function() {
    $pdoBuilder = new PDOBindBuilder();

    $reflection = new ReflectionClass(PDOBindBuilder::class);
    $method = $reflection->getMethod('checkType');
    $method->setAccessible(TRUE);

    $method->invokeArgs($pdoBuilder, [ 99999 ]);
  })->throws(Exception::class);

  test('checkValueWithType()', function() {
    $pdoBuilder = new PDOBindBuilder();

    $reflection = new ReflectionClass(PDOBindBuilder::class);
    $method = $reflection->getMethod('checkValueWithType');
    $method->setAccessible(TRUE);

    $valueTypes = [
      [ TRUE, PDO::PARAM_BOOL ],
      [ NULL, PDO::PARAM_NULL ],
      [ 123, PDO::PARAM_INT ],
      [ 123.456, PDO::PARAM_STR ],
      [ 'Test value', PDO::PARAM_STR ],
      [ 'Test value', PDO::PARAM_STR_CHAR ],
      [ 'Test value', PDO::PARAM_STR_NATL ],
    ];

    foreach ($valueTypes as $valueType) {
      expect($method->invokeArgs($pdoBuilder, $valueType))
        ->not()->toThrow(Exception::class);
    }
  });

  test('checkValueWithType() - should throw', function() {
    $pdoBuilder = new PDOBindBuilder();

    $reflection = new ReflectionClass(PDOBindBuilder::class);
    $method = $reflection->getMethod('checkValueWithType');
    $method->setAccessible(TRUE);

    expect(fn() => $method->invokeArgs($pdoBuilder, [ NULL, PDO::PARAM_BOOL ]))
      ->toThrow(Exception::class);

    expect(fn() => $method->invokeArgs($pdoBuilder, [ FALSE, PDO::PARAM_NULL ]))
      ->toThrow(Exception::class);

    expect(fn() => $method->invokeArgs($pdoBuilder, [ 123.45, PDO::PARAM_INT ]))
      ->toThrow(Exception::class);

    expect(fn() => $method->invokeArgs($pdoBuilder, [ NULL, PDO::PARAM_STR ]))
      ->toThrow(Exception::class);

    expect(fn() => $method->invokeArgs($pdoBuilder, [ TRUE, PDO::PARAM_STR ]))
      ->toThrow(Exception::class);
  });

});


describe('Public methods', function () {
  test('addValue()', function () {
    $pdoBuilder = new PDOBindBuilder();
    $placeholder1 = $pdoBuilder->addValue('Test value 1');
    $placeholder2 = $pdoBuilder->addValue(507);

    expect($placeholder1)->toBe(':placeholder___1');
    expect($placeholder2)->toBe(':placeholder___2');
  });

  test('addValueWithPrefix()', function () {
    $pdoBuilder = new PDOBindBuilder();
    $placeholder1 = $pdoBuilder->addValueWithPrefix('Test value 1', 'prefix');
    $placeholder2 = $pdoBuilder->addValueWithPrefix(507, 'prefix');

    expect($placeholder1)->toBe(':prefix___1');
    expect($placeholder2)->toBe(':prefix___2');
  });

  test('addValueWithPrefix() - prefix with characters not included in [_a-Z0-9]', function () {
    $pdoBuilder = new PDOBindBuilder();
    $placeholder1 = $pdoBuilder->addValueWithPrefix('Test value 1', 'sum(test_table)');

    expect($placeholder1)->toBe(':sum_test_table____1');
  });

  test('getValues()', function () {
    $pdoBuilder = new PDOBindBuilder();

    $placeholder1 = $pdoBuilder->addValue(TRUE, PDO::PARAM_BOOL);
    $placeholder2 = $pdoBuilder->addValueWithPrefix(507, 'prefix', PDO::PARAM_INT);
    $placeholder3 = $pdoBuilder->addValue('default');

    $values = $pdoBuilder->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(3);

    expect($values[$placeholder1]['value'])->toBe(TRUE);
    expect($values[$placeholder1]['type'])->toBe(PDO::PARAM_BOOL);

    expect($values[$placeholder2]['value'])->toBe(507);
    expect($values[$placeholder2]['type'])->toBe(PDO::PARAM_INT);

    expect($values[$placeholder3]['value'])->toBe('default');
    expect($values[$placeholder3]['type'])->toBe(PDO::PARAM_STR);
  });

  test('bindToStatement()', function() {
    $pdoBuilder = new PDOBindBuilder();
    $placeholder1 = $pdoBuilder->addValue('Test value 1');

    // Crear el mock
    $statement = Mockery::mock(PDOStatement::class);

    // Configurar expectativas en el mock
    $statement->shouldReceive('bindValue')
      ->with($placeholder1, 'Test value 1', PDO::PARAM_STR)
      ->once()
      ->andReturnTrue();

    $pdoBuilder->bindToStatement($statement);

    // Verificar que se haya llamado al método con los argumentos esperados
    $statement->shouldHaveReceived('bindValue')->with($placeholder1, 'Test value 1', PDO::PARAM_STR)->once();
  });

  test('debugQuery()', function() {
    $pdoBuilder = new PDOBindBuilder();
    $placeholder1 = $pdoBuilder->addValue('Test value 1');
    $placeholder2 = $pdoBuilder->addValue(507);

    $pdoQuery = "SELECT * FROM table WHERE id = $placeholder1 AND name = $placeholder2;";

    $query = $pdoBuilder->debugQuery($pdoQuery);

    expect($query)->toBe("SELECT * FROM table WHERE id = 'Test value 1' AND name = 507;");
  });

});

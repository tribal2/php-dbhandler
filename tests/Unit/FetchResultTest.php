<?php

use Tribal2\DbHandler\Core\FetchResult;

describe('FetchResult Class', function () {

  test('constructor initializes properties correctly', function () {
    $sampleData = [
      ['id' => 1, 'name' => 'Item 1'],
      ['id' => 2, 'name' => 'Item 2'],
    ];

    $fetchResult = new FetchResult($sampleData);

    // Verify the type of the object
    expect($fetchResult)->toBeInstanceOf(FetchResult::class);

    // Verify the properties
    expect($fetchResult->data)->toBeArray();
    expect($fetchResult->data)->toBe($sampleData);
    expect($fetchResult->count)->toBeInt();
    expect($fetchResult->count)->toBe(count($sampleData));
  });
});

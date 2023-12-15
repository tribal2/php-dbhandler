<?php

use Tribal2\DbHandler\Core\FetchPaginatedResult;

describe('FetchPaginatedResult Class', function () {

  test('constructor initializes properties correctly', function () {
      $sampleData = [
          ['id' => 1, 'name' => 'Item 1'],
          ['id' => 2, 'name' => 'Item 2'],
      ];
      $count = 50;
      $page = 2;
      $perPage = 10;

      $fetchPaginatedResult = new FetchPaginatedResult($sampleData, $count, $page, $perPage);

      // Verify the type of the object
      expect($fetchPaginatedResult)->toBeInstanceOf(FetchPaginatedResult::class);

      // Verify the initialization of the parent properties
      expect($fetchPaginatedResult->data)->toBeArray();
      expect($fetchPaginatedResult->data)->toBe($sampleData);
      expect($fetchPaginatedResult->count)->toBeInt();
      expect($fetchPaginatedResult->count)->toBe($count);

      // Verify the initialization of the pagination properties
      expect($fetchPaginatedResult->totalPages)->toBeInt();
      expect($fetchPaginatedResult->totalPages)->toBe((int)ceil($count / $perPage));
      expect($fetchPaginatedResult->page)->toBeInt();
      expect($fetchPaginatedResult->page)->toBe($page);
      expect($fetchPaginatedResult->perPage)->toBeInt();
      expect($fetchPaginatedResult->perPage)->toBe($perPage);
  });

});

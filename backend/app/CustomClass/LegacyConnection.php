<?php

namespace App\CustomClass;

use MongoDB\Client;

class LegacyConnection
{
  private $conn;
  private $primaryDb = 'RetailRightMain';

  public function __construct(string $adminId)
  {
    $this->conn = new Client(config('database.connections.mongodb.dsn'));
    $this->adminId = $adminId;
  }

  public function users() {
    return $this->conn->selectCollection($this->primaryDb, 'users');
  }

  public function preferences() {
    return $this->conn->selectCollection($this->primaryDb, 'preferences');
  }

  public function stores() {
    return $this->conn->selectCollection($this->primaryDb, 'stores');
  }

  public function orders()
  {
    return $this->conn->selectCollection($this->adminId, 'orders');
  }

  public function returns()
  {
    return $this->conn->selectCollection($this->adminId, 'returns');
  }

  public function items()
  {
    return $this->conn->selectCollection($this->adminId, 'items');
  }

  public function refItems()
  {
    return $this->conn->selectCollection($this->adminId, 'referenceitems');
  }
}
<?php

class Database
{
  // Specify database details:
  private $host = "localhost";
  private $db_name = "banking_service";
  private $username = "root";
  private $password = "root";
  public $connection;

  // Establish the database connection:
  public function getConnection()
  {
    $this->connection = new mysqli($this->host, $this->username, $this->password, $this->db_name);

    if ($this->connection->connect_error) {
      $this->connection = null;
    }

    return $this->connection;
  }
}

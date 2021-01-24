<?php

class User
{
  /*
    Note: Input data (JSON) is not used directly to process queries
    in order to decouple logic from format. This means that in case
    of a change of a property name of the input data 
    e.g. "$userDetails->lastName" might suddenly be "$userDetails->surName", 
    then only the 1 location where the input data is assigned
    to the corresponding class property has to be considered for a fix. 
    */

  // Properties as internal 'state';
  private $connection;
  private $table_name = "users";
  private $customerId;
  private $firstName;
  private $lastName;
  private $dateOfBirth;
  private $gender;

  // Constructor with $database as database connection:
  public function __construct($database)
  {
    try {
      $this->connection = $database;

      // Create table for 'users' if not exists:
      $this->seedUserTable();
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function getAll()
  {
    try {
      // Set limit to 10 table rows for demo:
      $limit = 10;

      // Build query parts:
      $columns = "customerId, firstName, lastName, dateOfBirth, gender";

      // Build executable query:
      $getUsersSQL = "SELECT {$columns} FROM {$this->table_name} LIMIT {$limit}";

      // Execute query and get result:
      $result = $this->connection->query($getUsersSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table rows of all (= 10) users:
      $userRows = $result->fetch_all(MYSQLI_ASSOC);

      // Setup response array:
      $response = array();

      // Add each found table row to array:
      foreach ($userRows as $userRow) {
        $response[] = $userRow;
      }

      // Convert to JSON:
      return json_encode($response);
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function getById($customerId)
  {
    try {
      // Assign class property:
      $this->customerId = $customerId;

      // Build query parts:
      $columns = "firstName, lastName, dateOfBirth, gender";

      // Build executable query:
      $getUserByIdSQL = "SELECT {$columns} FROM {$this->table_name} WHERE customerId={$this->customerId}";

      // Execute query and get result:
      $result = $this->connection->query($getUserByIdSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table row of matching user:
      $userRow = $result->fetch_object();

      // Prepare response object:
      $response = array(
        'customerId' => $this->customerId,
        'firstName' => $userRow->firstName,
        'lastName' => $userRow->lastName,
        'dateOfBirth' => $userRow->dateOfBirth,
        'gender' => $userRow->gender
      );

      // Convert to JSON:
      return json_encode($response);
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function add($userDetails)
  {
    try {
      // Check for complete input:
      checkEmptyInput($userDetails);
      checkWrongInput($userDetails, "User", "miss");

      // Assign sanitized class properties:
      $this->firstName = htmlspecialchars(strip_tags($userDetails->firstName));
      $this->lastName = htmlspecialchars(strip_tags($userDetails->lastName));
      $this->dateOfBirth = htmlspecialchars(strip_tags($userDetails->dateOfBirth));
      $this->gender = htmlspecialchars(strip_tags($userDetails->gender));

      // Build query parts:
      $columns = "firstName, lastName, dateOfBirth, gender";
      $values = "'{$this->firstName}','{$this->lastName}','{$this->dateOfBirth}','{$this->gender}'";

      // Build executable query
      $addUserSQL = "INSERT INTO {$this->table_name} ({$columns}) VALUES ({$values})";

      // Execute query and get result status:
      $status = $this->connection->query($addUserSQL);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function updateDetails($customerId, $newUserDetails)
  {
    try {
      // Restrict updating forbidden properties:
      $forbidden = array("customerId");

      // Check for invalid input:
      checkEmptyInput($newUserDetails);
      checkWrongInput($newUserDetails, "User", "wrong");

      // Assign class property:
      $this->customerId = $customerId;

      // Check for unknown ID first:
      $idExists = $this->getById($this->customerId);

      if (!$idExists) {
        return 0;
      }

      // Concatenate the text for "SET" parameter in query:
      $setPairs = "";
      foreach ($newUserDetails as $key => $value) {
        // Only include allowed keys for query:
        if (!in_array("{$key}", $forbidden)) {
          $setPairs .= "{$key}='{$value}', ";
        }
      }

      // Remove last whitespace:
      $setPairs = trim($setPairs);

      // Remove last comma:
      $setPairs = rtrim($setPairs, ",");

      // Build executable query:
      $updateUserSQL = "UPDATE {$this->table_name} SET {$setPairs} WHERE customerId={$this->customerId}";

      // Execute query and get status:
      $status = $this->connection->query($updateUserSQL);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  // ** Helper functions to consolidate code ** //

  private function seedUserTable()
  {
    try {
      // Initial creation of table 'users':
      $createTableSQL = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                customerId int(11) NOT NULL AUTO_INCREMENT,
                firstName varchar(32) NOT NULL,
                lastName varchar(32) NOT NULL,
                dateOfBirth varchar(32) NOT NULL,
                gender varchar(32) NOT NULL,
                PRIMARY KEY (customerId)
              ) ENGINE=InnoDB";

      $this->connection->query($createTableSQL);
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function checkEmptyResult($result)
  {
    try {
      // Exit fast on failure:
      if (!$result) {
        throw (new Exception("Database declined query with status 'false'"));
      }

      // Exit if ID does not exist:
      if (mysqli_num_rows($result) == 0) {
        throw (new Exception("No results found for this query"));
      }
    } catch (Throwable $e) {
      throw $e;
    }
  }
}

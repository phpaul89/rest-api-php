<?php

class Giro
{
  /*
        See also header note in 'User.php'
  */

  // Properties as internal 'state';
  private $connection;
  private $table_name = "giro_accounts";
  private $accountId;
  private $name;
  private $pin;
  private $dispo;

  // Constructor with $database as database connection:
  public function __construct($database)
  {
    try {
      $this->connection = $database;

      // Create table for 'giro_accounts' if not exists:
      $this->seedGiroTable();
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function getById($accountId)
  {
    try {
      // Assign class property:
      $this->accountId = $accountId;

      // Build query parts:
      $columns = "name, deposit, pin, dispo";

      // Build executable query:
      $getGiroByIdSQL = "SELECT {$columns} FROM {$this->table_name} WHERE accountId={$this->accountId}";

      // Execute query and get result:
      $result = $this->connection->query($getGiroByIdSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table row of matching giro account:
      $giroRow = $result->fetch_object();

      // Prepare response object, exclude "pin":
      $response = array(
        'accountId' => $this->accountId,
        'name' => $giroRow->name,
        'deposit' => $giroRow->deposit,
        'dispo' => $giroRow->dispo
      );

      // Convert to JSON:
      return json_encode($response);
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function add($giroDetails)
  {
    try {
      // Check for correct input, exit if incorrect:
      checkEmptyInput($giroDetails);
      checkWrongInput($giroDetails, "Giro", "all");

      // Assign class properties:
      $this->name = htmlspecialchars(strip_tags($giroDetails->name));
      $this->deposit = 0; // default
      $this->pin = htmlspecialchars(strip_tags($giroDetails->pin));
      $this->dispo = htmlspecialchars(strip_tags($giroDetails->dispo));

      // Build query parts:
      $columns = "name, deposit, pin, dispo";
      $values = "'{$this->name}','{$this->deposit}','{$this->pin}','{$this->dispo}'";

      // Build executable query
      $addGiroSQL = "INSERT INTO {$this->table_name} ({$columns}) VALUES ({$values})";

      // Execute query and get result status (true/false):
      $status = $this->connection->query($addGiroSQL);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function updateDetails($accountId, $newGiroDetails)
  {
    try {
      // Check for invalid input, exit on mismatch:
      checkEmptyInput($newGiroDetails);
      checkWrongInput($newGiroDetails, "Giro", "wrong");

      // Assign class property:
      $this->accountId = $accountId;

      // Restrict updating forbidden properties:
      $forbidden = array("accountId", "deposit");

      // Check for unknown ID first:
      $idExists = $this->getById($this->accountId);

      if (!$idExists) {
        return 0;
      }

      // Concatenate the text for "SET" parameter in query:
      $setPairs = "";
      foreach ($newGiroDetails as $key => $value) {
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
      $updateGiroSQL = "UPDATE {$this->table_name} SET {$setPairs} WHERE accountId={$this->accountId}";

      // Execute query and get status:
      $status = $this->connection->query($updateGiroSQL);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function cashTransfer($accountId, $action, $transactionDetails)
  {
    try {
      // Check for invalid input, exit on mismatch:
      checkEmptyInput($transactionDetails);
      checkWrongInput($transactionDetails, "CashTransfer", "all");

      $this->accountId = $accountId;
      $this->pin = htmlspecialchars(strip_tags($transactionDetails->pin));

      // 'Authenticate' by PIN, exit if PIN is incorrect:
      $this->authByPin($this->pin);

      // Get current balance and dispo of account:
      ["deposit" => $deposit, "dispo" => $dispo] = $this->getCredit();

      // Calculate new balance of account based on action:
      if ($action == "deposit") {
        $newBalance = $deposit + abs($transactionDetails->amount);
      }

      if ($action == "withdraw") {
        $newBalance = $deposit - abs($transactionDetails->amount);
      }

      // Limit withdrawal depending on dispo limit:
      if ($newBalance < $dispo) {
        throw new Exception("Cannot withdraw cash, dispo limit is reached");
      }

      // Build executable query:
      $updateBalanceSQL = "UPDATE {$this->table_name} SET deposit='{$newBalance}' WHERE accountId={$this->accountId}";

      // Update deposit and get status:
      $status = $this->connection->query($updateBalanceSQL);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  // ** Helper functions to consolidate code ** //

  private function seedGiroTable()
  {
    try {
      // Initial creation of table 'giro_accounts':
      $createTableSQL = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                accountId int(11) NOT NULL AUTO_INCREMENT,
                name varchar(32) NOT NULL,
                deposit int(11) NOT NULL,
                pin int(11) NOT NULL,
                dispo int(11) NOT NULL,
                PRIMARY KEY (accountId)
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

  private function authByPin($senderPin)
  {
    try {
      $getGiroPinSQL = "SELECT pin FROM {$this->table_name} WHERE accountId={$this->accountId}";
      $result = $this->connection->query($getGiroPinSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table row of matching giro account:
      $giroRow = $result->fetch_object();
      $giroPin = $giroRow->pin;

      // Compare both PINs:
      $isPinMatch = $senderPin == $giroPin;

      if (!$isPinMatch) {
        throw (new Exception("PIN is incorrect, please enter again"));
      }
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function getCredit()
  {
    try {
      $getCreditSQL = "SELECT deposit, dispo FROM {$this->table_name} WHERE accountId={$this->accountId}";
      $result = $this->connection->query($getCreditSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table row of matching giro account:
      $giroRow = $result->fetch_object();
      return array("deposit" => $giroRow->deposit, "dispo" => $giroRow->dispo);
    } catch (Throwable $e) {
      throw $e;
    }
  }
}

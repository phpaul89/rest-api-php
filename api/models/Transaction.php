<?php

class Transaction
{
  /*
    See also header note in 'User.php'
  */

  // Properties as internal 'state';
  private $connection;
  private $senderId;
  private $receiverId;
  private $amount;
  private $senderPin;


  // Constructor with $database as database connection:
  public function __construct($database)
  {
    try {
      $this->connection = $database;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  public function giroToGiro($wireDetails)
  {
    try {
      // Check for invalid input, exit on mismatch:
      checkEmptyInput($wireDetails);
      checkWrongInput($wireDetails, "Wire", "all");

      // Assign sanitized object properties:
      $this->senderId = htmlspecialchars(strip_tags($wireDetails->senderId));
      $this->receiverId = htmlspecialchars(strip_tags($wireDetails->receiverId));
      $this->amount = htmlspecialchars(strip_tags($wireDetails->amount));
      $this->senderPin = htmlspecialchars(strip_tags($wireDetails->senderPin));

      // Check for same account IDs:
      $this->checkForDifferentIds($this->senderId, $this->receiverId);

      // Get giro accounts by ID:
      $sender = $this->getById($this->senderId); // w/o PIN
      $receiver = $this->getById($this->receiverId); // w/o PIN

      // 'Authenticate' by comparing PIN in database and input PIN:
      $this->authByPin($this->senderId, $this->senderPin);

      // Check credit of sender account:
      $this->checkCredit($this->senderId);

      // Transfer money from sender to receiver:
      $status = $this->transfer($sender, $receiver, $this->amount);

      return $status;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function checkForDifferentIds($senderId, $receiverId)
  {
    try {
      if ($senderId == $receiverId) {
        throw (new Exception("Giro account IDs have to differ"));
      }
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function getById($accountId)
  {
    try {
      // Build query parts:
      $columns = "name, deposit, dispo";

      // Build executable query:
      $getGiroByIdSQL = "SELECT {$columns} FROM giro_accounts WHERE accountId={$accountId}";

      // Execute query and get result:
      $result = $this->connection->query($getGiroByIdSQL);

      // Check query failure:
      $this->checkEmptyResult($result);

      // Get table row of matching giro account:
      $giroRow = $result->fetch_object();

      // Prepare response object:
      $response = array(
        "accountId" => $accountId,
        "name" => $giroRow->name,
        "deposit" => $giroRow->deposit,
        "dispo" => $giroRow->dispo
      );

      return $response;
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function authByPin($senderId, $senderPin)
  {
    try {
      // Build query and execute:
      $getGiroPinSQL = "SELECT pin FROM giro_accounts WHERE accountId={$senderId}";
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

  private function checkCredit($senderId)
  {
    try {
      ["deposit" => $senderDeposit, "dispo" => $senderDispo] = $this->getCredit($senderId);

      // Determine total credit by utilizing dispo limit and current deposit:
      $credit = $senderDeposit + abs($senderDispo);
      $hasEnoughCredit = $credit > $this->amount;

      if (!$hasEnoughCredit) {
        throw (new Exception("Cannot transfer this amount, credit limit reached"));
      }
    } catch (Throwable $e) {
      throw $e;
    }
  }

  private function getCredit($senderId)
  {
    try {
      $getCreditSQL = "SELECT deposit, dispo FROM giro_accounts WHERE accountId={$senderId}";
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

  private function transfer($sender, $receiver, $amount)
  {
    try {
      // Update sender balance:
      ["deposit" => $senderDeposit, "accountId" => $senderId] = $sender;
      $newSenderBalance = $senderDeposit - abs($amount); // subtract from deposit
      $updateSenderDepositSQL = "UPDATE giro_accounts SET deposit='{$newSenderBalance}' WHERE accountId={$senderId}";

      // Update receiver balance:
      ["deposit" => $receiverDeposit, "accountId" => $receiverId] = $receiver;
      $newReceiverBalance = $receiverDeposit + abs($amount); // add to deposit
      $updateReceiverDepositSQL = "UPDATE giro_accounts SET deposit='{$newReceiverBalance}' WHERE accountId={$receiverId}";

      // Use multi-query to prevent edge cases on money "creation"/"destruction":
      $updateDepositsSQL = "{$updateSenderDepositSQL};{$updateReceiverDepositSQL}";
      $status = $this->connection->multi_query($updateDepositsSQL);

      return $status;
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

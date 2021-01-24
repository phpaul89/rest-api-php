<?php

include_once("./helper/prepareModels.php");

// Get a specific giro account:
Router::use("/api/giro/([0-9]*)", function ($accountId) {
  try {
    echo "GET: Giro details of account ID: {$accountId}\n\n";

    ["giro" => $giro, "db" => $db] = newGiroRequest();
    $response = $giro->getById($accountId);

    if ($response) {
      http_response_code(200); // 'Successful query'
      echo $response;
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to get giro account data"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "get");

// Create a giro account:
Router::use("/api/giro", function () {
  try {
    echo "POST: Create new giro account \n\n";

    ["giro" => $giro, "db" => $db] = newGiroRequest();
    $giroDetails = json_decode(file_get_contents("php://input"));
    $status = $giro->add($giroDetails);

    if ($status) {
      http_response_code(201); // 'Created resource'
      echo json_encode(array("message" => "Giro account was added to the database"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to add Giro account to the database"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "post");

// Change giro account details:
Router::use("/api/giro/([0-9]*)", function ($accountId) {
  try {
    echo "PATCH: Details of giro account ID: {$accountId}\n\n";

    ["giro" => $giro, "db" => $db] = newGiroRequest();
    $newGiroDetails = json_decode(file_get_contents("php://input"));
    $status = $giro->updateDetails($accountId, $newGiroDetails);

    if ($status) {
      http_response_code(201); // 'Updated resource'
      echo json_encode(array("message" => "Giro account details were updated successfully"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to update giro account details"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "patch");

// Transfer cash from/to a specific giro account:
Router::use("/api/giro/([[a-zA-Z]+]*)/([0-9]*)", function ($action, $accountId) {
  try {
    echo "PATCH: Start cash transaction for account ID: {$accountId}\n\n";

    $isValidAction = $action == "withdraw" || $action == "deposit";
    if (!$isValidAction) {
      throw new Exception("Unknown transaction type, only 'withdraw' and 'deposit' allowed");
    }

    ["giro" => $giro, "db" => $db] = newGiroRequest();
    $transactionDetails = json_decode(file_get_contents("php://input"));
    $status = $giro->cashTransfer($accountId, $action, $transactionDetails);

    if ($status) {
      http_response_code(200); // 'Successful query'
      echo json_encode(array("message" => "{$action}ing was successful"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to {$action} money"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "patch");

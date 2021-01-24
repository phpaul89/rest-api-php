<?php

include_once("./helper/prepareModels.php");

// Get (10) users:
Router::use("/api/user/all", function () {
  try {
    echo "GET: Every user with their details \n\n";

    ["user" => $user, "db" => $db] = newUserRequest();
    $response = $user->getAll();

    if ($response) {
      http_response_code(200); // 'Successful query'
      echo $response;
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to get user data"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "get");

// Get a specific user:
Router::use("/api/user/([0-9]*)", function ($customerId) {
  try {
    echo "GET: User details of customer ID: {$customerId}\n\n";

    ["user" => $user, "db" => $db] = newUserRequest();
    $response = $user->getById($customerId);

    if ($response) {
      http_response_code(200); // 'Successful query'
      echo $response;
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to get user data"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "get");

// Create a user:
Router::use("/api/user", function () {
  try {
    echo "POST: Create new user \n\n";

    ["user" => $user, "db" => $db] = newUserRequest();
    $userDetails = json_decode(file_get_contents("php://input"));
    $status = $user->add($userDetails);

    if ($status) {
      http_response_code(201); // 'Created resource'
      echo json_encode(array("message" => "User was added to the database"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to add user to the database"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "post");

// Change user details:
Router::use("/api/user/([0-9]*)", function ($customerId) {
  try {
    echo "PATCH: User details of customer ID: {$customerId}\n\n";

    ["user" => $user, "db" => $db] = newUserRequest();
    $newUserDetails = json_decode(file_get_contents("php://input"));
    $status = $user->updateDetails($customerId, $newUserDetails);

    if ($status) {
      http_response_code(201); // 'Updated resource'
      echo json_encode(array("message" => "User details were updated successfully"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to update user details"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "patch");

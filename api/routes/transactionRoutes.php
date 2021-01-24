<?php

include_once("./helper/prepareModels.php");

Router::use("/api/wire", function () {
  try {
    echo "POST: Wire money to another giro account \n\n";

    ["wire" => $wire, "db" => $db] = newWireRequest();
    $wireDetails = json_decode(file_get_contents("php://input"));
    $status = $wire->giroToGiro($wireDetails);

    if ($status) {
      http_response_code(200); // 'Successful query'
      echo json_encode(array("message" => "Wire transfer successful"));
    } else {
      http_response_code(503); // 'Service unavailable'
      echo json_encode(array("message" => "Unable to get giro account data"));
    }

    mysqli_close($db);
  } catch (Throwable $e) {
    http_response_code(400); // 'Bad request'
    echo json_encode(array("message" => "{$e->getMessage()}"));
  }
}, "post");

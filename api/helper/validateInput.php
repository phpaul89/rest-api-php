<?php

// Objects to compare input data against:
$UserTemplate = array(
  "firstName" => "",
  "lastName" => "",
  "dateOfBirth" => "",
  "gender" => ""
);
$GiroTemplate = array(
  "name" => "",
  "pin" => "",
  "dispo" => ""
);
$CashTransferTemplate = array(
  "pin" => "",
  "amount" => ""
);
$WireTemplate = array(
  "senderId" => "",
  "receiverId" => "",
  "senderPin" => "",
  "amount" => ""
);

// Consolidate format checking for all inputs:
function checkWrongInput($input, $origin, $flag)
{
  global $UserTemplate;
  global $GiroTemplate;
  global $CashTransferTemplate;
  global $WireTemplate;

  $templates = array(
    "User" => $UserTemplate,
    "Giro" => $GiroTemplate,
    "CashTransfer" => $CashTransferTemplate,
    "Wire" => $WireTemplate
  );

  switch ($flag) {
    case "wrong":
      checkProperties($templates[$origin], $input);
      break;

    case "miss":
      checkIncomplete($templates[$origin], $input);
      break;

    case "all":
      checkProperties($templates[$origin], $input);
      checkIncomplete($templates[$origin], $input);
      break;
  }
}

function checkProperties($template, $input)
{
  try {
    // Check if input data contains invalid properties for query:
    foreach ($input as $key => $value) {
      $isCorrectProperty = array_key_exists($key, $template);

      if (!$isCorrectProperty) {
        throw (new Exception("Request input contains unknown property: {$key}"));
      }
    }
  } catch (Throwable $e) {
    throw $e;
  }
}

function checkIncomplete($template, $input)
{
  try {
    // Check if input data has required properties for query:
    foreach ($template as $key => $value) {
      $isCorrectProperty = array_key_exists($key, $input);

      if (!$isCorrectProperty) {
        throw (new Exception("Request input misses property: {$key}"));
      }
    }
  } catch (Throwable $e) {
    throw $e;
  }
}

function checkEmptyInput($input)
{
  try {
    // True if empty:
    if ($input == NULL) {
      // Note: Any property not being quoted or any value which is entered as an Integer in the JSON
      // with prefixed zeros, e.g. "pin": 0001 or 0230 will lead
      // to this condition being TRUE. In this case the PIN
      // needs to be put in quotation marks -> "0001", "0230".
      throw (new Exception("Request input cannot be empty or contain a comma after the last property in the request JSON"));
    }

    // False if no properties:
    if (!get_object_vars($input)) {
      throw (new Exception("Request input contains no properties to process"));
    }
  } catch (Throwable $e) {
    throw $e;
  }
}

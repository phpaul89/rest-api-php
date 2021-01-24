<?php

// Instantiate a MySQL database:
include_once("./config/Database.php");

// Instantiate models:
include_once("./models/User.php");
include_once("./models/GiroAccount.php");
include_once("./models/Transaction.php");

function newUserRequest()
{
  $database = new Database();
  $db = $database->getConnection();
  $user = new User($db);

  return array("user" => $user, "db" => $db);
}

function newGiroRequest()
{
  $database = new Database();
  $db = $database->getConnection();
  $giro = new Giro($db);

  return array("giro" => $giro, "db" => $db);
}

function newWireRequest()
{
  $database = new Database();
  $db = $database->getConnection();
  $wire = new Transaction($db);

  return array("wire" => $wire, "db" => $db);
}

<?php

echo "Accessing the API.. \n\n";

// Configuration:
include_once("./config/header.php");

// Helper:
include_once("./helper/Router.php");
include_once("./helper/validateInput.php");

// Routing:
include_once("./routes/userRoutes.php");
include_once("./routes/giroAccountRoutes.php");
include_once("./routes/transactionRoutes.php");

Router::run("/");

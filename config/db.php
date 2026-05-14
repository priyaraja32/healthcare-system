<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "healthcare_project";

$conn = new mysqli(
$host,
$user,
$password,
$database
);

if($conn->connect_error){

    die(
    "Connection Failed : "
    . $conn->connect_error
    );

}

?>
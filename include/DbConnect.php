<?php

/**
 * Handling database connection
 *
 * @author arun kumar
 * @link URL Tutorial link
 */
class DbConnect
{
  private $conn;

  function __construct()
  {
  }

  function connect()
  {

    // $host="192.168.235.39";
    // // $host="localhost";
    // $dbuser="erpposh";
    // $dbpassword="erpposh";
    // $database="erp_posh";

    $host = "localhost";
    $dbuser = "root";
    $dbpassword = "";
    $database = "billspaye";
    $conn = mysqli_connect($host, $dbuser, $dbpassword, $database);
    return $conn;
  }
}

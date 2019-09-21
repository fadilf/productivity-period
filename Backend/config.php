<?php

$sql_user = [
	"Username" => "fadil",
	"Password" => "pithon",
	"Database" => "Productivity Period"
];

$mysqli = new mysqli(
	"localhost",
	$sql_user["Username"],
	$sql_user["Password"],
	$sql_user["Database"]
);
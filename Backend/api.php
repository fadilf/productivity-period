<?php
/*
 *	API interaction handler for server requests Chrome extension.
 *	
 *	Created for Productivity Period
 *	<https://github.com/fadilf/productivity-period>
 */

include("./functions.php");

/*
function sign_up($user_email, $password)
function fetch_user_ID($user_ID)
function fetch_user_email($user_email)
function verify_user($user_email, $password)
function create_session($user_ID, $title, $length)
function join_session($user_ID, $share_code)
function start_session($session_ID)
function running_session($session_ID)
function about_session($session_ID)
function list_session($session_ID)
function update_score($user_ID, $score)
function close_session($session_ID)
*/

switch($_POST["function"]){
	
	case "sign_up":
		$output = json_encode(sign_up($_POST["user_email"], $_POST["password"]));
		echo $output;
		break;
		
	/*case "fetch_user_ID":
		$output = json_encode(fetch_user_ID($_POST["user_ID"]));
		echo $output;
		break;
		
	case "fetch_user_email":
		$output = json_encode(fetch_user_email($_POST["user_email"]));
		echo $output;
		break;*/
		
	case "verify_user":
		$output = json_encode(verify_user($_POST["user_email"], $_POST["password"]));
		echo $output;
		break;
		
	case "create_session":
		$output = json_encode(create_session($_POST["user_ID"], $_POST["title"], $_POST["length"]));
		echo $output;
		break;
		
	case "join_session":
		$output = json_encode(join_session($_POST["user_ID"], $_POST["share_code"]));
		echo $output;
		break;
		
	case "start_session":
		$output = json_encode(start_session($_POST["session_ID"]));
		echo $output;
		break;
		
	case "running_session":
		$output = json_encode(running_session($_POST["session_ID"]));
		echo $output;
		break;
		
	case "about_session":
		$output = json_encode(about_session($_POST["session_ID"]));
		echo $output;
		break;
		
	case "list_session":
		$output = json_encode(list_session($_POST["session_ID"]));
		echo $output;
		break;
		
	case "update_score":
		$output = json_encode(update_score($_POST["user_ID"], $_POST["score"]));
		echo $output;
		break;
		
	case "close_session":
		$output = json_encode(close_session($_POST["session_ID"]));
		echo $output;
		break;
		
	default:
		echo json_encode("No valid function specified");
		break;
}
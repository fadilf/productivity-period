<?php
/*
 *	Backend fuction library for handling server requests. Used
 *	in conjunction with api.php to allow Chrome extension to
 *	handle user accounts and manage sessions.
 *	
 *	Created for Productivity Period
 *	<https://github.com/fadilf/productivity-period>
 */

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//include("./config.php");

/*
 *	Inserts a row for a new user with the username and password
 *	being passed in from the inputs in the Users table.
 *	
 *	Inputs:
 *		- $user_email: a string containing the email of the new user
 *		- $password: a string containing the password of the new user
 *	
 *	Outputs: true if successfully inserted, false if an error occurs
 *	when processing the SQL command.
 */
function sign_up($user_email, $user_fullname, $password){
	global $mysqli;
	$query = "INSERT INTO Users (Email, Name, Password) VALUES (?, ?, ?)";
	$stmt = $mysqli->prepare($query);
	
	// Converts 60 character password hash to 40 byte binary value
	$pass_hash = password_hash($password, PASSWORD_BCRYPT);
	$stmt->bind_param("sss", $user_email, $user_fullname, $pass_hash);
	
	// Returns result of SQL statement execution
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}

/*
 *	Fetches all of the data associated with a user based on user ID.
 *	
 *	Inputs:
 *		- $user_ID: an integer containing the ID of the user
 *	
 *	Outputs: the user info array if user-password combo exists, -1 if
 *	the ID is not in the table.
 */
function fetch_user_ID($user_ID){
	global $mysqli;
	$query = "SELECT `Email`, `Name`, `Password`, `Session Active`, `Session ID`, `Latest Session Score` FROM Users WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("i", $user_ID);
	$stmt->execute();
	$stmt->bind_result(
		$user_email,
		$user_fullname,
		$pass_hash,
		$session_active,
		$session_ID,
		$latest_session_score
	);
	$result = $stmt->fetch();
	$stmt->close();
	
	// Checks if email exists in table
	if($result){
		
		// Creates user info array to return
		$user = [
			"ID" => $user_ID,
			"Email" => $user_email,
			"Name" => $user_fullname,
			"Password Hash" => $pass_hash,
			"Session Active" => $session_active,
			"Session ID" => $session_ID,
			"Current Session Score" => $latest_session_score
		];

		return $user;
	} else {
		// No user exists with given ID
		return -1;
	}
}

/*
 *	Fetches all of the data associated with a user based on email address.
 *	
 *	Inputs:
 *		- $user_email: a string containing the email of the user
 *	
 *	Outputs: the user info array if user-password combo exists, -1 if
 *	the email is not in the table.
 */
function fetch_user_email($user_email){
	global $mysqli;
	$query = "SELECT `ID`, `Name`, `Password`, `Session Active`, `Session ID`, `Latest Session Score` FROM Users WHERE Email=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("s", $user_email);
	$stmt->execute();
	$stmt->bind_result(
		$user_ID,
		$user_fullname,
		$pass_hash,
		$session_active,
		$session_ID,
		$latest_session_score
	);
	$result = $stmt->fetch();
	$stmt->close();
	
	// Checks if email exists in table
	if($result){
		
		// Creates user info array to return
		$user = [
			"ID" => $user_ID,
			"Email" => $user_email,
			"Name" => $user_fullname,
			"Password Hash" => $pass_hash,
			"Session Active" => $session_active,
			"Session ID" => $session_ID,
			"Current Session Score" => $latest_session_score
		];

		return $user;
	} else {
		// No user exists with given email address
		return -1;
	}
}


/*
 *	Verifies a username-password combination as existing within
 *	the Users table.
 *	
 *	Inputs:
 *		- $user_email: a string containing the email of the user
 *		- $password: a string containing the password of the user
 *	
 *	Outputs: the user info array if user-password combo exists, 0 if
 *	the password is incorrect and -1 if the email is not in the table.
 */
function verify_user($user_email, $password){
	
	$user = fetch_user_email($user_email);
	
	// Checks if email exists in table
	if(gettype($user) == "array"){
		
		// Checks if password is the same as the stored hash
		if(password_verify($password, $user["Password Hash"])){
			
			// Erases password hash from return for frontend response
			unset($user["Password Hash"]);
			return $user;
			
		} else {
			// Password does not match account
			return 0;
		}
	} else {
		// No user exists with given email address
		return -1;
	}
}

/*
 *	Creates a row for a new session in the Active Sessions table
 *	on standby and generates a code that the user can share to invite
 *	others with to join the session.
 *	
 *	Inputs:
 *		- $user_ID: an integer containing the ID of the user
 *		- $title: a string for the title of the session
 *		- $length: an integer length of the session's time in minutes 
 *	
 *	Outputs: the string alpahnumeric 7 digit shareable invite code
 */
function create_session($user_ID, $title, $length){
	global $mysqli;
	$query = "INSERT INTO `Active Sessions` (Title, `Share Code`, Members, Length, `Start Time`) VALUES (?, ?, ?, ?, ?)";
	$stmt = $mysqli->prepare($query);
	$share_code = substr(uniqid(), 0, 7);
	$members = json_encode([$user_ID]);
	$start_time = 0;
	$stmt->bind_param("sssii", $title, $share_code, $members, $length, $start_time);
	$stmt->execute();
	$session_ID = $mysqli->insert_id;
	$stmt->close();
	
	$query = "UPDATE Users SET `Session Active`=?, `Session ID`=?, `Latest Session Score`=? WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$session_active = 1;
	$latest_session_score = 0;
	$stmt->bind_param("iiii", $session_active, $session_ID, $latest_session_score, $user_ID);
	
	// Returns result of SQL statement execution
	$stmt->execute();
	$stmt->close();
	return $share_code;
}

/*
 *	Adds a user to a session using the share code given to them
 *	
 *	Inputs:
 *		- $user_ID: an integer containing the ID of the user
 
 *		- $share_code: a string 7 digit alphanumeric invite code
 *	
 *	Outputs: an array of information about the session if successfully
 *	added to/already in session, 0 if the session is already ongoing
 *	and -1 if the share code does not exist
 */
function join_session($user_ID, $share_code){
	global $mysqli;
	$query = "SELECT ID, Title, Members, Length, `Start Time` FROM `Active Sessions` WHERE `Share Code`=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("s", $share_code);
	$stmt->execute();
	$stmt->bind_result($session_ID, $title, $members, $length, $start_time);
	$result = $stmt->fetch();
	$stmt->close();
	
	// If share code is valid for active session
	if($result){
		
		// If session is on standby
		if($start_time == "0"){
			
			// Update session to include new member
			$members = json_decode($members);
			if(!in_array($user_ID, $members)){
				array_push($members, $user_ID);
			}
			$members = json_encode($members);
			$query = "UPDATE `Active Sessions` SET Members=? WHERE ID=?";
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("si", $members, $session_ID);
			$stmt->execute();
			$stmt->close();
			
			// Update current session info of new member
			$query = "UPDATE Users SET `Session Active`=?, `Session ID`=?, `Latest Session Score`=? WHERE ID=?";
			$stmt = $mysqli->prepare($query);
			$session_active = 1;
			$latest_session_score = 0;
			$stmt->bind_param("iiii", $session_active, $session_ID, $latest_session_score, $user_ID);
			$stmt->execute();
			
			// Return info about session
			$session = [
				"ID" => $session_ID,
				"Title" => $title,
				"Length" => $length
			];
			$stmt->close();
			return $session;
			
		// If session is already ongoing
		} else {
			return 0;
		}
		
	// If share code does not point to any active session
	} else {
		return -1;
	}
}

/*
 *	Sets the start time of the session to UTC time at the moment
 *	the function is called
 *	
 *	Inputs:
 *		- $session_ID: a unique integer ID for a session
 *	
 *	Outputs: true if successfully started, false otherwise
 */
function start_session($session_ID){
	global $mysqli;
	$query = "UPDATE `Active Sessions` SET `Start Time`=? WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$time = time();
	$stmt->bind_param("ii", $time, $session_ID);
	$result = $stmt->execute();
	$stmt->close();
	return $time;
}

/*
 *	Returns the current stored data of all members of a session
 *	
 *	Inputs:
 *		- $session_ID: a unique integer ID for a session
 *	
 *	Outputs: an array of data for each member of the session, -1
 *	if session ID is invalid or SQL fails
 */
function running_session($session_ID){
	global $mysqli;
	$query = "SELECT `Start Time` FROM `Active Sessions` WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("i", $session_ID);
	$stmt->bind_result($start_time);
	$result = $stmt->execute();
	
	// If SQL query was successful
	if($result){
		
		// If session with given ID exists
		if($stmt->fetch()){
			$stmt->close();
			
			// If on standby, start time is 0
			if($start_time == 0){
				return false;
			} else {
				return true;
			}
		
		// If session ID does not match any active session
		} else {
			return -1;
		}
	} else {
		return -1;
	}
}

/*
 *	Returns data of all members of a session
 *	
 *	Inputs:
 *		- $session_ID: a unique integer ID for a session
 *	
 *	Outputs: an array of scores for each member of the session, -1
 *	if session ID is invalid
 */
function about_session($session_ID){
	global $mysqli;
	$query = "SELECT Members FROM `Active Sessions` WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("i", $session_ID);
	$stmt->bind_result($members);
	$result = $stmt->execute();
	$member_data = [];
	
	// If SQL query was successful
	if($result){
		
		// If session with given ID exists
		if($stmt->fetch()){
			$stmt->close();
			$members = json_decode($members);
			foreach($members as $member){
				array_push($member_data, fetch_user_ID($member));
			}
			return $member_data;
		
		// If session ID does not match any active session
		} else {
			return -1;
		}
	} else {
		return 0;
	}
}

/*
 *	Returns the current scores of all members of a session
 *	
 *	Inputs:
 *		- $session_ID: a unique integer ID for a session
 *	
 *	Outputs: an array of scores for each member of the session, -1
 *	if session ID is invalid
 */
function list_session($session_ID){
	global $mysqli;
	$query = "SELECT ID, `Latest Session Score` FROM Users WHERE `Session ID`=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("i", $session_ID);
	$stmt->bind_result($user_ID, $score);
	$stmt->execute();
	$member_scores = [];
	while($stmt->fetch()){
		$member_scores[$user_ID] = $score;
	}
	$stmt->close();
	return $member_scores;
}

/*
 *	Updates the focus score for a particular user
 *	
 *	Inputs:
 *		- $user_ID: a unique integer ID for a session
 *		- $score: the float score that the user currently has
 *	
 *	Outputs: true if successfully started, false otherwise
 */
function update_score($user_ID, $score){
	global $mysqli;
	$query = "UPDATE Users SET `Latest Session Score`=? WHERE ID=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("di", $score, $user_ID);
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}


/*
 *	Closes a session
 *	
 *	Inputs:
 *		- $session_ID: a unique integer ID for a session
 *	
 *	Outputs: true if successfully ended, false otherwise
 */
function close_session($session_ID){
	global $mysqli;
	
	// Resets all users to being inactive (no running session)
	$query = "UPDATE Users SET `Session Active`=0 WHERE `Session ID`=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("i", $session_ID);
	$result = $stmt->execute();
	$stmt->close();
	
	// If SQL Update statement successful
	if($result) {
		
		// Gets information about session to archive
		$query = "SELECT Title, Members, Length FROM `Active Sessions` WHERE ID=?";
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param("i", $session_ID);
		$stmt->bind_result($session_title, $members, $length);
		$stmt->execute();
		
		// If SQL Select statement successful
		if($stmt->fetch()){
			$stmt->close();
			
			// Create archive entry of session
			$query = "INSERT INTO `Sessions History` (ID, Title, Members, Length) VALUES (?, ?, ?, ?)";
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("issi", $session_ID, $session_title, $members, $length);
			$result = $stmt->execute();
			$stmt->close();
			
			// If SQL Insert statement successful
			if($result){
				
				// Remove active session, now that archive has been created
				$query = "DELETE FROM `Active Sessions` WHERE ID=?";
				$stmt = $mysqli->prepare($query);
				$stmt->bind_param("i", $session_ID);
				return $stmt->execute();

			} else {
				$stmt->close();
				return false;
			}
		} else {
			return false;
		}
		
	} else {
		return false;
	}
}
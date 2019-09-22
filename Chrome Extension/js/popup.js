var url = "https://hr9.000webhostapp.com/api.php";
var $container = $("#container");

function pageToggle(newPage){
	$container.children(".visible").removeClass("visible");
	$container.children("."+newPage).addClass("visible");
}

//chrome.storage.sync.set({user: undefined}, function() {
//	console.log('Value is set to ' + undefined);
//});

chrome.storage.sync.get('user', function(data) {
	var user = data.user;
	console.log("User object:");
	console.log(user);
	console.log("Name:" + user.Name);
	if (user === undefined || user === false) {
		pageToggle("login");
	} else {
		pageToggle("home");
		$(".name_fill").html(user.Name);
	}
});

$("#login-form").submit(function(event){
	event.preventDefault();
	var email = $("#login-form .email").val();
	var password = $("#login-form .password").val();
	var param = {
		"function": "verify_user",
		"user_email": email,
		"password": password
	}
	console.log("Parameters: ")
	console.log(param);
	$.post(url, param, function(response){
		if(response === "0"){
			alert("Incorrect password!");
		} else if (response === "-1"){
			alert("Email address not registered!");
		} else {
			chrome.storage.sync.set({'user': response}, function() {
				console.log(response.Name+' has logged in.');
			});
			$(".name_fill").html(response.Name);
			pageToggle("home");
		}
	});
});

$("#make-room-form").submit(function(event){
	event.preventDefault();
	var s_title = $("#make-room-form .session-name").val();
	var hours = $("#make-room-form .hours").val();
	var minutes = $("#make-room-form .minutes").val();
	var t_length = hours*60 + minutes*1;
	//create_session($_POST["user_ID"], $_POST["title"], $_POST["length"]))

	chrome.storage.sync.get('user', function(data){
		console.log("User ID: " + data.user.ID);
		var param = {
			"function": "create_session",
			"user_ID": data.user.ID,
			"title": s_title,
			"length": t_length
		}
		console.log("Parameters: ");
		console.log(param);
		$.post(url, param, function(response){
			var sharecode = response["Share Code"];
			console.log("Share code: " + sharecode);
			$("#share-code").html(sharecode);
			chrome.storage.sync.set({'session':response}, function(data){
				console.log(response);
				pageToggle("waiting-room");
				waitingRoom(response);
			});
		});
	});
});

$("#join-room-form").submit(function(event){
	event.preventDefault();
	var room_code = $("#join-room-form .room-code").val();

	chrome.storage.sync.get('user', function(data){
		console.log("User ID: " + data.user.ID);
		var param = {
			"function": "join_session",
			"user_ID": data.user.ID,
			"share_code": room_code
		}
		console.log("Parameters: ");
		console.log(param);
		$.post(url, param, function(response){
			console.log("Share code: " + room_code);
			$("#share-code").html(room_code);
			chrome.storage.sync.set({'session':response}, function(data){
				console.log(response);
				pageToggle("waiting-room");
				waitingRoom(response);
			});
		});
	});
});

$("#make-room").click(function(){
	pageToggle("make-room");
});

$("#join-room").click(function(){
	pageToggle("join-room");
});

var pollRoom;

function waitingRoom(session_info){
	console.log(session_info);
	var param = {
		"function": "about_session",
		"session_ID": session_info.ID
	}
	console.log("Starting poll");
	waitingRoomPoll(param);
	pollRoom = setInterval(function(){
		waitingRoomPoll(param);
	},5000);
}

function waitingRoomPoll(param){
	console.log("running poll...");
	$.post(url, param, function(response){
		//			console.log(response);
		$("#joined-members").html("")
		var members = response.Members;
		for(var member in members){
			member = members[member];
			//			console.log(member);
			var output = "<div class='j-member'>"+
				member.Name + "</div>";
			$("#joined-members").append(output);
		}
		if(response["Start Time"] != 0){
			beginRoom();
		}
	});
}

$("#start-room").click(function(){
	beginRoom();
});

function beginRoom(){
	clearInterval(pollRoom);
	chrome.storage.sync.get('session', function(data){
		console.log(data.session);
		//start_session($_POST["session_ID"])
		var param = {
			'function': "start_session",
			'session_ID': data.session.ID
		}
		$.post(url, param, function(response){
			console.log(response);
			$("#time-left").html(response);
			pageToggle("inside-room");
			startTracking(response);
		});
	});
}

function startTracking(length){
	var time_left = length;
	setInterval(function(){
		time_left--;
		$("#time-left").html(time_left);
	},60*1000);
	
}
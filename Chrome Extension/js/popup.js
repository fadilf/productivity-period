var url = "http://localhost/pp-api/api.php";
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
	console.log(user);
	console.log(user.Name);
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
	var t_length = hours*60 + minutes;
	//create_session($_POST["user_ID"], $_POST["title"], $_POST["length"]))
	
	chrome.storage.sync.get('user', function(data){
		console.log(data.user.ID);
		var param = {
			"function": "create_session",
			"user_id": data.user.ID,
			"title": s_title,
			"length": t_length
		}
		console.log(param);
		$.post(url, param, function(response){
			console.log(response);
			$("#share-code").html(response);
			pageToggle("waiting-room");
		});
	});
});

$("#make-room").click(function(){
	pageToggle("make-room");
});

$("#join-room").click(function(){
	pageToggle("join-room");
});

/*let changeColor = document.getElementById('changeColor');

chrome.storage.sync.get('color', function(data) {
	changeColor.style.backgroundColor = data.color;
	changeColor.setAttribute('value', data.color);
});

changeColor.onclick = function(element) {
	let color = element.target.value;
	chrome.tabs.query({active: true, currentWindow: true}, function(tabs) {
		chrome.tabs.executeScript(
			tabs[0].id,
			{code: 'document.body.style.backgroundColor = "' + color + '";'});
	});
};*/
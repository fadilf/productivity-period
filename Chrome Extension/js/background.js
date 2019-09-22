chrome.runtime.onInstalled.addListener(function() {
	chrome.storage.sync.set({user: false}, function() {
	});
	chrome.declarativeContent.onPageChanged.removeRules(undefined, function() {
		chrome.declarativeContent.onPageChanged.addRules([{
			conditions: [new chrome.declarativeContent.PageStateMatcher({
				pageUrl: {urlContains: 'h'},
			})],
			actions: [new chrome.declarativeContent.ShowPageAction()]
		}]);
	});
});
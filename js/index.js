var args = require('minimist')(process.argv.slice(2));

var commands = {
	fetch: require("./lib/fetch"),
	members: require("./lib/members")
}

if (commands[args._[0]]) {
	commands[args._[0]](args);
}

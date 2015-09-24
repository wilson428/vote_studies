var ghdownload = require('github-download'),
	exec = require('exec'),
	YAML = require("yamljs"),
	mkdirp = require("mkdirp"),
	rmdir = require('rimraf'),
	fs = require("fs");

module.exports = function() {
	rmdir("./data/tmp", function(err) {
		if (err) throw(err);
		ghdownload({ user: 'unitedstates', repo: 'congress-legislators', ref: 'master'}, "./data/tmp/")
		.on('error', function(err) {
		  console.error(err)
		})
		.on('end', function() {
			mkdirp("./data/members/", function() {
				console.log("Parsing current lawmakers.");
				var current = YAML.load("./data/tmp/legislators-current.yaml");
				fs.writeFileSync("./data/members/current.json", JSON.stringify(current, null, 2));

				console.log("Parsing historical lawmakers.");
				var current = YAML.load("./data/tmp/legislators-historical.yaml");
				fs.writeFileSync("./data/members/historical.json", JSON.stringify(current, null, 2));

				rmdir("./data/tmp", function() {
					console.log("Finished. Removing unitedstates/congress-legislators repo.");
				});
			});
		});
	}); // this fails if anything already in folder
}

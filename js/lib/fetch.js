var downcache = require("downcache"),
	cheerio = require("cheerio"),
	parseString = require('xml2js').parseString,
	mkdirp = require("mkdirp");
	fs = require("fs");

var fetch = module.exports = function(args) {
	var session = args.session || "114";

	downcache("http://www.govtrack.us/data/us/" + session + "/rolls/", function(err, resp, body) {
		var $ = cheerio.load(body);
		$("a").each(function(i, v) {
			var vote = $(v).attr("href"),
				url = "http://www.govtrack.us/data/us/" + session + "/rolls/" + vote;

			if (!~vote.indexOf(".xml")) {
				return;
			}

			downcache(url, function(err, resp, xml) {
				parseString(xml, function (err, result) {
					if (err) {
						throw(err);
					}
					var path = "./data/" + result.roll.$.where + "/" + result.roll.$.session;

					mkdirp(path, function(err) {
						fs.writeFile(path + "/" + vote.slice(0, -4) + ".json", JSON.stringify(result, null, 2), function() {
							console.log("Wrote", vote);
						});
					});
				});
			});
		})
	});
}
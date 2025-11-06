var env = process.env.env || "dev";
var config = require("./config.js").getConfig();

exports.respond = function(req, res) {
    return function (err, data) {
        var status = "ok";
        var message = "";
        var parameters = {};
    try {
        parameters = JSON.parse(JSON.stringify(req.parameters))
    } catch (e) { }
        if (err) {
            console.log("Error in " + req.path);
            console.log(err);
            console.log("Parameters: ")
            console.log(req);
            status = "error";
            message = err;
            data = null;
        }
        //logRequest(req, err, data)
        req.parameters = parameters;

        res.status(200).json({
            headers: {
                "Access-Control-Allow-Origin": "*"
            },
            status: status,
            message: message,
            data: data
        })
    }
}


exports.log = function(message)
{
    if (config.debug)
        console.log(message)
}

var config = require("./config.js").getConfig()
var $ = {};
var conn = require("./connectionUtils.js").getConnection()
var connectionUtils = require("./connectionUtils");
var middleware = require("./middleware.js")
const { v4: uuidv4 } = require('uuid');
var async = require("async");
const { param } = require("./index.js");

/*
exports.getCustomers = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM vCustomerSummary WHERE Active = '1' ORDER BY OrganizationName";
        var queryParameters = [params.cusomerId, params.name];
        var result = await conn.queryP(sql, queryParameters) // gets result
        
        middleware.log("getCustomers " + result)
    }
    catch (ex) {
        middleware.log("getCustomers error " + ex)
        return callback("getCustomers error: " + ex);
    }

    callback(null, {
        message: "getCustomers done",
        data: result
    })
};

exports.getCustomerById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM customer where CustomerId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};
*/
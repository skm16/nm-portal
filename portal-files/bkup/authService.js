var conn = require("./connectionUtils.js").getConnection();
var cache = require("./connectionUtils").cache;
var config = require("./config.js").getConfig();
var promisify = require("util").promisify;
var $ = {};
$.ajax = require("najax")

/*
exports.authenticate = async function(params, callback) {
    try {
        var res = await promisify(AM.authenticate)({
            userName: params.email,
            password: params.password,
            domain: params.domain
        });
        //var user = await promisify(exports.getUser)(res.user.GUID);
        callback(null, {
            authTokenGUID: res.authTokenGUID,
            user: user
        });
    } catch(e) {
        console.log("Error during authentication: " + e);
        callback("Invalid email or password");
    }
}
*/

exports.authenticate = async function(params, callback) {
    try {
        var res = await promisify(AM.authenticate)({
            userName: params.email,
            password: params.password,
            domain: config.domain
        });
        var user = await promisify(exports.getUser)(res.user.GUID);

        //Check if business is approved
        var vals = user.CompanyId;
        var sql = "SELECT Approved FROM business WHERE BusinessId = ?";
        conn.query(sql, vals, function (err, data) {
            if (err) {
                callback(err)
            } else {
                callback(null, {
                    authTokenGUID: res.authTokenGUID,
                    isAdmin: res.user.isAdmin,
                    user: user,
                    isApproved: data.length > 0 ? data[0].Approved : 0
                });
            }
        })        
    } catch(e) {
        console.log("Error during authentication: " + e);
        callback("Invalid email or password");
    }
}

exports.getAuthToken = async function(params, callback) {
    try {
        var res = await promisify(AM.getAuthToken)({
            authTokenGUID: params.authTokenGUID
        });
        var user = await promisify(exports.getUser)(res.user.GUID);
        callback(null, {
            authTokenGUID: res.authTokenGUID,
            isAdmin: res.user.isAdmin,
            user: user
        });
    } catch(e) {
        console.log("Error getting authToken: " + e);
        callback("Invalid authToken.");
    }
}

exports.updateAuthToken = async function(params, callback) {
    try {
        var res = await promisify(AM.updateAuthToken)({
            authTokenGUID: params.authTokenGUID
        });
        var user = await promisify(exports.getUser)(res.user.GUID);
        callback(null, {
            authTokenGUID: res.authTokenGUID,
            isAdmin: res.user.isAdmin,
            user: user
        });
    } catch(e) {
        console.log("Error getting authToken: " + e);
        callback("Invalid authToken.");
    }
}

exports.getUser = function(userId, callback) {
    conn.queryP("SELECT * FROM user where AccountManagerGUID = ?", [userId], function(err, data) {
        if(err) {
            callback(err)
        } else if(!data || !data.length) {
            callback("User not found.")
        } else {
            callback(null, data[0])
        }
    });
}

exports.recoverPwd = function(params, callback){

    var templateBody = "Dear " + params.firstName + ", <br><br/> You have been added as a user to modify your company details in the New Mexico Department of Agriculture community. Please follow this link to create your password and then you may login at https://nmdadirectory.rtsclients.com/admin/login.html with your email address and the password you created. <br/><br/> <a href=\"{url}\">Reset Password</a> <br/><br/>Thank you, <br/>New Mexico Department of Agriculture";
    var templateSubject = "NMDA Community - Password Reset";

    var options = {
        userName:params.userName,
        domain:config.domain,
        destination:"https://nmdadirectory.rtsclients.com/admin/login.html" ,
        host:config.domain,
        templateBody: templateBody,
        templateSubject: templateSubject,
    };
    AM.forgotPassword(options, callback);
}

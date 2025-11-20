var config = require("./config.js").getConfig()
var async = require("async")
var conn = require("./connectionUtils.js").getConnection();
var cache = require("./connectionUtils").cache;
const { v4: uuidv4 } = require('uuid');
var promisify = require("util").promisify;
var $ = {};
$.ajax = require("najax")
var generator = require('generate-password');
var authService = require("./authService.js")

var _ = require("lodash")

exports.getUsers = function (params, callback) {
    var sql = "select * from vUsers";
    conn.query(sql, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    });
}

exports.getUser = function (params, callback) {
    var vals = params.email;

    var sql = "select UserId from user where Email = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    });
}

exports.addUser = async function (params, callback) {
    try{
        var password = generator.generate({
            length: 8,
            numbers: true
        });

        params["Password"] = password;

        //Add the user to the account manager
        var res = await promisify(AM.addUser)({
            authTokenGUID: config.adminToken,
            firstName: params.FirstName,
            lastName: params.LastName,
            phone: "",
            email: params.Email,
            userName: params.Email,
            password: password,
            domain: config.domain
        })

        //Get the newly added user id
        var userId = res.userGUID;

        //Add the user to the user table
        var userInfo = {
            UserId: uuidv4(),
            FirstName: params.FirstName,
            LastName: params.LastName,
            Email: params.Email,
            CompanyId: params.BusinessId,
            AccountManagerGUID: userId
        }

        var sql = "insert into user set ?";
        var vals = [userInfo];
        conn.query(sql, vals, async function (err) {
            if (err) {
                callback(err);
            } else {
                //When the user is created, send them an email to reset their pw
                //authService.recoverPwd({userName: params.Email, firstName: params.FirstName, lastName: params.LastName}, callback)

                var templateBody = "Dear " + params.FirstName + ", <br><br/> You have been added as a user to modify your company details in the New Mexico Department of Agriculture community. Use the credentials below to login at https://nmdadirectory.rtsclients.com/admin/login.html and access your account. <br /><br />Email: " + params.Email + "<br />Pw: " + params.Password + " <br/><br/>Thank you, <br/>New Mexico Department of Agriculture";
                var templateSubject = "NMDA Community - Password Reset";

                var emailOptions = {
                    authTokenGUID: "910f09f5-df37-45d9-b5b7-f3e0cbb8c64b",
                    subject: templateSubject,
                    body: templateBody,
                    emails: params.Email,
                    priority: 0
                };

                $.ajax({
                    url: "https://realmessage.rtsclients.com/api/v2/QueueMessage",
                    type: "POST",
                    dataType: "json",
                    data: emailOptions,
                    success: function (res) {
                        //Add the user to the RMS as inactive
                        var rmsOptions = {
                            authTokenGUID: "910f09f5-df37-45d9-b5b7-f3e0cbb8c64b",
                            userId: userInfo.UserId,
                            userTypeId: "f148eb10-fd78-4433-9880-4c746425680d",
                            domain: "nmda.rtsclients.com",
                            firstName: userInfo.FirstName,
                            lastName: userInfo.LastName,
                            emailAddress: userInfo.Email,
                            active:1
                        };

                        //Add user to the RMS
                        $.ajax({
                            url: "https://wtbwwpgln7.execute-api.us-west-2.amazonaws.com/qa/CreateUser",
                            type: "POST",
                            data: rmsOptions,
                            success: function(res) {
                                callback(null, res.data);
                            }
                        })
                    },
                    error: function error(res) {
                        console.log(res);
                        callback(res);
                    }
                })

                //RM.sendEmail(emailOptions, callback);

                //callback(null);
            }
        });
    } catch(e) {
        console.log("error: " + e);
    }
}

exports.deleteUser = async function (params, callback) {
    //Delete the user from the account manager
    var res = await promisify(AM.deleteUser)({
        authTokenGUID: config.adminToken,
        userGUID: params.UserGuid,
        domain: config.domain
    })

    //Delete the user from the user table
    var sql = "DELETE FROM user WHERE UserId = ?";
    var vals = [params.UserId];
    conn.query(sql, vals, function (err) {
        if (err) {
            callback(err);
        } else {
            //Add the user to the RMS as inactive
            var rmsOptions = {
                authTokenGUID: "910f09f5-df37-45d9-b5b7-f3e0cbb8c64b",
                userId: params.UserId,
                domain: "nmda.rtsclients.com"
            };

            $.ajax({
                url: "https://wtbwwpgln7.execute-api.us-west-2.amazonaws.com/qa/DeleteUser ",
                type: "POST",
                data: rmsOptions,
                success: function(res) {
                    callback(null);
                }
            })
        }
    });
}
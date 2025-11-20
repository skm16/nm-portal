'use strict'

var express = require('express')
var app = express()
var cors = require('cors')
var async = require("async")
var config = require("./config.js").getConfig();
var bodyParser = require('body-parser');
var middleware = require("./middleware.js");
const ratelimit = require("./ratelimit");
const jwt = require('jsonwebtoken');
const fs = require('fs');

var authorize = middleware.authorize;
var authorizeAdmin = middleware.authorizeAdmin;
var respond = middleware.respond;

var env = process.env.env || "dev";
var port = process.env.PORT || 1741;

var authService = require("./authService.js")
var companyService = require("./companyService.js");
var commonService = require("./commonService");
var usersService = require("./usersService.js");

app.use(cors());

middleware.log("Service start: " + config.version + " " + env)

app.enable('trust proxy');
//app.use(express.json());
app.use(bodyParser.json({ extended: false }));
app.use(bodyParser.urlencoded({ extended: true }));

app.use(function (req, res, next) {
    if (req.method.toLowerCase() == "post") {
        req.parameters = req.body;
    } else {
        req.parameters = req.query;
    }
    next();
})

app.get("/health", function (req, res) {
    var status = "ok";
    var message = "Up and running!"
    var data = new Date().getTime().toString();
    res.json({
        status: status,
        message: message,
        data: data,
        env: env,
        version: config.version
    })
    middleware.log("Health " + config.version + " " + env + " " + data)
})

function isAPIAuthenticated(req, res, next) {
    if (typeof req.headers.authorization !== "undefined") {
        // retrieve the authorization header and parse out the
        // JWT using the split function
        let token = req.headers.authorization.split(" ")[1];
        let privateKey = fs.readFileSync(__dirname +'/private.pem', 'utf8');
        // Here we validate that the JSON Web Token is valid and has been 
        // created using the same private pass phrase
        jwt.verify(token, privateKey, { algorithm: "RS256" }, (err, user) => {
            
            // if there has been an error...
            if (err) {  
                // shut them out!
                res.status(500).json({ error: "Not Authorized" });
                throw new Error("Not Authorized");
            }
            // if the JWT is valid, allow them to hit
            // the intended endpoint
            return next();
        });
    } else {
        // No authorization header exists on the incoming
        // request, return not authorized and throw a new error 
        res.status(500).json({ error: "Not Authorized" });
        throw new Error("Not Authorized");
    }
}

// Use any/none/all of these:
// authorize - retrieves authToken
// authorizeAdmin - retrieves authToken and verifies user is an admin
// log - sends request to audit log service
// verify params - simple function to pre-check that all required parameters are present
app.get('/jwt', (req, res) => {
    let privateKey = fs.readFileSync(__dirname +'/private.pem', 'utf8');
    let token = jwt.sign({ "body": "nmdaapi" }, privateKey, { algorithm: 'RS256'});
    res.send(token);
})

app.post("/Authenticate", function(req, res) {
    authService.authenticate(req.parameters, respond(req,res));
})

app.get("/UpdateAuthToken", function(req, res) {
    authService.updateAuthToken(req.parameters, respond(req,res));
})

//account manager
app.get("/Login", function(req, res) {
    //commonService.testAccountManagerAuthenticateAwait(req.parameters, respond(req,res))
    authService.authenticate(req.parameters, respond(req,res))
});
app.post("/RecoverPwd", function(req, res) {
    authService.recoverPwd(req.parameters, respond(req,res))
});

app.get("/TestMemcache", function(req, res) {
    commonService.testMemcache(req.parameters, respond(req,res))
})

app.post("/AddUser", function(req, res) {
    usersService.addUser(req.parameters, respond(req,res));
})

app.post("/DeleteUser", function(req, res) {
    usersService.deleteUser(req.parameters, respond(req,res));
})

/*********** Companies *********/
app.get("/GetCompanies", function (req, res) {
    companyService.getCompanies(req.parameters, respond(req, res));
});

app.get("/GetCompanyById", function (req, res) {
    companyService.getCompanyById(req.parameters, respond(req, res));
});

app.get("/GetCompanyUsers", function (req, res) {
    companyService.getCompanyUsers(req.parameters, respond(req, res));
});

app.get("/GetCompanyGroups", function (req, res) {
    companyService.getCompanyGroups(req.parameters, respond(req, res));
});

app.post("/AddCompany", function (req, res) {
    companyService.addCompany(req.parameters, respond(req, res));
});

app.post("/UpdateCompany", function (req, res) {
    companyService.updateCompany(req.parameters, respond(req, res));
});

app.post("/DeleteCompany", function (req, res) {
    companyService.deleteCompany(req.parameters, respond(req, res));
});

app.post("/AddBusiness", function (req, res) {
    companyService.addBusiness(req.parameters, respond(req, res));
});

app.post("/UpdateBusiness", function (req, res) {
    companyService.updateBusiness(req.parameters, respond(req, res));
});

app.post("/DeleteBusiness", function (req, res) {
    companyService.deleteBusiness(req.parameters, respond(req, res));
});

app.post("/DeleteBusinessAddress", function (req, res) {
    companyService.deleteBusinessAddress(req.parameters, respond(req, res));
});

app.post("/UpdateBusinessCategory", function (req, res) {
    companyService.updateBusinessCategory(req.parameters, respond(req, res));
});

app.get("/GetBusinesses", function (req, res) {
    companyService.getBusinesses(req.parameters, respond(req, res));
});

app.get("/GetAllBusinesses", isAPIAuthenticated, ratelimit, function (req, res) {
    companyService.getBusinesses(req.parameters, respond(req, res));
});

app.get("/GetBusinessApprovals", function (req, res) {
    companyService.getBusinessApprovals(req.parameters, respond(req, res));
});

app.get("/GetBusinessById", function (req, res) {
    companyService.getBusinessById(req.parameters, respond(req, res));
});

app.get("/GetBusinessUsers", function (req, res) {
    companyService.getBusinessUsers(req.parameters, respond(req, res));
});

app.get("/GetBusinessAddresses", function (req, res) {
    companyService.getBusinessAddresses(req.parameters, respond(req, res));
});

app.get("/GetReimburseLead", function (req, res) {
    companyService.getReimburseLead(req.parameters, respond(req, res));
});

app.get("/GetReimburseLeadApprovals", function (req, res) {
    companyService.getReimburseLeadApprovals(req.parameters, respond(req, res));
});

app.get("/GetReimburseLeadById", function (req, res) {
    companyService.getReimburseLeadById(req.parameters, respond(req, res));
});

app.get("/GetReimburseLeadByBusinessId", function (req, res) {
    companyService.getReimburseLeadByBusinessId(req.parameters, respond(req, res));
});

app.post("/AddReimburseLead", function (req, res) {
    companyService.addReimburseLead(req.parameters, respond(req, res));
});

app.post("/UpdateReimburseLead", function (req, res) {
    companyService.updateReimburseLead(req.parameters, respond(req, res));
});

app.get("/GetReimburseAdvertising", function (req, res) {
    companyService.getReimburseAdvertising(req.parameters, respond(req, res));
});

app.get("/GetReimburseAdvertisingApprovals", function (req, res) {
    companyService.getReimburseAdvertisingApprovals(req.parameters, respond(req, res));
});

app.get("/GetReimburseAdvertisingById", function (req, res) {
    companyService.getReimburseAdvertisingById(req.parameters, respond(req, res));
});

app.get("/GetReimburseAdvertisingByBusinessId", function (req, res) {
    companyService.getReimburseAdvertisingByBusinessId(req.parameters, respond(req, res));
});

app.post("/AddReimburseAdvertising", function (req, res) {
    companyService.addReimburseAdvertising(req.parameters, respond(req, res));
});

app.post("/UpdateReimburseAdvertising", function (req, res) {
    companyService.updateReimburseAdvertising(req.parameters, respond(req, res));
});

app.get("/GetReimburseLabels", function (req, res) {
    companyService.getReimburseLabels(req.parameters, respond(req, res));
});

app.get("/GetReimburseLabelsApprovals", function (req, res) {
    companyService.getReimburseLabelsApprovals(req.parameters, respond(req, res));
});

app.get("/GetReimburseLabelsById", function (req, res) {
    companyService.getReimburseLabelsById(req.parameters, respond(req, res));
});

app.get("/GetReimburseLabelsByBusinessId", function (req, res) {
    companyService.getReimburseLabelsByBusinessId(req.parameters, respond(req, res));
});

app.post("/AddReimburseLabels", function (req, res) {
    companyService.addReimburseLabels(req.parameters, respond(req, res));
});

app.post("/UpdateReimburseLabels", function (req, res) {
    companyService.updateReimburseLabels(req.parameters, respond(req, res));
});

/*********** Group Types *********/
app.get("/GetGroupTypes", function (req, res) {
    companyService.getGroupTypes(req.parameters, respond(req, res));
});


if (env == "dev") {
    app.listen(port, function () {
        console.log('Listening on port ' + port);
    })
} else {
    module.exports = app;
}
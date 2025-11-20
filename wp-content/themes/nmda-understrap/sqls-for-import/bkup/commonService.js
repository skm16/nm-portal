var config = require("./config.js").getConfig()
var async = require("async")
var conn = require("./connectionUtils.js").getConnection()
var middleware = require("./middleware.js");
var connectionUtils = require("./connectionUtils");
var cache = connectionUtils.cache;
var promisify = require("util").promisify;

var _ = require("lodash")

exports.testMemcache = async function(params, callback) 
{
    middleware.log("testMemcache ")
    var cacheValue = null

    try
    {
        var code = Math.floor(Math.random() * 900000) + 100000
        var hashKey = crypto.createHash('sha256').update("test" + code).digest('hex')
        result = await cache.set(hashKey, code, 60)
        middleware.log("testMemcache set cache value " + cacheValue)

        var cacheValue = await cache.get(hashKey)
        middleware.log("testMemcache got cache value " + cacheValue)
        if (cacheValue == null)
            cacheValue = "NullCache"

        if (cacheValue == code)
            middleware.log("testMemcache test cache success" + code + " " + cacheValue)
        else
            return callback("testMemcache test cache fail " + code + " " + cacheValue);
    }
    catch(ex)
    {
        middleware.log("testMemcache Error" + ex)
        cacheValue = "testMemcache Error " + ex
    }
    
    callback(null, {
            message: "testMemcache result",
            data: cacheValue
    })
}

exports.sendEmail = function (params, callback) {  
    /*
    if (err) {
        callback(err);
    } else {
        callback(null);
    }
    */
}

exports.getStates = function (params, callback) {
    var sql = "select * from states order by text";
    conn.query(sql, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    });
}

exports.getPositions = function (params, callback) {
    var sql = "select * from positions order by positionName";
    conn.query(sql, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    });
}

exports.createFileAssociation = function (params, callback) {
    var fileInfo = {
        fileAssociationId: params.fileAssociationId,
        associationId: params.associationId,
        fileType: params.fileType,
        realFileGUID: params.realFileGUID,
        originalFileName: params.originalFileName
    }
    var sql = "insert into fileAssociations set ?";
    var vals = [fileInfo];
    conn.query(sql, vals, function (err) {
        if (err) {
            callback(err);
        } else {
            callback(null);
        }
    });
}

exports.getFileAssociations = function (params, callback) {
    var sql = "select * from fileAssociations where associationId = ? and fileType = ? and deleted = 0 order by originalFileName";
    var vals = [params.associationId, params.fileType];
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    });
}

exports.deleteFileAssociation = function (params, callback) {
    var sql;
    var vals;
    sql = "update fileAssociations set deleted = 1 where fileAssociationId = ?";
    vals = [params.fileAssociationId];
    conn.query(sql, vals, function (err) {
        if (err) {
            callback(err);
        } else {
            callback(null);
        }
    });
}

exports.testAccountManagerAuthenticateAwait = async function(params, callback)
{
    middleware.log("testAccountManagerAuthenticate " + params.username + " " + params.password + " " + params.domain)
    var result =  null

    try
    {
        result = await promisify(AM.authenticate)({
            userName: params.username,
            password: params.password,
            domain: params.domain
        })
    }
    catch (ex) {
        return callback("testAccountManagerAuthenticate error: " + ex);
    }

    callback(null, {
        message: "testAccountManagerAuthenticate done",
        data: result
    })
    console.log(result);
}

/*documents*/
exports.getDocuments = function (params, callback) {
    var sql = "select * from documents where projectId = ? order by created desc;";
    conn.query(sql, [params.projectID], callback);
}
exports.saveDocumentInfo = function (params, callback) {
    var vals = {
        documentId: params.documentId,
        applicantId: params.applicantId,
        name: params.name,
        created: new Date().getTime(),
        description: params.description,
        adminId: params.authorization.user.GUID
    };
    var sql = "insert into documents set ?;"
    conn.query(sql, vals, callback)
}
exports.deleteDocument = function (params, callback) {
    async.series([
        function (cb) {
            RF.deleteFile({
                GUID: params.documentId,
                authTokenGUID: params.authTokenGUID
            }, cb)
        },
        function (cb) {
            var sql = "delete from documents where documentId = ?;";
            conn.query(sql, [params.documentId], cb)
        }

    ], callback)
}
exports.getRealFileLink = function (params, callback) {
    RF.getS3Request({
        authTokenGUID: config.adminToken,
        fileGUID: params.documentId,
        GUID: params.documentId,
        domain: config.domain
    }, function (err, data) {
        if (data) {
            callback(null, data.url)
        } else {
            callback("Could not get file.")
        }
    })
}

exports.getFolderId = function (params, callback) {
    var sql = "select folderId from project where ProjectId = ?;"
    conn.query(sql, [params.projectId], function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data[0].folderId);
        }
    })
}
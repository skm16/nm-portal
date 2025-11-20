var config = require("./config.js").getConfig();
var mysql = require("mysql");
var promisify = require("util").promisify;
var env = process.env.env || "dev";

var connection = {}
var conn = null

exports.getConnection = function () {
    if (conn == null) {
        conn = mysql.createPool({
            multipleStatements: true,
            connectionLimit: 50,
            host: config.dbUrl,
            user: config.dbUser,
            password: config.dbPassword,
            database: config.dbName
            //typeCast: function (field, next) {
            //    if (field.type === 'DATE') {
            //        return field.string();
            //    }
            //    return next();
            //}
        })
        conn.queryP = promisify(conn.query).bind(conn);
    }
    return conn;
}


exports.cache = {
    get: async function (key) {
        return get(key)
    },
    getMulti: async function (key) {
        return getMulti(key)
    },
    set: async function (key, value, timeout) {
        return set(key, value, timeout)
    },
    add: async function (key, value, timeout) {
        return add(key, value, timeout)
    },
    del: async function (key) {
        return del(key)
    },
    settings: async function () {
        return settings()
    }
};



/*
if  (env != "dev") {
    var memcached = new Memcached(config.memcachedUrls);
    var get = promisify(memcached.get).bind(memcached);
    var set = promisify(memcached.set).bind(memcached);
    var add = promisify(memcached.add).bind(memcached);
    var del = promisify(memcached.del).bind(memcached);
    var incr = promisify(memcached.incr).bind(memcached);

    exports.cache = {
        get: async function (key) {
            return get(env + key)
        },
        set: async function (key, value, timeout) {
            return set(env + key, value, timeout)
        },
        add: async function (key, value, timeout) {
            return add(env + key, value, timeout)
        },
        del: async function (key) {
            return del(env + key)
        },
        incr: async function (key, amount) {
            return incr(env + key, amount)
        }
    };

} else {
    exports.cache = {
        get: function () { return null; },
        set: function () { return null; },
        add: function () { return null; },
        del: function () { return null; },
        incr: function () { return null; }
    };
}


*/
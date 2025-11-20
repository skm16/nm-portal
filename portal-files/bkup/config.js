var env = process.env.env || "dev";

var config = {}

config.dbUser = "nmdadbusr";

config.dbUrl = "nmda1.crrpwh5yscfu.us-west-2.rds.amazonaws.com";
//config.dbPassword = "iehqVlVsx1XqOG87XwoG";
config.dbPassword = "rtsDB20!";
config.domain="nmda.rtsclients.com";
config.adminToken = "2aa25436-5793-4a4a-b0a2-75b850d2502d";

if (env == "dev" || env == "qa") {
    config.dbName = "nmda_qa"
    config.appUrl = "http://qa.nmdadirectory.rtsclients.com"
} 

if (env == "uat") {

}

if (env == "prod") {
    config.dbName = "nmda"
    config.appUrl = "https://nmdadirectory.rtsclients.com";
}

exports.getConfig = function () {
    return config;
}

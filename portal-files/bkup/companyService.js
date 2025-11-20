var config = require("./config.js").getConfig()
var $ = {};
var conn = require("./connectionUtils.js").getConnection()
var connectionUtils = require("./connectionUtils");
var middleware = require("./middleware.js")
const { v4: uuidv4 } = require('uuid');
var async = require("async");
const { param } = require("./index.js");
var usersService = require("./usersService.js");
const { json } = require("body-parser");

exports.getCompanies = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT c.*, GROUP_CONCAT(gt.GroupTypeId) AS grps, GROUP_CONCAT(gt.GroupType) AS grpNames FROM company c LEFT JOIN company_groups cg ON c.CompanyId = cg.CompanyId LEFT JOIN group_type gt ON cg.GroupTypeId = gt.GroupTypeId GROUP BY c.CompanyId ORDER BY c.CompanyName";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getCompanies " + result)
    }
    catch (ex) {
        middleware.log("getCompanies error " + ex)
        return callback("getCompanies error: " + ex);
    }

    callback(null, {
        message: "getCompanies done",
        data: result
    })
};

exports.getCompanyById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT c.*, GROUP_CONCAT(gt.GroupTypeId) AS grps FROM company c LEFT JOIN company_groups cg ON c.CompanyId = cg.CompanyId LEFT JOIN group_type gt ON cg.GroupTypeId = gt.GroupTypeId WHERE c.CompanyId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getCompanyUsers = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM user";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.addCompany = async function (params, callback) {
    var companyId = uuidv4()
    var companyGroupId = "";
    
    var sql = "INSERT INTO company (CompanyId, CompanyName, FirstName, LastName, Address1, Address2, City, State, Zip, Phone, Email, Website, Logo, GroupTypeOther, ProductType) values (?)"; 
    var vals = [companyId, params.CompanyName, params.FirstName, params.LastName, params.Address1, params.Address2, params.City, params.State, params.Zip, params.Phone, params.Email, params.Website, params.Logo, params.GroupTypeOther, params.ProductType]
    conn.query(sql, [vals], function (err, data) {
        if (err) {
            callback(err)
        } else {
            if(params.Groups) {
                vals = [];
                sql = "INSERT INTO company_groups (CompanyGroupsId, CompanyId, GroupTypeId) values ?"; 
                    
                async.each(params.Groups.split(","), function(groupId, callbackG)
                {
                    companyGroupId = uuidv4()

                    vals.push([companyGroupId, companyId, groupId.trim()]);
                    callbackG();
                },
                function(err)
                {
                    conn.query(sql, [vals], function (err, data) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null, data)
                        }
                    });
                });
            } else {
                callback(null);
            }

            /*
            sql = "INSERT INTO company_groups (CompanyGroupdId, CompanyId, GroupTypeId) values (?)"; 
            vals = [companyGroupId, companyId, params.GroupTypeId]
            conn.query(sql, [vals], function (err, data) {
                if (err) {
                    callback(err)
                } else {
                    callback(null, data)
                }
            });
            */
        }
    });
};

exports.updateCompany = function (params, callback) {
    var sql = "UPDATE company SET CompanyName = '" + params.CompanyName + "', FirstName = '" + params.FirstName + "', LastName = '" + params.LastName + "', Address1 = '" + params.Address1 + "', Address2 = '" + params.Address2 + "', City = '" + params.City + "', State = '" + params.State + "', Zip = '" + params.Zip + "', Phone = '" + params.Phone + "', Email = '" + params.Email + "', Website = '" + params.Website + "', Logo = '" + params.Logo + "', GroupTypeOther = '" + params.GroupTypeOther + "', ProductType = '" + params.ProductType + "' WHERE CompanyId = '" + params.CompanyId + "'";
    conn.query(sql, function (err, data) {
        if (err) {
            callback(err)
        } else {
            if(params.Groups) {
                //Delete the current groups so we can re-add the
                sql = "DELETE FROM company_groups WHERE CompanyId = '" + params.CompanyId + "'";
                conn.query(sql, function (err, data) {
                    if (err) {
                        callback(err)
                    } else {
                        //Re-add the groups
                        vals = [];
                        sql = "INSERT INTO company_groups (CompanyGroupsId, CompanyId, GroupTypeId) values ?"; 
                            
                        async.each(params.Groups.split(","), function(groupId, callbackG)
                        {
                            companyGroupId = uuidv4()

                            vals.push([companyGroupId, params.CompanyId, groupId.trim()]);
                            callbackG();
                        },
                        function(err)
                        {
                            conn.query(sql, [vals], function (err, data) {
                                if (err) {
                                    callback(err)
                                } else {
                                    callback(null, data)
                                }
                            });
                        });
                    }
                });                
            } else {
                callback(null);
            }
        }
    })
}

exports.deleteCompany = function (params, callback) {
    var vals = params.CompanyId;

    //Delete company groups first
    var sql = "DELETE FROM company_groups WHERE CompanyId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            //Get company users and delete from the account manager
            var sql = "SELECT AccountManagerGUID, UserId FROM user WHERE CompanyId = ?";
            conn.query(sql, vals, function (err, data) {
                if (err) {
                    callback(err)
                } else {
                    //Loop through the users and delete them from the account manager/users table
                    async.each(data, function(userRec, callbackG)
                    {
                        var user = {};    
                        user["UserGuid"] = userRec.AccountManagerGUID;    
                        user["UserId"] = userRec.UserId;

                        usersService.deleteUser(user, callbackG);
                        callbackG();
                    },
                    function(err)
                    {
                        //Delete company final
                        sql = "DELETE FROM company WHERE CompanyId = ?";
                        conn.query(sql, vals, function (err, data) {
                            if (err) {
                                callback(err)
                            } else {
                                callback(null, data)
                            }
                        })
                    });                    
                }
            })
        }
    })   
}

exports.addBusiness = async function (params, callback) {
    var businessId = uuidv4();
    var sql = "", vals = [];
    var additionalAddresses = params.AdditionalAddresses;

    delete params.AdditionalAddresses;
    
    params["BusinessId"] = businessId;

    sql += "INSERT INTO business SET ?; ";
    vals.push(params)

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            insertAddresses(businessId, additionalAddresses);

            var user = {};
            user["Email"] = params.ContactEmail;
            user["FirstName"] = params.ContactFirstName;
            user["LastName"] = params.ContactLastName;
            user["BusinessId"] = params.BusinessId;

            usersService.addUser(user, callback);

            console.log("success");
            //callback(null, data)
        }
    });
};

exports.updateBusiness =  function (params, callback) {
    var sql = "", vals = [];
    var additionalAddresses = params.AdditionalAddresses;

    delete params.AdditionalAddresses;

    sql += "UPDATE business SET ? WHERE BusinessId = ?; ";
    vals.push(params)
    vals.push(params.BusinessId);

   conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            insertAddresses(params.BusinessId, additionalAddresses);

            console.log("success");
            callback(null, data)
        }
    });

    /*var data = await conn.queryP(sql,vals)
    callback(null, data)*/
}

exports.deleteBusiness = function (params, callback) {
    var vals = params.BusinessId;

    //Get company users and delete from the account manager
    var sql = "SELECT AccountManagerGUID, UserId FROM user WHERE CompanyId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            //Loop through the users and delete them from the account manager/users table
            async.each(data, function(userRec, callbackG)
            {
                var user = {};    
                user["UserGuid"] = userRec.AccountManagerGUID;    
                user["UserId"] = userRec.UserId;

                usersService.deleteUser(user, callbackG);
                callbackG();
            },
            function(err)
            {
                //Delete company final
                sql = "UPDATE business SET Deleted = 1 WHERE BusinessId = ?; ";
                
                conn.query(sql, vals, function (err, data) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, data)
                    }
                })
            });                    
        }
    })
}

exports.deleteBusinessAddress =  function (params, callback) {
    var sql = "", vals = [];
    
    sql += "Delete FROM business_address WHERE BusinessAddressId = ?; ";
    vals.push(params.BusinessAddressId);

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            callback(null, data)
        }
    });
}

exports.updateBusinessCategory =  function (params, callback) {
    var sql = "", vals = [];
    var id = params.BusinessAddressId;

    delete params.authTokenGUID;
    delete params.BusinessAddressId;

    sql += "UPDATE business_address SET ? WHERE BusinessAddressId = ?; ";
    vals.push(params)
    vals.push(id);

   conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });

    /*var data = await conn.queryP(sql,vals)
    callback(null, data)*/
}

exports.getBusinesses = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM business WHERE Approved = 1 and Deleted = 0 ORDER BY BusinessName, CreateDt";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getBusinesses " + result)
    }
    catch (ex) {
        middleware.log("getBusinesses error " + ex)
        return callback("getBusinesses error: " + ex);
    }

    callback(null, {
        message: "getBusinesses done",
        data: result
    })
};

exports.getBusinessApprovals = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM business WHERE Approved = 0 and Deleted = 0";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getBusinessApprovals " + result)
    }
    catch (ex) {
        middleware.log("getBusinessApprovals error " + ex)
        return callback("getBusinessApprovals error: " + ex);
    }

    callback(null, {
        message: "getBusinessApprovals done",
        data: result
    })
};

exports.getBusinessById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM business WHERE BusinessId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getBusinessUsers = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM user";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getBusinessAddresses = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM business_address WHERE BusinessId = ? ORDER BY AddressName";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getGroupTypes = async function (params, callback) {
    var sql = "SELECT * FROM group_type";
    conn.query(sql, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getCompanyGroups = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM company_groups WHERE CompanyId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getReimburseLead = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_lead WHERE Approved = 1";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseLead " + result)
    }
    catch (ex) {
        middleware.log("getReimburseLead error " + ex)
        return callback("getReimburseLead error: " + ex);
    }

    callback(null, {
        message: "getReimburseLead done",
        data: result
    })
};

exports.getReimburseLeadApprovals = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_lead WHERE Approved = 0";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseLeadApprovals " + result)
    }
    catch (ex) {
        middleware.log("getReimburseLeadApprovals error " + ex)
        return callback("getReimburseLeadApprovals error: " + ex);
    }

    callback(null, {
        message: "getReimburseLeadApprovals done",
        data: result
    })
};

exports.getReimburseLeadById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_lead WHERE LeadId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getReimburseLeadByBusinessId = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_lead WHERE BusinessId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.addReimburseLead = async function (params, callback) {
    var leadId = uuidv4()
    var sql = "", vals = [];
    
    params["LeadId"] = leadId;

    sql += "INSERT INTO csr_lead SET ?; ";
    vals.push(params)

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
};

exports.updateReimburseLead = function (params, callback) {
    var sql = "", vals = [];

    sql += "UPDATE csr_lead SET ? WHERE LeadId = ?; ";
    vals.push(params)
    vals.push(params.LeadId);

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
}

exports.getReimburseAdvertising = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_advertising WHERE Approved = 1";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseAdvertising " + result)
    }
    catch (ex) {
        middleware.log("getReimburseAdvertising error " + ex)
        return callback("getReimburseAdvertising error: " + ex);
    }

    callback(null, {
        message: "getReimburseAdvertising done",
        data: result
    })
};

exports.getReimburseAdvertisingApprovals = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_advertising WHERE Approved = 0";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseAdvertisingApprovals " + result)
    }
    catch (ex) {
        middleware.log("getReimburseAdvertisingApprovals error " + ex)
        return callback("getReimburseAdvertisingApprovals error: " + ex);
    }

    callback(null, {
        message: "getReimburseAdvertisingApprovals done",
        data: result
    })
};

exports.getReimburseAdvertisingById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_advertising WHERE AdvertisingId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getReimburseAdvertisingByBusinessId = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_advertising WHERE BusinessId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.addReimburseAdvertising = async function (params, callback) {
    var advertisingId = uuidv4()
    var sql = "", vals = [];
    
    params["AdvertisingId"] = advertisingId;

    sql += "INSERT INTO csr_advertising SET ?; ";
    vals.push(params)

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
};

exports.updateReimburseAdvertising = function (params, callback) {
    var sql = "", vals = [];

    sql += "UPDATE csr_advertising SET ? WHERE AdvertisingId = ?; ";
    vals.push(params)
    vals.push(params.AdvertisingId);

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
}

exports.getReimburseLabels = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_labels WHERE Approved = 1";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseLabels " + result)
    }
    catch (ex) {
        middleware.log("getReimburseLabels error " + ex)
        return callback("getReimburseLabels error: " + ex);
    }

    callback(null, {
        message: "getReimburseLabels done",
        data: result
    })
};

exports.getReimburseLabelsApprovals = async function (params, callback) {

    try {
        var conn = connectionUtils.getConnection();
        var sql = "SELECT * FROM csr_labels WHERE Approved = 0";
        var result = await conn.queryP(sql) // gets result
        
        middleware.log("getReimburseLabelsApprovals " + result)
    }
    catch (ex) {
        middleware.log("getReimburseLabelsApprovals error " + ex)
        return callback("getReimburseLabelsApprovals error: " + ex);
    }

    callback(null, {
        message: "getReimburseLabelsApprovals done",
        data: result
    })
};

exports.getReimburseLabelsById = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_labels WHERE LabelsId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.getReimburseLabelsByBusinessId = async function (params, callback) {
    var vals = params.id

    var sql = "SELECT * FROM csr_labels WHERE BusinessId = ?";
    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err)
        } else {
            callback(null, data)
        }
    })
};

exports.addReimburseLabels = async function (params, callback) {
    var labelsId = uuidv4()
    var sql = "", vals = [];
    
    params["LabelsId"] = labelsId;

    sql += "INSERT INTO csr_labels SET ?; ";
    vals.push(params)

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
};

exports.updateReimburseLabels = function (params, callback) {
    var sql = "", vals = [];

    sql += "UPDATE csr_labels SET ? WHERE LabelsId = ?; ";
    vals.push(params)
    vals.push(params.LabelsId);

    conn.query(sql, vals, function (err, data) {
        if (err) {
            callback(err);
        } else {
            console.log("success");
            callback(null, data)
        }
    });
}

function insertAddresses(businessId, addresses) {
    var sql = "", businessAddressId = "", vals = [];
    
    if(addresses) {
        addresses.forEach(addr => {
            businessAddressId = uuidv4()

            addr["BusinessId"] = businessId;
            addr["BusinessAddressId"] = businessAddressId;

            sql += "INSERT INTO business_address SET ?; ";
            vals.push(addr)
        });    

        conn.query(sql, vals, function (err, data) {
            console.log(err);
        });
    }
}
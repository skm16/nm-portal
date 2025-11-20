const setRateLimit = require("express-rate-limit");

// Rate limit middleware
const ratelimit = setRateLimit({
  windowMs: 60 * 1000,
  max: 1000,
  message: "You have exceeded your 1000 requests per minute limit.",
  headers: true,
  validate: {trustProxy: false}
});

module.exports = ratelimit;
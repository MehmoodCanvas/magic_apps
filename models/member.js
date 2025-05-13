const db = require('../db');

exports.findByEmail = (email, callback) => {
    db.query('SELECT * FROM members WHERE members_email = ?', [email], callback);
};

exports.createMember = (member, callback) => {
    db.query('INSERT INTO members SET ?', member, (err, result) => {
        if (err) {
            const dbError = {
                code: err.code,
                errno: err.errno,
                sqlState: err.sqlState,
                sqlMessage: err.sqlMessage,
                fatal: err.fatal
            };
            return callback(dbError, null);
        }
        callback(null, result);
    });
};


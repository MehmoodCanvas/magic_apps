const db = require('../db');

exports.findByEmail = (email,callback)=>{
    db.query ("SELECT * From members where members_email = ?",[email],callback);
}


exports.createMember = (member,callback)=>{
    db.query('INSERT INTO members Set ?',member,callback);
}
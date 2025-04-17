const bcrypt = require('bcrypt');
const Member = require('../models/member');
const jwt = require('jsonwebtoken');
require('dotenv').config();


exports.showLogin = (req,res)=> res.render('login');
exports.showSignup = (req,res)=> res.render('signup');

exports.login=(req,res)=>{
    const {members_email,members_password}= req.body;
    Member.findByEmail(members_email,(err,results)=>{
        if( results.length > 0){
            const member= results[0];
         bcrypt.compare(members_password, member.members_password, (err, match) => {
                if(match){

                    const token = jwt.sign(
                        {
                            id: member.members_id,
                            email: member.members_email,
                            name: `${member.members_first_name} ${member.members_last_name}`
                        },
                        process.env.JWT_SECRET,
                        { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
                    );
                    res.send({ status: 200 ,token:token, data:member });
                }else{
                    res.send(err);
                }
            })
        }else{
            res.send(err);

        }

    })
}
exports.register = (req, res) => {
    const { members_first_name,members_last_name, members_email , members_password } = req.body;
    bcrypt.hash(members_password, 10, (err, hash) => {
      const newUser = {members_first_name,members_last_name, members_email, members_password: hash };
    Member.createMember(newUser, (err, result) => {
      if (err) {
        res.send({ error: 'Error creating member', details: err });
      } else {
        res.send({ message: 'Member created successfully', data: result });
      }
    });
    });
};
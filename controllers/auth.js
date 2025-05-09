const bcrypt = require('bcrypt');
const Member = require('../models/member');
const jwt = require('jsonwebtoken');
const winston = require('winston');
require('dotenv').config();

// Configure winston logger
const logger = winston.createLogger({
    level: 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.printf(({ timestamp, level, message }) => `${timestamp} [${level.toUpperCase()}]: ${message}`)
    ),
    transports: [
        new winston.transports.File({ filename: 'events.log' })
    ]
});

exports.showLogin = (req, res) => {
    logger.info('Rendering login page');
    res.render('login');
};

exports.showSignup = (req, res) => {
    logger.info('Rendering signup page');
    res.render('signup');
};

exports.login = (req, res) => {
    const { members_email, members_password } = req.body;
    logger.info(`Login attempt for email: ${members_email}`);

    Member.findByEmail(members_email, (err, results) => {
        if (err) {
            logger.error(`Error finding member by email: ${err}`);
            return res.status(500).send(err);
        }

        if (results.length > 0) {
            const member = results[0];
            logger.info(`Member found: ${member.members_email}`);

            bcrypt.compare(members_password, member.members_password, (err, match) => {
                if (err) {
                    logger.error(`Error comparing passwords: ${err}`);
                    return res.status(500).send(err);
                }

                if (match) {
                    logger.info('Password match successful');
                    const token = jwt.sign(
                        {
                            id: member.members_id,
                            email: member.members_email,
                            name: `${member.members_first_name} ${member.members_last_name}`
                        },
                        process.env.JWT_SECRET,
                        { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
                    );
                    logger.info('JWT token generated');
                    res.send({ status: 200, token: token, data: member });
                } else {
                    logger.info('Password mismatch');
                    res.status(401).send({ error: 'Invalid credentials' });
                }
            });
        } else {
            logger.info('No member found with the provided email');
            res.status(404).send({ error: 'Member not found' });
        }
    });
};

exports.register = (req, res) => {
    const { members_first_name, members_last_name, members_email, members_password } = req.body;
    logger.info(`Registration attempt for email: ${members_email}`);

    bcrypt.hash(members_password, 10, (err, hash) => {
        if (err) {
            logger.error(`Error hashing in  password: ${err}`);
            return res.status(500).send({ error: 'Error hashing password', data: err });
        }

        logger.info('Password hashed successfully');
        const newUser = {
            members_first_name,
            members_last_name,
            members_email,
            members_password: hash
        };

        Member.createMember(newUser, (err, result) => {
            if (err) {
                logger.error(`Error creating member: ${err}`);
                return res.status(500).send({ error: 'Error creating member', data: err });
            }

            logger.info(`Member created successfully: ${members_email}`);
            res.status(201).send({ status: 'success', data: result });
        });
    });
};
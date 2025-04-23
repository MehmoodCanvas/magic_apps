const express = require('express');
const router = express.Router();
const auth = require('../controllers/auth');
const authMiddleware = require('../middleware/protected');

router.post('/login', auth.login);
router.post('/register', auth.register);
router.get('/dashboard', authMiddleware, (req, res) => {
    res.json({ message: `Welcome ${req.user.name}!`, user: req.user });
});


module.exports = router;

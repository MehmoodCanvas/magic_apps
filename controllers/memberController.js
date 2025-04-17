const User = require('../models/member');

exports.dashboard = (req, res) => {
  if (!req.session.user) return res.redirect('/login');
  User.getAllUsers((err, users) => {
    res.render('dashboard', { user: req.session.user, users });
  });
};

exports.deleteUser = (req, res) => {
  User.deleteUser(req.params.id, () => res.redirect('/dashboard'));
};

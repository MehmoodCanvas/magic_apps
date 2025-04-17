const express = require('express');
const session = require('express-session');
const bodyParser = require('body-parser');
const routes = require('./routes/routes');
const app = express();

app.use(express.static('public'));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(session({
    secret: 'sadasoduhdoahdh',
    resave: false,
    saveUninitialized: true,
  }));
app.use('/', routes);


port = 3000
app.listen(port, () => {
    console.log(`Server is running on http://localhost:${port}`);
  });
  
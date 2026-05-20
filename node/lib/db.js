const mysql = require('mysql2/promise');
require('dotenv').config();

function pool() {
  return mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    database: process.env.DB_NAME || 'agente_imoveis',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    waitForConnections: true,
    connectionLimit: 5,
    charset: 'utf8mb4'
  });
}

module.exports = { pool };

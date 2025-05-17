-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS farmer_db;
USE farmer_db;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS entries;
DROP TABLE IF EXISTS accounts;

-- Create accounts table
CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    last_transaction DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create entries table
CREATE TABLE entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    total_cost DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('debit', 'credit') DEFAULT 'debit',
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster searches
CREATE INDEX idx_entries_name ON entries(name);
CREATE INDEX idx_accounts_name ON accounts(name);

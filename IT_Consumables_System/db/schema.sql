-- ============================================================
-- IT Consumables Inventory and Stock Tracking System
-- SQLite Relational Schema
-- Eastlands College of Technology - KNEC DICT Module III Capstone
-- Author: Cornelius Kithuka Munyao
-- ============================================================

PRAGMA foreign_keys = ON;

-- 1. Item Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL,
    description   TEXT
);

-- 2. Consumables Inventory Items Table
CREATE TABLE IF NOT EXISTS items (
    item_id            INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name          TEXT NOT NULL,
    category_id        INTEGER,
    quantity_in_stock  INTEGER NOT NULL DEFAULT 0,
    minimum_threshold  INTEGER NOT NULL DEFAULT 5,
    unit_measure       TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- 3. Staff Members / Recipients Directory Table
CREATE TABLE IF NOT EXISTS staff_members (
    staff_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name    TEXT NOT NULL,
    department   TEXT NOT NULL,
    email        TEXT UNIQUE NOT NULL,
    phone_number TEXT NOT NULL
);

-- 4. Stock Transactions (Checkouts, Returns, Restocks) Table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id          INTEGER NOT NULL,
    staff_id         INTEGER NOT NULL,
    quantity_issued  INTEGER NOT NULL,
    transaction_type TEXT CHECK(transaction_type IN ('ISSUE', 'RETURN', 'RESTOCK')) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    issued_by        TEXT NOT NULL,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff_members(staff_id) ON DELETE CASCADE
);

-- 5. System Users Table (Authentication)
CREATE TABLE IF NOT EXISTS users (
    user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name     TEXT NOT NULL,
    role          TEXT DEFAULT 'Storekeeper'
);

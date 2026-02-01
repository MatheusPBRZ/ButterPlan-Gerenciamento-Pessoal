CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT CHECK(type IN ('income', 'expense')) NOT NULL, -- Entrada ou Saída
    description TEXT,
    amount REAL NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    address TEXT,
    notes TEXT
);

CREATE TABLE setlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    songs TEXT
);

CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER,
    event_date TEXT,
    location TEXT,
    description TEXT,
    status TEXT DEFAULT 'inquiry',
    FOREIGN KEY (contact_id) REFERENCES contacts(id)
);

CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER,
    amount_net REAL,
    tax_rate REAL,
    amount_gross REAL,
    payment_method TEXT,
    date TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE,
    value TEXT
);

CREATE TABLE musicians (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    instrument TEXT
);

CREATE TABLE booking_musicians (
    booking_id INTEGER,
    musician_id INTEGER,
    PRIMARY KEY (booking_id, musician_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (musician_id) REFERENCES musicians(id) ON DELETE CASCADE
);

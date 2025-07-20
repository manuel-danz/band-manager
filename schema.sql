CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(255),
    address TEXT,
    notes TEXT
);

CREATE TABLE setlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    songs TEXT
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT,
    event_date DATE,
    location TEXT,
    description TEXT,
    status ENUM('inquiry','confirmed','completed','cancelled') DEFAULT 'inquiry',
    FOREIGN KEY (contact_id) REFERENCES contacts(id)
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount_net DECIMAL(10,2),
    tax_rate DECIMAL(5,2),
    amount_gross DECIMAL(10,2),
    payment_method ENUM('cash','transfer'),
    date DATE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE,
    value TEXT
);

CREATE TABLE musicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    instrument VARCHAR(255)
);

CREATE TABLE booking_musicians (
    booking_id INT,
    musician_id INT,
    PRIMARY KEY (booking_id, musician_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (musician_id) REFERENCES musicians(id) ON DELETE CASCADE
);

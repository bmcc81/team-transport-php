CREATE TABLE IF NOT EXISTS vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) NOT NULL UNIQUE,
    make VARCHAR(50) DEFAULT NULL,
    model VARCHAR(50) DEFAULT NULL,
    year YEAR DEFAULT NULL,
    license_plate VARCHAR(20) NOT NULL,
    capacity INT DEFAULT NULL,
    status ENUM('available', 'in_service', 'maintenance', 'retired') DEFAULT 'available',
    assigned_driver_id INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
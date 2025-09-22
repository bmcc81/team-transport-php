CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customerCompanyName VARCHAR(255) NOT NULL,
    customerInternalHandlerName VARCHAR(255) NOT NULL,
    customerContactFirstName VARCHAR(100) NOT NULL,
    customerContactLastName VARCHAR(100) NOT NULL,
    customerEmail VARCHAR(255) NOT NULL UNIQUE,
    customerContactAddress VARCHAR(255) NOT NULL,
    customerContactCity VARCHAR(100) NOT NULL,
    customerContactStateOrProvince VARCHAR(100) NOT NULL,
    customerContactZipOrPostalCode VARCHAR(20) NOT NULL,
    customerContactCountry VARCHAR(100) NOT NULL,
    customerPhone VARCHAR(50) NOT NULL,
    customerFax VARCHAR(50) DEFAULT NULL,
    customerWebsite VARCHAR(255) DEFAULT NULL,
    created_by_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_customer_user FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT -- deletion is controlled by trigger
);




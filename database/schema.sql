-- =====================================================
-- DATABASE: somali_pos_db
-- =====================================================

-- 1. USERS TABLE (Admin iyo Cashier)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    role ENUM('admin', 'cashier') NOT NULL,
    permissions JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- 2. CUSTOMERS TABLE
CREATE TABLE customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    address TEXT,
    id_number VARCHAR(50),
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    current_balance DECIMAL(12,2) DEFAULT 0.00,
    total_purchases DECIMAL(12,2) DEFAULT 0.00,
    last_purchase_date DATE,
    status ENUM('active', 'blocked', 'inactive') DEFAULT 'active',
    registered_by INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (registered_by) REFERENCES users(user_id)
);

-- 3. SUPPLIERS TABLE
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    tax_number VARCHAR(50),
    payment_terms VARCHAR(50),
    contract_start DATE,
    contract_end DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT
);

-- 4. PRODUCTS TABLE
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(50) UNIQUE,
    barcode VARCHAR(50) UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    supplier_id INT,
    cost_price DECIMAL(12,2) NOT NULL,
    selling_price DECIMAL(12,2) NOT NULL,
    wholesale_price DECIMAL(12,2),
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 5,
    maximum_stock INT DEFAULT 100,
    unit VARCHAR(20) DEFAULT 'piece',
    location VARCHAR(50),
    expiry_date DATE,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 5. PURCHASES TABLE (Supplier transactions)
CREATE TABLE purchases (
    purchase_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    expected_delivery DATE,
    actual_delivery DATE,
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0.00,
    balance_due DECIMAL(12,2) DEFAULT 0.00,
    payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 6. PURCHASE ITEMS TABLE
CREATE TABLE purchase_items (
    purchase_item_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    selling_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    received_quantity INT DEFAULT 0,
    status ENUM('pending', 'received', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 7. SALES TABLE
CREATE TABLE sales (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    cashier_id INT NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0.00,
    balance_due DECIMAL(12,2) DEFAULT 0.00,
    payment_method ENUM('cash', 'evc_plus', 'shilin_somali', 'mixed') NOT NULL,
    payment_status ENUM('paid', 'partial', 'credit') DEFAULT 'paid',
    transaction_reference VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

-- 8. SALE ITEMS TABLE
CREATE TABLE sale_items (
    sale_item_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 9. DEBTS TABLE (Customer debts tracking)
CREATE TABLE debts (
    debt_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    sale_id INT NOT NULL,
    original_amount DECIMAL(12,2) NOT NULL,
    remaining_amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'written_off') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id)
);

-- 10. DEBT PAYMENTS TABLE
CREATE TABLE debt_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    debt_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'evc_plus', 'shilin_somali') NOT NULL,
    transaction_reference VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (debt_id) REFERENCES debts(debt_id),
    FOREIGN KEY (received_by) REFERENCES users(user_id)
);

-- 11. EXPENSES TABLE
CREATE TABLE expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    expense_number VARCHAR(50) UNIQUE NOT NULL,
    expense_category ENUM('rent', 'electricity', 'water', 'tax', 'salary', 
                          'maintenance', 'transport', 'marketing', 'office', 'other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank', 'evc_plus') DEFAULT 'cash',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    document_path VARCHAR(255),
    requested_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- 12. EXPENSE DOCUMENTS TABLE
CREATE TABLE expense_documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    expense_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (expense_id) REFERENCES expenses(expense_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- 13. CASHIER PERMISSIONS TABLE
CREATE TABLE cashier_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    cashier_id INT UNIQUE NOT NULL,
    can_edit_customer BOOLEAN DEFAULT true,
    can_add_expense BOOLEAN DEFAULT true,
    can_edit_own_expense BOOLEAN DEFAULT true,
    max_expense_amount DECIMAL(10,2) DEFAULT 100.00,
    needs_approval BOOLEAN DEFAULT true,
    can_view_reports BOOLEAN DEFAULT false,
    can_process_refund BOOLEAN DEFAULT false,
    max_discount_percent DECIMAL(5,2) DEFAULT 5.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id),
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

-- 14. FINANCIAL TRANSACTIONS TABLE (Complete ledger)
CREATE TABLE financial_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_type ENUM('sale', 'purchase', 'expense', 'debt_payment', 
                          'supplier_payment', 'salary', 'other') NOT NULL,
    reference_id INT NOT NULL,
    reference_table VARCHAR(50) NOT NULL,
    debit DECIMAL(12,2) DEFAULT 0.00,
    credit DECIMAL(12,2) DEFAULT 0.00,
    balance DECIMAL(12,2) DEFAULT 0.00,
    description TEXT,
    created_by INT,
    notes TEXT,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 15. DAILY SUMMARIES TABLE
CREATE TABLE daily_summaries (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    summary_date DATE UNIQUE NOT NULL,
    total_sales DECIMAL(12,2) DEFAULT 0.00,
    total_cash_sales DECIMAL(12,2) DEFAULT 0.00,
    total_evc_sales DECIMAL(12,2) DEFAULT 0.00,
    total_shilin_sales DECIMAL(12,2) DEFAULT 0.00,
    total_expenses DECIMAL(12,2) DEFAULT 0.00,
    total_debt_collected DECIMAL(12,2) DEFAULT 0.00,
    total_debt_created DECIMAL(12,2) DEFAULT 0.00,
    total_profit DECIMAL(12,2) DEFAULT 0.00,
    transaction_count INT DEFAULT 0,
    customer_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 16. SYSTEM SETTINGS TABLE
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

-- 17. AUDIT LOG TABLE (Track all changes)
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 18. PAYMENT GATEWAY LOGS
CREATE TABLE payment_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    gateway ENUM('evc_plus', 'shilin_somali') NOT NULL,
    transaction_type VARCHAR(50),
    request_data JSON,
    response_data JSON,
    status VARCHAR(20),
    reference VARCHAR(100),
    amount DECIMAL(12,2),
    phone_number VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 19. NOTIFICATIONS TABLE
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT false,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);Sign in to enable AI completions, or disable inline completions in Settings (DBCode > AI).

-- 20. BACKUP LOGS TABLE
CREATE TABLE backup_logs (
    backup_id INT PRIMARY KEY AUTO_INCREMENT,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    backup_type ENUM('full', 'incremental') NOT NULL,
    file_name VARCHAR(255),
    file_size BIGINT,
    status ENUM('success', 'failed') DEFAULT 'success',
    created_by INT,
    notes TEXT,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Insert default admin (Password: password)
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('business_name', 'So\'Mali Store', 'text', 'Magaca dukaanka'),
('business_phone', '+25260000000', 'text', 'Telefonka dukaanka'),
('business_address', 'Mogadishu, Somalia', 'text', 'Cinwaanka'),
('tax_rate', '0.00', 'number', 'Canshuurta (%)'),
('currency', 'USD', 'text', 'Lacagta la isticmaalo'),
('receipt_header', 'Mahadsanid!', 'text', 'Farriinta madaxa receipt-ka'),
('receipt_footer', 'Alxamdulilaah', 'text', 'Farriinta lugta receipt-ka'),
('low_stock_threshold', '10', 'number', 'Heerka kaydka hooseeya'),
('evc_merchant_id', 'HORMUUD123', 'text', 'EVC Plus Merchant ID'),
('shilin_merchant_id', 'SHILIN123', 'text', 'Shilin Somali Merchant ID'),
('backup_frequency', 'daily', 'text', 'Intee jeer la backup gareeyo'),
('session_timeout', '30', 'number', 'Session timeout (minutes)');

-- Insert default cashier permissions
INSERT INTO cashier_permissions (cashier_id, can_edit_customer, can_add_expense, 
                                 max_expense_amount, needs_approval, max_discount_percent)
SELECT user_id, true, true, 100.00, true, 5.00
FROM users WHERE role = 'cashier';

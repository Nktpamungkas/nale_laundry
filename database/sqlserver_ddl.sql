/*
  SQL Server DDL for Nale Laundry (Laravel 12)
  Jalankan script ini pada database SQL Server kosong.
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

CREATE TABLE migrations (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    migration NVARCHAR(255) NOT NULL,
    batch INT NOT NULL
);
GO

CREATE TABLE users (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    email NVARCHAR(255) NOT NULL,
    phone NVARCHAR(30) NULL,
    role NVARCHAR(20) NOT NULL CONSTRAINT DF_users_role DEFAULT ('staff'),
    is_active BIT NOT NULL CONSTRAINT DF_users_is_active DEFAULT (1),
    email_verified_at DATETIME2 NULL,
    password NVARCHAR(255) NOT NULL,
    remember_token NVARCHAR(100) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_users_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_users_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_users_email UNIQUE (email)
);
GO

CREATE INDEX IX_users_role ON users(role);
GO

CREATE TABLE password_reset_tokens (
    email NVARCHAR(255) NOT NULL PRIMARY KEY,
    token NVARCHAR(255) NOT NULL,
    created_at DATETIME2 NULL
);
GO

CREATE TABLE sessions (
    id NVARCHAR(255) NOT NULL PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address NVARCHAR(45) NULL,
    user_agent NVARCHAR(MAX) NULL,
    payload NVARCHAR(MAX) NOT NULL,
    last_activity INT NOT NULL,
    CONSTRAINT FK_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id)
);
GO

CREATE INDEX IX_sessions_user_id ON sessions(user_id);
CREATE INDEX IX_sessions_last_activity ON sessions(last_activity);
GO

CREATE TABLE cache (
    [key] NVARCHAR(255) NOT NULL PRIMARY KEY,
    [value] NVARCHAR(MAX) NOT NULL,
    expiration INT NOT NULL
);
GO

CREATE TABLE cache_locks (
    [key] NVARCHAR(255) NOT NULL PRIMARY KEY,
    owner NVARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);
GO

CREATE TABLE jobs (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    queue NVARCHAR(255) NOT NULL,
    payload NVARCHAR(MAX) NOT NULL,
    attempts TINYINT NOT NULL,
    reserved_at INT NULL,
    available_at INT NOT NULL,
    created_at INT NOT NULL
);
GO

CREATE INDEX IX_jobs_queue ON jobs(queue);
GO

CREATE TABLE job_batches (
    id NVARCHAR(255) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids NVARCHAR(MAX) NOT NULL,
    [options] NVARCHAR(MAX) NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL
);
GO

CREATE TABLE failed_jobs (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    uuid NVARCHAR(255) NOT NULL,
    connection NVARCHAR(MAX) NOT NULL,
    queue NVARCHAR(MAX) NOT NULL,
    payload NVARCHAR(MAX) NOT NULL,
    exception NVARCHAR(MAX) NOT NULL,
    failed_at DATETIME2 NOT NULL CONSTRAINT DF_failed_jobs_failed_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_failed_jobs_uuid UNIQUE (uuid)
);
GO

CREATE TABLE customers (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    code NVARCHAR(30) NOT NULL,
    name NVARCHAR(120) NOT NULL,
    phone NVARCHAR(30) NOT NULL,
    email NVARCHAR(255) NULL,
    address NVARCHAR(MAX) NULL,
    notes NVARCHAR(MAX) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_customers_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_customers_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_customers_code UNIQUE (code)
);
GO

CREATE TABLE inventory_items (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sku NVARCHAR(40) NOT NULL,
    name NVARCHAR(150) NOT NULL,
    category NVARCHAR(80) NULL,
    unit NVARCHAR(30) NOT NULL,
    minimum_stock DECIMAL(18,3) NOT NULL CONSTRAINT DF_inventory_items_minimum_stock DEFAULT (0),
    current_stock DECIMAL(18,3) NOT NULL CONSTRAINT DF_inventory_items_current_stock DEFAULT (0),
    average_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_inventory_items_average_cost DEFAULT (0),
    last_purchase_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_inventory_items_last_purchase_cost DEFAULT (0),
    is_active BIT NOT NULL CONSTRAINT DF_inventory_items_is_active DEFAULT (1),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_inventory_items_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_inventory_items_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_inventory_items_sku UNIQUE (sku)
);
GO

CREATE INDEX IX_inventory_items_is_active ON inventory_items(is_active);
GO

CREATE TABLE service_packages (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    code NVARCHAR(30) NOT NULL,
    name NVARCHAR(120) NOT NULL,
    pricing_unit NVARCHAR(20) NOT NULL CONSTRAINT DF_service_packages_pricing_unit DEFAULT ('kg'),
    sale_price DECIMAL(18,2) NOT NULL,
    labor_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_service_packages_labor_cost DEFAULT (0),
    overhead_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_service_packages_overhead_cost DEFAULT (0),
    estimated_hours DECIMAL(8,2) NOT NULL CONSTRAINT DF_service_packages_estimated_hours DEFAULT (0),
    description NVARCHAR(MAX) NULL,
    is_active BIT NOT NULL CONSTRAINT DF_service_packages_is_active DEFAULT (1),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_service_packages_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_service_packages_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_service_packages_code UNIQUE (code)
);
GO

CREATE INDEX IX_service_packages_is_active ON service_packages(is_active);
GO

CREATE TABLE service_package_materials (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    service_package_id BIGINT NOT NULL,
    inventory_item_id BIGINT NOT NULL,
    quantity_per_unit DECIMAL(18,4) NOT NULL,
    waste_percent DECIMAL(6,2) NOT NULL CONSTRAINT DF_service_package_materials_waste_percent DEFAULT (0),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_service_package_materials_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_service_package_materials_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_service_package_material UNIQUE (service_package_id, inventory_item_id),
    CONSTRAINT FK_spm_service_package FOREIGN KEY (service_package_id) REFERENCES service_packages(id) ON DELETE CASCADE,
    CONSTRAINT FK_spm_inventory_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
);
GO

CREATE TABLE laundry_orders (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    order_number NVARCHAR(40) NOT NULL,
    customer_id BIGINT NOT NULL,
    received_at DATETIME2 NOT NULL,
    due_at DATETIME2 NULL,
    status NVARCHAR(30) NOT NULL CONSTRAINT DF_laundry_orders_status DEFAULT ('received'),
    status_note NVARCHAR(MAX) NULL,
    subtotal DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_subtotal DEFAULT (0),
    discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_discount_amount DEFAULT (0),
    tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_tax_amount DEFAULT (0),
    grand_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_grand_total DEFAULT (0),
    hpp_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_hpp_total DEFAULT (0),
    payment_status NVARCHAR(20) NOT NULL CONSTRAINT DF_laundry_orders_payment_status DEFAULT ('unpaid'),
    paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_orders_paid_amount DEFAULT (0),
    pickup_at DATETIME2 NULL,
    created_by BIGINT NULL,
    updated_by BIGINT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_laundry_orders_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_laundry_orders_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_laundry_orders_order_number UNIQUE (order_number),
    CONSTRAINT FK_laundry_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT FK_laundry_orders_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT FK_laundry_orders_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
);
GO

CREATE INDEX IX_laundry_orders_status ON laundry_orders(status);
CREATE INDEX IX_laundry_orders_payment_status ON laundry_orders(payment_status);
GO

CREATE TABLE laundry_order_items (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    laundry_order_id BIGINT NOT NULL,
    service_package_id BIGINT NOT NULL,
    description NVARCHAR(MAX) NULL,
    quantity DECIMAL(18,3) NOT NULL CONSTRAINT DF_laundry_order_items_quantity DEFAULT (1),
    unit_price DECIMAL(18,2) NOT NULL,
    line_total DECIMAL(18,2) NOT NULL,
    material_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_order_items_material_cost DEFAULT (0),
    labor_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_order_items_labor_cost DEFAULT (0),
    overhead_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_order_items_overhead_cost DEFAULT (0),
    hpp_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_order_items_hpp_total DEFAULT (0),
    profit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_laundry_order_items_profit_amount DEFAULT (0),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_laundry_order_items_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_laundry_order_items_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT FK_laundry_order_items_order FOREIGN KEY (laundry_order_id) REFERENCES laundry_orders(id) ON DELETE CASCADE,
    CONSTRAINT FK_laundry_order_items_service_package FOREIGN KEY (service_package_id) REFERENCES service_packages(id)
);
GO

CREATE TABLE order_status_histories (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    laundry_order_id BIGINT NOT NULL,
    status NVARCHAR(30) NOT NULL,
    note NVARCHAR(MAX) NULL,
    changed_by BIGINT NULL,
    changed_at DATETIME2 NOT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_order_status_histories_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_order_status_histories_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT FK_order_status_histories_order FOREIGN KEY (laundry_order_id) REFERENCES laundry_orders(id) ON DELETE CASCADE,
    CONSTRAINT FK_order_status_histories_changed_by FOREIGN KEY (changed_by) REFERENCES users(id)
);
GO

CREATE INDEX IX_order_status_histories_status ON order_status_histories(status);
GO

CREATE TABLE payments (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    laundry_order_id BIGINT NOT NULL,
    payment_date DATETIME2 NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    method NVARCHAR(30) NOT NULL,
    reference_no NVARCHAR(60) NULL,
    note NVARCHAR(MAX) NULL,
    received_by BIGINT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_payments_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_payments_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT FK_payments_order FOREIGN KEY (laundry_order_id) REFERENCES laundry_orders(id) ON DELETE CASCADE,
    CONSTRAINT FK_payments_received_by FOREIGN KEY (received_by) REFERENCES users(id)
);
GO

CREATE TABLE stock_movements (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    inventory_item_id BIGINT NOT NULL,
    movement_date DATETIME2 NOT NULL,
    movement_type NVARCHAR(30) NOT NULL,
    funding_source NVARCHAR(30) NULL,
    reference_type NVARCHAR(60) NULL,
    reference_id BIGINT NULL,
    quantity_in DECIMAL(18,3) NOT NULL CONSTRAINT DF_stock_movements_quantity_in DEFAULT (0),
    quantity_out DECIMAL(18,3) NOT NULL CONSTRAINT DF_stock_movements_quantity_out DEFAULT (0),
    unit_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_stock_movements_unit_cost DEFAULT (0),
    total_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_stock_movements_total_cost DEFAULT (0),
    notes NVARCHAR(MAX) NULL,
    created_by BIGINT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_stock_movements_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_stock_movements_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT FK_stock_movements_inventory_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id),
    CONSTRAINT FK_stock_movements_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);
GO

CREATE INDEX IX_stock_movements_movement_type ON stock_movements(movement_type);
CREATE INDEX IX_stock_reference ON stock_movements(reference_type, reference_id);
GO

CREATE TABLE stock_opnames (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    opname_number NVARCHAR(40) NOT NULL,
    opname_date DATE NOT NULL,
    status NVARCHAR(20) NOT NULL CONSTRAINT DF_stock_opnames_status DEFAULT ('draft'),
    notes NVARCHAR(MAX) NULL,
    created_by BIGINT NULL,
    approved_by BIGINT NULL,
    posted_at DATETIME2 NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_stock_opnames_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_stock_opnames_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_stock_opnames_opname_number UNIQUE (opname_number),
    CONSTRAINT FK_stock_opnames_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT FK_stock_opnames_approved_by FOREIGN KEY (approved_by) REFERENCES users(id)
);
GO

CREATE INDEX IX_stock_opnames_status ON stock_opnames(status);
GO

CREATE TABLE stock_opname_items (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    stock_opname_id BIGINT NOT NULL,
    inventory_item_id BIGINT NOT NULL,
    system_stock DECIMAL(18,3) NOT NULL,
    actual_stock DECIMAL(18,3) NOT NULL,
    difference_stock DECIMAL(18,3) NOT NULL,
    adjustment_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_stock_opname_items_adjustment_cost DEFAULT (0),
    notes NVARCHAR(MAX) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_stock_opname_items_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_stock_opname_items_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT UQ_opname_item UNIQUE (stock_opname_id, inventory_item_id),
    CONSTRAINT FK_stock_opname_items_opname FOREIGN KEY (stock_opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE,
    CONSTRAINT FK_stock_opname_items_inventory_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
);
GO

CREATE TABLE whatsapp_notifications (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    laundry_order_id BIGINT NOT NULL,
    phone NVARCHAR(30) NOT NULL,
    event NVARCHAR(40) NOT NULL,
    message_text NVARCHAR(MAX) NOT NULL,
    request_payload NVARCHAR(MAX) NULL,
    response_status INT NULL,
    response_body NVARCHAR(MAX) NULL,
    is_success BIT NOT NULL CONSTRAINT DF_whatsapp_notifications_is_success DEFAULT (0),
    error_message NVARCHAR(MAX) NULL,
    sent_at DATETIME2 NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_whatsapp_notifications_created_at DEFAULT (SYSDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_whatsapp_notifications_updated_at DEFAULT (SYSDATETIME()),
    CONSTRAINT FK_whatsapp_notifications_order FOREIGN KEY (laundry_order_id) REFERENCES laundry_orders(id) ON DELETE CASCADE
);
GO

CREATE INDEX IX_whatsapp_notifications_is_success ON whatsapp_notifications(is_success);
CREATE INDEX IDX_wa_order_event ON whatsapp_notifications(laundry_order_id, event);
GO

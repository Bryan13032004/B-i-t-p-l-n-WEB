

CREATE OR ALTER TRIGGER trg_DeductStock
ON order_items
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- Kiểm tra tồn kho cho gọng (frame)
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN product_variants pv ON pv.variant_id = i.frame_variant_id
        WHERE i.frame_variant_id IS NOT NULL
          AND pv.stock_quantity < i.quantity
    )
    BEGIN
        RAISERROR(N'Gọng kính không đủ tồn kho.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- Trừ kho gọng
    UPDATE pv
    SET pv.stock_quantity = pv.stock_quantity - i.quantity
    FROM product_variants pv
    JOIN inserted i ON pv.variant_id = i.frame_variant_id
    WHERE i.frame_variant_id IS NOT NULL;

    -- Kiểm tra tồn kho cho tròng (lens)
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN product_variants pv ON pv.variant_id = i.lens_variant_id
        WHERE i.lens_variant_id IS NOT NULL
          AND pv.stock_quantity < i.quantity
    )
    BEGIN
        RAISERROR(N'Tròng kính không đủ tồn kho.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- Trừ kho tròng
    UPDATE pv
    SET pv.stock_quantity = pv.stock_quantity - i.quantity
    FROM product_variants pv
    JOIN inserted i ON pv.variant_id = i.lens_variant_id
    WHERE i.lens_variant_id IS NOT NULL;
END;
GO

CREATE OR ALTER TRIGGER trg_RestoreStock
ON orders
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Chỉ xử lý khi status chuyển sang 'Cancelled'
    -- deleted = trạng thái cũ, inserted = trạng thái mới
    IF NOT EXISTS (
        SELECT 1 FROM inserted i
        JOIN deleted d ON i.order_id = d.order_id
        WHERE i.status = 'Cancelled'
          AND d.status <> 'Cancelled'
    )
        RETURN;

    -- Hoàn kho gọng
    UPDATE pv
    SET pv.stock_quantity = pv.stock_quantity + oi.quantity
    FROM product_variants pv
    JOIN order_items oi ON pv.variant_id = oi.frame_variant_id
    JOIN inserted i     ON oi.order_id   = i.order_id
    JOIN deleted d      ON i.order_id    = d.order_id
    WHERE i.status = 'Cancelled'
      AND d.status <> 'Cancelled'
      AND oi.frame_variant_id IS NOT NULL;

    -- Hoàn kho tròng
    UPDATE pv
    SET pv.stock_quantity = pv.stock_quantity + oi.quantity
    FROM product_variants pv
    JOIN order_items oi ON pv.variant_id = oi.lens_variant_id
    JOIN inserted i     ON oi.order_id   = i.order_id
    JOIN deleted d      ON i.order_id    = d.order_id
    WHERE i.status = 'Cancelled'
      AND d.status <> 'Cancelled'
      AND oi.lens_variant_id IS NOT NULL;
END;
GO

CREATE OR ALTER TRIGGER trg_ValidatePrescription
ON order_items
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- Nếu có tròng kính nhưng không có đơn thuốc → block
    IF EXISTS (
        SELECT 1 FROM inserted
        WHERE lens_variant_id IS NOT NULL
          AND prescription_id IS NULL
    )
    BEGIN
        RAISERROR(N'Tròng kính cận/viễn bắt buộc phải kèm đơn thuốc (prescription_id).', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END;
GO

DECLARE @oid INT;

EXEC sp_CreateNormalOrder 
    @customer_id = 7, 
    @new_order_id = @oid OUTPUT;

EXEC sp_AddOrderItem 
    @order_id = @oid, 
    @frame_variant_id = 1, 
    @lens_variant_id = 10,
    @prescription_id = NULL,   -- thiếu đơn thuốc
    @quantity = 1;

	-- Test 2: Tồn kho tự trừ
SELECT variant_id, sku, stock_quantity 
FROM product_variants 
WHERE variant_id IN (1, 10);

DECLARE @oid INT;

EXEC sp_CreateNormalOrder 
    @customer_id = 7, 
    @new_order_id = @oid OUTPUT;

EXEC sp_AddOrderItem 
    @order_id = @oid, 
    @frame_variant_id = 1, 
    @lens_variant_id = 10,
    @prescription_id = 1,
    @quantity = 1;

SELECT variant_id, sku, stock_quantity 
FROM product_variants 
WHERE variant_id IN (1, 10);


-- Lấy order_id vừa tạo
DECLARE @oid INT;
SELECT @oid = MAX(order_id) FROM orders;

EXEC sp_UpdateOrderStatus 
    @order_id = @oid, 
    @new_status = 'Cancelled';

-- Kiểm tra tồn kho sau hủy → phải về lại 15 và 50
SELECT variant_id, sku, stock_quantity 
FROM product_variants 
WHERE variant_id IN (1, 10);

-- ============================================================
-- VIEW 1: TỔNG HỢP ĐƠN HÀNG
-- ============================================================
CREATE OR ALTER VIEW vw_OrderSummary AS
SELECT
    o.order_id,
    o.order_type,
    o.status                                    AS order_status,
    o.total_amount,
    o.created_at                                AS order_date,

    -- Thông tin khách hàng
    uc.user_id                                  AS customer_id,
    uc.username                                 AS customer_name,
    uc.phone                                    AS customer_phone,

    -- Nhân viên bán hàng
    us.username                                 AS sales_staff,

    -- Khuyến mãi
    pr.code                                     AS promo_code,
    pr.discount_percent,

    -- Thanh toán
    p.payment_method,
    p.payment_status,
    p.amount                                    AS paid_amount,

    -- Vận chuyển
    sh.shipping_company,
    sh.tracking_number,
    sh.delivery_status,
    sh.estimated_date,
    sh.delivery_address

FROM orders o
JOIN users uc               ON o.customer_id    = uc.user_id
LEFT JOIN users us          ON o.sales_staff_id = us.user_id
LEFT JOIN promotions pr     ON o.promo_id       = pr.promo_id
LEFT JOIN payments p        ON o.order_id       = p.order_id
LEFT JOIN shipments sh      ON o.order_id       = sh.order_id;
GO

-- ============================================================
-- VIEW 2: TIẾN ĐỘ SẢN XUẤT PRE-ORDER
-- ============================================================
CREATE OR ALTER VIEW vw_PreOrderProgress AS
SELECT
    pd.preorder_id,
    o.order_id,
    o.total_amount,

    -- Khách hàng
    uc.username                                 AS customer_name,
    uc.phone                                    AS customer_phone,

    -- Thông tin cọc
    pd.deposit_amount,
    pd.remaining_amount,
    pd.preorder_status,
    pd.expected_ready_date,
    pd.actual_ready_date,
    pd.special_request,

    -- Tiến độ sản xuất
    ps.stage_name,
    pl.status                                   AS stage_status,
    pl.started_at,
    pl.finished_at,
    pl.note                                     AS stage_note,

    -- Nhân viên phụ trách
    us.username                                 AS staff_name

FROM preorder_details pd
JOIN orders o               ON pd.order_id      = o.order_id
JOIN users uc               ON o.customer_id    = uc.user_id
LEFT JOIN production_logs pl    ON pd.preorder_id   = pl.preorder_id
LEFT JOIN production_stages ps  ON pl.stage_id      = ps.stage_id
LEFT JOIN users us              ON pl.staff_id      = us.user_id;
GO

-- ============================================================
-- VIEW 3: CẢNH BÁO TỒN KHO THẤP (dưới 5 sản phẩm)
-- ============================================================
CREATE OR ALTER VIEW vw_LowStock AS
SELECT
    pv.variant_id,
    pv.sku,
    pv.color,
    pv.size,
    pv.stock_quantity,

    -- Thông tin sản phẩm
    p.product_id,
    p.name                                      AS product_name,
    p.product_type,
    p.base_price + pv.additional_price          AS selling_price,

    -- Danh mục
    c.name                                      AS category_name,

    -- Mức cảnh báo
    CASE
        WHEN pv.stock_quantity = 0 THEN N'Hết hàng'
        WHEN pv.stock_quantity <= 3 THEN N'Nguy hiểm'
        WHEN pv.stock_quantity <= 5 THEN N'Thấp'
    END                                         AS warning_level

FROM product_variants pv
JOIN products p     ON pv.product_id    = p.product_id
JOIN categories c   ON p.category_id   = c.category_id
WHERE pv.stock_quantity <= 5;
GO

-- ============================================================
-- VIEW 4: DOANH THU THEO THÁNG
-- ============================================================
CREATE OR ALTER VIEW vw_RevenueByMonth AS
SELECT
    YEAR(o.created_at)                          AS year,
    MONTH(o.created_at)                         AS month,
    FORMAT(o.created_at, 'MM/yyyy')             AS month_label,

    -- Số đơn
    COUNT(DISTINCT o.order_id)                  AS total_orders,
    COUNT(DISTINCT CASE 
        WHEN o.order_type = 'PREORDER' 
        THEN o.order_id END)                    AS preorders,

    -- Doanh thu
    SUM(p.amount)                               AS total_revenue,
    AVG(p.amount)                               AS avg_order_value,

    -- Theo trạng thái thanh toán
    SUM(CASE WHEN p.payment_status = 'Paid'
        THEN p.amount ELSE 0 END)               AS paid_revenue,
    SUM(CASE WHEN p.payment_status = 'Refunded'
        THEN p.amount ELSE 0 END)               AS refunded_amount,

    -- Phương thức thanh toán phổ biến nhất
    MAX(p.payment_method)                       AS common_payment_method

FROM orders o
JOIN payments p     ON o.order_id = p.order_id
WHERE o.status NOT IN ('Cancelled')
GROUP BY
    YEAR(o.created_at),
    MONTH(o.created_at),
    FORMAT(o.created_at, 'MM/yyyy');
GO

-- ============================================================
-- KIỂM TRA 4 VIEWS
-- ============================================================
SELECT * FROM vw_OrderSummary;
SELECT * FROM vw_PreOrderProgress;
SELECT * FROM vw_LowStock;
SELECT * FROM vw_RevenueByMonth;

-- ============================================================
-- INDEXES TỐI ƯU HIỆU NĂNG
-- ============================================================

-- ============================================================
-- BẢNG orders
-- Hay dùng WHERE status, customer_id, created_at trong báo cáo
-- ============================================================
CREATE INDEX IX_orders_customer_id
ON orders (customer_id);

CREATE INDEX IX_orders_status
ON orders (status);

CREATE INDEX IX_orders_created_at
ON orders (created_at DESC);

-- Index kết hợp: lọc đơn theo khách + trạng thái cùng lúc
CREATE INDEX IX_orders_customer_status
ON orders (customer_id, status);

-- ============================================================
-- BẢNG order_items
-- Hay JOIN với orders, product_variants
-- ============================================================
CREATE INDEX IX_order_items_order_id
ON order_items (order_id);

CREATE INDEX IX_order_items_frame_variant
ON order_items (frame_variant_id);

CREATE INDEX IX_order_items_lens_variant
ON order_items (lens_variant_id);

-- ============================================================
-- BẢNG product_variants
-- Hay WHERE stock_quantity (cảnh báo kho), JOIN với products
-- ============================================================
CREATE INDEX IX_product_variants_product_id
ON product_variants (product_id);

CREATE INDEX IX_product_variants_stock
ON product_variants (stock_quantity);

-- ============================================================
-- BẢNG payments
-- Hay WHERE payment_status, GROUP BY order_id
-- ============================================================
CREATE INDEX IX_payments_order_id
ON payments (order_id);

CREATE INDEX IX_payments_status
ON payments (payment_status);

-- ============================================================
-- BẢNG shipments
-- Hay WHERE delivery_status, JOIN với orders
-- ============================================================
CREATE INDEX IX_shipments_order_id
ON shipments (order_id);

CREATE INDEX IX_shipments_delivery_status
ON shipments (delivery_status);

-- ============================================================
-- BẢNG prescriptions
-- Hay WHERE customer_id khi tạo đơn
-- ============================================================
CREATE INDEX IX_prescriptions_customer_id
ON prescriptions (customer_id);

-- ============================================================
-- BẢNG support_tickets
-- Hay WHERE status, customer_id
-- ============================================================
CREATE INDEX IX_support_tickets_customer_id
ON support_tickets (customer_id);

CREATE INDEX IX_support_tickets_status
ON support_tickets (status);

-- ============================================================
-- BẢNG preorder_details
-- Hay WHERE preorder_status
-- ============================================================
CREATE INDEX IX_preorder_details_status
ON preorder_details (preorder_status);

-- ============================================================
-- KIỂM TRA TẤT CẢ INDEXES ĐÃ TẠO
-- ============================================================
SELECT
    t.name      AS table_name,
    i.name      AS index_name,
    i.type_desc AS index_type,
    STRING_AGG(c.name, ', ')
        WITHIN GROUP (ORDER BY ic.key_ordinal) AS columns
FROM sys.indexes i
JOIN sys.tables t           ON i.object_id  = t.object_id
JOIN sys.index_columns ic   ON i.object_id  = ic.object_id
                           AND i.index_id   = ic.index_id
JOIN sys.columns c          ON ic.object_id = c.object_id
                           AND ic.column_id = c.column_id
WHERE i.type_desc = 'NONCLUSTERED'
  AND t.name IN (
      'orders','order_items','product_variants',
      'payments','shipments','prescriptions',
      'support_tickets','preorder_details'
  )
GROUP BY t.name, i.name, i.type_desc
ORDER BY t.name, i.name;


CREATE OR ALTER TRIGGER trg_AutoCreateShipment
ON orders
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Khi status chuyển sang Confirmed
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN deleted d ON i.order_id = d.order_id
        WHERE i.status = 'Confirmed'
          AND d.status <> 'Confirmed'
    )
    BEGIN
        INSERT INTO shipments (
            order_id,
            shipping_company,
            tracking_number,
            delivery_status
        )
        SELECT 
            i.order_id,
            N'Giao Hàng Nhanh',
            'TRK' + CAST(ABS(CHECKSUM(NEWID())) % 1000000 AS NVARCHAR),
            'Preparing'
        FROM inserted i;
    END
END;
GO

CREATE OR ALTER TRIGGER trg_AutoCreateShipment
ON orders
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Khi status chuyển sang Confirmed
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN deleted d ON i.order_id = d.order_id
        WHERE i.status = 'Confirmed'
          AND d.status <> 'Confirmed'
    )
    BEGIN
        INSERT INTO shipments (
            order_id,
            shipping_company,
            tracking_number,
            delivery_status
        )
        SELECT 
            i.order_id,
            N'Giao Hàng Nhanh',
            'TRK' + CAST(ABS(CHECKSUM(NEWID())) % 1000000 AS NVARCHAR),
            'Preparing'
        FROM inserted i;
    END
END;
GO


CREATE OR ALTER PROCEDURE sp_CreatePreorder
    @order_id INT,
    @deposit_amount DECIMAL(10,2),
    @expected_date DATE,
    @note NVARCHAR(255)
AS
BEGIN
    INSERT INTO preorder_details (
        order_id,
        deposit_amount,
        remaining_amount,
        preorder_status,
        expected_ready_date,
        special_request
    )
    SELECT
        @order_id,
        @deposit_amount,
        o.total_amount - @deposit_amount,
        'Pending',
        @expected_date,
        @note
    FROM orders o
    WHERE o.order_id = @order_id;
END;
GO

CREATE OR ALTER PROCEDURE sp_UpdateProductionStage
    @preorder_id INT,
    @stage_id INT,
    @status NVARCHAR(50),
    @staff_id INT
AS
BEGIN
    UPDATE production_logs
    SET 
        status = @status,
        staff_id = @staff_id,
        finished_at = CASE 
            WHEN @status = 'Done' THEN GETDATE() 
            ELSE NULL 
        END
    WHERE preorder_id = @preorder_id
      AND stage_id = @stage_id;
END;
GO

CREATE OR ALTER TRIGGER trg_UpdatePreorderStatus
ON production_logs
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Nếu tất cả stage DONE → hoàn thành preorder
    IF EXISTS (
        SELECT 1
        FROM preorder_details pd
        WHERE NOT EXISTS (
            SELECT 1
            FROM production_logs pl
            WHERE pl.preorder_id = pd.preorder_id
              AND pl.status <> 'Done'
        )
    )
    BEGIN
        UPDATE preorder_details
        SET 
            preorder_status = 'Completed',
            actual_ready_date = GETDATE()
        WHERE preorder_id IN (
            SELECT DISTINCT preorder_id FROM inserted
        );
    END
END;
GO
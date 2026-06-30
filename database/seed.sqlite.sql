DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM users;

INSERT INTO users (id, login_id, password_hash, role, name) VALUES
    (1, 'reception01', '$2y$12$wk8HtRV03E0AFXOgA2dpT.Zma/CoiXpOrGIqAs.qllck7Zf3jdDYS', 'receptionist', '注文受付係'),
    (2, 'account01', '$2y$12$aIUAC7HwB06tGwuiSUDvLOoAdw07NO8BzOsj43ei8xwltVJtaicfa', 'accountant', '会計係'),
    (3, 'shipper01', '$2y$12$1af9afQ1bfQaTmel92yJQeicye4QZ540Ferz16B3Rs3tdR9aooQ8e', 'shipper', '商品発送係');

INSERT INTO products (id, product_no, name, price, category, maker, image_path, stock_quantity_1, stock_quantity_2) VALUES
    (1, 'PRD-001', 'ワイヤレスマウス', 2980, 'PC周辺機器', 'Open Gadget', 'assets/img/products/product-001.svg', 20, 12),
    (2, 'PRD-002', 'メカニカルキーボード', 9800, 'PC周辺機器', 'Open Gadget', 'assets/img/products/product-002.svg', 10, 8),
    (3, 'PRD-003', 'USB-C 充電器', 2480, 'アクセサリ', 'Fast Charge', 'assets/img/products/product-003.svg', 35, 18),
    (4, 'PRD-004', 'A4コピー用紙 500枚', 680, '事務用品', 'Paper Works', 'assets/img/products/product-004.svg', 50, 30),
    (5, 'PRD-005', 'ラベルプリンター', 12800, '事務用品', 'Label Lab', 'assets/img/products/product-005.svg', 6, 4);

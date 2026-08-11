CREATE TABLE IF NOT EXISTS product_colors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    color_name VARCHAR(60) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_product_colors_product (product_id),
    CONSTRAINT fk_product_colors_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_sizes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    size_name VARCHAR(60) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_product_sizes_product (product_id),
    CONSTRAINT fk_product_sizes_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample items with modifiers for testing sub-items functionality

-- Insert Fried Rice
INSERT INTO items (category_id, name, slug, description, price, is_available, created_at, updated_at)
SELECT id, 'Fried Rice', 'fried-rice', 'Classic fried rice', 250.00, 1, NOW(), NOW()
FROM categories WHERE name = 'Rice & Noodles'
ON DUPLICATE KEY UPDATE name=name;

-- Get the item ID
SET @fried_rice_id = (SELECT id FROM items WHERE slug = 'fried-rice');

-- Add modifiers for Fried Rice
INSERT INTO item_modifiers (item_id, name, type, price_adjustment, is_active, created_at, updated_at)
VALUES 
(@fried_rice_id, 'Small', 'portion', -50.00, 1, NOW(), NOW()),
(@fried_rice_id, 'Large', 'portion', 100.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name=name;

-- Insert Coca Cola
INSERT INTO items (category_id, name, slug, description, price, is_available, created_at, updated_at)
SELECT id, 'Coca Cola', 'coca-cola', 'Refreshing cola drink', 100.00, 1, NOW(), NOW()
FROM categories WHERE name = 'Beverages'
ON DUPLICATE KEY UPDATE name=name;

-- Get the item ID
SET @coca_cola_id = (SELECT id FROM items WHERE slug = 'coca-cola');

-- Add modifiers for Coca Cola
INSERT INTO item_modifiers (item_id, name, type, price_adjustment, is_active, created_at, updated_at)
VALUES 
(@coca_cola_id, '250ml', 'size', 0.00, 1, NOW(), NOW()),
(@coca_cola_id, '500ml', 'size', 50.00, 1, NOW(), NOW()),
(@coca_cola_id, '1 Liter', 'size', 120.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name=name;

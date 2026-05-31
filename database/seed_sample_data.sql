USE pharmacy_management;

INSERT INTO categories (name, description)
VALUES
    ('Analgesics', 'Pain relief and fever management medicines'),
    ('Antibiotics', 'Prescription medicines used for bacterial infections'),
    ('Antimalarials', 'Medicines used for malaria treatment and prevention'),
    ('Vitamins', 'Supplements and multivitamin products'),
    ('Digestive Health', 'Medicines for stomach and digestive conditions')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO suppliers (company_name, contact_person, phone, email, address)
SELECT 'MediPlus Distribution', 'Amina Sossa', '+229 97000001', 'sales@mediplus.local', 'Cotonou, Benin'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE company_name = 'MediPlus Distribution');

INSERT INTO suppliers (company_name, contact_person, phone, email, address)
SELECT 'WestCare Pharma Supply', 'Koffi Mensah', '+229 97000002', 'orders@westcare.local', 'Porto-Novo, Benin'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE company_name = 'WestCare Pharma Supply');

INSERT INTO suppliers (company_name, contact_person, phone, email, address)
SELECT 'AfriHealth Wholesale', 'Fatou Diop', '+229 97000003', 'contact@afrihealth.local', 'Abomey-Calavi, Benin'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE company_name = 'AfriHealth Wholesale');

INSERT INTO medicines (
    category_id,
    supplier_id,
    medicine_code,
    medicine_name,
    generic_name,
    purchase_price,
    selling_price,
    quantity,
    minimum_stock,
    manufacturing_date,
    expiry_date,
    description
)
VALUES
    (
        (SELECT id FROM categories WHERE name = 'Analgesics'),
        (SELECT id FROM suppliers WHERE company_name = 'MediPlus Distribution'),
        'MED-PAR-500',
        'Paracetamol 500mg',
        'Acetaminophen',
        20.00,
        35.00,
        250,
        40,
        '2026-01-10',
        '2028-01-10',
        'Tablet for pain and fever relief'
    ),
    (
        (SELECT id FROM categories WHERE name = 'Antibiotics'),
        (SELECT id FROM suppliers WHERE company_name = 'WestCare Pharma Supply'),
        'MED-AMX-500',
        'Amoxicillin 500mg',
        'Amoxicillin',
        65.00,
        100.00,
        120,
        25,
        '2026-02-15',
        '2027-08-15',
        'Capsule antibiotic'
    ),
    (
        (SELECT id FROM categories WHERE name = 'Antimalarials'),
        (SELECT id FROM suppliers WHERE company_name = 'AfriHealth Wholesale'),
        'MED-ART-120',
        'Artemether Lumefantrine 20/120mg',
        'Artemether + Lumefantrine',
        450.00,
        650.00,
        80,
        20,
        '2026-03-01',
        '2027-03-01',
        'Antimalarial blister pack'
    ),
    (
        (SELECT id FROM categories WHERE name = 'Vitamins'),
        (SELECT id FROM suppliers WHERE company_name = 'MediPlus Distribution'),
        'MED-VIT-C',
        'Vitamin C 1000mg',
        'Ascorbic Acid',
        75.00,
        125.00,
        180,
        30,
        '2026-01-20',
        '2028-06-20',
        'Immune support supplement'
    ),
    (
        (SELECT id FROM categories WHERE name = 'Digestive Health'),
        (SELECT id FROM suppliers WHERE company_name = 'WestCare Pharma Supply'),
        'MED-ORS-001',
        'Oral Rehydration Salts',
        'ORS',
        35.00,
        60.00,
        200,
        35,
        '2026-02-01',
        '2028-02-01',
        'Rehydration sachet'
    ),
    (
        (SELECT id FROM categories WHERE name = 'Digestive Health'),
        (SELECT id FROM suppliers WHERE company_name = 'AfriHealth Wholesale'),
        'MED-OME-20',
        'Omeprazole 20mg',
        'Omeprazole',
        55.00,
        90.00,
        95,
        20,
        '2026-04-01',
        '2027-10-01',
        'Capsule for acid reflux'
    )
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    supplier_id = VALUES(supplier_id),
    medicine_name = VALUES(medicine_name),
    generic_name = VALUES(generic_name),
    purchase_price = VALUES(purchase_price),
    selling_price = VALUES(selling_price),
    quantity = VALUES(quantity),
    minimum_stock = VALUES(minimum_stock),
    manufacturing_date = VALUES(manufacturing_date),
    expiry_date = VALUES(expiry_date),
    description = VALUES(description);

INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_id)
SELECT id, 'adjustment', quantity, NULL
FROM medicines
WHERE medicine_code IN (
    'MED-PAR-500',
    'MED-AMX-500',
    'MED-ART-120',
    'MED-VIT-C',
    'MED-ORS-001',
    'MED-OME-20'
);

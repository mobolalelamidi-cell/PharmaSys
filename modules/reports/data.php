<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';

// Dev-only bypass: allow local requests to fetch report JSON with ?debug=1
$debug_bypass = false;
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (in_array($remote, ['127.0.0.1', '::1'])) {
        $debug_bypass = true;
    }
}

if (!$debug_bypass) {
    require_role(['admin', 'pharmacist']);
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::connection();

    $from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null;
    $to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : null;

    // normalize to datetime bounds when provided
    $from_dt = $from ? ($from . ' 00:00:00') : null;
    $to_dt = $to ? ($to . ' 23:59:59') : null;

    $params = [];
    $dateWhereSales = '';
    if ($from_dt !== null) {
        $dateWhereSales .= ' AND s.sale_date >= :from';
        $params[':from'] = $from_dt;
    }
    if ($to_dt !== null) {
        $dateWhereSales .= ' AND s.sale_date <= :to';
        $params[':to'] = $to_dt;
    }

    $dateWherePurchases = '';
    $paramsP = [];
    if ($from_dt !== null) {
        $dateWherePurchases .= ' AND p.purchase_date >= :pfrom';
        $paramsP[':pfrom'] = $from_dt;
    }
    if ($to_dt !== null) {
        $dateWherePurchases .= ' AND p.purchase_date <= :pto';
        $paramsP[':pto'] = $to_dt;
    }

    $dateWhereExpenses = '';
    $paramsE = [];
    if ($from_dt !== null) {
        $dateWhereExpenses .= ' AND e.expense_date >= :efrom';
        $paramsE[':efrom'] = $from_dt;
    }
    if ($to_dt !== null) {
        $dateWhereExpenses .= ' AND e.expense_date <= :eto';
        $paramsE[':eto'] = $to_dt;
    }

    // total sales
    $sql = "SELECT COALESCE(SUM(s.total_amount),0) AS total FROM sales s WHERE 1=1 {$dateWhereSales}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $totalSales = (float) $stmt->fetchColumn();

    // total purchases
    $sql = "SELECT COALESCE(SUM(p.total_amount),0) AS total FROM purchases p WHERE 1=1 {$dateWherePurchases}";
    $stmt = $db->prepare($sql);
    $stmt->execute($paramsP);
    $totalPurchases = (float) $stmt->fetchColumn();

    // total expenses
    $sql = "SELECT COALESCE(SUM(e.amount),0) AS total FROM expenses e WHERE 1=1 {$dateWhereExpenses}";
    $stmt = $db->prepare($sql);
    $stmt->execute($paramsE);
    $totalExpenses = (float) $stmt->fetchColumn();

    // COGS (use medicines.purchase_price * quantity sold)
    $sql = "SELECT COALESCE(SUM(si.quantity * m.purchase_price),0) AS cogs
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN medicines m ON si.medicine_id = m.id
            WHERE 1=1 {$dateWhereSales}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cogs = (float) $stmt->fetchColumn();

    // top products
    $sql = "SELECT m.medicine_name, SUM(si.quantity) AS qty, COALESCE(SUM(si.subtotal),0) AS revenue
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN medicines m ON si.medicine_id = m.id
            WHERE 1=1 {$dateWhereSales}
            GROUP BY si.medicine_id
            ORDER BY qty DESC
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // low stock
    $sql = "SELECT id, medicine_name, quantity, minimum_stock FROM medicines WHERE quantity <= minimum_stock ORDER BY quantity ASC LIMIT 50";
    $stmt = $db->query($sql);
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [
        'summary' => [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses,
            'cogs' => $cogs,
            'gross_profit' => $totalSales - $cogs,
        ],
        'top_products' => $topProducts,
        'low_stock' => $lowStock,
    ];

    echo json_encode($result);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $ex->getMessage()]);
}

<?php
// 1. PostgreSQL Database Credentials
$host     = '127.0.0.1';
$port     = '5432';          
$db       = 'erp_masters';   
$user     = 'postgres';      
$pass     = 'admin123';      

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // ----------------------------------------------------
    // 2. Pagination Logic
    // ----------------------------------------------------
    $itemsPerPage = 10;

    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) {
        $currentPage = 1;
    }

    // Get total count of records from prch_vendors
    $totalStmt = $pdo->query('SELECT COUNT(*) FROM "prch_vendors"');
    $totalRecords = (int) $totalStmt->fetchColumn();

    $totalPages = ceil($totalRecords / $itemsPerPage);
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }

    $offset = ($currentPage - 1) * $itemsPerPage;

    // ----------------------------------------------------
    // 3. Fetch Paginated Records from prch_vendors
    // ----------------------------------------------------
    $sql = 'SELECT * FROM "prch_vendors" ORDER BY id ASC LIMIT :limit OFFSET :offset';
    // Note: Change 'id' above to your actual primary key column if it's named differently (e.g., 'vendor_id')
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $vendors = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("PostgreSQL Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors List</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .pagination { display: flex; list-style: none; padding: 0; margin: 0; gap: 5px; }
        .pagination a, .pagination span {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #007bff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pagination a:hover { background-color: #f1f1f1; }
        .pagination .active span {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination .disabled span {
            color: #ccc;
            border-color: #eee;
            cursor: not-allowed;
        }
        .page-info { color: #555; font-size: 14px; }
    </style>
</head>
<body>

    <h2>Vendors List (`prch_vendors`)</h2>

    <?php if (!empty($vendors)): ?>
        <table>
            <thead>
                <tr>
                    <!-- Dynamically generate headers based on the database columns -->
                    <?php foreach (array_keys($vendors[0]) as $columnName): ?>
                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $columnName))); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-container">
            <div class="page-info">
                Showing <?php echo $totalRecords > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $itemsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
            </div>

            <ul class="pagination">
                <?php if ($currentPage > 1): ?>
                    <li><a href="?page=<?php echo $currentPage - 1; ?>">&laquo; Prev</a></li>
                <?php else: ?>
                    <li class="disabled"><span>&laquo; Prev</span></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <li class="active"><span><?php echo $i; ?></span></li>
                    <?php else: ?>
                        <li><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li><a href="?page=<?php echo $currentPage + 1; ?>">Next &raquo;</a></li>
                <?php else: ?>
                    <li class="disabled"><span>Next &raquo;</span></li>
                <?php endif; ?>
            </ul>
        </div>

    <?php else: ?>
        <p>No records found in the <code>prch_vendors</code> table.</p>
    <?php endif; ?>

</body>
</html>
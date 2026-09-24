<?php
// 1. Database Connection Parameters

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = '127.0.0.1';
$db       = 'erp_masters';
$user     = 'postgres';
$pass     = 'admin123';
$port     = '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Pagination Logic
    $itemsPerPage = 10;
    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) { $currentPage = 1; }

    $totalStmt = $pdo->query("SELECT COUNT(*) FROM prch_item_new");
    $totalRecords = $totalStmt->fetchColumn();

    $totalPages = ceil($totalRecords / $itemsPerPage);
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }

    $offset = ($currentPage - 1) * $itemsPerPage;

    // 3. Fetch Paginated Records (Ensure 'id' or primary key is included)
    $sql = "SELECT id, item_name, category_id, sub_category_id, description FROM prch_item_new LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Items with Edit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        
        .btn-edit {
            background-color: #28a745;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-edit:hover { background-color: #218838; }

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
        .pagination .active span { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination .disabled span { color: #ccc; border-color: #eee; cursor: not-allowed; }
        .page-info { color: #555; font-size: 14px; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <h2>Purchase Items List (`prch_item_new`)</h2>
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message'])."-- Item-".$_SESSION['items']; ?>
    </div>
    <?php 
    // Clear message after showing so it disappears on next reload
    unset($_SESSION['message']); 
    unset($_SESSION['message_type']); 
     unset($_SESSION['items']); 
    ?>
<?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert-success">Item updated successfully!</div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Category ID</th>
                    <th>Sub Category ID</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row) :
                    $sql_category = "SELECT id, name FROM prch_item_categories WHERE id = :cat_id";
                    $stmt = $pdo->prepare($sql_category); 
                    // Pass category_id (not item id)
                    $stmt->execute([':cat_id' => $row['category_id']]); 
                    // fetch() returns a single row array instead of fetchAll()
                    $category = $stmt->fetch(); 
                    $category_name = $category['name'] ?? 'N/A';

                   $subCatInput = trim($row['sub_category_id'] ?? '');

                    if (empty($subCatInput)) {
                        // 1. If empty, fallback to 'N/A'
                        $sub_category_name = 'N/A';
                    } elseif (!is_numeric($subCatInput)) {
                        // 2. If it contains characters (is already text), show it directly
                        $sub_category_name = $subCatInput;
                    } else {
                        // 3. If it is a numeric ID, fetch the name from the database
                        $sql_category = "SELECT name FROM prch_subcategories WHERE id = :sub_cat_id";
                        $stmt2 = $pdo->prepare($sql_category); 
                        $stmt2->execute([':sub_cat_id' => $subCatInput]); 
                        $subCatRow = $stmt2->fetch(); 
                        // Show fetched name, or fallback to the ID if not found in DB
                        $sub_category_name = $subCatRow['name'] ?? $subCatInput;
                    }
    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['item_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($category_name."||-".$row['category_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sub_category_name."||-".$row['sub_category_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                        <td>
                            <a href="edit_item.php?id=<?php echo urlencode($row['id']); ?>" class="btn-edit">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-container">
            <div class="page-info">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $itemsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
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
        <p>No records found in <code>prch_item_new</code> table.</p>
    <?php endif; ?>

</body>
</html>
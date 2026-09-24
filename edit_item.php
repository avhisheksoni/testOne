<?php
session_start();
include('connect.php');

$message = '';
$messageType = '';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Get item ID from URL or POST body
    $itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$itemId) {
        die("<div style='font-family:sans-serif; margin: 40px;'><h2>Invalid Request</h2><p>No valid Item ID provided.</p><a href='index.php'>&larr; Back to Items List</a></div>");
    }

    // Capture the return URL so Cancel / Back returns to the exact page (e.g. index.php?page=2)
    $backUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

    // ----------------------------------------------------
    // Fetch Item Record
    // ----------------------------------------------------
    $fetchStmt = $pdo->prepare("SELECT * FROM prch_item_new WHERE id = :id");
    $fetchStmt->execute([':id' => $itemId]);
    $item = $fetchStmt->fetch();

    if (!$item) {
        die("<div style='font-family:sans-serif; margin: 40px;'><h2>Record Not Found</h2><p>No item found with ID #{$itemId}.</p><a href='index.php'>&larr; Back to Items List</a></div>");
    }

    // Fetch Vendors
    $vendorStmt = $pdo->query("SELECT id, company FROM prch_vendors ORDER BY company ASC");
    $allVendors = $vendorStmt->fetchAll();

    // Prepare Selected Vendors
    $rawVendorIds = $item['vendor_ids'] ?? '';
    if (is_array($rawVendorIds)) {
        $selectedVendors = $rawVendorIds;
    } else {
        $cleanIds = trim($rawVendorIds, '{} ');
        $selectedVendors = !empty($cleanIds) ? explode(',', $cleanIds) : [];
    }

} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Detail & Edit - <?php echo htmlspecialchars($item['item_name'] ?? 'Item'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        :root {
            --primary: #007bff;
            --primary-hover: #0056b3;
            --bg-gray: #f8f9fa;
            --border-color: #e0e0e0;
            --text-dark: #333333;
            --text-muted: #6c757d;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: #f4f6f9;
            color: var(--text-dark);
            margin: 0;
            padding: 30px;
        }

        .container { max-width: 900px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-link { text-decoration: none; color: var(--primary); font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; }
        .back-link:hover { text-decoration: underline; }
        .title-badge { background-color: #e9ecef; color: var(--text-muted); padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; }
        
        .alert { padding: 14px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .card { background: #ffffff; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 25px; overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-color); background-color: #ffffff; }
        .card-header h3 { margin: 0; font-size: 16px; color: var(--text-dark); }
        .card-body { padding: 20px; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: span 2; }

        label { font-size: 13px; font-weight: 600; color: #495057; }
        input[type="text"], textarea, select { width: 100%; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
        
        .actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
        .btn { padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 5px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
    </style>
</head>
<body>

<div class="container">

    <!-- Header Navigation -->
    <div class="page-header">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-link">&larr; Back to Purchase Items List</a>
        <span class="title-badge">Record ID: #<?php echo htmlspecialchars($item['id']); ?></span>
    </div>

    <!-- System Message Alert -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <form action="item_detail_update.php?id=<?php echo urlencode($item['id']); ?>" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">

        <!-- Card 1: Core Item Information -->
        <div class="card">
            <div class="card-header">
                <h3>General Item Details</h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="item_name">Item Name <span style="color:red">*</span></label>
                        <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Item Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Classification & Meta Information -->
        <div class="card">
            <div class="card-header">
                <h3>Categorization & Supplier Info</h3>
            </div>
            <?php 
            $stmt = $pdo->query("SELECT id, name FROM prch_item_categories ORDER BY name ASC");
            $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtsub = $pdo->query("SELECT id, name FROM prch_subcategories ORDER BY name ASC");
            $allCategoriessub = $stmtsub->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($allCategories as $category): ?>
                                <?php $isSelected = (string)($category['id'] ?? '') === (string)($item['category_id'] ?? ''); ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sub_category_id">Sub-Category</label>
                        <select id="sub_category_id" name="sub_category_id" class="form-control">
                            <option value="">-- Select Sub-Category --</option>
                            <?php foreach ($allCategoriessub as $sub_cat): ?>
                                <?php $isSelected = (string)($sub_cat['id'] ?? '') === (string)($item['sub_category_id'] ?? ''); ?>
                                <option value="<?php echo htmlspecialchars($sub_cat['id']); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub_cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vendor Checklist -->
                    <div class="form-group full-width">
                        <?php $selectedVendorIds = array_map('strval', $selectedVendors ?? []); ?>
                        <label class="form-label"><strong>Vendor List:</strong></label>
                        <div class="border rounded p-2 bg-light" style="max-height: 220px; overflow-y: auto; border: 1px solid #ced4da;">
                            <?php foreach ($allVendors as $vendor): ?>
                                <?php $isChecked = in_array((string)$vendor['id'], $selectedVendorIds, true); ?>
                                <div style="margin: 4px 0;">
                                    <input 
                                        type="checkbox" 
                                        name="vendor_id[]" 
                                        value="<?php echo htmlspecialchars($vendor['id']); ?>" 
                                        id="vendor_<?php echo htmlspecialchars($vendor['id']); ?>"
                                        <?php echo $isChecked ? 'checked' : ''; ?>
                                    >
                                    <label for="vendor_<?php echo htmlspecialchars($vendor['id']); ?>" style="font-weight: normal; cursor: pointer;">
                                        <strong>#<?php echo htmlspecialchars($vendor['id']); ?></strong> — <?php echo htmlspecialchars($vendor['company']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="dimension">Dimension</label>
                        <input type="text" id="dimension" name="dimension" placeholder="Length x Width x Height" value="<?php echo htmlspecialchars($item['dimension'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Action Buttons -->
        <div class="actions-bar">
            <!-- Dynamic Cancel Link -->
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>

</div>

</body>
</html>
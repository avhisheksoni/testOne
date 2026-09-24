<?php
// Database configuration
$host = '127.0.0.1';
$db   = 'erp_masters';$user = 'postgres';
$pass = 'admin123';$port = '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn,$user, $pass,$options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle file upload submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $fileTmpPath =$_FILES['csv_file']['tmp_name'];
    $fileName =$_FILES['csv_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        die("Error: Please upload a valid CSV file.");
    }

    if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
        // Read header row
        $headers = fgetcsv($handle);$rowCount = 0;
        $pdo->beginTransaction();

        try {
            // Prepared statement for dynamic insertion
            $stmt =$pdo->prepare("
                INSERT INTO prch_item_pro 
                (serial_no, item_number, part_number, item_name, description, unit_id, hsn_code, gst, category_id, brand_id, product_service_name, current_stock, min_stock_level, max_stock_level, department, location, consumption, consumable, id, sub_category_id, specification_name, vendor_ids, dimension, main_categorgy_id, category_type_id, if_both) 
                VALUES 
                (:serial_no, :item_number, :part_number, :item_name, :description, :unit_id, :hsn_code, :gst, :category_id, :brand_id, :product_service_name, :current_stock, :min_stock_level, :max_stock_level, :department, :location, :consumption, :consumable, :id, :sub_category_id, :specification_name, :vendor_ids, :dimension, :main_categorgy_id, :category_type_id, :if_both)
            ");

            while (($data = fgetcsv($handle)) !== FALSE) {
                // Map CSV row to associative array based on headers
                $row = array_combine($headers,$data);

                // Convert empty strings to null for database compatibility
                foreach ($row as $key =>$value) {
                    if ($value === '' \vert{}\vert{}$value === 'NaN') {
                        $row[$key] = null;
                    }
                }

                $stmt->execute([
                    ':serial_no' => $row['serial_no'] ?? null,
                    ':item_number' => $row['item_number'] ?? null,
                    ':part_number' => $row['part_number'] ?? null,
                    ':item_name' => $row['item_name'] ?? null,
                    ':description' => $row['description'] ?? null,
                    ':unit_id' => $row['unit_id'] ?? null,
                    ':hsn_code' => $row['hsn_code'] ?? null,
                    ':gst' => $row['gst'] ?? null,
                    ':category_id' => $row['category_id'] ?? null,
                    ':brand_id' => $row['brand_id'] ?? null,
                    ':product_service_name' => $row['product_service_name'] ?? null,
                    ':current_stock' => $row['current_stock'] ?? 0,                     ':min_stock_level' =>$row['min_stock_level'] ?? 0,
                    ':max_stock_level' => $row['max_stock_level'] ?? 0,                     ':department' =>$row['department'] ?? null,
                    ':location' => $row['location'] ?? null,
                    ':consumption' => $row['consumption'] ?? null,
                    ':consumable' => $row['consumable'] ?? null,
                    ':id' => $row['id'] ?? null,
                    ':sub_category_id' => $row['sub_category_id'] ?? null,
                    ':specification_name' => $row['specification_name'] ?? null,
                    ':vendor_ids' => $row['vendor_ids'] ?? null,
                    ':dimension' => $row['dimension'] ?? null,
                    ':main_categorgy_id' => $row['main_categorgy_id'] ?? null,
                    ':category_type_id' => $row['category_type_id'] ?? null,
                    ':if_both' => $row['if_both'] ?? 0,
                ]);

                $rowCount++;
            }

            fclose($handle);$pdo->commit();
            echo "<h3 style='color: green;'>Successfully imported $rowCount rows into `prch_item_pro`!</h3>";

        } catch (\Exception $e) {$pdo->rollBack();
            echo "<h3 style='color: red;'>Import failed: " . $e->getMessage() . "</h3>";
        }
    }
}
?>

<!-- HTML Upload Form -->
<!DOCTYPE html>
<html>
<head>
    <title>Import prch_item_pro CSV</title>
</head>
<body>
    <h2>Upload CSV File for prch_item_pro</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload and Import</button>
    </form>
</body>
</html>
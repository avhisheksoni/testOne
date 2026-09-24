<?php 
session_start();
include('connect.php');

 $pdo = new PDO($dsn, $user, $pass, $options);

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
  $itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
         $itemName      = trim($_POST['item_name'] ?? '');
         $categoryId    = trim($_POST['category_id'] ?? '');
         $subCategoryId = trim($_POST['sub_category_id'] ?? '');
        // $vendorName    = trim($_POST['vendor_id'] ?? '');
         $vendor_ids = isset($_POST['vendor_id']) && is_array($_POST['vendor_id']) 
    ? array_map('trim', $_POST['vendor_id']) 
    : [0];
         $dimension   = trim($_POST['dimension'] ?? '');
         $description   = trim($_POST['description'] ?? '');


        //   if(count($vendor_ids) >= 4){
        //     true;
        //  }else{
        //     header("Location: edit_item.php?id=" . $itemId); Deepak Pathak To do
        //  }

                if (empty($categoryId)) {
                    echo $message = "category Id is required.";
                    $messageType = "error";
                    return false;
                }else{
                    true;
                }

                if (empty($subCategoryId)) {
                    echo  $message = "sub_category_id Id is required.";
                    $messageType = "error";
                    return false;
                }else{
                    true;
                }
                //     if (empty($vendor_ids)) {
                //     echo $message = "vendor_name Id is required."; Deepak Pathak to do
                //     $messageType = "error";
                //     return false;
                // }else{
                //     true;
                // }
 //$vendor_ids_str = is_array($vendor_ids) ? implode(',', $vendor_ids) : $vendor_ids;  for deepak pathak
  $vendor_ids_str = null;
//                     if (empty($description)) {
//                     echo $message = "Description Id is required.";
//                     $messageType = "error";
//                     return false;
//                 }else{
//                     true;
//                 }
                //     if (empty($dimension)) {
                //     echo $message = "Dimension Id is required.";
                //     $messageType = "error";
                //     return false;
                // }else{
                //     true;
                // }

                if (empty($itemName)) {
                    echo $message = "Item Name is required.";
                    $messageType = "error";
                    return false;
                } else{
                    true;
                }
        
        
            // Note: Update 'vendor_name' if column exists in prch_item_new table
            $updateSql = "UPDATE prch_item_new 
                          SET item_name = :item_name, 
                              category_id = :category_id, 
                              sub_category_id = :sub_category_id,
                              dimension= :dimension, 
                              vendor_ids= :vendor_ids,
                              description = :description 
                          WHERE id = :id";
                        

            $stmt = $pdo->prepare($updateSql);
            $success = $stmt->execute([
                ':item_name'       => $itemName,
                ':category_id'     => $categoryId,
                ':sub_category_id'  => $subCategoryId,
                ':description'     => $description,
                ':dimension'       => $dimension,
                'vendor_ids'       => $vendor_ids_str,
                ':id'              => $itemId,
            ]);

            if ($success) {
            //    echo  $message = "Item details updated successfully!";
            //     $messageType = "success";
            $_SESSION['message'] = "Item details updated successfully!";
            $_SESSION['message_type'] = "success";
            $_SESSION['items']     =  $itemName;
            } else {
                // echo $message = "Failed to update item details.";
                // $messageType = "error";
                $_SESSION['message'] = "Failed to update item details.";
                $_SESSION['message_type'] = "danger"; // Bootstrap alert style
            }
         header("Location: index.php");     
    }

?>
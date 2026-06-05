<?php
include "database.php";
include "on_session.php";

if (isset($_GET['id']) && isset($_GET['from'])) {
    $identification = $conn->real_escape_string($_GET['id']);
    $type = $_GET['from'];

    switch ($type) {
        case "category":
            $table = "category";
            $column = "hashed_id";
            $link = "Category";
            break;

        case "brand":
            $table = "brand";
            $column = "hashed_id";
            $link = "Brand";
            break;

        case "product_list":
            $table = "product";
            $column = "id";
            $link = "Product-list";
            break;

        case "warehouse":
            $table = "warehouse";
            $column = "hashed_id";
            $link = "Warehouses";
            break;

        case "supplier":
            $table = "supplier";
            $column = "hashed_id";
            $link = "Suppliers";
            break;

        case "platform":
            $table = "logistic_partner";
            $column = "hashed_id";
            $link = "logistic-partner";
            break;

        case "courier":
            $table = "courier";
            $column = "hashed_id";
            $link = "Courier";
            break;

        case "access_level":
            $table = "user_position";
            $column = "id";
            $link = "Access-levels";
            break;

        case "item-destination":
            $table = "item_location";
            $column = "id";
            $link = "item-destination";
            break;

        default:
            exit("Invalid type.");
    }

    // Construct the query
    $update_query = "UPDATE $table SET current_status = 1 WHERE $column = '$identification'";

    if ($conn->query($update_query) === TRUE) {
        header("Location: ../$link/?update=success");
        $conn->close();
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<?php
session_start();
require_once 'includes/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Reports</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-primary{
            background-color: var(--primary);
    color: white;
    border: none;
    padding: 15px 20px;
    font-family: "Poppins", sans-serif;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
    display: flex
;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
        }
        
    </style>
</head>
<body>
<div class="app-container">
 
    <header class="header">
        <div class="logo">
            <i class="fas fa-cash-register"></i>
            <h1>POS System</h1>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="inventory.php" class="nav-link">
                <i class="fas fa-boxes"></i> Inventory
            </a>
            <a href="sales.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Sales
            </a>
            <a href="returns.php" class="nav-link active ">
                <i class="fas fa-undo"></i> Returns
            </a>
            <a href="accounts.php" class="nav-link ">
                <i class="fas fa-users"></i> Accounts
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
           
        </nav>
    </header>

    <main class="content">
        <div class="page-header">
            <h1>Reports</h1>
        </div>
        
        <div class="reports-container">
            <div class="card">
                <div class="card-header">
                    <h2>Sales Reports</h2>
                </div>
                <div class="card-body">
                    <form id="sales-report-form" action="api/export_sales_report.php" method="get">
                        <div class="form-group">
                            <label for="report-month">Month:</label>
                            <select id="report-month" name="month" required>
                                <?php
                                for ($i = 1; $i <= 12; $i++) {
                                    $selected = ($i == date('m')) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="report-year">Year:</label>
                            <select id="report-year" name="year" required>
                                <?php
                                $currentYear = date('Y');
                                for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                                    $selected = ($i == $currentYear) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
           >
        </div>
    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    
});
</script>
</body>
</html>

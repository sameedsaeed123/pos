<?php
session_start();
require_once 'includes/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Returns</title>
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
    border-radius: 5%;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
        }
        
    </style>
</head>
<body>
<div class="app-container">
    <!-- Header with navigation -->
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
            <h1>Returns Management</h1>
            <div class="page-actions">
                
            </div>
        </div>
        
        <!-- Returns Navigation -->
        
        
        <!-- Process Return Section -->
        <div id="process-return" class="content-section active">
            <div class="card">
                <div class="card-header">
                    <h2>Find Sale</h2>
                </div>
                <div class="card-body">
                    <form id="find-sale-form" class="form">
                        <div class="form-group">
                            <label for="transaction-id">Transaction ID:</label>
                            <input type="text" id="transaction-id" name="transaction_id" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Find Sale</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Sale Details (hidden by default) -->
            <div id="sale-details" class="card" style="display: none;">
                <div class="card-header">
                    <h2>Sale Details</h2>
                </div>
                <div class="card-body">
                    <div class="sale-info">
                        <div class="info-group">
                            <span class="info-label">Customer:</span>
                            <span id="customer-name-display" class="info-value">-</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Date:</span>
                            <span id="sale-date-display" class="info-value">-</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Total:</span>
                            <span id="sale-total-display" class="info-value">-</span>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Return Qty</th>
                                </tr>
                            </thead>
                            <tbody id="sale-items-body">
                                <!-- Sale items will be populated here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group">
                        <label for="return-reason">Reason for Return:</label>
                        <textarea id="return-reason" name="return_reason" rows="3" required></textarea>
                    </div>
                      
                    <div class="form-actions">
                        <button id="process-return-btn" class="btn btn-primary" >Process Return</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Report Modal -->

            </div>
        </div>
    </main>
</div>

<script src="js/returns.js"></script>
</body>
</html>

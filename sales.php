<?php
session_start();
require_once 'includes/db_config.php';

// Run migration to ensure account_id column exists in sales table
$migrationUrl = "api/migrate_sales_table.php";
$migrationResponse = file_get_contents($migrationUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Sales</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sales.css">
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
            <a href="sales.php" class="nav-link active">
                <i class="fas fa-shopping-cart"></i> Sales
            </a>
            <a href="returns.php" class="nav-link">
                <i class="fas fa-undo"></i> Returns
            </a>
            <a href="accounts.php" class="nav-link">
                <i class="fas fa-users"></i> Accounts
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
           
        </nav>
    </header>

    <main class="content">
        <div class="content-header">
            <h1>Sales</h1>
        </div>
        
        <div class="content-nav">
            <button class="nav-button active" data-target="new-sale">New Sale</button>
            <button class="nav-button" data-target="sales-history">Sales History</button>
        </div>
        
        <div class="content-section active" id="new-sale">
            <div class="sale-container">
                <div class="sale-left">
                    <div class="barcode-scanner">
                        <h3>Barcode Scanner</h3>
                        <div class="input-group">
                            <input type="text" id="barcode-input" placeholder="Scan barcode or enter manually">
                            <button id="add-to-cart-btn" class="primary-btn">Add to Cart</button>
                        </div>
                    </div>
                    
                    <div class="cart">
                        <h3>Shopping Cart</h3>
                        <div class="cart-items">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-items-body">
                                    <tr>
                                        <td colspan="5" class="text-center">No items in cart</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="sale-right">
                    <div class="checkout-panel">
                        <h3>Checkout</h3>
                        
                        <div class="checkout-form">
                            <div class="form-group">
                                <label for="customer-name">Customer Name</label>
                                <input type="text" id="customer-name" placeholder="Walk-in Customer">
                            </div>
                            
                            <div class="form-group">
                                <label for="account-select">Customer Account</label>
                                <select id="account-select">
                                    <option value="">No Account (Cash Sale)</option>
                                    <!-- Accounts will be loaded here -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment-method">Payment Method</label>
                                <select id="payment-method">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="account">Account</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount-type">Discount Type</label>
                                <select id="discount-type">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount">Discount</label>
                                <input type="number" id="discount" min="0" value="0">
                            </div>
                            
                            <div class="checkout-summary">
                                <div class="summary-row">
                                    <div class="summary-label">Subtotal:</div>
                                    <div class="summary-value">PKR <span id="subtotal">0.00</span></div>
                                </div>
                                <div class="summary-row total">
                                    <div class="summary-label">Total:</div>
                                    <div class="summary-value">PKR <span id="total">0.00</span></div>
                                </div>
                            </div>
                            
                            <div class="checkout-actions">
                                <button id="checkout-btn" class="primary-btn">Complete Sale</button>
                                <button id="clear-cart-btn" class="secondary-btn">Clear Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-section" id="sales-history">
            <div class="filter-container">
                <div class="filter-group">
                    <input type="text" id="sales-search" placeholder="Search by transaction ID or customer">
                </div>
                <div class="filter-group">
                    <label>From:</label>
                    <input type="date" id="date-from">
                </div>
                <div class="filter-group">
                    <label>To:</label>
                    <input type="date" id="date-to">
                </div>
                <div class="filter-group">
                    <button id="filter-sales-btn" class="primary-btn">Filter</button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sales-history-body">
                        <tr>
                            <td colspan="7" class="text-center">No sales found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Receipt Modal -->
<div id="receipt-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Receipt</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div id="receipt-container" class="receipt"></div>
            <div class="modal-actions">
                <button id="print-receipt" class="primary-btn">Print Receipt</button>
                <button class="secondary-btn close-modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div id="sale-details-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Sale Details</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div id="sale-details-container"></div>
        </div>
    </div>
</div>

<script src="js/sales.js"></script>
</body>
</html>

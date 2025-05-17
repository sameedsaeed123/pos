<?php
session_start();
require_once 'includes/db_config.php';

// Check and update database schema

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Accounts</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/accounts.css">
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
            <a href="returns.php" class="nav-link">
                <i class="fas fa-undo"></i> Returns
            </a>
            <a href="accounts.php" class="nav-link active">
                <i class="fas fa-users"></i> Accounts
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
           
        </nav>
    </header>

    <main class="content">
        <div class="content-header">
            <h1>Customer Accounts</h1>
            <button id="add-account-btn" class="primary-btn">
                <i class="fas fa-plus"></i> New Account
            </button>
        </div>
        
        <div class="search-container">
            <input type="text" id="account-search" class="search-input" placeholder="Search accounts...">
        </div>
        
        <div id="accounts-container" class="account-grid">
            <!-- Accounts will be loaded here -->
            <div class="loading">Loading accounts...</div>
        </div>
    </main>
</div>

<!-- Add/Edit Account Modal -->
<div id="account-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="account-modal-title">Add New Account</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="account-form">
                <input type="hidden" id="account-id">
                <div class="form-group">
                    <label for="account-name">Account Name *</label>
                    <input type="text" id="account-name" required>
                </div>
                <div class="form-group">
                    <label for="account-contact">Contact Number</label>
                    <input type="text" id="account-contact">
                </div>
                <div class="form-group">
                    <label for="account-email">Email</label>
                    <input type="email" id="account-email">
                </div>
                <div class="form-group" id="initial-balance-group">
                    <label for="account-initial-balance">Initial Balance</label>
                    <input type="number" id="account-initial-balance" min="0" step="0.01" value="0">
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-btn">Save Account</button>
                    <button type="button" class="secondary-btn close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Balance Modal -->
<div id="add-balance-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Balance</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="add-balance-form">
                <input type="hidden" id="balance-account-id">
                <div class="form-group">
                    <label for="account-name-display">Account</label>
                    <div id="account-name-display" class="form-control-static"></div>
                </div>
                <div class="form-group">
                    <label for="current-balance-display">Current Balance</label>
                    <div id="current-balance-display" class="form-control-static"></div>
                </div>
                <div class="form-group">
                    <label for="add-balance-amount">Amount to Add *</label>
                    <input type="number" id="add-balance-amount" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="add-balance-notes">Notes</label>
                    <textarea id="add-balance-notes" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-btn">Add Balance</button>
                    <button type="button" class="secondary-btn close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Account Details Modal -->
<div id="account-details-modal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Account Details</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div id="account-details-container">
                <div class="loading">Loading account details...</div>
            </div>
        </div>
    </div>
</div>

<!-- Account Statement Modal -->
<div id="account-statement-modal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2>Account Statement</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="date-filter">
                <input type="date" id="statement-from-date" class="date-input">
                <input type="date" id="statement-to-date" class="date-input">
                <button id="generate-statement-btn" class="filter-btn">Generate Statement</button>
                <button id="print-statement-btn" class="print-btn">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <div id="account-statement-container">
                <div class="loading">Select date range and generate statement</div>
            </div>
        </div>
    </div>
</div>

<script src="js/accounts.js"></script>
</body>
</html>

<?php
session_start();



require_once 'includes/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Inventory</title>
    <link rel="stylesheet" href="CSS-FOLDER/styles.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <!-- Header with navigation -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-cash-register"></i>
            <h1>POS System</h1>
        </div>
        < <nav class="nav-menu">
            <a href="index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="inventory.php" class="nav-link active">
                <i class="fas fa-boxes"></i> Inventory
            </a>
            <a href="sales.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Sales
            </a>
            <a href="returns.php" class="nav-link">
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
        <h1>Inventory Management</h1>
        
        <!-- Main navigation buttons -->
        <div class="main-nav">
            <button class="nav-button active" data-target="inventory-list">
                <i class="fas fa-list"></i> Product List
            </button>
            <button class="nav-button" data-target="add-product">
                <i class="fas fa-plus-circle"></i> Add Product
            </button>
            <button class="nav-button" data-target="categories">
                <i class="fas fa-tags"></i> Categories
            </button>
            <button class="nav-button" data-target="stock-adjustment">
                <i class="fas fa-balance-scale"></i> Stock Adjustment
            </button>
        </div>
        
        <!-- Content sections -->
        <section id="inventory-list" class="content-section active">
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="inventory-search" placeholder="Search products by name, barcode or category...">
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Purchase Price</th>
                            <th>Sale Price</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-list-body">
                        <!-- Product data will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Add Product Section -->
        <section id="add-product" class="content-section">
            <div class="form-container">
                <h2>Add New Product</h2>
                <form id="add-product-form">
                    <div class="form-group">
                        <label for="barcode">Barcode</label>
                        <div class="barcode-scanner">
                            <input type="text" id="barcode" name="barcode" required>
                            <button type="button" id="generate-barcode">Generate</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="product_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
    <label for="edit-category">Category</label>
    <select id="edit-category" name="category" required>
        <!-- Categories will be loaded dynamically -->
    </select>
</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase-price">Purchase Price</label>
                            <input type="number" id="purchase-price" name="purchase_price" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sale-price">Sale Price</label>
                            <input type="number" id="sale-price" name="sale_price" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Initial Quantity</label>
                            <input type="number" id="quantity" name="quantity" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reorder-level">Reorder Level</label>
                            <input type="number" id="reorder-level" name="reorder_level" min="0" value="10" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </button>
                </form>
            </div>
        </section>
        
        <!-- Categories Section -->
        <section id="categories" class="content-section">
            <div class="form-container">
                <h2>Manage Categories</h2>
                <form id="add-category-form">
                    <div class="form-group">
                        <label for="category-name">Category Name</label>
                        <input type="text" id="category-name" name="category_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category-description">Description</label>
                        <textarea id="category-description" name="category_description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus-circle"></i> Add Category
                    </button>
                </form>
                
                <div class="table-container mt-20">
                    <h3>Existing Categories</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categories-list-body">
                            <!-- Categories will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        
        <!-- Stock Adjustment Section -->
        <section id="stock-adjustment" class="content-section">
            <div class="form-container">
                <h2>Stock Adjustment</h2>
                <form id="stock-adjustment-form">
                    <div class="form-group">
                        <label for="adjustment-barcode">Scan Barcode</label>
                        <div class="barcode-scanner">
                            <input type="text" id="adjustment-barcode" name="barcode" required>
                            <button type="button" id="search-product">Search</button>
                        </div>
                    </div>
                    
                    <div id="product-details" style="display: none;">
                        <div class="form-group">
                            <label>Product Name</label>
                            <div id="product-name-display" class="form-control-static"></div>
                            <input type="hidden" id="product-id" name="product_id">
                        </div>
                        
                        <div class="form-group">
                            <label>Current Stock</label>
                            <div id="current-stock-display" class="form-control-static"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="adjustment-type">Adjustment Type</label>
                            <select id="adjustment-type" name="adjustment_type" required>
                                <option value="add">Add Stock</option>
                                <option value="subtract">Subtract Stock</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="adjustment-quantity">Quantity</label>
                            <input type="number" id="adjustment-quantity" name="quantity" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adjustment-reason">Reason</label>
                            <textarea id="adjustment-reason" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i> Apply Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<!-- Modal for editing product -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Edit Product</h2>
        <form id="edit-product-form">
            <input type="hidden" id="edit-product-id" name="product_id">
            
            <div class="form-group">
                <label for="edit-barcode">Barcode</label>
                <input type="text" id="edit-barcode" name="barcode" required>
            </div>
            
            <div class="form-group">
                <label for="edit-product-name">Product Name</label>
                <input type="text" id="edit-product-name" name="product_name" required>
            </div>
            
            <div class="form-group">
                <label for="edit-description">Description</label>
                <textarea id="edit-description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
    <label for="category">Category</label>
    <select id="category" name="category" required>
        <option value="" disabled selected>Select a category</option>
        <!-- Categories will be loaded dynamically -->
    </select>
</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-purchase-price">Purchase Price</label>
                    <input type="number" id="edit-purchase-price" name="purchase_price" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-sale-price">Sale Price</label>
                    <input type="number" id="edit-sale-price" name="sale_price" min="0" step="0.01" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-quantity">Quantity</label>
                    <input type="number" id="edit-quantity" name="quantity" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-reorder-level">Reorder Level</label>
                    <input type="number" id="edit-reorder-level" name="reorder_level" min="0" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Update Product
            </button>
        </form>
    </div>
</div>

<script src="js/inventory.js"></script>
</body>
</html>

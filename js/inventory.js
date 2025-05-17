document.addEventListener("DOMContentLoaded", () => {
  // Debug mode - set to false in production
  const DEBUG = true;

  function debug(message, data) {
    if (DEBUG) {
      console.log(`[Inventory] ${message}`, data || "");
    }
  }

  debug("Inventory.js loaded");

  // Navigation functionality
  const navButtons = document.querySelectorAll(".nav-button");
  const contentSections = document.querySelectorAll(".content-section");

  navButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const target = this.getAttribute("data-target");
      debug(`Navigation to: ${target}`);

      // Update active button
      navButtons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");

      // Show target section
      contentSections.forEach((section) => {
        section.classList.remove("active");
        if (section.id === target) {
          section.classList.add("active");
        }
      });
    });
  });

  // Load inventory data
  loadInventory();

  // Load categories for dropdowns
  loadCategories();

  // Barcode scanner functionality
  const barcodeInput = document.getElementById("barcode");
  if (barcodeInput) {
    debug("Setting up barcode input");

    // Focus on barcode input when page loads
    setTimeout(() => barcodeInput.focus(), 500);

    barcodeInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        const barcode = this.value.trim();
        if (barcode) {
          checkExistingProduct(barcode);
        }
      }
    });
  }

  // Generate barcode button
  const generateBarcodeBtn = document.getElementById("generate-barcode");
  if (generateBarcodeBtn) {
    generateBarcodeBtn.addEventListener("click", () => {
      const barcode = generateRandomBarcode();
      const barcodeInput = document.getElementById("barcode");
      if (barcodeInput) {
        barcodeInput.value = barcode;
        showMessage("info", "Random barcode generated");
      }
    });
  }

  // Add product form submission
  const addProductForm = document.getElementById("add-product-form");
  if (addProductForm) {
    addProductForm.addEventListener("submit", function (e) {
      e.preventDefault();
      debug("Add product form submitted");

      const formData = new FormData(this);
      
      // Debug form data
      if (DEBUG) {
        console.log("Form data being submitted:");
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
        }
      }

      fetch("api/add_product.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
              throw new Error(`Invalid response: ${text}`);
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            showMessage("success", data.message);
            addProductForm.reset();
            loadInventory(); // Reload inventory list

            // Focus back on barcode input
            const barcodeInput = document.getElementById("barcode");
            if (barcodeInput) {
              setTimeout(() => barcodeInput.focus(), 500);
            }
          } else {
            showMessage("error", data.message);
          }
        })
        .catch((error) => {
          console.error("Error adding product:", error);
          showMessage("error", "Error adding product. Please try again.");
        });
    });
  }

  // Edit product form submission
  const editProductForm = document.getElementById("edit-product-form");
  if (editProductForm) {
    editProductForm.addEventListener("submit", function (e) {
      e.preventDefault();
      debug("Edit product form submitted");

      const formData = new FormData(this);
      
      // Debug form data
      if (DEBUG) {
        console.log("Form data being submitted:");
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
        }
      }

      fetch("api/update_product.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
              throw new Error(`Invalid response: ${text}`);
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            showMessage("success", data.message);
            const editModal = document.getElementById("edit-modal");
            if (editModal) {
              editModal.style.display = "none";
            }
            loadInventory(); // Reload inventory list
          } else {
            showMessage("error", data.message);
          }
        })
        .catch((error) => {
          console.error("Error updating product:", error);
          showMessage("error", "Error updating product. Please try again.");
        });
    });
  }

  // Search functionality
  const inventorySearch = document.getElementById("inventory-search");
  if (inventorySearch) {
    inventorySearch.addEventListener("input", function () {
      loadInventory(this.value);
    });
  }

  // Modal close button
  const closeModalButtons = document.querySelectorAll(".close-modal");
  if (closeModalButtons) {
    closeModalButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const modal = button.closest(".modal");
        if (modal) {
          modal.style.display = "none";
        }
      });
    });
  }

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    document.querySelectorAll(".modal").forEach((modal) => {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // Add category form submission
  const addCategoryForm = document.getElementById("add-category-form");
  if (addCategoryForm) {
    addCategoryForm.addEventListener("submit", function (e) {
      e.preventDefault();
      debug("Add category form submitted");

      const formData = new FormData(this);
      
      // Debug form data
      if (DEBUG) {
        console.log("Category form data being submitted:");
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
        }
      }

      fetch("api/add_category.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
              throw new Error(`Invalid response: ${text}`);
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            showMessage("success", data.message);
            addCategoryForm.reset();
            loadCategories(); // Reload categories
          } else {
            showMessage("error", data.message);
          }
        })
        .catch((error) => {
          console.error("Error adding category:", error);
          showMessage("error", "Error adding category. Please try again.");
        });
    });
  }

  // Stock adjustment form
  const stockAdjustmentForm = document.getElementById("stock-adjustment-form");
  if (stockAdjustmentForm) {
    const searchProductBtn = document.getElementById("search-product");
    if (searchProductBtn) {
      searchProductBtn.addEventListener("click", () => {
        const barcode = document.getElementById("adjustment-barcode").value.trim();
        if (barcode) {
          searchProductForAdjustment(barcode);
        } else {
          showMessage("error", "Please enter a barcode");
        }
      });
    }

    stockAdjustmentForm.addEventListener("submit", function (e) {
      e.preventDefault();
      debug("Stock adjustment form submitted");

      const formData = new FormData(this);

      fetch("api/adjust_stock.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
              throw new Error(`Invalid response: ${text}`);
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            showMessage("success", data.message);
            stockAdjustmentForm.reset();
            document.getElementById("product-details").style.display = "none";
            loadInventory(); // Reload inventory list
          } else {
            showMessage("error", data.message);
          }
        })
        .catch((error) => {
          console.error("Error adjusting stock:", error);
          showMessage("error", "Error adjusting stock. Please try again.");
        });
    });
  }
});

// Check if product exists by barcode
function checkExistingProduct(barcode) {
  fetch(`api/get_product.php?barcode=${encodeURIComponent(barcode)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          throw new Error(`Invalid response: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        // Product exists, fill the form with its data
        showMessage("info", "Product found with this barcode. You can update its details.");

        const product = data.product;
        document.getElementById("product-name").value = product.product_name;
        document.getElementById("description").value = product.description || "";
        document.getElementById("category").value = product.category || "";
        document.getElementById("purchase-price").value = product.purchase_price;
        document.getElementById("sale-price").value = product.sale_price;
        document.getElementById("quantity").value = product.quantity;
        document.getElementById("reorder-level").value = product.reorder_level || 10;
      } else {
        // Product doesn't exist, just keep the barcode
        showMessage("info", "New barcode. Please fill in product details.");

        // Clear other fields but keep the barcode
        document.getElementById("product-name").value = "";
        document.getElementById("description").value = "";
        document.getElementById("category").value = "";
        document.getElementById("purchase-price").value = "";
        document.getElementById("sale-price").value = "";
        document.getElementById("quantity").value = "0";
        document.getElementById("reorder-level").value = "10";

        // Focus on product name field
        document.getElementById("product-name").focus();
      }
    })
    .catch((error) => {
      console.error("Error checking product:", error);
      showMessage("error", "Error checking product. Please try again.");
    });
}

// Search product for stock adjustment
function searchProductForAdjustment(barcode) {
  fetch(`api/get_product.php?barcode=${encodeURIComponent(barcode)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          throw new Error(`Invalid response: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        const product = data.product;
        document.getElementById("product-name-display").textContent = product.product_name;
        document.getElementById("current-stock-display").textContent = product.quantity;
        document.getElementById("product-id").value = product.id;
        document.getElementById("product-details").style.display = "block";
        showMessage("success", "Product found");
      } else {
        document.getElementById("product-details").style.display = "none";
        showMessage("error", "Product not found");
      }
    })
    .catch((error) => {
      console.error("Error searching product:", error);
      showMessage("error", "Error searching product. Please try again.");
    });
}

// Generate random barcode
function generateRandomBarcode() {
  let barcode = "";
  for (let i = 0; i < 13; i++) {
    barcode += Math.floor(Math.random() * 10);
  }
  return barcode;
}

// Load categories function
function loadCategories() {
  fetch("api/get_categories.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          throw new Error(`Invalid response: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      console.log("Categories loaded:", data);
      
      // Populate category dropdowns
      const categoryDropdowns = document.querySelectorAll("#category, #edit-category");

      categoryDropdowns.forEach((dropdown) => {
        if (dropdown) {
          console.log("Populating dropdown:", dropdown.id);
          
          // Keep the first option (usually "Select a category")
          const firstOption = dropdown.options[0];
          dropdown.innerHTML = "";
          if (firstOption) {
            dropdown.appendChild(firstOption);
          }

          // Add categories from API
          data.forEach((category) => {
            const option = document.createElement("option");
            option.value = category.name;
            option.textContent = category.name;
            dropdown.appendChild(option);
            console.log(`Added option: ${category.name}`);
          });
        }
      });

      // Populate categories table
      const categoriesBody = document.getElementById("categories-list-body");
      if (categoriesBody) {
        categoriesBody.innerHTML = "";

        if (data.length === 0) {
          categoriesBody.innerHTML = '<tr><td colspan="3" class="text-center">No categories found</td></tr>';
          return;
        }

        data.forEach((category) => {
          const row = document.createElement("tr");

          row.innerHTML = `
            <td>${category.name}</td>
            <td>${category.description || "N/A"}</td>
            <td>
              <button class="action-btn" onclick="editCategory(${category.id})">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="action-btn delete-btn" onclick="deleteCategory(${category.id})">
                <i class="fas fa-trash"></i> Delete
              </button>
            </td>
          `;

          categoriesBody.appendChild(row);
        });
      }
    })
    .catch((error) => {
      console.error("Error loading categories:", error);
      const categoriesBody = document.getElementById("categories-list-body");
      if (categoriesBody) {
        categoriesBody.innerHTML = '<tr><td colspan="3" class="text-center">Error loading categories</td></tr>';
      }
    });
}

function loadInventory(search = "") {
  fetch(`api/get_inventory.php?search=${encodeURIComponent(search)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          throw new Error(`Invalid response: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      const tbody = document.getElementById("inventory-list-body");
      if (!tbody) {
        console.error("Inventory list body element not found");
        return;
      }

      tbody.innerHTML = "";

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No products found</td></tr>';
        return;
      }

      // Populate the table with inventory data
      data.forEach((product) => {
        const row = document.createElement("tr");

        // Determine stock status class
        let stockClass = "";
        if (product.quantity <= 0) {
          stockClass = "text-danger";
        } else if (product.quantity <= (product.reorder_level || 10)) {
          stockClass = "text-warning";
        }

        row.innerHTML = `
          <td>${product.barcode}</td>
          <td>${product.product_name}</td>
          <td>${product.category || "N/A"}</td>
          <td>PKR ${Number.parseFloat(product.purchase_price).toFixed(2)}</td>
          <td>PKR ${Number.parseFloat(product.sale_price).toFixed(2)}</td>
          <td class="${stockClass}">${product.quantity}</td>
          <td>
            <div class="action-buttons">
              <button class="action-btn" onclick="editProduct(${product.id})">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="action-btn delete-btn" onclick="deleteProduct(${product.id})">
                <i class="fas fa-trash"></i> Delete
              </button>
            </div>
          </td>
        `;

        tbody.appendChild(row);
      });
    })
    .catch((error) => {
      console.error("Error loading inventory:", error);
      const tbody = document.getElementById("inventory-list-body");
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading inventory data</td></tr>';
      }
    });
}

// Edit product function - FIXED VERSION
window.editProduct = (id) => {
  console.log("Editing product with ID:", id);
  
  fetch(`api/get_product.php?id=${id}`)
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log("Product data received:", data);
      
      if (data.success) {
        // Get the product from the response
        const product = data.product;
        console.log("Product object:", product);
        
        // Ensure ID field exists and is populated
        const idField = document.getElementById("edit-product-id");
        if (!idField) {
          console.error("ID field not found in edit form");
          throw new Error("Missing ID field in form");
        }
        
        // Set the product ID in the form
        idField.value = product.id;
        
        // Populate the rest of the form fields
        document.getElementById("edit-barcode").value = product.barcode;
        document.getElementById("edit-product-name").value = product.product_name;
        document.getElementById("edit-description").value = product.description || "";
        document.getElementById("edit-purchase-price").value = product.purchase_price;
        document.getElementById("edit-sale-price").value = product.sale_price;
        document.getElementById("edit-quantity").value = product.quantity;
        
        // Set reorder level if it exists
        const reorderLevelField = document.getElementById("edit-reorder-level");
        if (reorderLevelField) {
          reorderLevelField.value = product.reorder_level || 10;
        }

        // Set category
        const categorySelect = document.getElementById("edit-category");
        if (categorySelect) {
          // First set default selection
          categorySelect.selectedIndex = 0;
          
          // Then try to find and select the matching category
          for (let i = 0; i < categorySelect.options.length; i++) {
            if (categorySelect.options[i].value === product.category) {
              categorySelect.selectedIndex = i;
              console.log(`Selected category: ${product.category} at index ${i}`);
              break;
            }
          }
        }

        // Show the modal
        document.getElementById("edit-modal").style.display = "block";
      } else {
        showMessage("error", data.message || "Error loading product details");
      }
    })
    .catch(error => {
      console.error("Error getting product details:", error);
      showMessage("error", "Error loading product details. Please try again.");
    });
};

// Delete product function
window.deleteProduct = (id) => {
  if (confirm("Are you sure you want to delete this product? This action cannot be undone.")) {
    fetch(`api/delete_product.php?id=${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          return response.text().then(text => {
            throw new Error(`Invalid response: ${text}`);
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          showMessage("success", data.message);
          loadInventory(); // Reload inventory list
        } else {
          showMessage("error", data.message);
        }
      })
      .catch((error) => {
        console.error("Error deleting product:", error);
        showMessage("error", "Error deleting product. Please try again.");
      });
  }
};

// Edit category function
window.editCategory = (id) => {
  fetch(`api/get_category.php?id=${id}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          throw new Error(`Invalid response: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        const category = data.category;

        // Create modal for editing category
        const modalHTML = `
          <div id="edit-category-modal" class="modal" style="display: block;">
            <div class="modal-content">
              <span class="close-category-modal">&times;</span>
              <h2>Edit Category</h2>
              <form id="edit-category-form">
                <input type="hidden" id="edit-category-id" name="category_id" value="${category.id}">
                
                <div class="form-group">
                  <label for="edit-category-name">Category Name</label>
                  <input type="text" id="edit-category-name" name="category_name" value="${category.name}" required>
                </div>
                
                <div class="form-group">
                  <label for="edit-category-description">Description</label>
                  <textarea id="edit-category-description" name="category_description" rows="3">${category.description || ""}</textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                  <i class="fas fa-save"></i> Update Category
                </button>
              </form>
            </div>
          </div>
        `;

        // Append modal to body
        document.body.insertAdjacentHTML("beforeend", modalHTML);

        // Add event listeners
        document.querySelector(".close-category-modal").addEventListener("click", () => {
          document.getElementById("edit-category-modal").remove();
        });

        document.getElementById("edit-category-form").addEventListener("submit", function (e) {
          e.preventDefault();

          const formData = new FormData(this);
          
          // Debug form data
          if (DEBUG) {
            console.log("Category edit form data being submitted:");
            for (let pair of formData.entries()) {
              console.log(pair[0] + ': ' + pair[1]);
            }
          }
          
          fetch("api/update_category.php", {
            method: "POST",
            body: formData
          })
          .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              return response.text().then(text => {
                throw new Error(`Invalid response: ${text}`);
              });
            }
            return response.json();
          })
          .then(data => {
            if (data.success) {
              showMessage("success", data.message);
              document.getElementById("edit-category-modal").remove();
              loadCategories();
            } else {
              showMessage("error", data.message);
            }
          })
          .catch(error => {
            console.error("Error updating category:", error);
            showMessage("error", "Error updating category. Please try again.");
          });
        });
      } else {
        showMessage("error", data.message || "Error loading category details");
      }
    })
    .catch((error) => {
      console.error("Error getting category details:", error);
      showMessage("error", "Error loading category details. Please try again.");
    });
};

// Delete category function
window.deleteCategory = (id) => {
  if (confirm("Are you sure you want to delete this category? Products in this category will not be deleted.")) {
    fetch(`api/delete_category.php?id=${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          return response.text().then(text => {
            throw new Error(`Invalid response: ${text}`);
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          showMessage("success", data.message);
          loadCategories(); // Reload categories
        } else {
          showMessage("error", data.message);
        }
      })
      .catch((error) => {
        console.error("Error deleting category:", error);
        showMessage("error", "Error deleting category. Please try again.");
      });
  }
};

// Show message function
function showMessage(type, text) {
  // Check if notification container exists
  let container = document.getElementById("notification-container");

  if (!container) {
    container = document.createElement("div");
    container.id = "notification-container";
    container.style.position = "fixed";
    container.style.top = "20px";
    container.style.right = "20px";
    container.style.zIndex = "9999";
    document.body.appendChild(container);
  }

  const notification = document.createElement("div");
  notification.className = `notification ${type}`;

  // Style the notification
  notification.style.backgroundColor = type === "success" ? "#4CAF50" : type === "error" ? "#F44336" : "#2196F3";
  notification.style.color = "white";
  notification.style.padding = "15px 20px";
  notification.style.marginBottom = "10px";
  notification.style.borderRadius = "4px";
  notification.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
  notification.style.display = "flex";
  notification.style.justifyContent = "space-between";
  notification.style.alignItems = "center";
  notification.style.minWidth = "250px";
  notification.style.maxWidth = "350px";
  notification.style.animation = "slideIn 0.3s ease-out forwards";

  notification.innerHTML = `
    <div>${text}</div>
    <button style="background:none; border:none; color:white; font-size:20px; cursor:pointer; margin-left:10px;">&times;</button>
  `;

  // Add close button functionality
  const closeButton = notification.querySelector("button");
  closeButton.addEventListener("click", () => {
    notification.style.animation = "slideOut 0.3s ease-out forwards";
    setTimeout(() => {
      if (notification.parentNode === container) {
        container.removeChild(notification);
      }
    }, 300);
  });

  container.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode === container) {
      notification.style.animation = "slideOut 0.3s ease-out forwards";
      setTimeout(() => {
        if (notification.parentNode === container) {
          container.removeChild(notification);
        }
      }, 300);
    }
  }, 5000);

  // Add CSS animations if they don't exist
  if (!document.getElementById("notification-styles")) {
    const style = document.createElement("style");
    style.id = "notification-styles";
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);
  }
}
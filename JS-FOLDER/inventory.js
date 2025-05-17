document.addEventListener("DOMContentLoaded", () => {
    // Navigation functionality
    const navButtons = document.querySelectorAll(".nav-button")
    const contentSections = document.querySelectorAll(".content-section")
  
    navButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const target = this.getAttribute("data-target")
  
        // Update active button
        navButtons.forEach((btn) => btn.classList.remove("active"))
        this.classList.add("active")
  
        // Show target section
        contentSections.forEach((section) => {
          section.classList.remove("active")
          if (section.id === target) {
            section.classList.add("active")
          }
        })
      })
    })
  
    // Load inventory data on page load
    loadInventory()
  
    // Load categories for dropdowns
    loadCategories()
  
    // Barcode scanner functionality for adding products
    const barcodeInput = document.getElementById("barcode")
    barcodeInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault()
        checkExistingBarcode(this.value)
      }
    })
  
    // Auto-focus barcode input when the add product section is shown
    document.querySelector('[data-target="add-product"]').addEventListener("click", () => {
      setTimeout(() => {
        document.getElementById("barcode").focus()
      }, 100)
    })
  
    // Generate random barcode
    document.getElementById("generate-barcode").addEventListener("click", () => {
      const randomBarcode = generateRandomBarcode()
      document.getElementById("barcode").value = randomBarcode
    })
  
    // Add product form submission
    document.getElementById("add-product-form").addEventListener("submit", function (e) {
      e.preventDefault()
      addProduct(this)
    })
  
    // Edit product form submission
    document.getElementById("edit-product-form").addEventListener("submit", function (e) {
      e.preventDefault()
      updateProduct(this)
    })
  
    // Add category form submission
    document.getElementById("add-category-form").addEventListener("submit", function (e) {
      e.preventDefault()
      addCategory(this)
    })
  
    // Stock adjustment barcode scanner
    document.getElementById("adjustment-barcode").addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault()
        searchProductForAdjustment(this.value)
      }
    })
  
    // Search product button in stock adjustment
    document.getElementById("search-product").addEventListener("click", () => {
      const barcode = document.getElementById("adjustment-barcode").value
      searchProductForAdjustment(barcode)
    })
  
    // Stock adjustment form submission
    document.getElementById("stock-adjustment-form").addEventListener("submit", function (e) {
      e.preventDefault()
      adjustStock(this)
    })
  
    // Search functionality
    document.getElementById("inventory-search").addEventListener("input", function () {
      loadInventory(this.value)
    })
  
    // Close modal buttons
    document.querySelectorAll(".close-modal").forEach((button) => {
      button.addEventListener("click", () => {
        document.querySelectorAll(".modal").forEach((modal) => {
          modal.style.display = "none"
        })
      })
    })
  
    // Close modal when clicking outside
    window.addEventListener("click", (e) => {
      document.querySelectorAll(".modal").forEach((modal) => {
        if (e.target === modal) {
          modal.style.display = "none"
        }
      })
    })
  })
  
  // Load inventory data
  function loadInventory(search = "") {
    showLoader("inventory-list-body")
  
    fetch(`actions/get_products.php?search=${encodeURIComponent(search)}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok")
        }
        return response.json()
      })
      .then((data) => {
        const tbody = document.getElementById("inventory-list-body")
        tbody.innerHTML = ""
  
        if (data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center">No products found</td></tr>'
          return
        }
  
        data.forEach((product) => {
          const row = document.createElement("tr")
  
          // Add warning class for low stock
          if (product.quantity <= product.reorder_level) {
            row.classList.add("low-stock")
          }
  
          row.innerHTML = `
                      <td>${product.barcode}</td>
                      <td>${product.name}</td>
                      <td>${product.category_name || "N/A"}</td>
                      <td>₹${Number.parseFloat(product.purchase_price).toFixed(2)}</td>
                      <td>₹${Number.parseFloat(product.sale_price).toFixed(2)}</td>
                      <td>${product.quantity}</td>
                      <td>
                          <button class="action-btn edit-btn" onclick="editProduct(${product.id})">
                              <i class="fas fa-edit"></i>
                          </button>
                          <button class="action-btn delete-btn" onclick="deleteProduct(${product.id})">
                              <i class="fas fa-trash"></i>
                          </button>
                      </td>
                  `
  
          tbody.appendChild(row)
        })
      })
      .catch((error) => {
        console.error("Error loading inventory:", error)
        document.getElementById("inventory-list-body").innerHTML =
          '<tr><td colspan="7" class="text-center">Error loading inventory data</td></tr>'
      })
  }
  
  // Load categories
  function loadCategories() {
    fetch("actions/get_categories.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok")
        }
        return response.json()
      })
      .then((data) => {
        // Populate category dropdowns
        const categorySelect = document.getElementById("category")
        const editCategorySelect = document.getElementById("edit-category")
  
        // Clear existing options except the first one
        categorySelect.innerHTML = '<option value="" disabled selected>Select a category</option>'
        editCategorySelect.innerHTML = '<option value="" disabled selected>Select a category</option>'
  
        data.forEach((category) => {
          const option = document.createElement("option")
          option.value = category.id
          option.textContent = category.name
  
          const optionClone = option.cloneNode(true)
  
          categorySelect.appendChild(option)
          editCategorySelect.appendChild(optionClone)
        })
  
        // Also populate the categories table
        const tbody = document.getElementById("categories-list-body")
        tbody.innerHTML = ""
  
        if (data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="3" class="text-center">No categories found</td></tr>'
          return
        }
  
        data.forEach((category) => {
          const row = document.createElement("tr")
  
          row.innerHTML = `
                      <td>${category.name}</td>
                      <td>${category.description || "N/A"}</td>
                      <td>
                          <button class="action-btn edit-btn" onclick="editCategory(${category.id})">
                              <i class="fas fa-edit"></i>
                          </button>
                          <button class="action-btn delete-btn" onclick="deleteCategory(${category.id})">
                              <i class="fas fa-trash"></i>
                          </button>
                      </td>
                  `
  
          tbody.appendChild(row)
        })
      })
      .catch((error) => {
        console.error("Error loading categories:", error)
      })
  }
  
  // Check if barcode already exists
  function checkExistingBarcode(barcode) {
    if (!barcode) return
  
    fetch(`actions/check_barcode.php?barcode=${encodeURIComponent(barcode)}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.exists) {
          // Product exists, fill the form with its data
          document.getElementById("product-name").value = data.product.name
          document.getElementById("description").value = data.product.description
          document.getElementById("category").value = data.product.category_id
          document.getElementById("purchase-price").value = data.product.purchase_price
          document.getElementById("sale-price").value = data.product.sale_price
          document.getElementById("quantity").value = data.product.quantity
          document.getElementById("reorder-level").value = data.product.reorder_level
  
          showNotification("Product with this barcode already exists. You can update the details.", "warning")
        } else {
          // Clear form fields except barcode
          document.getElementById("product-name").value = ""
          document.getElementById("description").value = ""
          document.getElementById("category").selectedIndex = 0
          document.getElementById("purchase-price").value = ""
          document.getElementById("sale-price").value = ""
          document.getElementById("quantity").value = "0"
          document.getElementById("reorder-level").value = "10"
  
          // Focus on product name field
          document.getElementById("product-name").focus()
        }
      })
      .catch((error) => {
        console.error("Error checking barcode:", error)
        showNotification("Error checking barcode", "error")
      })
  }
  
  // Generate random barcode
  function generateRandomBarcode() {
    let barcode = ""
    for (let i = 0; i < 12; i++) {
      barcode += Math.floor(Math.random() * 10)
    }
    return barcode
  }
  
  // Add product
  function addProduct(form) {
    const formData = new FormData(form)
  
    fetch("actions/add_product.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification(data.message, "success")
          form.reset()
          loadInventory()
          // Focus on barcode field
          document.getElementById("barcode").focus()
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error adding product:", error)
        showNotification("Error adding product", "error")
      })
  }
  
  // Edit product
  window.editProduct = (id) => {
    fetch(`actions/get_product.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const product = data.product
  
          document.getElementById("edit-product-id").value = product.id
          document.getElementById("edit-barcode").value = product.barcode
          document.getElementById("edit-product-name").value = product.name
          document.getElementById("edit-description").value = product.description
          document.getElementById("edit-category").value = product.category_id
          document.getElementById("edit-purchase-price").value = product.purchase_price
          document.getElementById("edit-sale-price").value = product.sale_price
          document.getElementById("edit-quantity").value = product.quantity
          document.getElementById("edit-reorder-level").value = product.reorder_level
  
          document.getElementById("edit-modal").style.display = "block"
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error getting product:", error)
        showNotification("Error getting product details", "error")
      })
  }
  
  // Update product
  function updateProduct(form) {
    const formData = new FormData(form)
  
    fetch("actions/update_product.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification(data.message, "success")
          document.getElementById("edit-modal").style.display = "none"
          loadInventory()
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error updating product:", error)
        showNotification("Error updating product", "error")
      })
  }
  
  // Delete product
  window.deleteProduct = (id) => {
    if (confirm("Are you sure you want to delete this product? This action cannot be undone.")) {
      fetch(`actions/delete_product.php?id=${id}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showNotification(data.message, "success")
            loadInventory()
          } else {
            showNotification(data.message, "error")
          }
        })
        .catch((error) => {
          console.error("Error deleting product:", error)
          showNotification("Error deleting product", "error")
        })
    }
  }
  
  // Add category
  function addCategory(form) {
    const formData = new FormData(form)
  
    fetch("actions/add_category.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification(data.message, "success")
          form.reset()
          loadCategories()
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error adding category:", error)
        showNotification("Error adding category", "error")
      })
  }
  
  // Edit category
  window.editCategory = (id) => {
    fetch(`actions/get_category.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const category = data.category
  
          // Create a modal for editing category
          const modal = document.createElement("div")
          modal.className = "modal"
          modal.id = "edit-category-modal"
  
          modal.innerHTML = `
                      <div class="modal-content">
                          <span class="close-modal">&times;</span>
                          <h2>Edit Category</h2>
                          <form id="edit-category-form">
                              <input type="hidden" name="category_id" value="${category.id}">
                              
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
                  `
  
          document.body.appendChild(modal)
  
          // Show the modal
          modal.style.display = "block"
  
          // Close modal when clicking on X
          modal.querySelector(".close-modal").addEventListener("click", () => {
            modal.remove()
          })
  
          // Close modal when clicking outside
          window.addEventListener("click", (e) => {
            if (e.target === modal) {
              modal.remove()
            }
          })
  
          // Handle form submission
          modal.querySelector("#edit-category-form").addEventListener("submit", function (e) {
            e.preventDefault()
  
            const formData = new FormData(this)
  
            fetch("actions/update_category.php", {
              method: "POST",
              body: formData,
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  showNotification(data.message, "success")
                  modal.remove()
                  loadCategories()
                } else {
                  showNotification(data.message, "error")
                }
              })
              .catch((error) => {
                console.error("Error updating category:", error)
                showNotification("Error updating category", "error")
              })
          })
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error getting category:", error)
        showNotification("Error getting category details", "error")
      })
  }
  
  // Delete category
  window.deleteCategory = (id) => {
    if (confirm("Are you sure you want to delete this category? Products in this category will not be deleted.")) {
      fetch(`actions/delete_category.php?id=${id}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showNotification(data.message, "success")
            loadCategories()
          } else {
            showNotification(data.message, "error")
          }
        })
        .catch((error) => {
          console.error("Error deleting category:", error)
          showNotification("Error deleting category", "error")
        })
    }
  }
  
  // Search product for stock adjustment
  function searchProductForAdjustment(barcode) {
    if (!barcode) {
      showNotification("Please enter a barcode", "warning")
      return
    }
  
    fetch(`actions/check_barcode.php?barcode=${encodeURIComponent(barcode)}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.exists) {
          // Product exists, show details
          document.getElementById("product-id").value = data.product.id
          document.getElementById("product-name-display").textContent = data.product.name
          document.getElementById("current-stock-display").textContent = data.product.quantity
  
          // Show product details section
          document.getElementById("product-details").style.display = "block"
  
          // Focus on adjustment quantity
          document.getElementById("adjustment-quantity").focus()
        } else {
          showNotification("Product not found. Please check the barcode.", "error")
          document.getElementById("product-details").style.display = "none"
        }
      })
      .catch((error) => {
        console.error("Error checking barcode:", error)
        showNotification("Error checking barcode", "error")
      })
  }
  
  // Adjust stock
  function adjustStock(form) {
    const formData = new FormData(form)
  
    fetch("actions/adjust_stock.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification(data.message, "success")
          form.reset()
          document.getElementById("product-details").style.display = "none"
          loadInventory()
  
          // Focus on barcode field
          document.getElementById("adjustment-barcode").focus()
        } else {
          showNotification(data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error adjusting stock:", error)
        showNotification("Error adjusting stock", "error")
      })
  }
  
  // Show notification
  function showNotification(message, type) {
    const notification = document.createElement("div")
    notification.className = `notification ${type}`
    notification.textContent = message
  
    document.body.appendChild(notification)
  
    // Show notification
    setTimeout(() => {
      notification.classList.add("show")
    }, 10)
  
    // Hide and remove notification after 3 seconds
    setTimeout(() => {
      notification.classList.remove("show")
      setTimeout(() => {
        notification.remove()
      }, 300)
    }, 3000)
  }
  
  // Show loader
  function showLoader(elementId) {
    const element = document.getElementById(elementId)
    element.innerHTML = `
          <tr>
              <td colspan="7" class="text-center">
                  <div class="loader"></div>
                  <p>Loading...</p>
              </td>
          </tr>
      `
  }
  
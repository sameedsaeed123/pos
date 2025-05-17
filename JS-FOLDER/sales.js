document.addEventListener("DOMContentLoaded", () => {
  // Debug mode - set to false in production
  const DEBUG = true

  function debug(message, data) {
    if (DEBUG) {
      console.log(`[Sales] ${message}`, data || "")
    }
  }

  debug("Sales.js loaded")

  // Global variables to store cart data
  window.cartItems = []
  window.subtotal = 0
  window.discount = 0
  window.total = 0

  // Navigation functionality
  const navButtons = document.querySelectorAll(".nav-button")
  const contentSections = document.querySelectorAll(".content-section")

  navButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const target = this.getAttribute("data-target")
      debug(`Navigation to: ${target}`)

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

      // Load sales history if on that tab
      if (target === "sales-history") {
        loadSalesHistory()
      }
    })
  })

  // Barcode input event
  const barcodeInput = document.getElementById("barcode-input")
  if (barcodeInput) {
    barcodeInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault()
        addProductToCart()
      }
    })
  }

  // Add to cart button
  const addToCartBtn = document.getElementById("add-to-cart-btn")
  if (addToCartBtn) {
    addToCartBtn.addEventListener("click", addProductToCart)
  }

  // Discount input events
  const discountInput = document.getElementById("discount")
  if (discountInput) {
    discountInput.addEventListener("input", updateCartTotal)
  }

  // Discount type change
  const discountType = document.getElementById("discount-type")
  if (discountType) {
    discountType.addEventListener("change", updateCartTotal)
  }

  // Checkout button
  const checkoutBtn = document.getElementById("checkout-btn")
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", completeSale)
  }

  // Clear cart button
  const clearCartBtn = document.getElementById("clear-cart-btn")
  if (clearCartBtn) {
    clearCartBtn.addEventListener("click", clearCart)
  }

  // Filter sales button
  const filterSalesBtn = document.getElementById("filter-sales-btn")
  if (filterSalesBtn) {
    filterSalesBtn.addEventListener("click", loadSalesHistory)
  }

  // Sales search input
  const salesSearch = document.getElementById("sales-search")
  if (salesSearch) {
    salesSearch.addEventListener("input", function () {
      if (this.value.length >= 3 || this.value.length === 0) {
        loadSalesHistory()
      }
    })
  }

  // Modal close buttons
  const closeModalButtons = document.querySelectorAll(".close-modal")
  closeModalButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const modal = this.closest(".modal")
      modal.style.display = "none"
    })
  })

  // Print receipt button
  const printReceiptBtn = document.getElementById("print-receipt")
  if (printReceiptBtn) {
    printReceiptBtn.addEventListener("click", () => {
      const receiptContent = document.getElementById("receipt-container").innerHTML
      const printWindow = window.open("", "_blank")
      printWindow.document.write(`
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body { font-family: 'Courier New', monospace; font-size: 12px; }
                .receipt { width: 300px; margin: 0 auto; }
                .receipt-header { text-align: center; margin-bottom: 10px; }
                .receipt-item { margin-bottom: 5px; }
                .receipt-total { margin-top: 10px; font-weight: bold; }
                .receipt-footer { text-align: center; margin-top: 20px; font-size: 10px; }
                @media print {
                    @page { margin: 0; }
                    body { margin: 1cm; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">${receiptContent}</div>
            <script>
                window.onload = function() { window.print(); window.close(); }
            </script>
        </body>
        </html>
      `)
      printWindow.document.close()
    })
  }

  // Window click event to close modals
  window.addEventListener("click", (event) => {
    const modals = document.querySelectorAll(".modal")
    modals.forEach((modal) => {
      if (event.target === modal) {
        modal.style.display = "none"
      }
    })
  })
})

// Add product to cart
function addProductToCart() {
  const barcodeInput = document.getElementById("barcode-input")
  const barcode = barcodeInput.value.trim()

  if (!barcode) {
    showNotification("Please enter a barcode", "error")
    return
  }

  // Show loading notification
  showNotification("Searching for product...", "info")

  // Fetch product data from server
  fetch(`api/get_product.php?barcode=${encodeURIComponent(barcode)}`)
    .then((response) => {
      // Store status and statusText for better error reporting
      const status = response.status
      const statusText = response.statusText

      if (!response.ok) {
        return response.text().then((text) => {
          // Try to parse as JSON first
          try {
            const errorData = JSON.parse(text)
            throw new Error(`Server error (${status}): ${errorData.message || statusText}`)
          } catch (e) {
            // If not JSON or parsing failed, return the raw text or status
            if (text && text.trim().length > 0) {
              throw new Error(`Server error (${status}): ${text.substring(0, 100)}...`)
            } else {
              throw new Error(`HTTP error! Status: ${status} ${statusText}`)
            }
          }
        })
      }

      return response.text().then((text) => {
        try {
          return JSON.parse(text)
        } catch (e) {
          console.error("Invalid JSON response:", text)
          throw new Error("Server returned an invalid response. Please check server logs.")
        }
      })
    })
    .then((data) => {
      if (data.success) {
        const product = data.product

        // Check if product is already in cart
        const existingItemIndex = window.cartItems.findIndex((item) => item.id === product.id)

        if (existingItemIndex !== -1) {
          // Update quantity if product already in cart
          window.cartItems[existingItemIndex].quantity += 1
          window.cartItems[existingItemIndex].total =
            window.cartItems[existingItemIndex].quantity * window.cartItems[existingItemIndex].price
        } else {
          // Add new product to cart
          window.cartItems.push({
            id: product.id,
            barcode: product.barcode,
            name: product.product_name, // Using product_name from inventory table
            price: Number.parseFloat(product.sale_price),
            quantity: 1,
            total: Number.parseFloat(product.sale_price),
          })
        }

        // Update cart display
        updateCartDisplay()
        updateCartTotal()

        // Clear barcode input
        barcodeInput.value = ""
        barcodeInput.focus()

        showNotification(`${product.product_name} added to cart`, "success")
      } else {
        showNotification(data.message || "Product not found", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification(`Error fetching product data: ${error.message}`, "error")
    })
}

// Update cart display
function updateCartDisplay() {
  const cartBody = document.getElementById("cart-items-body")

  if (window.cartItems.length === 0) {
    cartBody.innerHTML = '<tr><td colspan="5" class="text-center">No items in cart</td></tr>'
    return
  }

  let html = ""

  window.cartItems.forEach((item, index) => {
    html += `
      <tr>
          <td>${item.name}</td>
          <td>${item.price.toFixed(2)}</td>
          <td>
              <div class="quantity-control">
                  <button type="button" class="quantity-btn" onclick="updateItemQuantity(${index}, -1)">-</button>
                  <input type="number" value="${item.quantity}" min="1" onchange="updateItemQuantityInput(${index}, this.value)">
                  <button type="button" class="quantity-btn" onclick="updateItemQuantity(${index}, 1)">+</button>
              </div>
          </td>
          <td>${item.total.toFixed(2)}</td>
          <td>
              <button type="button" class="action-btn delete-btn" onclick="removeCartItem(${index})">
                  <i class="fas fa-trash"></i>
              </button>
          </td>
      </tr>
    `
  })

  cartBody.innerHTML = html
}

// Update item quantity
function updateItemQuantity(index, change) {
  const newQuantity = window.cartItems[index].quantity + change

  if (newQuantity < 1) {
    return
  }

  window.cartItems[index].quantity = newQuantity
  window.cartItems[index].total = window.cartItems[index].quantity * window.cartItems[index].price

  updateCartDisplay()
  updateCartTotal()
}

// Update item quantity from input
function updateItemQuantityInput(index, value) {
  const quantity = Number.parseInt(value)

  if (isNaN(quantity) || quantity < 1) {
    updateCartDisplay() // Reset display to current values
    return
  }

  window.cartItems[index].quantity = quantity
  window.cartItems[index].total = window.cartItems[index].quantity * window.cartItems[index].price

  updateCartDisplay()
  updateCartTotal()
}

// Remove item from cart
function removeCartItem(index) {
  window.cartItems.splice(index, 1)
  updateCartDisplay()
  updateCartTotal()
}

// Update cart total
function updateCartTotal() {
  // Calculate subtotal
  window.subtotal = window.cartItems.reduce((sum, item) => sum + item.total, 0)

  // Get discount value and type
  const discountInput = document.getElementById("discount")
  const discountTypeSelect = document.getElementById("discount-type")

  window.discount = Number.parseFloat(discountInput.value) || 0
  window.discountType = discountTypeSelect.value

  // Calculate total based on discount type
  if (window.discountType === "percentage") {
    window.total = window.subtotal - window.subtotal * (window.discount / 100)
  } else {
    window.total = window.subtotal - window.discount
  }

  // Ensure total is not negative
  window.total = Math.max(window.total, 0)

  // Update display
  document.getElementById("subtotal").textContent = window.subtotal.toFixed(2)
  document.getElementById("total").textContent = window.total.toFixed(2)
}

// Complete sale
function completeSale() {
  if (window.cartItems.length === 0) {
    showNotification("Cart is empty", "error")
    return
  }

  // Get customer name and payment method
  const customerName = document.getElementById("customer-name").value.trim() || "Walk-in Customer"
  const paymentMethod = document.getElementById("payment-method").value

  // Prepare sale data
  const saleData = {
    customer_name: customerName,
    payment_method: paymentMethod,
    subtotal: window.subtotal,
    discount: window.discount,
    discount_type: window.discountType,
    total: window.total,
    items: window.cartItems,
  }

  // Show loading indicator
  showNotification("Processing sale...", "info")

  // Send data to server
  fetch("api/complete_sale.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(saleData),
  })
    .then((response) => {
      // Check if response is ok
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }

      // Try to parse as JSON, but handle non-JSON responses
      return response.text().then((text) => {
        try {
          return JSON.parse(text)
        } catch (e) {
          // If response is not valid JSON, throw an error with the response text
          console.error("Invalid JSON response:", text)
          throw new Error("Server returned an invalid response. Please check server logs.")
        }
      })
    })
    .then((data) => {
      if (data.success) {
        // Show receipt
        showReceipt(data.sale_id, data.transaction_id, customerName, saleData)

        // Clear cart
        clearCart()

        showNotification("Sale completed successfully", "success")
      } else {
        showNotification(data.message || "Error completing sale", "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification(`Error processing sale: ${error.message}`, "error")
    })
}

// Show receipt
function showReceipt(saleId, transactionId, customerName, saleData) {
  const receiptModal = document.getElementById("receipt-modal")
  const receiptContainer = document.getElementById("receipt-container")

  const date = new Date()
  const formattedDate = `${date.getDate().toString().padStart(2, "0")}/${(date.getMonth() + 1).toString().padStart(2, "0")}/${date.getFullYear()} ${date.getHours().toString().padStart(2, "0")}:${date.getMinutes().toString().padStart(2, "0")}`

  let itemsHtml = ""
  saleData.items.forEach((item) => {
    itemsHtml += `
      <div class="receipt-item">
          <div>${item.name} x ${item.quantity}</div>
          <div class="receipt-item-price">${item.total.toFixed(2)}</div>
      </div>
    `
  })

  let discountText = ""
  if (saleData.discount > 0) {
    if (saleData.discount_type === "percentage") {
      discountText = `Discount (${saleData.discount}%): ${(saleData.subtotal * (saleData.discount / 100)).toFixed(2)}`
    } else {
      discountText = `Discount: ${saleData.discount.toFixed(2)}`
    }
  }

  receiptContainer.innerHTML = `
    <div class="receipt-header">
        <h3>POS System</h3>
        <p>Receipt #${transactionId}</p>
        <p>${formattedDate}</p>
    </div>
    
    <div class="receipt-customer">
        <p>Customer: ${customerName}</p>
        <p>Payment: ${saleData.payment_method}</p>
    </div>
    
    <div class="receipt-items">
        <div class="receipt-divider"></div>
        ${itemsHtml}
        <div class="receipt-divider"></div>
    </div>
    
    <div class="receipt-summary">
        <div class="receipt-subtotal">
            <div>Subtotal:</div>
            <div>${saleData.subtotal.toFixed(2)}</div>
        </div>
        ${
          discountText
            ? `
            <div class="receipt-discount">
                <div>${discountText}</div>
            </div>
        `
            : ""
        }
        <div class="receipt-total">
            <div>Total:</div>
            <div>${saleData.total.toFixed(2)}</div>
        </div>
    </div>
    
    <div class="receipt-footer">
        <p>Thank you for your purchase!</p>
        <p>Sale ID: ${saleId}</p>
    </div>
  `

  receiptModal.style.display = "block"
}

// Clear cart
function clearCart() {
  window.cartItems = []
  updateCartDisplay()
  updateCartTotal()

  // Reset form fields
  document.getElementById("customer-name").value = ""
  document.getElementById("discount").value = "0"
  document.getElementById("discount-type").value = "percentage"
}

// Load sales history
function loadSalesHistory() {
  const searchInput = document.getElementById("sales-search")
  const dateFrom = document.getElementById("date-from")
  const dateTo = document.getElementById("date-to")

  const search = searchInput ? searchInput.value.trim() : ""
  const fromDate = dateFrom ? dateFrom.value : ""
  const toDate = dateTo ? dateTo.value : ""

  const params = new URLSearchParams()
  if (search) params.append("search", search)
  if (fromDate) params.append("from_date", fromDate)
  if (toDate) params.append("to_date", toDate)

  const salesHistoryBody = document.getElementById("sales-history-body")
  salesHistoryBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading sales history...</td></tr>'

  fetch(`api/get_sales.php?${params.toString()}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.text().then((text) => {
        try {
          return JSON.parse(text)
        } catch (e) {
          console.error("Invalid JSON response:", text)
          throw new Error("Server returned an invalid response. Please check server logs.")
        }
      })
    })
    .then((data) => {
      if (data.success) {
        if (data.sales.length === 0) {
          salesHistoryBody.innerHTML = '<tr><td colspan="7" class="text-center">No sales found</td></tr>'
          return
        }

        let html = ""

        data.sales.forEach((sale) => {
          html += `
            <tr>
                <td>${sale.transaction_id}</td>
                <td>${sale.customer_name}</td>
                <td>${sale.item_count}</td>
                <td>${Number.parseFloat(sale.final_amount).toFixed(2)}</td>
                <td>${sale.payment_method}</td>
                <td>${sale.sale_date}</td>
                <td>
                    <button type="button" class="action-btn view-btn" onclick="viewSaleDetails(${sale.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="action-btn print-btn" onclick="printSaleReceipt(${sale.id})">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>
          `
        })

        salesHistoryBody.innerHTML = html
      } else {
        salesHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center">${data.message || "Error loading sales"}</td></tr>`
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      salesHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center">Error loading sales history: ${error.message}</td></tr>`
    })
}

// View sale details
function viewSaleDetails(saleId) {
  const modal = document.getElementById("sale-details-modal")
  const container = document.getElementById("sale-details-container")

  container.innerHTML = '<div class="loading">Loading sale details...</div>'
  modal.style.display = "block"

  fetch(`api/get_sale_details.php?sale_id=${saleId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.text().then((text) => {
        try {
          return JSON.parse(text)
        } catch (e) {
          console.error("Invalid JSON response:", text)
          throw new Error("Server returned an invalid response. Please check server logs.")
        }
      })
    })
    .then((data) => {
      if (data.success) {
        const sale = data.sale
        const items = data.items

        let itemsHtml = ""
        items.forEach((item) => {
          itemsHtml += `
            <tr>
                <td>${item.product_name}</td>
                <td>${Number.parseFloat(item.unit_price).toFixed(2)}</td>
                <td>${item.quantity}</td>
                <td>${Number.parseFloat(item.total_price).toFixed(2)}</td>
            </tr>
          `
        })

        let discountHtml = ""
        if (Number.parseFloat(sale.discount) > 0) {
          if (sale.discount_type === "percentage") {
            discountHtml = `<p><strong>Discount:</strong> ${sale.discount}% (${(Number.parseFloat(sale.total_amount) * (Number.parseFloat(sale.discount) / 100)).toFixed(2)})</p>`
          } else {
            discountHtml = `<p><strong>Discount:</strong> ${Number.parseFloat(sale.discount).toFixed(2)}</p>`
          }
        }

        container.innerHTML = `
          <div class="sale-details">
              <div class="sale-info">
                  <p><strong>Transaction ID:</strong> ${sale.transaction_id}</p>
                  <p><strong>Customer:</strong> ${sale.customer_name}</p>
                  <p><strong>Date:</strong> ${sale.sale_date}</p>
                  <p><strong>Payment Method:</strong> ${sale.payment_method}</p>
                  <p><strong>Subtotal:</strong> ${Number.parseFloat(sale.total_amount).toFixed(2)}</p>
                  ${discountHtml}
                  <p><strong>Total:</strong> ${Number.parseFloat(sale.final_amount).toFixed(2)}</p>
              </div>
              
              <div class="sale-items">
                  <h3>Items</h3>
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>Product</th>
                              <th>Price</th>
                              <th>Quantity</th>
                              <th>Total</th>
                          </tr>
                      </thead>
                      <tbody>
                          ${itemsHtml}
                      </tbody>
                  </table>
              </div>
          </div>
        `
      } else {
        container.innerHTML = `<div class="error">${data.message || "Error loading sale details"}</div>`
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      container.innerHTML = `<div class="error">Error loading sale details: ${error.message}</div>`
    })
}

// Print sale receipt
function printSaleReceipt(saleId) {
  fetch(`api/get_sale_details.php?sale_id=${saleId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.text().then((text) => {
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON response:", text);
          throw new Error("Server returned an invalid response. Please check server logs.");
        }
      });
    })
    .then((data) => {
      if (data.success) {
        const sale = data.sale;
        const items = data.items;

        let itemsHtml = "";
        items.forEach((item) => {
          itemsHtml += `
            <div class="receipt-item">
                <div>${item.product_name} x ${item.quantity}</div>
                <div class="receipt-item-price">${Number.parseFloat(item.total_price).toFixed(2)}</div>
            </div>
          `;
        });

        let discountText = "";
        if (Number.parseFloat(sale.discount) > 0) {
          if (sale.discount_type === "percentage") {
            discountText = `Discount (${sale.discount}%): ${(Number.parseFloat(sale.total_amount) * (Number.parseFloat(sale.discount) / 100)).toFixed(2)}`;
          } else {
            discountText = `Discount: ${Number.parseFloat(sale.discount).toFixed(2)}`;
          }
        }

        const receiptHtml = `
          <div class="receipt-header">
              <h3>POS System</h3>
              <p>Receipt #${sale.transaction_id}</p>
              <p>${sale.sale_date}</p>
          </div>
          
          <div class="receipt-customer">
              <p>Customer: ${sale.customer_name}</p>
              <p>Payment: ${sale.payment_method}</p>
          </div>
          
          <div class="receipt-items">
              <div class="receipt-divider"></div>
              ${itemsHtml}
              <div class="receipt-divider"></div>
          </div>
          
          <div class="receipt-summary">
              <div class="receipt-subtotal">
                  <div>Subtotal:</div>
                  <div>${Number.parseFloat(sale.total_amount).toFixed(2)}</div>
              </div>
              ${
                discountText
                  ? `
                  <div class="receipt-discount">
                      <div>${discountText}</div>
                  </div>
              `
                  : ""
              }
              <div class="receipt-total">
                  <div>Total:</div>
                  <div>${Number.parseFloat(sale.final_amount).toFixed(2)}</div>
              </div>
          </div>
          
          <div class="receipt-footer">
              <p>Thank you for your purchase!</p>
              <p>Sale ID: ${sale.id}</p>
          </div>
        `;

        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
          <html>
          <head>
              <title>Receipt</title>
              <style>
                  body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; }
                  .receipt { width: 72.1mm; padding: 5mm; box-sizing: border-box; }
                  .receipt-header, .receipt-footer { text-align: center; margin-bottom: 10px; }
                  .receipt-item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                  .receipt-divider { border-top: 1px dashed #000; margin: 5px 0; }
                  .receipt-summary > div { display: flex; justify-content: space-between; margin-top: 5px; }
                  .receipt-total { font-weight: bold; }
                  @media print {
                      @page {
                          size: 72.1mm 210mm;
                          margin: 0;
                      }
                      body {
                          margin: 0;
                      }
                  }
              </style>
          </head>
          <body>
              <div class="receipt">${receiptHtml}</div>
              <script>
                  window.onload = function() {
                      window.print();
                      window.close();
                  };
              </script>
          </body>
          </html>
        `);
        printWindow.document.close();
      } else {
        showNotification(data.message || "Error loading sale details", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Error loading sale details: " + error.message, "error");
    });
}

// Show notification
function showNotification(message, type = "info") {
  // Check if notification container exists, create if not
  let notificationContainer = document.getElementById("notification-container")

  if (!notificationContainer) {
    notificationContainer = document.createElement("div")
    notificationContainer.id = "notification-container"
    notificationContainer.style.position = "fixed"
    notificationContainer.style.top = "20px"
    notificationContainer.style.right = "20px"
    notificationContainer.style.zIndex = "9999"
    document.body.appendChild(notificationContainer)
  }

  // Create notification element
  const notification = document.createElement("div")
  notification.className = `notification ${type}`
  notification.innerHTML = `
      <div class="notification-content">
          <span class="notification-message">${message}</span>
          <button class="notification-close">&times;</button>
      </div>
  `

  // Style the notification
  notification.style.backgroundColor = type === "success" ? "#4CAF50" : type === "error" ? "#F44336" : "#2196F3"
  notification.style.color = "white"
  notification.style.padding = "12px 16px"
  notification.style.marginBottom = "10px"
  notification.style.borderRadius = "4px"
  notification.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)"
  notification.style.display = "flex"
  notification.style.justifyContent = "space-between"
  notification.style.alignItems = "center"
  notification.style.minWidth = "250px"
  notification.style.maxWidth = "350px"
  notification.style.animation = "slideIn 0.3s ease-out forwards"

  // Add close button event
  const closeButton = notification.querySelector(".notification-close")
  closeButton.style.background = "none"
  closeButton.style.border = "none"
  closeButton.style.color = "white"
  closeButton.style.fontSize = "20px"
  closeButton.style.cursor = "pointer"
  closeButton.style.marginLeft = "10px"

  closeButton.addEventListener("click", () => {
    notification.style.animation = "slideOut 0.3s ease-out forwards"
    setTimeout(() => {
      notificationContainer.removeChild(notification)
    }, 300)
  })

  // Add notification to container
  notificationContainer.appendChild(notification)

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode === notificationContainer) {
      notification.style.animation = "slideOut 0.3s ease-out forwards"
      setTimeout(() => {
        if (notification.parentNode === notificationContainer) {
          notificationContainer.removeChild(notification)
        }
      }, 300)
    }
  }, 5000)

  // Add CSS animations
  const style = document.createElement("style")
  style.textContent = `
      @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
          from { transform: translateX(0); opacity: 1; }
          to { transform: translateX(100%); opacity: 0; }
      }
  `
  document.head.appendChild(style)
}

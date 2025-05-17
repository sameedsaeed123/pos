document.addEventListener("DOMContentLoaded", () => {
  // Debug mode - set to false in production
  const DEBUG = true

  function debug(message, data) {
    if (DEBUG) {
      console.log(`[Returns] ${message}`, data || "")
    }
  }

  debug("Returns.js loaded")

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

      // Load returns history when switching to that tab
      if (target === "returns-history") {
        loadReturnsHistory()
      }
    })
  })

  // Find sale form
  const findSaleForm = document.getElementById("find-sale-form")
  if (findSaleForm) {
    findSaleForm.addEventListener("submit", (e) => {
      e.preventDefault()
      const transactionId = document.getElementById("transaction-id").value.trim()

      if (!transactionId) {
        showMessage("error", "Please enter a transaction ID")
        return
      }

      findSale(transactionId)
    })
  }

  // Process return button
  const processReturnBtn = document.getElementById("process-return-btn")
  if (processReturnBtn) {
    processReturnBtn.addEventListener("click", processReturn)
  }

  // Filter returns button
  const filterReturnsBtn = document.getElementById("filter-returns-btn")
  if (filterReturnsBtn) {
    filterReturnsBtn.addEventListener("click", () => {
      loadReturnsHistory()
    })
  }

  // Modal close buttons
  const closeModalButtons = document.querySelectorAll(".close-modal")
  if (closeModalButtons) {
    closeModalButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const modal = button.closest(".modal")
        if (modal) {
          modal.style.display = "none"
        }
      })
    })
  }

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    document.querySelectorAll(".modal").forEach((modal) => {
      if (e.target === modal) {
        modal.style.display = "none"
      }
    })
  })
})

// Find sale by transaction ID
function findSale(transactionId) {
  showMessage("info", "Searching for sale...")

  fetch(`api/get_sale_by_transaction.php?transaction_id=${encodeURIComponent(transactionId)}`)
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
        displaySaleDetails(data.sale, data.items)
      } else {
        showMessage("error", data.message || "Sale not found")
        document.getElementById("sale-details").style.display = "none"
      }
    })
    .catch((error) => {
      console.error("Error finding sale:", error)
      showMessage("error", "Error finding sale. Please try again.")
      document.getElementById("sale-details").style.display = "none"
    })
}

// Display sale details for return
function displaySaleDetails(sale, items) {
  const saleDetails = document.getElementById("sale-details")
  if (!saleDetails) return

  // Display customer name
  document.getElementById("customer-name-display").textContent = sale.customer_name || "Walk-in Customer"

  // Display sale date
  const saleDate = new Date(sale.sale_date)
  document.getElementById("sale-date-display").textContent = saleDate.toLocaleString()

  // Display sale total
  document.getElementById("sale-total-display").textContent = `PKR ${Number.parseFloat(sale.final_amount).toFixed(2)}`

  // Display sale items
  const saleItemsBody = document.getElementById("sale-items-body")
  saleItemsBody.innerHTML = ""

  items.forEach((item) => {
    // Check if the item has been partially or fully returned
    const returnedQuantity = item.quantity_returned || 0
    const availableQuantity = item.quantity - returnedQuantity

    // Skip items that have been fully returned
    if (availableQuantity <= 0) {
      return
    }

    const row = document.createElement("tr")

    row.innerHTML = `
      <td>${item.product_name || "Unknown Product"}</td>
      <td>PKR ${Number.parseFloat(item.unit_price).toFixed(2)}</td>
      <td>${item.quantity} (${availableQuantity} available)</td>
      <td>PKR ${Number.parseFloat(item.total_price).toFixed(2)}</td>
      <td>
        <input type="number" class="return-quantity" 
               data-item-id="${item.id}" 
               data-product-id="${item.product_id}"
               data-price="${item.unit_price}"
               min="0" max="${availableQuantity}" value="0">
      </td>
    `

    saleItemsBody.appendChild(row)
  })

  // Store sale ID for later use
  saleDetails.dataset.saleId = sale.id
  saleDetails.dataset.transactionId = sale.transaction_id
  saleDetails.dataset.customerName = sale.customer_name || "Walk-in Customer"

  // Show sale details
  saleDetails.style.display = "block"

  showMessage("success", "Sale found")
}

// Process return
function processReturn() {
  const saleDetails = document.getElementById("sale-details")
  if (!saleDetails || !saleDetails.dataset.saleId) {
    showMessage("error", "No sale selected")
    return
  }

  const saleId = saleDetails.dataset.saleId
  const transactionId = saleDetails.dataset.transactionId
  const customerName = saleDetails.dataset.customerName
  const returnReason = document.getElementById("return-reason").value.trim()

  if (!returnReason) {
    showMessage("error", "Please provide a reason for the return")
    return
  }

  // Get return items
  const returnItems = []
  document.querySelectorAll(".return-quantity").forEach((input) => {
    const quantity = Number.parseInt(input.value)
    if (quantity > 0) {
      returnItems.push({
        item_id: input.dataset.itemId,
        product_id: input.dataset.productId,
        quantity: quantity,
        price: Number.parseFloat(input.dataset.price),
      })
    }
  })

  if (returnItems.length === 0) {
    showMessage("error", "Please select at least one item to return")
    return
  }

  // Prepare return data
  const returnData = {
    sale_id: saleId,
    transaction_id: transactionId,
    customer_name: customerName,
    reason: returnReason,
    items: returnItems,
  }

  showMessage("info", "Processing return...")

  // Send return data to server
  console.log("Return data being sent:", returnData) // Log the return data
  fetch("api/process_return.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(returnData),
  })
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
        showMessage("success", "Return processed successfully")

        // Reset form
        document.getElementById("find-sale-form").reset()
        document.getElementById("return-reason").value = ""
        document.getElementById("sale-details").style.display = "none"

        // Reload returns history if visible
        if (document.getElementById("returns-history").classList.contains("active")) {
          loadReturnsHistory()
        }
      } else {
        showMessage("error", data.message || "Error processing return")
      }
    })
    
}

// Load returns history
function loadReturnsHistory() {
  const returnsSearch = document.getElementById("returns-search")
  const dateFrom = document.getElementById("return-date-from")
  const dateTo = document.getElementById("return-date-to")

  const search = returnsSearch ? returnsSearch.value.trim() : ""
  const fromDate = dateFrom ? dateFrom.value : ""
  const toDate = dateTo ? dateTo.value : ""

  const params = new URLSearchParams()
  if (search) params.append("search", search)
  if (fromDate) params.append("from_date", fromDate)
  if (toDate) params.append("to_date", toDate)

  const returnsHistoryBody = document.getElementById("returns-history-body")
  if (!returnsHistoryBody) return

  returnsHistoryBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading returns history...</td></tr>'

  fetch(`api/get_returns.php?${params.toString()}`)
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
        if (data.returns.length === 0) {
          returnsHistoryBody.innerHTML = '<tr><td colspan="6" class="text-center">No returns found</td></tr>'
          return
        }

        returnsHistoryBody.innerHTML = ""

        data.returns.forEach((returnData) => {
          const row = document.createElement("tr")

          // Format date
          const returnDate = new Date(returnData.return_date)
          const formattedDate = returnDate.toLocaleString()

          row.innerHTML = `
            <td>${returnData.return_id}</td>
            <td>${returnData.transaction_id}</td>
            <td>${returnData.item_count}</td>
            <td>PKR ${Number.parseFloat(returnData.total_amount).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
              <button class="action-btn" onclick="viewReturnDetails(${returnData.id})">
                <i class="fas fa-eye"></i> View
              </button>
            </td>
          `

          returnsHistoryBody.appendChild(row)
        })
      } else {
        returnsHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center">${data.message || "Error loading returns"}</td></tr>`
      }
    })
    .catch((error) => {
      console.error("Error loading returns history:", error)
      returnsHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center">Error loading returns history: ${error.message}</td></tr>`
    })
}

// View return details
window.viewReturnDetails = (returnId) => {
  const modal = document.getElementById("return-details-modal")
  const container = document.getElementById("return-details-container")

  if (!modal || !container) return

  container.innerHTML = '<div class="loading">Loading return details...</div>'
  modal.style.display = "block"

  fetch(`api/get_return_details.php?return_id=${returnId}`)
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
        const returnData = data.return
        const items = data.items

        let itemsHtml = ""
        items.forEach((item) => {
          itemsHtml += `
            <tr>
              <td>${item.product_name}</td>
              <td>PKR ${Number.parseFloat(item.unit_price).toFixed(2)}</td>
              <td>${item.quantity}</td>
              <td>PKR ${Number.parseFloat(item.total_price).toFixed(2)}</td>
            </tr>
          `
        })

        // Format date
        const returnDate = new Date(returnData.return_date)
        const formattedDate = returnDate.toLocaleString()

        container.innerHTML = `
          <div class="return-details">
            <div class="return-info">
              <p><strong>Return ID:</strong> ${returnData.return_id}</p>
              <p><strong>Transaction ID:</strong> ${returnData.transaction_id}</p>
              <p><strong>Date:</strong> ${formattedDate}</p>
              <p><strong>Reason:</strong> ${returnData.reason}</p>
              <p><strong>Total Amount:</strong> PKR ${Number.parseFloat(returnData.total_amount).toFixed(2)}</p>
            </div>
            
            <div class="return-items">
              <h3>Returned Items</h3>
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
        container.innerHTML = `<div class="error">${data.message || "Error loading return details"}</div>`
      }
    })
    .catch((error) => {
      console.error("Error loading return details:", error)
      container.innerHTML = `<div class="error">Error loading return details: ${error.message}</div>`
    })
}

// Show message function
function showMessage(type, text) {
  // Check if notification container exists
  let container = document.getElementById("notification-container")

  if (!container) {
    container = document.createElement("div")
    container.id = "notification-container"
    container.style.position = "fixed"
    container.style.top = "20px"
    container.style.right = "20px"
    container.style.zIndex = "9999"
    document.body.appendChild(container)
  }

  const notification = document.createElement("div")
  notification.className = `notification ${type}`

  // Style the notification
  notification.style.backgroundColor = type === "success" ? "#4CAF50" : type === "error" ? "#F44336" : "#2196F3"
  notification.style.color = "white"
  notification.style.padding = "15px 20px"
  notification.style.marginBottom = "10px"
  notification.style.borderRadius = "4px"
  notification.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)"
  notification.style.display = "flex"
  notification.style.justifyContent = "space-between"
  notification.style.alignItems = "center"
  notification.style.minWidth = "250px"
  notification.style.maxWidth = "350px"
  notification.style.animation = "slideIn 0.3s ease-out forwards"

  notification.innerHTML = `
    <div>${text}</div>
    <button style="background:none; border:none; color:white; font-size:20px; cursor:pointer; margin-left:10px;">&times;</button>
  `

  // Add close button functionality
  const closeButton = notification.querySelector("button")
  closeButton.addEventListener("click", () => {
    notification.style.animation = "slideOut 0.3s ease-out forwards"
    setTimeout(() => {
      if (notification.parentNode === container) {
        container.removeChild(notification)
      }
    }, 300)
  })

  container.appendChild(notification)

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode === container) {
      notification.style.animation = "slideOut 0.3s ease-out forwards"
      setTimeout(() => {
        if (notification.parentNode === container) {
          container.removeChild(notification)
        }
      }, 300)
    }
  }, 5000)

  // Add CSS animations if they don't exist
  if (!document.getElementById("notification-styles")) {
    const style = document.createElement("style")
    style.id = "notification-styles"
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
}

document.addEventListener("DOMContentLoaded", () => {
    // Debug mode - set to false in production
    const DEBUG = true
  
    function debug(message, data) {
      if (DEBUG) {
        console.log(`[Sales] ${message}`, data || "")
      }
    }
  
    debug("Sales.js loaded")
  
    // Load products
    loadProducts()
  
    // Load accounts for dropdown
    loadAccountsDropdown()
  
    // Search product
    const searchInput = document.getElementById("product-search")
    if (searchInput) {
      searchInput.addEventListener("input", () => {
        const searchTerm = searchInput.value.trim()
        if (searchTerm.length >= 2) {
          searchProducts(searchTerm)
        } else if (searchTerm.length === 0) {
          loadProducts()
        }
      })
    }
  
    // Barcode scanner input
    const barcodeInput = document.getElementById("barcode-input")
    if (barcodeInput) {
      barcodeInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault()
          const barcode = barcodeInput.value.trim()
          if (barcode) {
            searchByBarcode(barcode)
            barcodeInput.value = ""
          }
        }
      })
    }
  
    // Process sale button
    const processSaleBtn = document.getElementById("process-sale-btn")
    if (processSaleBtn) {
      processSaleBtn.addEventListener("click", processSale)
    }
  
    // Clear cart button
    const clearCartBtn = document.getElementById("clear-cart-btn")
    if (clearCartBtn) {
      clearCartBtn.addEventListener("click", clearCart)
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
  
    // Account selection change
    const accountSelect = document.getElementById("account-select")
    if (accountSelect) {
      accountSelect.addEventListener("change", updatePaymentOptions)
    }
  })
  
  // Load accounts for dropdown
  function loadAccountsDropdown() {
    const accountSelect = document.getElementById("account-select")
    if (!accountSelect) return
  
    fetch("api/get_accounts_dropdown.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }
        return response.json()
      })
      .then((data) => {
        if (data.success) {
          // Add default option
          accountSelect.innerHTML = '<option value="">No Account (Cash Sale)</option>'
  
          // Add accounts
          data.accounts.forEach((account) => {
            const balance = Number.parseFloat(account.balance)
       
            const availableCredit = balance + creditLimit
  
            const option = document.createElement("option")
            option.value = account.id
            option.textContent = `${account.name} (Balance: PKR ${balance.toFixed(2)}, Available: PKR ${availableCredit.toFixed(2)})`
            option.dataset.balance = balance
            option.dataset.creditLimit = creditLimit
  
            accountSelect.appendChild(option)
          })
        } else {
          showMessage("error", data.message || "Error loading accounts")
        }
      })
      .catch((error) => {
        console.error("Error loading accounts:", error)
        showMessage("error", "Error loading accounts. Please try again.")
      })
  }
  
  // Update payment options based on account selection
  function updatePaymentOptions() {
    const accountSelect = document.getElementById("account-select")
    const paymentMethodSelect = document.getElementById("payment-method")
    const paymentStatusSelect = document.getElementById("payment-status")
  
    if (!accountSelect || !paymentMethodSelect || !paymentStatusSelect) return
  
    const selectedAccount = accountSelect.value
  
    if (selectedAccount) {
      // If account is selected, set payment method to "account" and status to "paid"
      paymentMethodSelect.value = "account"
      paymentStatusSelect.value = "paid"
  
      // Disable payment method and status selection
      paymentMethodSelect.disabled = true
      paymentStatusSelect.disabled = true
    } else {
      // If no account is selected, enable payment method and status selection
      paymentMethodSelect.disabled = false
      paymentStatusSelect.disabled = false
  
      // Reset to default values
      paymentMethodSelect.value = "cash"
      paymentStatusSelect.value = "paid"
    }
  }
  
  // Process sale
  function processSale() {
    const cartItems = getCartItems()
    if (cartItems.length === 0) {
      showMessage("error", "Cart is empty")
      return
    }
  
    const customerName = document.getElementById("customer-name").value.trim() || "Walk-in Customer"
    const accountId = document.getElementById("account-select").value
    const paymentMethod = document.getElementById("payment-method").value
    const paymentStatus = document.getElementById("payment-status").value
    const subtotal = calculateSubtotal()
    const discount = Number.parseFloat(document.getElementById("discount-amount").value) || 0
    const tax = Number.parseFloat(document.getElementById("tax-amount").value) || 0
    const finalAmount = calculateFinalAmount()
  
    // Validate if using account
    if (accountId) {
      const selectedOption = document.getElementById("account-select").selectedOptions[0]
      const balance = Number.parseFloat(selectedOption.dataset.balance)
      const creditLimit = Number.parseFloat(selectedOption.dataset.creditLimit)
      const availableCredit = balance + creditLimit
  
    
    }
  
    const saleData = {
      customer_name: customerName,
      account_id: accountId,
      payment_method: paymentMethod,
      payment_status: paymentStatus,
      subtotal: subtotal,
      discount: discount,
      tax: tax,
      final_amount: finalAmount,
      items: cartItems,
    }
  
    showMessage("info", "Processing sale...")
  
    fetch("api/process_sale_with_account.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(saleData),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }
        return response.json()
      })
      .then((data) => {
        if (data.success) {
          showMessage("success", "Sale processed successfully")
  
          // Show receipt
          showReceipt(
            data.transaction_id,
            customerName,
            cartItems,
            subtotal,
            discount,
            tax,
            finalAmount,
            paymentMethod,
            paymentStatus,
          )
  
          // Clear cart
          clearCart()
  
          // Reset form
          document.getElementById("customer-name").value = ""
          document.getElementById("account-select").value = ""
          document.getElementById("payment-method").value = "cash"
          document.getElementById("payment-status").value = "paid"
          document.getElementById("discount-amount").value = "0"
          document.getElementById("tax-amount").value = "0"
  
          // Update payment options
          updatePaymentOptions()
        } else {
          showMessage("error", data.message || "Error processing sale")
        }
      })
      .catch((error) => {
        console.error("Error processing sale:", error)
        showMessage("error", "Error processing sale. Please try again.")
      })
  }
  
  // Rest of the sales.js functions remain the same
  
  // Mock functions to resolve undeclared variable errors.  These should be replaced with actual implementations.
  function loadProducts() {
    console.warn("loadProducts() is a mock function. Replace with actual implementation.")
  }
  
  function searchProducts(searchTerm) {
    console.warn("searchProducts() is a mock function. Replace with actual implementation.")
  }
  
  function searchByBarcode(barcode) {
    console.warn("searchByBarcode() is a mock function. Replace with actual implementation.")
  }
  
  function clearCart() {
    console.warn("clearCart() is a mock function. Replace with actual implementation.")
  }
  
  function showMessage(type, message) {
    console.warn("showMessage() is a mock function. Replace with actual implementation.")
    alert(`${type}: ${message}`) // Basic alert for demonstration
  }
  
  function getCartItems() {
    console.warn("getCartItems() is a mock function. Replace with actual implementation.")
    return [] // Return an empty array for demonstration
  }
  
  function calculateSubtotal() {
    console.warn("calculateSubtotal() is a mock function. Replace with actual implementation.")
    return 0 // Return 0 for demonstration
  }
  
  function calculateFinalAmount() {
    console.warn("calculateFinalAmount() is a mock function. Replace with actual implementation.")
    return 0 // Return 0 for demonstration
  }
  
  function showReceipt(
    transaction_id,
    customerName,
    cartItems,
    subtotal,
    discount,
    tax,
    finalAmount,
    paymentMethod,
    paymentStatus,
  ) {
    console.warn("showReceipt() is a mock function. Replace with actual implementation.")
    alert(`Receipt for Transaction ID: ${transaction_id}`) // Basic alert for demonstration
  }
  
document.addEventListener("DOMContentLoaded", () => {
  // Debug mode - set to false in production
  const DEBUG = true

  function debug(message, data) {
    if (DEBUG) {
      console.log(`[Accounts] ${message}`, data || "")
    }
  }

  debug("Accounts.js loaded")

  // Load accounts
  loadAccounts()

  // Search functionality
  const searchInput = document.getElementById("account-search")
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      loadAccounts(searchInput.value)
    })
  }

  // Add account button
  const addAccountBtn = document.getElementById("add-account-btn")
  if (addAccountBtn) {
    addAccountBtn.addEventListener("click", () => {
      openAddAccountModal()
    })
  }

  // Account form submission
  const accountForm = document.getElementById("account-form")
  if (accountForm) {
    accountForm.addEventListener("submit", (e) => {
      e.preventDefault()
      saveAccount()
    })
  }

  // Add balance form submission
  const addBalanceForm = document.getElementById("add-balance-form")
  if (addBalanceForm) {
    addBalanceForm.addEventListener("submit", (e) => {
      e.preventDefault()
      addBalance()
    })
  }

  // Generate statement button
  const generateStatementBtn = document.getElementById("generate-statement-btn")
  if (generateStatementBtn) {
    generateStatementBtn.addEventListener("click", () => {
      generateAccountStatement()
    })
  }

  // Print statement button
  const printStatementBtn = document.getElementById("print-statement-btn")
  if (printStatementBtn) {
    printStatementBtn.addEventListener("click", () => {
      printAccountStatement()
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

// Load accounts
function loadAccounts(search = "") {
  const accountsContainer = document.getElementById("accounts-container")
  if (!accountsContainer) return

  accountsContainer.innerHTML = '<div class="loading">Loading accounts...</div>'

  fetch(`api/get_accounts.php${search ? `?search=${encodeURIComponent(search)}` : ""}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        if (data.accounts.length === 0) {
          accountsContainer.innerHTML = '<div class="no-data">No accounts found</div>'
          return
        }

        accountsContainer.innerHTML = ""

        data.accounts.forEach((account) => {
          const balance = Number.parseFloat(account.balance)

          const accountCard = document.createElement("div")
          accountCard.className = "account-card"
          accountCard.innerHTML = `
            <div class="account-header">
              <div class="account-name">${account.name}</div>
              <div class="account-balance ${balance < 0 ? "negative" : ""}">${formatCurrency(balance)}</div>
            </div>
            <div class="account-details">
              <div>${account.contact || "No contact"}</div>
              <div>${account.email || "No email"}</div>
            </div>
            <div class="account-actions">
              <button class="action-btn view-account-btn" data-id="${account.id}">
                <i class="fas fa-eye"></i> View
              </button>
              <button class="action-btn edit-account-btn" data-id="${account.id}">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="action-btn add-balance-btn" data-id="${account.id}" data-name="${account.name}" data-balance="${account.balance}">
                <i class="fas fa-plus-circle"></i> Add Balance
              </button>
              <button class="action-btn statement-btn" data-id="${account.id}" data-name="${account.name}">
                <i class="fas fa-file-invoice"></i> Statement
              </button>
            </div>
          `

          accountsContainer.appendChild(accountCard)

          // Add event listeners
          const viewBtn = accountCard.querySelector(".view-account-btn")
          if (viewBtn) {
            viewBtn.addEventListener("click", () => {
              viewAccountDetails(account.id)
            })
          }

          const editBtn = accountCard.querySelector(".edit-account-btn")
          if (editBtn) {
            editBtn.addEventListener("click", () => {
              editAccount(account.id)
            })
          }

          const addBalanceBtn = accountCard.querySelector(".add-balance-btn")
          if (addBalanceBtn) {
            addBalanceBtn.addEventListener("click", () => {
              openAddBalanceModal(account.id, account.name, account.balance)
            })
          }

          const statementBtn = accountCard.querySelector(".statement-btn")
          if (statementBtn) {
            statementBtn.addEventListener("click", () => {
              openAccountStatementModal(account.id, account.name)
            })
          }
        })
      } else {
        accountsContainer.innerHTML = `<div class="error">${data.message || "Error loading accounts"}</div>`
      }
    })
    .catch((error) => {
      console.error("Error loading accounts:", error)
      accountsContainer.innerHTML = `<div class="error">Error loading accounts: ${error.message}</div>`
    })
}

// Open add account modal
function openAddAccountModal() {
  const modal = document.getElementById("account-modal")
  const modalTitle = document.getElementById("account-modal-title")
  const form = document.getElementById("account-form")
  const initialBalanceGroup = document.getElementById("initial-balance-group")

  if (!modal || !modalTitle || !form || !initialBalanceGroup) return

  modalTitle.textContent = "Add New Account"
  form.reset()
  document.getElementById("account-id").value = ""
  initialBalanceGroup.style.display = "block"

  modal.style.display = "block"
}

// Edit account
function editAccount(accountId) {
  const modal = document.getElementById("account-modal")
  const modalTitle = document.getElementById("account-modal-title")
  const form = document.getElementById("account-form")
  const initialBalanceGroup = document.getElementById("initial-balance-group")

  if (!modal || !modalTitle || !form || !initialBalanceGroup) return

  modalTitle.textContent = "Edit Account"
  form.reset()
  initialBalanceGroup.style.display = "none"

  // Fetch account details
  fetch(`api/get_account.php?id=${accountId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        const account = data.account
        document.getElementById("account-id").value = account.id
        document.getElementById("account-name").value = account.name
        document.getElementById("account-contact").value = account.contact || ""
        document.getElementById("account-email").value = account.email || ""

        modal.style.display = "block"
      } else {
        showMessage("error", data.message || "Error loading account details")
      }
    })
    .catch((error) => {
      console.error("Error loading account details:", error)
      showMessage("error", "Error loading account details. Please try again.")
    })
}

// Save account
function saveAccount() {
  const accountId = document.getElementById("account-id").value
  const name = document.getElementById("account-name").value
  const contact = document.getElementById("account-contact").value
  const email = document.getElementById("account-email").value
  const initialBalance = document.getElementById("account-initial-balance").value

  if (!name) {
    showMessage("error", "Account name is required")
    return
  }

  const accountData = {
    id: accountId,
    name: name,
    contact: contact,
    email: email,
  }

  if (!accountId) {
    accountData.initial_balance = initialBalance
  }

  const url = accountId ? "api/update_account.php" : "api/add_account.php"

  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(accountData),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showMessage("success", data.message || "Account saved successfully")
        document.getElementById("account-modal").style.display = "none"
        loadAccounts()
      } else {
        showMessage("error", data.message || "Error saving account")
      }
    })
    .catch((error) => {
      console.error("Error saving account:", error)
      showMessage("error", "Error saving account. Please try again.")
    })
}

// Open add balance modal
function openAddBalanceModal(accountId, accountName, balance) {
  const modal = document.getElementById("add-balance-modal")
  const form = document.getElementById("add-balance-form")
  const accountNameDisplay = document.getElementById("account-name-display")
  const currentBalanceDisplay = document.getElementById("current-balance-display")

  if (!modal || !form || !accountNameDisplay || !currentBalanceDisplay) return

  form.reset()
  document.getElementById("balance-account-id").value = accountId
  accountNameDisplay.textContent = accountName
  currentBalanceDisplay.textContent = formatCurrency(balance)

  modal.style.display = "block"
}

// Add balance
function addBalance() {
  const accountId = document.getElementById("balance-account-id").value
  const amount = document.getElementById("add-balance-amount").value
  const notes = document.getElementById("add-balance-notes").value

  if (!accountId || !amount || Number.parseFloat(amount) <= 0) {
    showMessage("error", "Please enter a valid amount")
    return
  }

  const balanceData = {
    account_id: accountId,
    amount: amount,
    notes: notes,
  }

  fetch("api/add_account_balance.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(balanceData),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showMessage("success", data.message || "Balance added successfully")
        document.getElementById("add-balance-modal").style.display = "none"
        loadAccounts()
      } else {
        showMessage("error", data.message || "Error adding balance")
      }
    })
    .catch((error) => {
      console.error("Error adding balance:", error)
      showMessage("error", "Error adding balance. Please try again.")
    })
}

// View account details
function viewAccountDetails(accountId) {
  const modal = document.getElementById("account-details-modal")
  const container = document.getElementById("account-details-container")

  if (!modal || !container) return

  container.innerHTML = '<div class="loading">Loading account details...</div>'
  modal.style.display = "block"

  fetch(`api/get_account.php?id=${accountId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        const account = data.account
        const transactions = data.transactions

        let transactionsHtml = ""
        if (transactions.length === 0) {
          transactionsHtml = '<div class="no-data">No transactions found</div>'
        } else {
          transactionsHtml = '<div class="transaction-history">'
          transactions.forEach((transaction) => {
            const amount = Number.parseFloat(transaction.amount)
            const transactionType = transaction.transaction_type
            let amountClass = ""
            let amountPrefix = ""

            if (transactionType === "deposit") {
              amountClass = "deposit"
              amountPrefix = "+"
            } else if (transactionType === "sale") {
              amountClass = "sale"
              amountPrefix = "-"
            } else if (transactionType === "return") {
              amountClass = "return"
              amountPrefix = "+"
            }

            const transactionDate = new Date(transaction.transaction_date)
            const formattedDate = transactionDate.toLocaleString()

            transactionsHtml += `
              <div class="transaction-item">
                <div>
                  <div>${formattedDate}</div>
                  <div>${transaction.transaction_type.charAt(0).toUpperCase() + transaction.transaction_type.slice(1)}</div>
                  <div>${transaction.notes || ""}</div>
                  ${transaction.reference_id ? `<div>Ref: ${transaction.reference_id}</div>` : ""}
                </div>
                <div class="transaction-amount ${amountClass}">
                  ${amountPrefix}${formatCurrency(amount)}
                </div>
              </div>
            `
          })
          transactionsHtml += "</div>"
        }

        container.innerHTML = `
          <div class="account-details-header">
            <h3>${account.name}</h3>
            <div class="account-balance ${Number.parseFloat(account.balance) < 0 ? "negative" : ""}">${formatCurrency(account.balance)}</div>
          </div>
          <div class="account-info">
            <div><strong>Contact:</strong> ${account.contact || "Not provided"}</div>
            <div><strong>Email:</strong> ${account.email || "Not provided"}</div>
            <div><strong>Created:</strong> ${new Date(account.created_at).toLocaleString()}</div>
          </div>
          <h4>Transaction History</h4>
          ${transactionsHtml}
        `
      } else {
        container.innerHTML = `<div class="error">${data.message || "Error loading account details"}</div>`
      }
    })
    .catch((error) => {
      console.error("Error loading account details:", error)
      container.innerHTML = `<div class="error">Error loading account details: ${error.message}</div>`
    })
}

// Open account statement modal
function openAccountStatementModal(accountId, accountName) {
  const modal = document.getElementById("account-statement-modal")
  const container = document.getElementById("account-statement-container")

  if (!modal || !container) return

  // Set default date range (current month)
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)

  document.getElementById("statement-from-date").value = formatDateForInput(firstDay)
  document.getElementById("statement-to-date").value = formatDateForInput(today)

  // Store account ID and name for later use
  modal.dataset.accountId = accountId
  modal.dataset.accountName = accountName

  container.innerHTML = '<div class="message">Select date range and click "Generate Statement"</div>'
  modal.style.display = "block"
}

// Generate account statement
function generateAccountStatement() {
  const modal = document.getElementById("account-statement-modal")
  const container = document.getElementById("account-statement-container")

  if (!modal || !container) return

  const accountId = modal.dataset.accountId
  const accountName = modal.dataset.accountName
  const fromDate = document.getElementById("statement-from-date").value
  const toDate = document.getElementById("statement-to-date").value

  if (!accountId || !fromDate || !toDate) {
    showMessage("error", "Please select a valid date range")
    return
  }

  container.innerHTML = '<div class="loading">Generating statement...</div>'

  fetch(`api/get_account_statement.php?id=${accountId}&from_date=${fromDate}&to_date=${toDate}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        const account = data.account
        const transactions = data.transactions
        const summary = data.summary

        let transactionsHtml = ""
        if (transactions.length === 0) {
          transactionsHtml = '<tr><td colspan="5" class="text-center">No transactions found in this period</td></tr>'
        } else {
          transactions.forEach((transaction) => {
            const amount = Number.parseFloat(transaction.amount)
            const transactionType = transaction.transaction_type
            let amountClass = ""
            let amountPrefix = ""

            if (transactionType === "deposit") {
              amountClass = "deposit"
              amountPrefix = "+"
            } else if (transactionType === "sale") {
              amountClass = "sale"
              amountPrefix = "-"
            } else if (transactionType === "return") {
              amountClass = "return"
              amountPrefix = "+"
            }

            const transactionDate = new Date(transaction.transaction_date)
            const formattedDate = transactionDate.toLocaleDateString()

            transactionsHtml += `
              <tr>
                <td>${formattedDate}</td>
                <td>${transaction.transaction_type.charAt(0).toUpperCase() + transaction.transaction_type.slice(1)}</td>
                <td>${transaction.reference_id || "-"}</td>
                <td>${transaction.notes || "-"}</td>
                <td class="amount-cell ${amountClass}">${amountPrefix}${formatCurrency(amount)}</td>
              </tr>
            `
          })
        }

        container.innerHTML = `
          <div class="statement-container" id="printable-statement">
            <div class="statement-header">
              <div>
                <div class="statement-title">Account Statement</div>
                <div>${accountName}</div>
              </div>
              <div class="statement-date">
                <div>From: ${new Date(fromDate).toLocaleDateString()}</div>
                <div>To: ${new Date(toDate).toLocaleDateString()}</div>
              </div>
            </div>
            
            <table class="statement-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Reference</th>
                  <th>Description</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>${new Date(fromDate).toLocaleDateString()}</td>
                  <td>Opening Balance</td>
                  <td>-</td>
                  <td>Balance at start of period</td>
                  <td class="amount-cell">${formatCurrency(summary.opening_balance)}</td>
                </tr>
                ${transactionsHtml}
              </tbody>
            </table>
            
            <div class="statement-summary">
              <div>Closing Balance</div>
              <div>${formatCurrency(summary.closing_balance)}</div>
            </div>
          </div>
        `
      } else {
        container.innerHTML = `<div class="error">${data.message || "Error generating statement"}</div>`
      }
    })
    .catch((error) => {
      console.error("Error generating statement:", error)
      container.innerHTML = `<div class="error">Error generating statement: ${error.message}</div>`
    })
}

// Print account statement
function printAccountStatement() {
  const printableContent = document.getElementById("printable-statement")

  if (!printableContent) {
    showMessage("error", "Please generate a statement first")
    return
  }

  const printWindow = window.open("", "_blank")

  printWindow.document.write(`
    <html>
    <head>
      <title>Account Statement</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .statement-container { max-width: 800px; margin: 0 auto; }
        .statement-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .statement-title { font-size: 18px; font-weight: bold; }
        .statement-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .statement-table th, .statement-table td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
        .amount-cell { text-align: right; }
        .deposit { color: #27ae60; }
        .sale { color: #e74c3c; }
        .return { color: #3498db; }
        .statement-summary { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-weight: bold; }
        @media print {
          @page { margin: 0.5cm; }
          body { margin: 1cm; }
        }
      </style>
    </head>
    <body>
      ${printableContent.outerHTML}
      <script>
        window.onload = function() { window.print(); window.close(); }
      </script>
    </body>
    </html>
  `)

  printWindow.document.close()
}

// Format date for input fields (YYYY-MM-DD)
function formatDateForInput(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

// Format currency
function formatCurrency(amount) {
  return "PKR " + Number.parseFloat(amount).toFixed(2)
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

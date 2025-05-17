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

  // Initial load
  loadEntries()
  loadNameOptions()
  loadAccounts()

  // Toggle between existing and new name
  const entryTypeRadios = document.querySelectorAll('input[name="entry_type"]')
  entryTypeRadios.forEach((radio) => {
    radio.addEventListener("change", function () {
      const existingContainer = document.getElementById("existing-name-container")
      const newContainer = document.getElementById("new-name-container")

      if (this.value === "existing") {
        existingContainer.style.display = "block"
        newContainer.style.display = "none"
      } else {
        existingContainer.style.display = "none"
        newContainer.style.display = "block"
      }
    })
  })

  // Search functionality for dropdown
  const nameSearch = document.getElementById("name-search")
  const existingNameSelect = document.getElementById("existing-name")
  const selectWithSearch = document.querySelector(".select-with-search")

  nameSearch?.addEventListener("focus", () => {
    selectWithSearch.classList.add("searching")
  })

  nameSearch?.addEventListener("blur", () => {
    setTimeout(() => {
      selectWithSearch.classList.remove("searching")
    }, 200)
  })

  nameSearch?.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase()
    const options = existingNameSelect.options

    for (let i = 0; i < options.length; i++) {
      const optionText = options[i].text.toLowerCase()
      if (optionText.includes(searchTerm)) {
        options[i].style.display = ""
      } else {
        options[i].style.display = "none"
      }
    }
  })

  // Form submissions
  document.getElementById("add-farmer-form")?.addEventListener("submit", (e) => {
    e.preventDefault()
    addEntry()
  })

  document.getElementById("edit-farmer-form")?.addEventListener("submit", (e) => {
    e.preventDefault()
    updateEntry()
  })

  document.getElementById("statement-form")?.addEventListener("submit", (e) => {
    e.preventDefault()
    generateStatement()
  })

  // Search functionality
  document.getElementById("farmer-search")?.addEventListener("input", (e) => {
    loadEntries(e.target.value)
  })

  document.getElementById("account-search")?.addEventListener("input", (e) => {
    loadAccounts(e.target.value)
  })

  // Modal functionality
  document.querySelector(".close-modal")?.addEventListener("click", () => {
    document.getElementById("edit-modal").style.display = "none"
  })

  window.addEventListener("click", (e) => {
    const modal = document.getElementById("edit-modal")
    if (e.target === modal) {
      modal.style.display = "none"
    }
  })

  // Account filters
  const accountFilterButtons = document.querySelectorAll(".account-filters .filter-btn")
  if (accountFilterButtons.length > 0) {
    accountFilterButtons.forEach((button) => {
      button.addEventListener("click", function () {
        // Update active button
        accountFilterButtons.forEach((btn) => btn.classList.remove("active"))
        this.classList.add("active")

        // Get filter value and search text
        const filter = this.getAttribute("data-filter")
        const searchText = document.getElementById("account-search").value || ""

        // Apply filter
        loadAccounts(searchText, filter)
      })
    })
  }
})

// Function to load accounts
function loadAccounts(search = "", filter = "all") {
  fetch(`actions/get_accounts.php?search=${encodeURIComponent(search)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      const tbody = document.getElementById("accounts-list-body")
      tbody.innerHTML = ""

      if (!data || data.length === 0) {
        tbody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center">No accounts found</td>
        </tr>
      `
        return
      }

      // Filter accounts based on balance
      let filteredData = data
      if (filter !== "all") {
        filteredData = data.filter((account) => {
          const balance = Number.parseFloat(account.balance)
          if (filter === "nil") return Math.abs(balance) < 0.01 // Consider very small values as zero
          if (filter === "creditor") return balance > 0
          if (filter === "debtor") return balance < 0
          return true
        })
      }

      if (filteredData.length === 0) {
        tbody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center">No accounts match the selected filter</td>
        </tr>
      `
        return
      }

      filteredData.forEach((account) => {
        const balance = Number.parseFloat(account.balance)
        const balanceClass = balance > 0 ? "text-success" : balance < 0 ? "text-danger" : ""
        const balanceSign = balance > 0 ? "+" : balance < 0 ? "-" : ""

        const row = document.createElement("tr")
        row.innerHTML = `
        <td>${account.name}</td>
        <td class="${balanceClass}">
          PKR ${balanceSign} ${Math.abs(balance).toFixed(2)}
        </td>
        <td>${account.last_transaction ? new Date(account.last_transaction).toLocaleDateString() : "N/A"}</td>
        <td>
          <a href="account-details.php?id=${account.id}" class="action-btn">View Details</a>
        </td>
      `
        tbody.appendChild(row)
      })
    })
    .catch((error) => {
      console.error("Error loading accounts:", error)
      const tbody = document.getElementById("accounts-list-body")
      tbody.innerHTML = `
      <tr>
        <td colspan="4" class="text-center">Error loading accounts. Please try again.</td>
      </tr>
    `
    })
}

// Function to load entries
function loadEntries(search = "") {
  fetch(`actions/get_entries.php?search=${encodeURIComponent(search)}`)
    .then((response) => response.json())
    .then((data) => {
      const tbody = document.getElementById("farmer-list-body")
      tbody.innerHTML = ""

      if (data.length === 0) {
        tbody.innerHTML = `
              <tr>
                  <td colspan="6" class="text-center">No entries found</td>
              </tr>
          `
        return
      }

      data.forEach((entry) => {
        const transactionType = entry.transaction_type || "debit" // Default to debit if not set
        const transactionIcon =
          transactionType === "credit"
            ? '<i class="fas fa-arrow-up text-success"></i> Credit'
            : '<i class="fas fa-arrow-down text-danger"></i> Debit'

        const row = document.createElement("tr")
        row.innerHTML = `
              <td>${entry.name}</td>
              <td>${entry.description || "N/A"}</td>
              <td class="${transactionType === "debit" ? "text-danger" : "text-success"}">
                  PKR ${transactionType === "debit" ? "-" : "+"} ${Number.parseFloat(entry.total_cost).toFixed(2)}
              </td>
              <td>${transactionIcon}</td>
              <td>${new Date(entry.entry_date).toLocaleDateString()}</td>
              <td>
                  <button class="action-btn" onclick="editEntry(${entry.id})">Update</button>
                  <button class="action-btn delete-btn" onclick="deleteEntry(${entry.id})">Delete</button>
              </td>
          `
        tbody.appendChild(row)
      })
    })
    .catch((error) => {
      console.error("Error loading entries:", error)
      showMessage("error", "Error loading entries")
    })
}

// Declare currentEntryId in the global scope
let currentEntryId = null

// Edit entry functionality
window.editEntry = (id) => {
  currentEntryId = id
  fetch(`actions/get_entry.php?id=${id}`)
    .then((response) => response.json())
    .then((entry) => {
      document.getElementById("edit-farmer-name").value = entry.name
      document.getElementById("edit-description").value = entry.description || ""
      document.getElementById("edit-total-cost").value = entry.total_cost

      // Set transaction type radio buttons
      if (entry.transaction_type === "credit") {
        document.getElementById("edit-credit").checked = true
      } else {
        document.getElementById("edit-debit").checked = true
      }

      document.getElementById("edit-entry-date").value = entry.entry_date
      document.getElementById("edit-modal").style.display = "block"
    })
    .catch((error) => showMessage("error", "Error loading entry data"))
}

function updateEntry() {
  const formData = new FormData(document.getElementById("edit-farmer-form"))
  formData.append("id", currentEntryId) // Add stored ID to form data
  formData.append("transaction_type", document.querySelector('input[name="edit_transaction_type"]:checked').value)

  fetch("actions/update_entry.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      // Check if the response is JSON
      const contentType = response.headers.get("content-type")
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Response is not JSON. Received: " + contentType)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showMessage("success", data.message)
        document.getElementById("edit-modal").style.display = "none"
        loadEntries()
        loadAccounts() // Refresh accounts list as well
        currentEntryId = null // Clear stored ID
      } else {
        showMessage("error", data.message)
      }
    })
    .catch((error) => {
      console.error("Error updating entry:", error)
      showMessage("error", "Update failed: " + error.message)
    })
}

// Delete entry functionality
window.deleteEntry = (id) => {
  if (!confirm("Are you sure you want to delete this entry? This action cannot be undone.")) {
    return
  }

  const formData = new FormData()
  formData.append("id", id)

  fetch("actions/delete_entry.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      // Check if the response is JSON
      const contentType = response.headers.get("content-type")
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Response is not JSON. Received: " + contentType)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showMessage("success", data.message)
        loadEntries()
        loadAccounts() // Refresh accounts list as well
      } else {
        showMessage("error", data.message)
      }
    })
    .catch((error) => {
      console.error("Error deleting entry:", error)
      showMessage("error", "Delete failed: " + error.message)
    })
}

// Add entry function
function addEntry() {
  const form = document.getElementById("add-farmer-form")
  const formData = new FormData()

  // Handle the entry type (new or existing)
  const entryType = document.querySelector('input[name="entry_type"]:checked').value
  let name = ""

  if (entryType === "new") {
    name = document.getElementById("new-name").value
  } else {
    name = document.getElementById("existing-name").value
  }

  if (!name) {
    showMessage("error", "Please enter or select a name")
    return
  }

  // Get other form values
  const description = document.getElementById("description").value
  const total_cost = document.getElementById("total-cost").value
  const transaction_type = document.querySelector('input[name="transaction_type"]:checked').value
  const entry_date = document.getElementById("entry-date").value

  // Validate inputs
  if (!description) {
    showMessage("error", "Please enter a description")
    return
  }

  if (!total_cost || Number.parseFloat(total_cost) <= 0) {
    showMessage("error", "Please enter a valid amount greater than zero")
    return
  }

  if (!entry_date) {
    showMessage("error", "Please select a date")
    return
  }

  // Add data to FormData
  formData.append("name", name)
  formData.append("description", description)
  formData.append("total_cost", total_cost)
  formData.append("transaction_type", transaction_type)
  formData.append("entry_date", entry_date)

  // Send request
  fetch("actions/add_entry.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      // Check if the response is JSON
      const contentType = response.headers.get("content-type")
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Response is not JSON. Received: " + contentType)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        showMessage("success", data.message)
        form.reset()
        loadEntries()
        loadAccounts() // Refresh accounts list as well
        loadNameOptions() // Refresh name options
      } else {
        showMessage("error", data.message)
      }
    })
    .catch((error) => {
      console.error("Error adding entry:", error)
      showMessage("error", "Failed to add entry: " + error.message)
    })
}

// View account details
function viewAccountDetails(id) {
  // First check if the ID is valid
  if (!id || isNaN(id) || id <= 0) {
    showMessage("error", "Invalid account ID")
    return
  }

  // Redirect to the account details page
  window.location.href = `account-details.php?id=${id}`
}

// Utility functions
function showMessage(type, text) {
  const messageDiv = document.createElement("div")
  messageDiv.className = `message ${type}`
  messageDiv.textContent = text
  document.body.appendChild(messageDiv)
  setTimeout(() => messageDiv.remove(), 5000)
}

function loadNameOptions() {
  const selectFarmer = document.getElementById("select-farmer")
  const existingName = document.getElementById("existing-name")

  fetch("actions/get_distinct_names.php")
    .then((response) => response.json())
    .then((data) => {
      // Update statement form dropdown
      if (selectFarmer) {
        selectFarmer.innerHTML = '<option value="" disabled selected>Select a name</option>'

        if (data.length === 0) {
          const option = document.createElement("option")
          option.value = ""
          option.textContent = "No names available"
          option.disabled = true
          selectFarmer.appendChild(option)
        } else {
          data.forEach((entry) => {
            const option = document.createElement("option")
            option.value = entry.name
            option.textContent = entry.name
            selectFarmer.appendChild(option)
          })
        }
      }

      // Update existing name dropdown in add entry form
      if (existingName) {
        existingName.innerHTML = '<option value="" disabled selected>Select a name</option>'

        if (data.length === 0) {
          const option = document.createElement("option")
          option.value = ""
          option.textContent = "No names available"
          option.disabled = true
          existingName.appendChild(option)
        } else {
          data.forEach((entry) => {
            const option = document.createElement("option")
            option.value = entry.name
            option.textContent = entry.name
            existingName.appendChild(option)
          })
        }
      }
    })
    .catch((error) => {
      console.error("Error loading names:", error)
      if (selectFarmer) {
        selectFarmer.innerHTML = '<option value="" disabled selected>Error loading names</option>'
      }
      if (existingName) {
        existingName.innerHTML = '<option value="" disabled selected>Error loading names</option>'
      }
    })
}

// Update the generateStatement function in script.js
function generateStatement() {
  const farmerName = document.getElementById("select-farmer").value
  const fromDate = document.getElementById("from-date").value
  const toDate = document.getElementById("to-date").value

  if (!farmerName) {
    showMessage("error", "Please select a name")
    return
  }

  // Create a form to submit in a new window
  const form = document.createElement("form")
  form.method = "POST"
  form.action = "actions/generate_statement_pdf.php"
  form.target = "_blank" // Open in new tab/window

  // Add form fields
  const nameField = document.createElement("input")
  nameField.type = "hidden"
  nameField.name = "name"
  nameField.value = farmerName
  form.appendChild(nameField)

  // Only add date fields if they are provided
  if (fromDate) {
    const fromDateField = document.createElement("input")
    fromDateField.type = "hidden"
    fromDateField.name = "from_date"
    fromDateField.value = fromDate
    form.appendChild(fromDateField)
  }

  if (toDate) {
    const toDateField = document.createElement("input")
    toDateField.type = "hidden"
    toDateField.name = "to_date"
    toDateField.value = toDate
    form.appendChild(toDateField)
  }

  // Add form to document, submit it, and remove it
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)

  showMessage("success", "Generating statement...")
}

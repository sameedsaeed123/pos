document.addEventListener("DOMContentLoaded", () => {
  // Load dashboard data
  loadDashboardStats()
  loadRecentSales()
  loadLowStockItems()
})

// Update the loadDashboardStats function to properly display net revenue
function loadDashboardStats() {
  // Fetch dashboard statistics from the server
  fetch("api/dashboard_stats.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .then((data) => {
      // Update the dashboard stats
      document.getElementById("inventory-count").textContent = data.inventory_count
      document.getElementById("sales-count").textContent = data.sales_count
      document.getElementById("revenue-amount").textContent = "PKR " + Number.parseFloat(data.revenue_amount).toFixed(2)
      document.getElementById("returns-count").textContent = data.returns_count

      // Add a tooltip to revenue amount to show it's net of returns
      const revenueElement = document.getElementById("revenue-amount")
      if (revenueElement) {
        revenueElement.title = `Net revenue (after returns). Total returns: PKR ${Number.parseFloat(data.total_returns_amount || 0).toFixed(2)}`
      }

      // Update subtitle to show this is all-time revenue
      const revenueSubtitle = document.querySelector(".dashboard-card:nth-child(3) .dashboard-card-subtitle")
      if (revenueSubtitle) {
        revenueSubtitle.textContent = "All-time net revenue"
      }
    })
    .catch((error) => {
      console.error("Error loading dashboard stats:", error)
      // Set default values in case of error
      document.getElementById("inventory-count").textContent = "0"
      document.getElementById("sales-count").textContent = "0"
      document.getElementById("revenue-amount").textContent = "PKR 0.00"
      document.getElementById("returns-count").textContent = "0"
    })
}

function loadRecentSales() {
  // Fetch recent sales from the server
  fetch("api/recent_sales.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .then((data) => {
      const tbody = document.getElementById("recent-sales-body")
      tbody.innerHTML = ""

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No recent sales found</td></tr>'
        return
      }

      // Populate the table with recent sales data
      data.forEach((sale) => {
        const row = document.createElement("tr")

        // Format the date
        const saleDate = new Date(sale.sale_date)
        const formattedDate =
          saleDate.toLocaleDateString() + " " + saleDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })

        // Create status badge class based on payment status
        const statusClass =
          sale.payment_status === "paid" ? "status-paid" : sale.payment_status === "pending" ? "status-pending" : ""

        row.innerHTML = `
                  <td>${sale.transaction_id}</td>
                  <td>${sale.customer_name || "Walk-in Customer"}</td>
                  <td>PKR ${Number.parseFloat(sale.final_amount).toFixed(2)}</td>
                  <td>${formattedDate}</td>
                  <td><span class="status-badge ${statusClass}">${sale.payment_status}</span></td>
              `

        tbody.appendChild(row)
      })
    })
    .catch((error) => {
      console.error("Error loading recent sales:", error)
      const tbody = document.getElementById("recent-sales-body")
      tbody.innerHTML = '<tr><td colspan="5" class="text-center">Error loading recent sales</td></tr>'
    })
}

function loadLowStockItems() {
  // Fetch low stock items from the server
  fetch("api/low_stock_items.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .then((data) => {
      const tbody = document.getElementById("low-stock-body")
      tbody.innerHTML = ""

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No low stock items found</td></tr>'
        return
      }

      // Populate the table with low stock items data
      data.forEach((item) => {
        const row = document.createElement("tr")

        // Determine status class based on quantity
        let statusClass = "status-ok"
        let statusText = "In Stock"

        if (item.quantity <= 0) {
          statusClass = "status-out"
          statusText = "Out of Stock"
        } else if (item.quantity <= 10) {
          statusClass = "status-low"
          statusText = "Low Stock"
        }

        row.innerHTML = `
                  <td>${item.product_name}</td>
                  <td>${item.barcode}</td>
                  <td>PKR ${Number.parseFloat(item.sale_price).toFixed(2)}</td>
                  <td>${item.quantity}</td>
                  <td><span class="status-badge ${statusClass}">${statusText}</span></td>
              `

        tbody.appendChild(row)
      })
    })
    .catch((error) => {
      console.error("Error loading low stock items:", error)
      const tbody = document.getElementById("low-stock-body")
      tbody.innerHTML = '<tr><td colspan="5" class="text-center">Error loading low stock items</td></tr>'
    })
}

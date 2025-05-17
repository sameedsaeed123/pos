import { Chart } from "@../components/ui/chart"
document.addEventListener("DOMContentLoaded", () => {
  // Debug mode - set to false in production
  const DEBUG = true

  function debug(message, data) {
    if (DEBUG) {
      console.log(`[Reports] ${message}`, data || "")
    }
  }

  debug("Reports.js loaded")

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

      // Load appropriate report data
      if (target === "sales-report") {
        loadSalesReport()
      } else if (target === "inventory-report") {
        loadInventoryReport()
      } else if (target === "revenue-report") {
        loadRevenueReport()
      }
    })
  })

  // Period change events
  const salesPeriod = document.getElementById("sales-period")
  if (salesPeriod) {
    salesPeriod.addEventListener("change", loadSalesReport)
  }

  const revenuePeriod = document.getElementById("revenue-period")
  if (revenuePeriod) {
    revenuePeriod.addEventListener("change", loadRevenueReport)
  }

  // Inventory filter change
  const inventoryFilter = document.getElementById("inventory-filter")
  if (inventoryFilter) {
    inventoryFilter.addEventListener("change", loadInventoryReport)
  }

  // Export buttons
  const exportSalesBtn = document.getElementById("export-sales-report")
  if (exportSalesBtn) {
    exportSalesBtn.addEventListener("click", () => exportReport("sales"))
  }

  const exportInventoryBtn = document.getElementById("export-inventory-report")
  if (exportInventoryBtn) {
    exportInventoryBtn.addEventListener("click", () => exportReport("inventory"))
  }

  const exportRevenueBtn = document.getElementById("export-revenue-report")
  if (exportRevenueBtn) {
    exportRevenueBtn.addEventListener("click", () => exportReport("revenue"))
  }

  // Load initial reports
  if (document.getElementById("sales-report").classList.contains("active")) {
    loadSalesReport()
  } else if (document.getElementById("inventory-report").classList.contains("active")) {
    loadInventoryReport()
  } else if (document.getElementById("revenue-report").classList.contains("active")) {
    loadRevenueReport()
  }
})

// Load sales report
function loadSalesReport() {
  const period = document.getElementById("sales-period").value

  // Show loading indicators
  document.getElementById("sales-report-body").innerHTML =
    '<tr><td colspan="6" class="text-center">Loading data...</td></tr>'

  // Clear previous chart if exists
  if (window.salesChart) {
    window.salesChart.destroy()
  }

  fetch(`api/get_sales_report.php?period=${period}`)
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
        displaySalesReport(data.report, period)
        createSalesChart(data.report, period)
      } else {
        document.getElementById("sales-report-body").innerHTML =
          `<tr><td colspan="6" class="text-center">${data.message || "Error loading sales report"}</td></tr>`
        document.getElementById("sales-chart").innerHTML = '<div class="text-center">No data available</div>'
      }
    })
    .catch((error) => {
      console.error("Error loading sales report:", error)
      document.getElementById("sales-report-body").innerHTML =
        `<tr><td colspan="6" class="text-center">Error loading sales report: ${error.message}</td></tr>`
      document.getElementById("sales-chart").innerHTML = '<div class="text-center">Error loading chart</div>'
    })
}

// Display sales report data
function displaySalesReport(report, period) {
  const tbody = document.getElementById("sales-report-body")
  if (!tbody) return

  if (report.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>'
    return
  }

  tbody.innerHTML = ""

  report.forEach((item) => {
    const row = document.createElement("tr")

    row.innerHTML = `
      <td>${item.period}</td>
      <td>${item.sales_count}</td>
      <td>${item.items_sold}</td>
      <td>PKR ${Number.parseFloat(item.revenue).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.returns).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.net_revenue).toFixed(2)}</td>
    `

    tbody.appendChild(row)
  })
}

// Create sales chart
function createSalesChart(report, period) {
  const ctx = document.getElementById("sales-chart").getContext("2d")

  // Prepare data
  const labels = report.map((item) => item.period)
  const salesData = report.map((item) => item.sales_count)
  const revenueData = report.map((item) => item.net_revenue)

  // Create chart
  window.salesChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Sales Count",
          data: salesData,
          backgroundColor: "rgba(54, 162, 235, 0.5)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1,
          yAxisID: "y",
        },
        {
          label: "Revenue (PKR)",
          data: revenueData,
          backgroundColor: "rgba(75, 192, 192, 0.5)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1,
          type: "line",
          yAxisID: "y1",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          position: "left",
          title: {
            display: true,
            text: "Sales Count",
          },
        },
        y1: {
          beginAtZero: true,
          position: "right",
          title: {
            display: true,
            text: "Revenue (PKR)",
          },
          grid: {
            drawOnChartArea: false,
          },
        },
      },
      plugins: {
        title: {
          display: true,
          text: `Sales Report (${getPeriodTitle(period)})`,
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              let label = context.dataset.label || ""
              if (label) {
                label += ": "
              }
              if (context.dataset.label === "Revenue (PKR)") {
                label += "PKR " + context.raw.toFixed(2)
              } else {
                label += context.raw
              }
              return label
            },
          },
        },
      },
    },
  })
}

// Load inventory report
function loadInventoryReport() {
  const filter = document.getElementById("inventory-filter").value

  // Show loading indicators
  document.getElementById("inventory-report-body").innerHTML =
    '<tr><td colspan="8" class="text-center">Loading data...</td></tr>'

  // Clear previous chart if exists
  if (window.inventoryChart) {
    window.inventoryChart.destroy()
  }

  fetch(`api/get_inventory_report.php?filter=${filter}`)
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
        displayInventoryReport(data.report, filter)
        createInventoryChart(data.report, filter)
      } else {
        document.getElementById("inventory-report-body").innerHTML =
          `<tr><td colspan="8" class="text-center">${data.message || "Error loading inventory report"}</td></tr>`
        document.getElementById("inventory-chart").innerHTML = '<div class="text-center">No data available</div>'
      }
    })
    .catch((error) => {
      console.error("Error loading inventory report:", error)
      document.getElementById("inventory-report-body").innerHTML =
        `<tr><td colspan="8" class="text-center">Error loading inventory report: ${error.message}</td></tr>`
      document.getElementById("inventory-chart").innerHTML = '<div class="text-center">Error loading chart</div>'
    })
}

// Display inventory report data
function displayInventoryReport(report, filter) {
  const tbody = document.getElementById("inventory-report-body")
  if (!tbody) return

  if (report.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">No data available</td></tr>'
    return
  }

  tbody.innerHTML = ""

  report.forEach((item) => {
    const row = document.createElement("tr")

    // Determine status
    let status = "In Stock"
    let statusClass = "status-ok"

    if (item.quantity <= 0) {
      status = "Out of Stock"
      statusClass = "status-out"
    } else if (item.quantity <= item.reorder_level) {
      status = "Low Stock"
      statusClass = "status-low"
    }

    row.innerHTML = `
      <td>${item.product_name}</td>
      <td>${item.barcode}</td>
      <td>${item.category || "N/A"}</td>
      <td>PKR ${Number.parseFloat(item.purchase_price).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.sale_price).toFixed(2)}</td>
      <td>${item.quantity}</td>
      <td>PKR ${Number.parseFloat(item.value).toFixed(2)}</td>
      <td><span class="status-badge ${statusClass}">${status}</span></td>
    `

    tbody.appendChild(row)
  })
}

// Create inventory chart
function createInventoryChart(report, filter) {
  const ctx = document.getElementById("inventory-chart").getContext("2d")

  // Prepare data based on filter
  let chartData
  let chartType
  let chartTitle

  if (filter === "all" || filter === "low-stock" || filter === "out-of-stock") {
    // For these filters, show quantity by category
    const categories = {}

    report.forEach((item) => {
      const category = item.category || "Uncategorized"
      if (!categories[category]) {
        categories[category] = 0
      }
      categories[category] += Number.parseInt(item.quantity)
    })

    chartData = {
      labels: Object.keys(categories),
      datasets: [
        {
          label: "Quantity",
          data: Object.values(categories),
          backgroundColor: [
            "rgba(255, 99, 132, 0.5)",
            "rgba(54, 162, 235, 0.5)",
            "rgba(255, 206, 86, 0.5)",
            "rgba(75, 192, 192, 0.5)",
            "rgba(153, 102, 255, 0.5)",
            "rgba(255, 159, 64, 0.5)",
          ],
          borderColor: [
            "rgba(255, 99, 132, 1)",
            "rgba(54, 162, 235, 1)",
            "rgba(255, 206, 86, 1)",
            "rgba(75, 192, 192, 1)",
            "rgba(153, 102, 255, 1)",
            "rgba(255, 159, 64, 1)",
          ],
          borderWidth: 1,
        },
      ],
    }

    chartType = "pie"
    chartTitle = `Inventory by Category (${getFilterTitle(filter)})`
  } else if (filter === "best-selling") {
    // For best-selling, show top products
    const sortedProducts = report
      .slice(0, 10)
      .map((item) => ({
        name: item.product_name,
        sold: item.sold || 0,
      }))
      .sort((a, b) => b.sold - a.sold)

    chartData = {
      labels: sortedProducts.map((item) => item.name),
      datasets: [
        {
          label: "Units Sold",
          data: sortedProducts.map((item) => item.sold),
          backgroundColor: "rgba(75, 192, 192, 0.5)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1,
        },
      ],
    }

    chartType = "bar"
    chartTitle = "Best Selling Products"
  }

  // Create chart
  window.inventoryChart = new Chart(ctx, {
    type: chartType,
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: chartTitle,
        },
        legend: {
          position: chartType === "pie" ? "right" : "top",
        },
      },
    },
  })
}

// Load revenue report
function loadRevenueReport() {
  const period = document.getElementById("revenue-period").value

  // Show loading indicators
  document.getElementById("revenue-report-body").innerHTML =
    '<tr><td colspan="6" class="text-center">Loading data...</td></tr>'

  // Clear previous chart if exists
  if (window.revenueChart) {
    window.revenueChart.destroy()
  }

  fetch(`api/get_revenue_report.php?period=${period}`)
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
        displayRevenueReport(data.report, period)
        createRevenueChart(data.report, period)
      } else {
        document.getElementById("revenue-report-body").innerHTML =
          `<tr><td colspan="6" class="text-center">${data.message || "Error loading revenue report"}</td></tr>`
        document.getElementById("revenue-chart").innerHTML = '<div class="text-center">No data available</div>'
      }
    })
    .catch((error) => {
      console.error("Error loading revenue report:", error)
      document.getElementById("revenue-report-body").innerHTML =
        `<tr><td colspan="6" class="text-center">Error loading revenue report: ${error.message}</td></tr>`
      document.getElementById("revenue-chart").innerHTML = '<div class="text-center">Error loading chart</div>'
    })
}

// Display revenue report data
function displayRevenueReport(report, period) {
  const tbody = document.getElementById("revenue-report-body")
  if (!tbody) return

  if (report.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>'
    return
  }

  tbody.innerHTML = ""

  report.forEach((item) => {
    const row = document.createElement("tr")

    // Calculate profit margin
    const profitMargin = (item.net_profit / item.gross_revenue) * 100

    row.innerHTML = `
      <td>${item.period}</td>
      <td>PKR ${Number.parseFloat(item.gross_revenue).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.cost_of_goods).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.returns).toFixed(2)}</td>
      <td>PKR ${Number.parseFloat(item.net_profit).toFixed(2)}</td>
      <td>${profitMargin.toFixed(2)}%</td>
    `

    tbody.appendChild(row)
  })
}

// Create revenue chart
function createRevenueChart(report, period) {
  const ctx = document.getElementById("revenue-chart").getContext("2d")

  // Prepare data
  const labels = report.map((item) => item.period)
  const grossRevenueData = report.map((item) => item.gross_revenue)
  const netProfitData = report.map((item) => item.net_profit)
  const costData = report.map((item) => item.cost_of_goods)

  // Create chart
  window.revenueChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Gross Revenue",
          data: grossRevenueData,
          backgroundColor: "rgba(54, 162, 235, 0.5)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1,
        },
        {
          label: "Cost of Goods",
          data: costData,
          backgroundColor: "rgba(255, 99, 132, 0.5)",
          borderColor: "rgba(255, 99, 132, 1)",
          borderWidth: 1,
        },
        {
          label: "Net Profit",
          data: netProfitData,
          backgroundColor: "rgba(75, 192, 192, 0.5)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1,
          type: "line",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: "Amount (PKR)",
          },
        },
      },
      plugins: {
        title: {
          display: true,
          text: `Revenue Report (${getPeriodTitle(period)})`,
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              let label = context.dataset.label || ""
              if (label) {
                label += ": "
              }
              label += "PKR " + context.raw.toFixed(2)
              return label
            },
          },
        },
      },
    },
  })
}

// Export report to CSV
function exportReport(reportType) {
  let endpoint
  let filename

  switch (reportType) {
    case "sales":
      const salesPeriod = document.getElementById("sales-period").value
      endpoint = `api/export_sales_report.php?period=${salesPeriod}`
      filename = `sales_report_${salesPeriod}_${formatDate(new Date())}.csv`
      break
    case "inventory":
      const inventoryFilter = document.getElementById("inventory-filter").value
      endpoint = `api/export_inventory_report.php?filter=${inventoryFilter}`
      filename = `inventory_report_${inventoryFilter}_${formatDate(new Date())}.csv`
      break
    case "revenue":
      const revenuePeriod = document.getElementById("revenue-period").value
      endpoint = `api/export_revenue_report.php?period=${revenuePeriod}`
      filename = `revenue_report_${revenuePeriod}_${formatDate(new Date())}.csv`
      break
    default:
      showMessage("error", "Invalid report type")
      return
  }

  fetch(endpoint)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.blob()
    })
    .then((blob) => {
      // Create download link
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement("a")
      a.style.display = "none"
      a.href = url
      a.download = filename

      // Append to body and trigger download
      document.body.appendChild(a)
      a.click()

      // Clean up
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    })
    .catch((error) => {
      console.error("Error exporting report:", error)
      showMessage("error", `Error exporting report: ${error.message}`)
    })
}

// Helper functions
function getPeriodTitle(period) {
  switch (period) {
    case "daily":
      return "Daily"
    case "weekly":
      return "Weekly"
    case "monthly":
      return "Monthly"
    case "yearly":
      return "Yearly"
    default:
      return "Custom"
  }
}

function getFilterTitle(filter) {
  switch (filter) {
    case "all":
      return "All Products"
    case "low-stock":
      return "Low Stock"
    case "out-of-stock":
      return "Out of Stock"
    case "best-selling":
      return "Best Selling"
    default:
      return "Custom"
  }
}

function formatDate(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`
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

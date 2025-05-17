<?php
// Prevent any output before PDF generation
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pdf_errors.log');

// Include required files
require_once(__DIR__ . '/../tcpdf/tcpdf.php');
require_once 'db_config.php';
require_once(__DIR__ . '/../tcpdf/include/tcpdf_fonts.php');

// Main process
try {
  // Validate inputs
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      throw new Exception("Invalid request method");
  }
  
  if (empty($_POST['name'])) {
      throw new Exception("Missing account name");
  }

  $conn = getConnection();
  
  $name = $conn->real_escape_string($_POST['name']);
  $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

  // Get account information
  $accountStmt = $conn->prepare("SELECT * FROM mandi_accounts WHERE name = ?");
  $accountStmt->bind_param("s", $name);
  $accountStmt->execute();
  $accountResult = $accountStmt->get_result();
  
  if ($accountResult->num_rows === 0) {
      throw new Exception("Account not found for $name");
  }
  
  $accountData = $accountResult->fetch_assoc();
  $currentBalance = $accountData['balance'];
  $accountCreatedDate = $accountData['created_at'];

  // Get the first and last entry dates if not provided
  $dateRangeStmt = $conn->prepare("
    SELECT 
        MIN(entry_date) as first_date,
        MAX(entry_date) as last_date
    FROM mandi_entries 
    WHERE name = ?
");
$dateRangeStmt->bind_param("s", $name);
$dateRangeStmt->execute();
$dateRangeResult = $dateRangeStmt->get_result();
$dateRangeData = $dateRangeResult->fetch_assoc();

// Use provided dates if available, otherwise use first/last entry dates
$from_date = !empty($_POST['from_date']) ? $_POST['from_date'] : $dateRangeData['first_date'];
$to_date = !empty($_POST['to_date']) ? $_POST['to_date'] : $dateRangeData['last_date'];

// If still no dates (no entries), use account creation date and current date
if (empty($from_date)) {
    $from_date = date('Y-m-d', strtotime($accountCreatedDate));
}
if (empty($to_date)) {
    $to_date = date('Y-m-d');
}

  // Get entries within date range with optional filter
  $sql = "SELECT id, entry_date, description, total_cost, transaction_type 
    FROM mandi_entries 
    WHERE name = ? 
    AND entry_date BETWEEN ? AND ?";

  // Add filter condition if needed
  if ($filter === 'debit') {
      $sql .= " AND transaction_type = 'debit'";
  } else if ($filter === 'credit') {
      $sql .= " AND transaction_type = 'credit'";
  }

  $sql .= " ORDER BY entry_date ASC, id ASC";

  $stmt = $conn->prepare($sql);
  
  $stmt->bind_param("sss", $name, $from_date, $to_date);
  
  if (!$stmt->execute()) {
      throw new Exception("Error fetching records: " . $stmt->error);
  }
  
  $result = $stmt->get_result();
  
  // Calculate opening balance (balance before the from_date)
  $openingBalanceStmt = $conn->prepare("
      SELECT 
          SUM(CASE WHEN transaction_type = 'credit' THEN total_cost ELSE 0 END) as total_credit,
          SUM(CASE WHEN transaction_type = 'debit' THEN total_cost ELSE 0 END) as total_debit
      FROM mandi_entries 
      WHERE name = ? AND entry_date < ?
  ");
  $openingBalanceStmt->bind_param("ss", $name, $from_date);
  $openingBalanceStmt->execute();
  $openingBalanceResult = $openingBalanceStmt->get_result();
  $openingBalanceRow = $openingBalanceResult->fetch_assoc();
  
  $openingBalance = ($openingBalanceRow['total_credit'] ?? 0) - ($openingBalanceRow['total_debit'] ?? 0);
  $runningBalance = $openingBalance;

  // Collect all entries in an array to avoid issues with output buffering
  $entries = [];
  $totalDebit = 0;
  $totalCredit = 0;
  
  while ($row = $result->fetch_assoc()) {
      // Update running balance
      if ($row['transaction_type'] === 'debit') {
          $runningBalance -= $row['total_cost'];
          $totalDebit += $row['total_cost'];
      } else {
          $runningBalance += $row['total_cost'];
          $totalCredit += $row['total_cost'];
      }
      
      // Add running balance to the row
      $row['running_balance'] = $runningBalance;
      $entries[] = $row;
  }

  // Clear any output that might have been generated
  ob_clean();

  // Create new PDF document with RTL support
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

  // Set document information
  $pdf->SetCreator('Farmer Management System');
  $pdf->SetAuthor('Farmer Management System');
  $pdf->SetTitle('Mandi Account Ledger');
  $pdf->SetSubject('Mandi Account Ledger for ' . $name);

  // Remove default header/footer
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);

  // Set default monospaced font
  $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

  // Set margins
  $pdf->SetMargins(10, 15, 10);

  // Set auto page breaks
  $pdf->SetAutoPageBreak(TRUE, 15);

  // Set image scale factor
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  
  // Add a page
  $pdf->AddPage('L'); // Landscape orientation for more columns

  // Set font
  $pdf->SetFont('helvetica', 'B', 16);

  // Title
  $filterText = '';
  if ($filter === 'debit') {
      $filterText = ' (Debit Transactions)';
  } else if ($filter === 'credit') {
      $filterText = ' (Credit Transactions)';
  }

  $pdf->Cell(0, 10, 'Mandi Account Ledger' . $filterText, 0, 1, 'C');
  
  // Account Information
  $pdf->SetFont('helvetica', 'B', 12);
  $pdf->Cell(40, 7, 'Account Name:', 0, 0);
  $pdf->SetFont('helvetica', '', 12);
  $pdf->Cell(100, 7, $name, 0, 0);
  
  $pdf->SetFont('helvetica', 'B', 12);
  $pdf->Cell(40, 7, 'Period:', 0, 0);
  $pdf->SetFont('helvetica', '', 12);
  $pdf->Cell(0, 7, date('d/m/Y', strtotime($from_date)) . ' to ' . date('d/m/Y', strtotime($to_date)), 0, 1);
  
  $pdf->SetFont('helvetica', 'B', 12);
  $pdf->Cell(40, 7, 'Opening Balance:', 0, 0);
  $pdf->SetFont('helvetica', '', 12);
  $pdf->Cell(0, 7, 'PKR ' . ($openingBalance >= 0 ? '+' : '') . number_format($openingBalance, 2), 0, 1);
  
  $pdf->Ln(5);

  // Table header
  $pdf->SetFillColor(52, 73, 94); // Dark blue header
  $pdf->SetTextColor(255, 255, 255); // White text
  $pdf->SetFont('helvetica', 'B', 10);
  
  // Column widths
  $dateWidth = 25;
  $idWidth = 20;
  $descWidth = 80;
  $debitWidth = 35;
  $creditWidth = 35;
  $balanceWidth = 40;
  
  // Row height for entries (increased to accommodate 2 lines of description)
  $rowHeight = 16; // Increased from 8
  $headerHeight = 10;
  
  $pdf->Cell($dateWidth, $headerHeight, 'Date', 1, 0, 'C', 1);
  $pdf->Cell($idWidth, $headerHeight, 'Ref No.', 1, 0, 'C', 1);
  $pdf->Cell($descWidth, $headerHeight, 'Description', 1, 0, 'C', 1);
  $pdf->Cell($debitWidth, $headerHeight, 'Debit (PKR)', 1, 0, 'C', 1);
  $pdf->Cell($creditWidth, $headerHeight, 'Credit (PKR)', 1, 0, 'C', 1);
  $pdf->Cell($balanceWidth, $headerHeight, 'Balance (PKR)', 1, 1, 'C', 1);

  // Reset text color
  $pdf->SetTextColor(0, 0, 0);
  
  // Opening balance row
  $pdf->SetFont('helvetica', 'B', 10);
  
  // Save the starting X and Y position
  $startX = $pdf->GetX();
  $startY = $pdf->GetY();
  
  $pdf->Cell($dateWidth, $rowHeight, date('d/m/Y', strtotime($from_date)), 1, 0, 'C');
  $pdf->Cell($idWidth, $rowHeight, '-', 1, 0, 'C');
  
  // Move to the position for the description cell
  $currentX = $pdf->GetX();
  $currentY = $pdf->GetY();
  
  // Draw the description cell border
  $pdf->Cell($descWidth, $rowHeight, '', 1, 0, 'L');
  
  // Write the description text inside the cell
  $pdf->SetXY($currentX, $currentY);
  $pdf->MultiCell($descWidth, $rowHeight/2, 'Opening Balance', 0, 'L');
  
  // Move to the position after the description cell
  $pdf->SetXY($currentX + $descWidth, $currentY);
  
  $pdf->Cell($debitWidth, $rowHeight, '-', 1, 0, 'R');
  $pdf->Cell($creditWidth, $rowHeight, '-', 1, 0, 'R');
  
  // Format opening balance with color
  if ($openingBalance >= 0) {
      $pdf->SetTextColor(0, 128, 0); // Green for positive
  } else {
      $pdf->SetTextColor(255, 0, 0); // Red for negative
  }
  $pdf->Cell($balanceWidth, $rowHeight, number_format(abs($openingBalance), 2), 1, 1, 'R');
  $pdf->SetTextColor(0, 0, 0); // Reset to black
  
  // Reset font
  $pdf->SetFont('helvetica', '', 10);

  // Table data
  $rowCount = 0;
  foreach ($entries as $entry) {
      $rowCount++;
      $date = date('d/m/Y', strtotime($entry['entry_date']));
      $description = $entry['description'];
      $amount = $entry['total_cost'];
      $type = $entry['transaction_type'];
      $balance = $entry['running_balance'];
      
      // Alternate row colors
      if ($rowCount % 2 == 0) {
          $pdf->SetFillColor(240, 240, 240); // Light gray for even rows
          $fill = 1;
      } else {
          $pdf->SetFillColor(255, 255, 255); // White for odd rows
          $fill = 1;
      }
      
      // Save the starting position
      $startX = $pdf->GetX();
      $startY = $pdf->GetY();
      
      // Draw cells except description
      $pdf->Cell($dateWidth, $rowHeight, $date, 1, 0, 'C', $fill);
      $pdf->Cell($idWidth, $rowHeight, $entry['id'], 1, 0, 'C', $fill);
      
      // Get current position before description cell
      $currentX = $pdf->GetX();
      $currentY = $pdf->GetY();
      
      // Draw the description cell border
      $pdf->Cell($descWidth, $rowHeight, '', 1, 0, 'L', $fill);
      
      // Write the description text inside the cell
      $pdf->SetXY($currentX, $currentY);
      $pdf->MultiCell($descWidth, $rowHeight/2, $description, 0, 'L', $fill);
      
      // Move to the position after the description cell
      $pdf->SetXY($currentX + $descWidth, $currentY);
      
      // Debit column
      if ($type === 'debit') {
          $pdf->SetTextColor(255, 0, 0); // Red for debit
          $pdf->Cell($debitWidth, $rowHeight, number_format($amount, 2), 1, 0, 'R', $fill);
          $pdf->SetTextColor(0, 0, 0); // Reset to black
          $pdf->Cell($creditWidth, $rowHeight, '-', 1, 0, 'R', $fill);
      } else {
          $pdf->Cell($debitWidth, $rowHeight, '-', 1, 0, 'R', $fill);
          $pdf->SetTextColor(0, 128, 0); // Green for credit
          $pdf->Cell($creditWidth, $rowHeight, number_format($amount, 2), 1, 0, 'R', $fill);
          $pdf->SetTextColor(0, 0, 0); // Reset to black
      }
      
      // Balance column
      if ($balance >= 0) {
          $pdf->SetTextColor(0, 128, 0); // Green for positive
      } else {
          $pdf->SetTextColor(255, 0, 0); // Red for negative
      }
      $pdf->Cell($balanceWidth, $rowHeight, number_format(abs($balance), 2), 1, 1, 'R', $fill);
      $pdf->SetTextColor(0, 0, 0); // Reset to black
  }

  // Summary footer
  $pdf->SetFillColor(240, 240, 240);
  $pdf->SetFont('helvetica', 'B', 10);
  
  // Totals row
  $pdf->Cell($dateWidth + $idWidth + $descWidth, $rowHeight, 'Total', 1, 0, 'R', 1);
  
  // Total Debit
  $pdf->SetTextColor(255, 0, 0); // Red for debit
  $pdf->Cell($debitWidth, $rowHeight, number_format($totalDebit, 2), 1, 0, 'R', 1);
  
  // Total Credit
  $pdf->SetTextColor(0, 128, 0); // Green for credit
  $pdf->Cell($creditWidth, $rowHeight, number_format($totalCredit, 2), 1, 0, 'R', 1);
  
  // Closing Balance
  $pdf->SetTextColor(0, 0, 0); // Reset to black
  $pdf->Cell($balanceWidth, $rowHeight, 'Closing Balance', 1, 1, 'C', 1);
  
  // Closing Balance Value
  $pdf->Cell($dateWidth + $idWidth + $descWidth + $debitWidth + $creditWidth, $rowHeight, '', 1, 0, 'R', 1);
  
  if ($runningBalance >= 0) {
      $pdf->SetTextColor(0, 128, 0); // Green for positive
      $balanceText = number_format(abs($runningBalance), 2);
  } else {
      $pdf->SetTextColor(255, 0, 0); // Red for negative
      $balanceText = number_format(abs($runningBalance), 2);
  }
  
  $pdf->Cell($balanceWidth, $rowHeight, $balanceText, 1, 1, 'R', 1);
  
  // Reset text color
  $pdf->SetTextColor(0, 0, 0);
  
  // Additional information
  $pdf->Ln(10);
  $pdf->SetFont('helvetica', 'B', 10);
  $pdf->Cell(0, 7, 'Statement Information', 0, 1);
  
  $pdf->SetFont('helvetica', '', 9);
  $pdf->Cell(0, 6, 'Generated on: ' . date('d/m/Y H:i:s'), 0, 1);
  $pdf->Cell(0, 6, 'Farmer Management System', 0, 1);
  
  // Signature
  $pdf->Ln(15);
  $pdf->Cell(0, 7, 'Authorized Signature: _______________________', 0, 1);
  
  // Footer note
  $pdf->Ln(5);
  $pdf->SetFont('helvetica', 'I', 8);
  $pdf->MultiCell(0, 5, 'Note: This is a computer-generated statement and does not require a signature. Please report any discrepancies within 15 days.', 0, 'L');

  // Close and output PDF document
  $pdf->Output('mandi_ledger_' . $name . '_' . date('Y-m-d') . '.pdf', 'D');
  exit;

} catch (Exception $e) {
  // Clean output buffer
  ob_clean();
  
  // Log error
  error_log('PDF Generation Error: ' . $e->getMessage());
  
  // Send error response
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html>
  <html>
  <head>
      <title>Error</title>
      <style>
          body { font-family: Arial, sans-serif; margin: 50px; }
          .error { background-color: #ffebee; border: 1px solid #f44336; padding: 20px; border-radius: 5px; }
          h2 { color: #d32f2f; }
          a { color: #2196F3; text-decoration: none; }
          a:hover { text-decoration: underline; }
      </style>
  </head>
  <body>
      <div class="error">
          <h2>Error Generating PDF</h2>
          <p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>
          <p><a href="javascript:history.back()">Go Back</a></p>
      </div>
  </body>
  </html>';
  exit;
}

// The following code is for generating the font definition file and is not part of the main PDF generation process.
// It's kept here for convenience and can be run separately if needed.

// Font file path
$fontFile = __DIR__ . '/../tcpdf/fonts/NotoNastaliqUrdu-Regular.ttf';

// Check if the font file exists
if (!file_exists($fontFile)) {
    error_log("Error: Font file not found at $fontFile");
    // Optionally, you can throw an exception here if the font is critical
    // throw new Exception("Font file not found at $fontFile");
} else {
    //echo "Starting font conversion process...<br>";

    try {
        // Create the font definition file
        $fontname = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 96);

        if ($fontname) {
            //echo "Success! Font definition file created: $fontname<br>";
            //echo "You can now use this font in TCPDF with: \$pdf->AddFont('$fontname', '', '', true);<br>";
        } else {
            error_log("Error: Failed to create font definition file.");
            //echo "Error: Failed to create font definition file.<br>";
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        //echo "Error: " . $e->getMessage() . "<br>";
    }

    // Additional information
    /*echo "<br>Font information:<br>";
    echo "- Font file: $fontFile<br>";
    echo "- PHP version: " . phpversion() . "<br>";
    echo "- TCPDF version: " . TCPDF_STATIC::getTCPDFVersion() . "<br>";*/
}
?>

<?php
require_once 'db.php';
check_auth();

$mysqli->set_charset('utf8mb4');

// Set timezone to Pakistan (UTC+5) so created_at matches local time
date_default_timezone_set('Asia/Karachi');
$mysqli->query("SET time_zone = '+05:00'");

// Ensure bills table has all required columns
$mysqli->query("CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(100),
    bill_date VARCHAR(50),
    bill_time VARCHAR(50),
    salesman VARCHAR(100),
    sub_total DECIMAL(12,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    payment DECIMAL(12,2) DEFAULT 0,
    change_amount DECIMAL(12,2) DEFAULT 0,
    grand_total DECIMAL(12,2) DEFAULT 0,
    total_profit DECIMAL(12,2) DEFAULT 0,
    user_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$result = $mysqli->query("SHOW COLUMNS FROM bills LIKE 'total_profit'");
if ($result && $result->num_rows === 0) {
    $mysqli->query("ALTER TABLE bills ADD COLUMN total_profit DECIMAL(12,2) DEFAULT 0");
}

$discRes = $mysqli->query("SHOW COLUMNS FROM bills LIKE 'discount_percent'");
if ($discRes && $discRes->num_rows === 0) {
    $mysqli->query("ALTER TABLE bills ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 0");
    $mysqli->query("ALTER TABLE bills ADD COLUMN discount_amount DECIMAL(12,2) DEFAULT 0");
}

$uCol = $mysqli->query("SHOW COLUMNS FROM bills LIKE 'user_id'");
if ($uCol && $uCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE bills ADD COLUMN user_id INT DEFAULT 0");
}

// Ensure expenses table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_type VARCHAR(20) NOT NULL,
    period_value VARCHAR(50) NOT NULL,
    salesman_expense DECIMAL(12,2) DEFAULT 0,
    shop_rent DECIMAL(12,2) DEFAULT 0,
    other_expenses DECIMAL(12,2) DEFAULT 0,
    UNIQUE KEY (user_id, period_type, period_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle POST to save expenses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_expenses'])) {
    $pType = $_POST['period_type'] ?? '';
    $pValue = $_POST['period_value'] ?? '';
    $sExp = floatval($_POST['salesman_expense'] ?? 0);
    $rExp = floatval($_POST['shop_rent'] ?? 0);
    $oExp = floatval($_POST['other_expenses'] ?? 0);
    $uid = (int)$_SESSION['user_id'];
    
    $stmt = $mysqli->prepare("INSERT INTO expenses (user_id, period_type, period_value, salesman_expense, shop_rent, other_expenses) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE salesman_expense=VALUES(salesman_expense), shop_rent=VALUES(shop_rent), other_expenses=VALUES(other_expenses)");
    $stmt->bind_param("issddd", $uid, $pType, $pValue, $sExp, $rExp, $oExp);
    $stmt->execute();
    
    $redirectParams = $_GET;
    $redirectUrl = "reports.php?" . http_build_query($redirectParams);
    header("Location: " . $redirectUrl);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Determine latest active date in database for quick jump helper
$latestDateRes = $mysqli->query("SELECT DATE(created_at) as latest_d FROM bills WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
$latestActiveDate = ($latestDateRes && $lr = $latestDateRes->fetch_assoc()) ? $lr['latest_d'] : date('Y-m-d');

// Read filter parameters
$filterType = $_GET['type'] ?? 'daily';
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterWeek = $_GET['week'] ?? date('Y-\WW');
$filterMonth = $_GET['month'] ?? date('Y-m');
$filterYear = $_GET['year'] ?? date('Y');
$filterMetric = $_GET['metric'] ?? 'profit';
$chartStyle = $_GET['chart_style'] ?? ($filterType === 'daily' ? 'line' : 'bar');

$whereClause = "";
$displayTitle = "Reports & Analytics";
$periodBadge = "";

if ($filterType === 'daily') {
    $whereClause = "WHERE DATE(created_at) = '" . $mysqli->real_escape_string($filterDate) . "' AND user_id = $user_id";
    $displayTitle = "Daily Performance — " . date('d M Y', strtotime($filterDate));
    $periodBadge = date('l, M d, Y', strtotime($filterDate));
} else if ($filterType === 'weekly') {
    $yearWeek = str_replace('-W', '', $filterWeek);
    $whereClause = "WHERE YEARWEEK(created_at, 3) = '" . $mysqli->real_escape_string($yearWeek) . "' AND user_id = $user_id";
    $displayTitle = "Weekly Performance — Week " . substr($filterWeek, -2) . ", " . substr($filterWeek, 0, 4);
    $periodBadge = "Week " . substr($filterWeek, -2) . ", " . substr($filterWeek, 0, 4);
} else if ($filterType === 'monthly') {
    $year = substr($filterMonth, 0, 4);
    $month = substr($filterMonth, 5, 2);
    $whereClause = "WHERE YEAR(created_at) = '" . $mysqli->real_escape_string($year) . "' AND MONTH(created_at) = '" . $mysqli->real_escape_string($month) . "' AND user_id = $user_id";
    $displayTitle = "Monthly Performance — " . date('F Y', strtotime($filterMonth . '-01'));
    $periodBadge = date('F Y', strtotime($filterMonth . '-01'));
} else if ($filterType === 'annual') {
    $whereClause = "WHERE YEAR(created_at) = '" . $mysqli->real_escape_string($filterYear) . "' AND user_id = $user_id";
    $displayTitle = "Annual Performance — Year " . htmlspecialchars($filterYear);
    $periodBadge = "Year " . htmlspecialchars($filterYear);
}

// Fetch bills chronologically for chart
$billsQuery = $mysqli->query("SELECT * FROM bills $whereClause ORDER BY created_at ASC");

$totalSales = 0;
$totalProfit = 0;
$chartData = [];
$billsData = [];
$totalQuantitySold = 0;

if ($billsQuery && $billsQuery->num_rows > 0) {
    while ($bill = $billsQuery->fetch_assoc()) {
        $totalSales += (float)$bill['grand_total'];
        $totalProfit += (float)$bill['total_profit'];
        
        $dateStr = $bill['created_at'];
        
        if ($filterType === 'daily') {
            $sortKey = strtotime($dateStr);
            $label = date('h:i:s A', strtotime($dateStr)); // Second-by-second granularity
        } else if ($filterType === 'weekly') {
            $sortKey = date('Y-m-d', strtotime($dateStr));
            $label = date('D, M d', strtotime($dateStr));
        } else if ($filterType === 'monthly') {
            $sortKey = date('Y-m-d', strtotime($dateStr));
            $label = date('M d', strtotime($dateStr));
        } else if ($filterType === 'annual') {
            $sortKey = date('Y-m', strtotime($dateStr));
            $label = date('F', strtotime($dateStr));
        }
        
        if (!isset($chartData[$sortKey])) {
            $chartData[$sortKey] = ['label' => $label, 'value' => 0];
        }
        $valToAdd = ($filterMetric === 'sales') ? (float)$bill['grand_total'] : (float)$bill['total_profit'];
        $chartData[$sortKey]['value'] += $valToAdd;
        
        // Push in reverse to display latest bills first in the table
        array_unshift($billsData, $bill);
    }
}

ksort($chartData);
$chartLabels = [];
$chartValues = [];
$chartKeys = [];
foreach ($chartData as $key => $data) {
    $chartLabels[] = $data['label'];
    $chartValues[] = $data['value'];
    $chartKeys[] = $key;
}

// Operating expenses query
$periodValue = '';
if ($filterType === 'daily') $periodValue = $filterDate;
else if ($filterType === 'weekly') $periodValue = $filterWeek;
else if ($filterType === 'monthly') $periodValue = $filterMonth;
else if ($filterType === 'annual') $periodValue = $filterYear;

$salesmanExp = 0;
$shopRent = 0;
$otherExp = 0;

$expenseQuery = $mysqli->prepare("SELECT salesman_expense, shop_rent, other_expenses FROM expenses WHERE user_id = ? AND period_type = ? AND period_value = ?");
$expenseQuery->bind_param("iss", $user_id, $filterType, $periodValue);
$expenseQuery->execute();
$expResult = $expenseQuery->get_result();
if ($expRow = $expResult->fetch_assoc()) {
    $salesmanExp = (float)$expRow['salesman_expense'];
    $shopRent = (float)$expRow['shop_rent'];
    $otherExp = (float)$expRow['other_expenses'];
}

$totalGrossProfit = $totalProfit;
$purchaseCost = max(0, $totalSales - $totalGrossProfit);
$totalOperatingExpenses = $salesmanExp + $shopRent + $otherExp;
$netProfit = $totalGrossProfit - $totalOperatingExpenses;
$billsCount = count($billsData);
$avgTicket = $billsCount > 0 ? ($totalSales / $billsCount) : 0;
$profitMargin = $totalSales > 0 ? (($netProfit / $totalSales) * 100) : 0;
$grossMargin = $totalSales > 0 ? (($totalGrossProfit / $totalSales) * 100) : 0;

// All-time store stats
$allTimeRes = $mysqli->query("SELECT COUNT(*) as total_bills, COALESCE(SUM(grand_total), 0) as lifetime_sales, COALESCE(SUM(total_profit), 0) as lifetime_profit FROM bills WHERE user_id = $user_id");
$allTime = $allTimeRes ? $allTimeRes->fetch_assoc() : ['total_bills' => 0, 'lifetime_sales' => 0, 'lifetime_profit' => 0];


// Distinct available years in database for dropdown
$yearsRes = $mysqli->query("SELECT DISTINCT YEAR(created_at) as yr FROM bills WHERE user_id = $user_id AND created_at IS NOT NULL ORDER BY yr DESC");
$availableYears = [];
while ($yrRow = $yearsRes->fetch_assoc()) {
    if (!empty($yrRow['yr'])) $availableYears[] = $yrRow['yr'];
}
if (!in_array(date('Y'), $availableYears)) {
    array_unshift($availableYears, date('Y'));
}

// Current logged-in user email
$userEmail = $_SESSION['email'] ?? 'aslampc406@gmail.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aslam POS — Premium Analytics & Dashboard</title>

<!-- Modern Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
  /* Navy Brand Palette */
  --navy-950: #070d1e;
  --navy-900: #0b152d;
  --navy-850: #0f1c3d;
  --navy-800: #16264c;
  --navy-700: #1e3568;
  --navy-border: #1a2a4e;
  
  /* Yellow / Amber Accents */
  --yellow-500: #f59e0b;
  --yellow-400: #fbbf24;
  --yellow-300: #fcd34d;
  --yellow-600: #d97706;
  --yellow-glow: rgba(245, 158, 11, 0.25);
  --yellow-light: #fffbeb;
  --yellow-border: #fde68a;
  
  /* Canvas & Neutral */
  --bg-main: #f8fafc;
  --card-bg: #ffffff;
  --border-subtle: #e2e8f0;
  --border-light: #f1f5f9;
  
  /* Text */
  --text-primary: #0f172a;
  --text-secondary: #475569;
  --text-muted: #94a3b8;
  
  /* Status Colors */
  --emerald-500: #10b981;
  --emerald-600: #059669;
  --emerald-light: #ecfdf5;
  --emerald-border: #a7f3d0;
  
  --rose-500: #ef4444;
  --rose-600: #dc2626;
  --rose-light: #fef2f2;
  --rose-border: #fecaca;
  
  --blue-500: #3b82f6;
  --blue-light: #eff6ff;
  
  /* Sidebar Width */
  --sidebar-w: 270px;
  --topbar-h: 70px;
  
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.06), 0 2px 4px -2px rgba(0,0,0,0.04);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: var(--bg-main);
  color: var(--text-primary);
  min-height: 100vh;
  display: flex;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* -------------------------------------------------------------
   NAVY SIDEBAR
   ------------------------------------------------------------- */
.sidebar {
  width: var(--sidebar-w);
  background: linear-gradient(180deg, var(--navy-950) 0%, var(--navy-900) 50%, var(--navy-850) 100%);
  color: #ffffff;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 1000;
  border-right: 1px solid var(--navy-border);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--navy-800) transparent;
}

.sidebar-brand {
  padding: 24px 22px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--navy-border);
}

.brand-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--yellow-400), var(--yellow-600));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--navy-950);
  font-weight: 800;
  font-size: 22px;
  box-shadow: 0 4px 14px var(--yellow-glow);
  flex-shrink: 0;
}

.brand-text h1 {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 0.3px;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
}

.brand-text .brand-badge {
  background: rgba(245, 158, 11, 0.2);
  color: var(--yellow-400);
  border: 1px solid rgba(245, 158, 11, 0.4);
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.brand-text p {
  font-size: 11.5px;
  color: var(--text-muted);
  margin-top: 2px;
  font-weight: 500;
}

.sidebar-nav {
  padding: 18px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.nav-section-title {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.1px;
  color: #64748b;
  padding: 14px 12px 6px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 600;
  border-radius: var(--radius-sm);
  transition: all 0.2s ease;
  position: relative;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
  transform: translateX(2px);
}

.nav-link.active {
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.16) 0%, rgba(245, 158, 11, 0.04) 100%);
  color: var(--yellow-400);
  border-left: 3px solid var(--yellow-400);
  font-weight: 700;
}

.nav-link.active .nav-icon {
  color: var(--yellow-400);
}

.nav-icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  flex-shrink: 0;
  transition: color 0.2s;
}

.nav-pill {
  margin-left: auto;
  font-size: 11px;
  padding: 2px 7px;
  border-radius: 12px;
  font-weight: 700;
  background: rgba(255,255,255,0.08);
  color: #e2e8f0;
}

.nav-link.active .nav-pill {
  background: var(--yellow-400);
  color: var(--navy-950);
}

/* Sidebar User Footer */
.sidebar-footer {
  padding: 16px;
  border-top: 1px solid var(--navy-border);
  background: rgba(0, 0, 0, 0.18);
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--navy-800), var(--navy-700));
  border: 2px solid var(--yellow-400);
  color: var(--yellow-400);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
}

.user-details {
  overflow: hidden;
  flex: 1;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.user-role {
  font-size: 11px;
  color: #10b981;
  display: flex;
  align-items: center;
  gap: 5px;
  font-weight: 600;
}

.status-dot {
  width: 6px;
  height: 6px;
  background: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 6px #10b981;
}

/* Mobile Sidebar Backdrop */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(11, 21, 45, 0.65);
  backdrop-filter: blur(4px);
  z-index: 999;
}

/* -------------------------------------------------------------
   MAIN CONTENT WRAPPER
   ------------------------------------------------------------- */
.main-wrapper {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

/* -------------------------------------------------------------
   TOP HEADER BAR
   ------------------------------------------------------------- */
.topbar {
  height: var(--topbar-h);
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-subtle);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 900;
  box-shadow: var(--shadow-sm);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-sidebar-toggle {
  display: none;
  background: transparent;
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  width: 38px;
  height: 38px;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-primary);
}

.topbar-title {
  display: flex;
  flex-direction: column;
}

.topbar-title h2 {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar-title .time-pill {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-top {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.btn-pos {
  background: var(--navy-900);
  color: #ffffff;
}

.btn-pos:hover {
  background: var(--navy-800);
  box-shadow: var(--shadow-sm);
}


.btn-action-yellow {
  background: linear-gradient(135deg, var(--yellow-400), var(--yellow-500));
  color: var(--navy-950);
  border: none;
  font-weight: 700;
  box-shadow: 0 2px 8px var(--yellow-glow);
}

.btn-action-yellow:hover {
  background: linear-gradient(135deg, var(--yellow-300), var(--yellow-500));
  transform: translateY(-1px);
}

/* -------------------------------------------------------------
   DASHBOARD BODY
   ------------------------------------------------------------- */
.dashboard-container {
  padding: 24px 28px 40px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Fast Date Alert / Jump Helper (if current day has no bills) */
.quick-jump-banner {
  background: var(--yellow-light);
  border: 1px solid var(--yellow-border);
  border-radius: var(--radius-md);
  padding: 12px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 13.5px;
  color: #92400e;
}

.quick-jump-banner strong {
  font-weight: 700;
}

.quick-jump-link {
  background: var(--yellow-500);
  color: #ffffff;
  padding: 5px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
}

.quick-jump-link:hover {
  background: var(--yellow-600);
}

/* -------------------------------------------------------------
   FILTER TOOLBAR (PREMIUM CARD)
   ------------------------------------------------------------- */
.filter-card {
  background: var(--card-bg);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
  padding: 18px 22px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.filter-left-form {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-secondary);
}

.filter-select, .filter-input {
  padding: 9px 13px;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border-subtle);
  background: #ffffff;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-primary);
  outline: none;
  transition: all 0.2s ease;
  font-family: inherit;
}

.filter-select:focus, .filter-input:focus {
  border-color: var(--yellow-500);
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

.filter-pills {
  display: flex;
  background: #f1f5f9;
  border-radius: var(--radius-sm);
  padding: 3px;
  gap: 2px;
}

.filter-pill-btn {
  padding: 6px 12px;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: 6px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.filter-pill-btn:hover {
  color: var(--text-primary);
}

.filter-pill-btn.active {
  background: #ffffff;
  color: var(--navy-900);
  box-shadow: var(--shadow-sm);
}

.metric-pill-btn.active-sales {
  background: var(--blue-500);
  color: #ffffff;
}

.metric-pill-btn.active-profit {
  background: var(--emerald-500);
  color: #ffffff;
}

/* -------------------------------------------------------------
   KPI CARDS GRID
   ------------------------------------------------------------- */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
}

.kpi-card {
  background: var(--card-bg);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
  padding: 22px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: transparent;
}

.kpi-revenue::before { background: linear-gradient(90deg, var(--yellow-400), var(--yellow-600)); }
.kpi-profit::before { background: linear-gradient(90deg, #10b981, #059669); }
.kpi-gross::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
.kpi-expense::before { background: linear-gradient(90deg, #ef4444, #b91c1c); }

.kpi-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.kpi-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.kpi-title {
  font-size: 12.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-secondary);
}

.kpi-period {
  font-size: 11.5px;
  color: var(--text-muted);
  font-weight: 500;
}

.kpi-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.icon-yellow {
  background: var(--yellow-light);
  color: var(--yellow-600);
  border: 1px solid var(--yellow-border);
}

.icon-emerald {
  background: var(--emerald-light);
  color: var(--emerald-600);
  border: 1px solid var(--emerald-border);
}

.icon-blue {
  background: var(--blue-light);
  color: var(--blue-500);
  border: 1px solid #bfdbfe;
}

.icon-rose {
  background: var(--rose-light);
  color: var(--rose-600);
  border: 1px solid var(--rose-border);
}

.kpi-value {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.5px;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.kpi-profit .kpi-value { color: var(--emerald-600); }
.kpi-expense .kpi-value { color: var(--rose-600); }

.kpi-footer {
  border-top: 1px dashed var(--border-subtle);
  padding-top: 12px;
  margin-top: 4px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 12px;
  color: var(--text-secondary);
}

.kpi-footer-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.badge-profit-pct {
  background: var(--emerald-light);
  color: var(--emerald-600);
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 11px;
}

/* -------------------------------------------------------------
   CHARTS SECTION (PREMIUM INTERACTIVE CANVAS)
   ------------------------------------------------------------- */
.chart-section {
  background: var(--card-bg);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  border-bottom: 1px solid var(--border-light);
  padding-bottom: 16px;
}

.chart-title-area h3 {
  font-size: 17px;
  font-weight: 800;
  color: var(--navy-900);
  display: flex;
  align-items: center;
  gap: 8px;
}

.chart-title-area p {
  font-size: 12.5px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.chart-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.chart-canvas-container {
  position: relative;
  height: 380px;
  width: 100%;
}

.chart-drilldown-hint {
  font-size: 11.5px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 5px;
  background: #f8fafc;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  align-self: flex-start;
}

/* -------------------------------------------------------------
   SECONDARY STATS STRIP
   ------------------------------------------------------------- */
.stats-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.strip-item {
  background: var(--card-bg);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-md);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--shadow-sm);
}

.strip-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.strip-content {
  display: flex;
  flex-direction: column;
}

.strip-label {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
}

.strip-val {
  font-size: 17px;
  font-weight: 800;
  color: var(--text-primary);
}

/* -------------------------------------------------------------
   BILLS & TRANSACTIONS TABLE
   ------------------------------------------------------------- */
.table-section {
  background: var(--card-bg);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.table-header {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  border-bottom: 1px solid var(--border-subtle);
}

.table-header-left h3 {
  font-size: 17px;
  font-weight: 800;
  color: var(--navy-900);
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-header-left p {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.table-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.search-box {
  display: flex;
  align-items: center;
  border: 1.5px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  background: #ffffff;
  padding: 0 10px;
  gap: 8px;
  transition: border-color 0.2s;
}

.search-box:focus-within {
  border-color: var(--yellow-500);
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
}

.search-box input {
  border: none;
  outline: none;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary);
  padding: 8px 0;
  width: 180px;
}

.btn-table-action {
  background: #ffffff;
  border: 1.5px solid var(--border-subtle);
  color: var(--text-secondary);
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-table-action:hover {
  background: #f8fafc;
  color: var(--text-primary);
  border-color: #cbd5e1;
}

.table-wrapper {
  overflow-x: auto;
  width: 100%;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
  text-align: left;
}

.premium-table th {
  background: #f8fafc;
  color: var(--navy-900);
  font-weight: 700;
  padding: 13px 18px;
  border-bottom: 1px solid var(--border-subtle);
  font-size: 12.5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.premium-table td {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border-light);
  color: var(--text-primary);
  vertical-align: middle;
}

.premium-table tbody tr {
  transition: background-color 0.15s;
}

.premium-table tbody tr:hover {
  background-color: #f8fafc;
}

.bill-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: 'Consolas', monospace;
  font-weight: 700;
  color: var(--navy-900);
  background: #e2e8f0;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 12.5px;
}

.salesman-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #475569;
  font-weight: 600;
}

.disc-pill {
  font-size: 11px;
  background: var(--yellow-light);
  color: #b45309;
  border: 1px solid var(--yellow-border);
  padding: 2px 6px;
  border-radius: 6px;
  font-weight: 700;
  margin-left: 5px;
}

.sale-amount {
  font-weight: 700;
  color: var(--navy-950);
}

.profit-amount {
  font-weight: 700;
  color: var(--emerald-600);
}

.btn-view-items {
  background: var(--navy-900);
  color: #ffffff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all 0.15s;
}

.btn-view-items:hover {
  background: var(--navy-800);
  box-shadow: var(--shadow-sm);
}

.btn-delete-bill {
  background: var(--rose-light);
  color: var(--rose-600);
  border: 1px solid var(--rose-border);
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  margin-left: 6px;
  transition: all 0.15s;
}

.btn-delete-bill:hover {
  background: var(--rose-600);
  color: #ffffff;
}

/* Expandable Bill Items Row */
.details-row {
  display: none;
  background-color: #fafbfc;
}

.details-wrapper {
  padding: 16px 20px;
  border-left: 3px solid var(--yellow-500);
  background: #ffffff;
  margin: 8px 18px 16px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-subtle);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
}

.details-summary-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  background: #f8fafc;
  padding: 10px 14px;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  margin-bottom: 12px;
  font-size: 12.5px;
  color: var(--text-secondary);
}

.details-summary-strip strong {
  color: var(--navy-900);
}

.sub-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}

.sub-table th {
  background: #f1f5f9;
  color: var(--text-secondary);
  font-weight: 700;
  padding: 8px 10px;
  text-transform: none;
  letter-spacing: normal;
  border-bottom: 1px solid var(--border-subtle);
}

.sub-table td {
  padding: 10px;
  border-bottom: 1px solid var(--border-light);
  color: var(--text-primary);
}

.thumb-img {
  width: 32px;
  height: 32px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  cursor: pointer;
  transition: transform 0.15s;
}

.thumb-img:hover {
  transform: scale(1.15);
  box-shadow: var(--shadow-sm);
}

/* -------------------------------------------------------------
   MODAL DIALOG (OPERATING EXPENSES)
   ------------------------------------------------------------- */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(11, 21, 45, 0.65);
  backdrop-filter: blur(4px);
  z-index: 2000;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: #ffffff;
  border-radius: var(--radius-lg);
  max-width: 440px;
  width: 100%;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  animation: modalSlide 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-subtle);
}

@keyframes modalSlide {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
  padding: 18px 22px;
  background: linear-gradient(135deg, var(--navy-950), var(--navy-900));
  color: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  font-size: 16px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 20px;
  cursor: pointer;
  line-height: 1;
}

.modal-close-btn:hover {
  color: #ffffff;
}

.modal-body {
  padding: 22px;
}

.form-field {
  margin-bottom: 16px;
}

.form-field label {
  display: block;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-secondary);
  margin-bottom: 6px;
}

.form-field input {
  width: 100%;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border-subtle);
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  outline: none;
}

.form-field input:focus {
  border-color: var(--yellow-500);
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

.modal-footer {
  padding: 14px 22px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid var(--border-light);
}

.btn-modal-cancel {
  background: #f1f5f9;
  color: var(--text-secondary);
  border: none;
  padding: 9px 16px;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
}

.btn-modal-save {
  background: linear-gradient(135deg, var(--yellow-400), var(--yellow-500));
  color: var(--navy-950);
  border: none;
  padding: 9px 18px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  box-shadow: 0 2px 6px var(--yellow-glow);
}

/* -------------------------------------------------------------
   IMAGE LIGHTBOX MODAL
   ------------------------------------------------------------- */
#imageModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(5px);
  z-index: 3000;
  align-items: center;
  justify-content: center;
  cursor: zoom-out;
}

#imageModal img {
  max-width: 90%;
  max-height: 85vh;
  border-radius: var(--radius-md);
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  border: 3px solid #ffffff;
}

/* -------------------------------------------------------------
   PRINT STYLES
   ------------------------------------------------------------- */
@media print {
  .sidebar, .topbar, .filter-card, .chart-controls, .btn-sidebar-toggle, .btn-view-items, .btn-delete-bill, .quick-jump-banner, .btn-table-action, .search-box {
    display: none !important;
  }
  .main-wrapper {
    margin-left: 0 !important;
  }
  .dashboard-container {
    padding: 0 !important;
  }
  body {
    background: #ffffff !important;
  }
  .kpi-card, .chart-section, .table-section {
    border: 1px solid #cbd5e1 !important;
    box-shadow: none !important;
  }
  .details-row {
    display: table-row !important;
  }
}

/* -------------------------------------------------------------
   RESPONSIVE (MOBILE & TABLET)
   ------------------------------------------------------------- */
@media screen and (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .sidebar-overlay.open {
    display: block;
  }
  .main-wrapper {
    margin-left: 0;
  }
  .btn-sidebar-toggle {
    display: inline-flex;
  }
  .kpi-grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  }
}

@media screen and (max-width: 768px) {
  .topbar {
    padding: 0 16px;
    height: 64px;
  }
  .topbar-actions .btn-pos span {
    display: none;
  }
  .dashboard-container {
    padding: 16px;
    gap: 18px;
  }
  .filter-card {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-left-form {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-group {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-select, .filter-input, .btn-action-yellow {
    width: 100%;
  }
  .chart-canvas-container {
    height: 280px;
  }
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  .search-box input {
    width: 100%;
  }
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .stats-strip {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<!-- Mobile Sidebar Backdrop Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- -------------------------------------------------------------
     NAVY SIDEBAR
     ------------------------------------------------------------- -->
<aside class="sidebar" id="appSidebar">
  <!-- Brand Header -->
  <div class="sidebar-brand">
    <div class="brand-icon">
      ⚡
    </div>
    <div class="brand-text">
      <h1>
        ASLAM POS
        <span class="brand-badge">PRO</span>
      </h1>
      <p>Billing & Inventory System</p>
    </div>
  </div>

  <!-- Navigation Menu -->
  <nav class="sidebar-nav">
    <div class="nav-section-title">Overview & Analytics</div>
    
    <a href="reports.php?type=daily" class="nav-link <?= $filterType === 'daily' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      </div>
      <span>Daily Dashboard</span>
      <?php if ($filterType === 'daily'): ?>
        <span class="nav-pill"><?= $billsCount ?> Bills</span>
      <?php endif; ?>
    </a>

    <a href="reports.php?type=weekly" class="nav-link <?= $filterType === 'weekly' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      </div>
      <span>Weekly Report</span>
    </a>

    <a href="reports.php?type=monthly" class="nav-link <?= $filterType === 'monthly' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
      </div>
      <span>Monthly Performance</span>
    </a>

    <a href="reports.php?type=annual" class="nav-link <?= $filterType === 'annual' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
      </div>
      <span>Annual Summary</span>
    </a>

    <div class="nav-section-title">POS & Operations</div>

    <a href="bill.php" class="nav-link">
      <div class="nav-icon" style="color: var(--yellow-400);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
      </div>
      <span>POS Checkout Terminal</span>
      <span class="nav-pill" style="background: rgba(245, 158, 11, 0.2); color: var(--yellow-400);">Go ➜</span>
    </a>


    <div class="nav-section-title">Tools & Reports</div>

    <a href="javascript:void(0)" onclick="openExpenseModal()" class="nav-link">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
      </div>
      <span>Manage Expenses</span>
    </a>

    <a href="javascript:void(0)" onclick="window.print()" class="nav-link">
      <div class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      </div>
      <span>Print / PDF Report</span>
    </a>
  </nav>

  <!-- User Profile Info -->
  <div class="sidebar-footer">
    <div class="user-avatar">
      AM
    </div>
    <div class="user-details">
      <div class="user-name">Aslam Malik</div>
      <div class="user-role">
        <span class="status-dot"></span> Store Admin
      </div>
    </div>
  </div>
</aside>

<!-- -------------------------------------------------------------
     MAIN CONTENT WRAPPER
     ------------------------------------------------------------- -->
<div class="main-wrapper">
  <!-- Top Navigation Bar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="btn-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>

      <div class="topbar-title">
        <h2>
          <span><?= htmlspecialchars($displayTitle) ?></span>
        </h2>
        <div class="time-pill">
          🕒 Live System Time: <strong><?= date('d M Y, h:i A') ?></strong> (PKT)
        </div>
      </div>
    </div>

    <div class="topbar-actions">
      <a href="bill.php" class="btn-top btn-pos" title="Open POS Billing Counter">
        <span>🛒</span> <span>POS Terminal</span>
      </a>
      <button onclick="openExpenseModal()" class="btn-top btn-action-yellow" title="Log Operating Expenses">
        <span>💰</span> <span>Expenses</span>
      </button>
    </div>
  </header>

  <!-- Dashboard Body Container -->
  <main class="dashboard-container">
    
    <!-- Quick Jump Banner if viewing a date with 0 sales and a recent active date exists -->
    <?php if ($filterType === 'daily' && $billsCount === 0 && !empty($latestActiveDate) && $latestActiveDate !== $filterDate): ?>
      <div class="quick-jump-banner">
        <div>
          ℹ️ No bills recorded for today (<strong><?= date('d M Y', strtotime($filterDate)) ?></strong>) yet. Latest transactions were recorded on <strong><?= date('d M Y', strtotime($latestActiveDate)) ?></strong>.
        </div>
        <a href="reports.php?type=daily&date=<?= htmlspecialchars($latestActiveDate) ?>" class="quick-jump-link">
          <span>📅 Jump to <?= date('d M', strtotime($latestActiveDate)) ?> Data</span> ➜
        </a>
      </div>
    <?php endif; ?>

    <!-- -------------------------------------------------------------
         FILTER CONTROLS CARD
         ------------------------------------------------------------- -->
    <div class="filter-card">
      <form method="GET" class="filter-left-form" id="filterForm">
        <!-- Metric hidden or switcher -->
        <input type="hidden" name="metric" id="hiddenMetric" value="<?= htmlspecialchars($filterMetric) ?>">

        <div class="filter-group">
          <label class="filter-label" for="filterType">Timeframe:</label>
          <select name="type" id="filterType" class="filter-select" onchange="handleTypeChange()">
            <option value="daily" <?= $filterType === 'daily' ? 'selected' : '' ?>>📅 Daily</option>
            <option value="weekly" <?= $filterType === 'weekly' ? 'selected' : '' ?>>📊 Weekly</option>
            <option value="monthly" <?= $filterType === 'monthly' ? 'selected' : '' ?>>📆 Monthly</option>
            <option value="annual" <?= $filterType === 'annual' ? 'selected' : '' ?>>📈 Annual</option>
          </select>
        </div>

        <div class="filter-group" id="filterInputContainer">
          <!-- Daily Date Input -->
          <input type="date" name="date" id="filterDate" class="filter-input" value="<?= htmlspecialchars($filterDate) ?>" style="<?= $filterType !== 'daily' ? 'display:none;' : '' ?>">
          
          <!-- Weekly Week Input -->
          <input type="week" name="week" id="filterWeek" class="filter-input" value="<?= htmlspecialchars($filterWeek) ?>" style="<?= $filterType !== 'weekly' ? 'display:none;' : '' ?>">

          <!-- Monthly Month Input -->
          <input type="month" name="month" id="filterMonth" class="filter-input" value="<?= htmlspecialchars($filterMonth) ?>" style="<?= $filterType !== 'monthly' ? 'display:none;' : '' ?>">

          <!-- Annual Year Selector -->
          <select name="year" id="filterYear" class="filter-select" style="<?= $filterType !== 'annual' ? 'display:none;' : '' ?>">
            <?php foreach ($availableYears as $yr): ?>
              <option value="<?= $yr ?>" <?= ($filterYear == $yr) ? 'selected' : '' ?>><?= $yr ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn-top btn-action-yellow">
          <span>Apply Filter</span> ➜
        </button>
      </form>

      <!-- Metric Switcher Pills: Profit vs Sales -->
      <div class="filter-pills">
        <a href="<?= buildUrl(['metric' => 'profit']) ?>" class="filter-pill-btn <?= $filterMetric === 'profit' ? 'active metric-pill-btn active-profit' : '' ?>" title="Show Net Profit focus">
          <span>📈 Profit Focus</span>
        </a>
        <a href="<?= buildUrl(['metric' => 'sales']) ?>" class="filter-pill-btn <?= $filterMetric === 'sales' ? 'active metric-pill-btn active-sales' : '' ?>" title="Show Sales Revenue focus">
          <span>💳 Revenue Focus</span>
        </a>
      </div>
    </div>

    <!-- -------------------------------------------------------------
         MODERN KPI CARDS
         ------------------------------------------------------------- -->
    <div class="kpi-grid">
      <!-- 1. Total Sales / Revenue -->
      <div class="kpi-card kpi-revenue">
        <div class="kpi-header">
          <div class="kpi-title-wrap">
            <span class="kpi-title">Total Sales (Revenue)</span>
            <span class="kpi-period"><?= htmlspecialchars($periodBadge) ?></span>
          </div>
          <div class="kpi-icon-badge icon-yellow">
            💳
          </div>
        </div>
        <div class="kpi-value">Rs. <?= number_format($totalSales, 2) ?></div>
        <div class="kpi-footer">
          <div class="kpi-footer-row">
            <span>Purchase Cost (COGS):</span>
            <strong>Rs. <?= number_format($purchaseCost, 2) ?></strong>
          </div>
          <div class="kpi-footer-row">
            <span>Gross Profit:</span>
            <strong style="color: var(--emerald-600);">Rs. <?= number_format($totalGrossProfit, 2) ?></strong>
          </div>
        </div>
      </div>

      <!-- 2. Net Profit -->
      <div class="kpi-card kpi-profit">
        <div class="kpi-header">
          <div class="kpi-title-wrap">
            <span class="kpi-title">Net Profit</span>
            <span class="kpi-period">After Operating Expenses</span>
          </div>
          <div class="kpi-icon-badge icon-emerald">
            📈
          </div>
        </div>
        <div class="kpi-value">Rs. <?= number_format($netProfit, 2) ?></div>
        <div class="kpi-footer">
          <div class="kpi-footer-row">
            <span>Net Profit Margin:</span>
            <span class="badge-profit-pct"><?= number_format($profitMargin, 1) ?>%</span>
          </div>
          <div class="kpi-footer-row">
            <span>Gross Margin:</span>
            <strong><?= number_format($grossMargin, 1) ?>%</strong>
          </div>
        </div>
      </div>

      <!-- 3. Operating Expenses -->
      <div class="kpi-card kpi-expense">
        <div class="kpi-header">
          <div class="kpi-title-wrap">
            <span class="kpi-title">Operating Expenses</span>
            <span class="kpi-period">Period Overhead</span>
          </div>
          <div class="kpi-icon-badge icon-rose">
            🧾
          </div>
        </div>
        <div class="kpi-value">Rs. <?= number_format($totalOperatingExpenses, 2) ?></div>
        <div class="kpi-footer">
          <div class="kpi-footer-row">
            <span>Salesman: <strong>Rs. <?= number_format($salesmanExp, 0) ?></strong></span>
            <span>Rent: <strong>Rs. <?= number_format($shopRent, 0) ?></strong></span>
          </div>
          <div class="kpi-footer-row" style="margin-top:2px;">
            <span>Other: <strong>Rs. <?= number_format($otherExp, 0) ?></strong></span>
            <button onclick="openExpenseModal()" style="border:none; background:var(--rose-light); color:var(--rose-600); font-weight:700; padding:2px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Edit Expenses ✎</button>
          </div>
        </div>
      </div>

      <!-- 4. Transactions & Orders -->
      <div class="kpi-card kpi-gross">
        <div class="kpi-header">
          <div class="kpi-title-wrap">
            <span class="kpi-title">Invoices & Ticket</span>
            <span class="kpi-period">Activity Breakdown</span>
          </div>
          <div class="kpi-icon-badge icon-blue">
            🛍️
          </div>
        </div>
        <div class="kpi-value"><?= number_format($billsCount) ?> <span style="font-size:16px; font-weight:600; color:var(--text-secondary);">Bills</span></div>
        <div class="kpi-footer">
          <div class="kpi-footer-row">
            <span>Average Ticket Size:</span>
            <strong>Rs. <?= number_format($avgTicket, 2) ?></strong>
          </div>
          <div class="kpi-footer-row">
            <span>Lifetime Orders:</span>
            <strong><?= number_format($allTime['total_bills']) ?> Orders</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- -------------------------------------------------------------
         SECONDARY LIFETIME / STORE STATS STRIP
         ------------------------------------------------------------- -->
    <div class="stats-strip">
      <div class="strip-item">
        <div class="strip-icon icon-yellow">
          🏆
        </div>
        <div class="strip-content">
          <span class="strip-label">All-Time Revenue</span>
          <span class="strip-val">Rs. <?= number_format($allTime['lifetime_sales'], 2) ?></span>
        </div>
      </div>

      <div class="strip-item">
        <div class="strip-icon icon-emerald">
          💎
        </div>
        <div class="strip-content">
          <span class="strip-label">All-Time Net Profit</span>
          <span class="strip-val">Rs. <?= number_format($allTime['lifetime_profit'], 2) ?></span>
        </div>
      </div>

      <div class="strip-item">
        <div class="strip-icon icon-blue">
          📑
        </div>
        <div class="strip-content">
          <span class="strip-label">Total Bills Issued</span>
          <span class="strip-val"><?= number_format($allTime['total_bills']) ?> Orders</span>
        </div>
      </div>

    </div>

    <!-- -------------------------------------------------------------
         INTERACTIVE CHART SECTION
         ------------------------------------------------------------- -->
    <div class="chart-section" id="chartCard">
      <div class="chart-header">
        <div class="chart-title-area">
          <h3>
            <span>📊</span>
            <span>Performance Trend Analysis</span>
          </h3>
          <p>
            Tracking <?= $filterMetric === 'sales' ? 'Revenue (Sales)' : 'Net Profit' ?> over <?= htmlspecialchars($periodBadge) ?>
          </p>
        </div>

        <div class="chart-controls">
          <button type="button" class="btn-table-action" onclick="toggleChartType()" id="chartTypeToggleBtn">
            <span>🔄 Switch to <?= $chartStyle === 'line' ? 'Bar Chart' : 'Line Curve' ?></span>
          </button>
        </div>
      </div>

      <?php if (!empty($chartLabels)): ?>
        <div class="chart-canvas-container">
          <canvas id="performanceChart"></canvas>
        </div>
        <div class="chart-drilldown-hint">
          💡 <strong>Interactive Drill-down:</strong> Click on any point on the chart to instantly drill down into detailed second-by-second views for that specific day or month!
        </div>
      <?php else: ?>
        <div style="text-align:center; padding: 50px 20px; color: var(--text-muted);">
          <div style="font-size: 36px; margin-bottom: 8px;">📉</div>
          <div style="font-size: 15px; font-weight: 700; color: var(--text-secondary);">No sales transactions found for this period.</div>
          <div style="font-size: 13px; margin-top: 4px;">Try selecting another date from the filter bar above or ringing up a new bill at the POS.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- -------------------------------------------------------------
         BILLS & TRANSACTIONS DETAILED TABLE
         ------------------------------------------------------------- -->
    <div class="table-section">
      <div class="table-header">
        <div class="table-header-left">
          <h3>
            <span>🧾</span>
            <span>Period Invoices & Transaction History</span>
            <span style="font-size:12px; background:#e2e8f0; color:var(--navy-900); padding:2px 8px; border-radius:12px; font-weight:700;">
              <?= count($billsData) ?> Bills
            </span>
          </h3>
          <p>Complete record of customer invoices, item breakdowns, discounts, and margins</p>
        </div>

        <div class="table-header-actions">
          <div class="search-box">
            <span>🔍</span>
            <input type="text" id="billSearchInput" placeholder="Filter by bill #, salesman..." oninput="filterBillsTable()">
          </div>

          <button class="btn-table-action" onclick="exportBillsToCSV()" title="Export this list to CSV / Excel">
            <span>📥 Export CSV</span>
          </button>

          <button class="btn-table-action" onclick="window.print()" title="Print this invoice list">
            <span>🖨️ Print</span>
          </button>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="premium-table" id="billsTable">
          <thead>
            <tr>
              <th>Bill #</th>
              <th>Date & Time</th>
              <th>Salesman</th>
              <th>Total Sale</th>
              <th>Profit</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($billsData)): ?>
              <?php foreach ($billsData as $bill): ?>
                <tr class="bill-row" id="row-<?= $bill['id'] ?>" data-search="<?= strtolower(htmlspecialchars($bill['bill_number'] . ' ' . $bill['salesman'] . ' ' . $bill['bill_date'])) ?>">
                  <td>
                    <span class="bill-badge">#<?= htmlspecialchars($bill['bill_number']) ?></span>
                  </td>
                  <td>
                    <div style="font-weight:600; color:var(--navy-900);"><?= htmlspecialchars($bill['bill_date']) ?></div>
                    <div style="font-size:11.5px; color:var(--text-muted);"><?= htmlspecialchars($bill['bill_time']) ?></div>
                  </td>
                  <td>
                    <span class="salesman-tag">
                      <span>👤</span>
                      <span><?= htmlspecialchars($bill['salesman']) ?></span>
                    </span>
                  </td>
                  <td>
                    <span class="sale-amount">Rs. <?= number_format($bill['grand_total'], 2) ?></span>
                    <?php if (isset($bill['discount_percent']) && floatval($bill['discount_percent']) > 0): ?>
                      <span class="disc-pill">-<?= rtrim(rtrim(number_format($bill['discount_percent'], 2), '0'), '.') ?>%</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="profit-amount">Rs. <?= number_format($bill['total_profit'], 2) ?></span>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <button class="btn-view-items" onclick="toggleDetails(<?= $bill['id'] ?>)">
                      <span>Items</span> ▾
                    </button>
                    <button class="btn-delete-bill" onclick="deleteBill(<?= $bill['id'] ?>)" title="Permanently delete this bill">
                      🗑️
                    </button>
                  </td>
                </tr>

                <!-- Expandable Bill Items Details Row -->
                <tr id="details-<?= $bill['id'] ?>" class="details-row">
                  <td colspan="6" style="padding: 0;">
                    <div class="details-wrapper">
                      <!-- Summary Strip -->
                      <div class="details-summary-strip">
                        <span><strong>Subtotal:</strong> Rs. <?= number_format($bill['sub_total'], 2) ?></span>
                        <?php if (isset($bill['discount_percent']) && floatval($bill['discount_percent']) > 0): ?>
                          <span><strong>Discount (<?= rtrim(rtrim(number_format($bill['discount_percent'], 2), '0'), '.') ?>%):</strong> -Rs. <?= number_format($bill['discount_amount'] ?? ($bill['sub_total'] - $bill['grand_total']), 2) ?></span>
                        <?php endif; ?>
                        <span><strong>Grand Total:</strong> Rs. <?= number_format($bill['grand_total'], 2) ?></span>
                        <span><strong>Paid:</strong> Rs. <?= number_format($bill['payment'], 2) ?></span>
                        <span><strong>Change:</strong> Rs. <?= number_format($bill['change_amount'], 2) ?></span>
                      </div>

                      <?php
                        $itemsQuery = $mysqli->query("SELECT * FROM bill_items WHERE bill_id = " . intval($bill['id']));
                      ?>
                      <table class="sub-table">
                        <thead>
                          <tr>
                            <th style="width: 32%;">Item Description</th>
                            <th style="width: 10%;">Size</th>
                            <th style="width: 12%;">Unit Price</th>
                            <th style="width: 8%;">Qty</th>
                            <th style="width: 10%;">Disc%</th>
                            <th style="width: 14%;">Line Total</th>
                            <th style="width: 14%;">Profit</th>
                            <th style="width: 10%; text-align:right;">Edit</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if ($itemsQuery && $itemsQuery->num_rows > 0): ?>
                            <?php while ($item = $itemsQuery->fetch_assoc()): ?>
                              <tr>
                                <td>
                                  <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if (!empty($item['image'])): ?>
                                      <img src="<?= htmlspecialchars($item['image']) ?>" alt="Photo" class="thumb-img" onclick="openLightbox('<?= htmlspecialchars($item['image']) ?>')" title="Click to view full photo">
                                    <?php endif; ?>
                                    <span style="font-weight: 600;"><?= htmlspecialchars($item['description']) ?></span>
                                  </div>
                                </td>
                                <td><?= htmlspecialchars($item['size'] ?: '-') ?></td>
                                <td>Rs. <?= number_format($item['price'], 2) ?></td>
                                <td><span style="background:#e2e8f0; font-weight:700; padding:1px 6px; border-radius:4px;"><?= htmlspecialchars($item['quantity']) ?></span></td>
                                <td>
                                  <?= (isset($item['discount_percent']) && floatval($item['discount_percent']) > 0) ? '<span style="color:#b45309; font-weight:700;">' . htmlspecialchars($item['discount_percent']) . '%</span>' : '-' ?>
                                </td>
                                <td style="font-weight:700;">Rs. <?= number_format($item['amount'], 2) ?></td>
                                <td style="color: var(--emerald-600); font-weight:700;">Rs. <?= number_format($item['profit'] ?? 0, 2) ?></td>
                                <td style="text-align: right;">
                                  <button class="btn-table-action" style="padding:3px 8px; font-size:11px;" onclick="changeReportItem(<?= $item['id'] ?>, '<?= addslashes($item['description']) ?>', <?= $item['price'] ?>, <?= $item['quantity'] ?>, <?= floatval($item['discount_percent'] ?? 0) ?>)">
                                    ✎ Edit
                                  </button>
                                </td>
                              </tr>
                            <?php endwhile; ?>
                          <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; color:var(--text-muted); padding:12px;">No line items recorded for this invoice.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="text-align:center; padding: 40px; color: var(--text-muted);">
                  <div style="font-size:32px; margin-bottom:8px;">🔍</div>
                  <div style="font-size:15px; font-weight:700; color:var(--text-secondary);">No invoice records found for this period.</div>
                  <div style="font-size:13px; margin-top:4px;">Adjust the filter dates or view all-time history in the navigation.</div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- -------------------------------------------------------------
     EXPENSES EDIT MODAL
     ------------------------------------------------------------- -->
<div class="modal-overlay" id="expenseModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>
        <span>💰</span>
        <span>Operating Expenses — <?= htmlspecialchars($periodBadge) ?></span>
      </h3>
      <button class="modal-close-btn" onclick="closeExpenseModal()">&times;</button>
    </div>

    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="period_type" value="<?= htmlspecialchars($filterType) ?>">
        <input type="hidden" name="period_value" value="<?= htmlspecialchars($periodValue) ?>">

        <div class="form-field">
          <label>Salesman Expense / Salary (Rs.)</label>
          <input type="number" step="0.01" name="salesman_expense" value="<?= htmlspecialchars($salesmanExp) ?>" placeholder="0.00">
        </div>

        <div class="form-field">
          <label>Shop Rent Expense (Rs.)</label>
          <input type="number" step="0.01" name="shop_rent" value="<?= htmlspecialchars($shopRent) ?>" placeholder="0.00">
        </div>

        <div class="form-field">
          <label>Other Operating & Utility Expenses (Rs.)</label>
          <input type="number" step="0.01" name="other_expenses" value="<?= htmlspecialchars($otherExp) ?>" placeholder="0.00">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeExpenseModal()">Cancel</button>
        <button type="submit" name="save_expenses" class="btn-modal-save">Save Expenses</button>
      </div>
    </form>
  </div>
</div>

<!-- Image Preview Lightbox -->
<div id="imageModal" onclick="this.style.display='none'">
  <img id="lightboxImg" src="" alt="Enlarged Product Photo">
</div>

<!-- -------------------------------------------------------------
     JAVASCRIPT LOGIC
     ------------------------------------------------------------- -->
<script>
// Sidebar Toggle (Mobile)
function toggleSidebar() {
  const sidebar = document.getElementById('appSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
}

function closeSidebar() {
  document.getElementById('appSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// Filter Type Switcher
function handleTypeChange() {
  const type = document.getElementById('filterType').value;
  const d = document.getElementById('filterDate');
  const w = document.getElementById('filterWeek');
  const m = document.getElementById('filterMonth');
  const y = document.getElementById('filterYear');

  d.style.display = (type === 'daily') ? 'inline-block' : 'none';
  w.style.display = (type === 'weekly') ? 'inline-block' : 'none';
  m.style.display = (type === 'monthly') ? 'inline-block' : 'none';
  y.style.display = (type === 'annual') ? 'inline-block' : 'none';
}

// Expand / Collapse Line Items
function toggleDetails(id) {
  const row = document.getElementById('details-' + id);
  if (row) {
    if (row.style.display === 'table-row') {
      row.style.display = 'none';
    } else {
      row.style.display = 'table-row';
    }
  }
}

// Lightbox for item thumbnails
function openLightbox(url) {
  const m = document.getElementById('imageModal');
  const img = document.getElementById('lightboxImg');
  img.src = url;
  m.style.display = 'flex';
}

// Operating Expenses Modal
function openExpenseModal() {
  document.getElementById('expenseModal').style.display = 'flex';
}

function closeExpenseModal() {
  document.getElementById('expenseModal').style.display = 'none';
}

// Edit Line Item Endpoint
function changeReportItem(id, currentDesc, currentPrice, currentQty, currentDisc = 0) {
  const newDesc = prompt("Change Item Description:", currentDesc);
  if (newDesc === null) return;
  
  const newPrice = prompt("Change Price (Rs.):", currentPrice);
  if (newPrice === null) return;
  
  const newQty = prompt("Change Quantity:", currentQty);
  if (newQty === null) return;

  const newDisc = prompt("Change Item Discount (%):", currentDisc);
  if (newDisc === null) return;
  
  fetch('update_bill_item.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: id,
      description: newDesc,
      price: parseFloat(newPrice),
      quantity: parseInt(newQty),
      discount_percent: parseFloat(newDisc) || 0
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      location.reload();
    } else {
      alert("Error: " + (data.error || 'Failed to update item'));
    }
  })
  .catch(err => alert("Failed to update item. Please check network."));
}

// Delete Bill Endpoint
function deleteBill(id) {
  if (!confirm('Are you sure you want to permanently delete this bill and all its records?')) return;
  fetch('delete_bill.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      location.reload();
    } else {
      alert('Error deleting bill: ' + (data.error || 'Failed'));
    }
  })
  .catch(err => alert('Network error during deletion'));
}

// Search Filter on Table
function filterBillsTable() {
  const query = document.getElementById('billSearchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('.bill-row');
  
  rows.forEach(r => {
    const text = r.getAttribute('data-search') || '';
    const billId = r.id.replace('row-', '');
    const detailsRow = document.getElementById('details-' + billId);
    
    if (text.includes(query)) {
      r.style.display = '';
    } else {
      r.style.display = 'none';
      if (detailsRow) detailsRow.style.display = 'none';
    }
  });
}

// Export Bills to CSV
function exportBillsToCSV() {
  let csv = ['Bill Number,Date,Time,Salesman,Total Sale (Rs),Total Profit (Rs)'];
  const rows = document.querySelectorAll('.bill-row');
  
  rows.forEach(r => {
    if (r.style.display !== 'none') {
      const cols = r.querySelectorAll('td');
      if (cols.length >= 5) {
        const billNo = cols[0].innerText.replace('#', '').trim();
        const dateParts = cols[1].innerText.trim().split('\n');
        const d = dateParts[0] || '';
        const t = dateParts[1] || '';
        const salesman = cols[2].innerText.replace('👤', '').trim();
        const total = cols[3].innerText.replace('Rs.', '').replace(/,/g, '').trim().split(' ')[0];
        const profit = cols[4].innerText.replace('Rs.', '').replace(/,/g, '').trim();
        csv.push(`"${billNo}","${d}","${t}","${salesman}","${total}","${profit}"`);
      }
    }
  });
  
  const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `Aslam_POS_Report_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

// Chart.js Setup
const chartLabels = <?= json_encode($chartLabels) ?>;
const chartValues = <?= json_encode($chartValues) ?>;
const chartKeys = <?= json_encode($chartKeys) ?>;
let chartInstance = null;
let currentChartType = '<?= $chartStyle ?>';

function initChart() {
  if (!chartLabels || chartLabels.length === 0) return;
  const canvas = document.getElementById('performanceChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // Gradient fill
  let gradient = ctx.createLinearGradient(0, 0, 0, 380);
  const isProfit = '<?= $filterMetric ?>' === 'profit';

  if (isProfit) {
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
  } else {
    gradient.addColorStop(0, 'rgba(245, 158, 11, 0.45)');
    gradient.addColorStop(1, 'rgba(245, 158, 11, 0.02)');
  }

  const primaryColor = isProfit ? '#10b981' : '#f59e0b';

  chartInstance = new Chart(ctx, {
    type: currentChartType,
    data: {
      labels: chartLabels,
      datasets: [{
        label: '<?= $filterMetric === "sales" ? "Total Revenue (Rs.)" : "Net Profit (Rs.)" ?>',
        data: chartValues,
        backgroundColor: currentChartType === 'line' ? gradient : primaryColor,
        borderColor: primaryColor,
        borderWidth: 2.5,
        borderRadius: currentChartType === 'bar' ? 6 : 0,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: primaryColor,
        pointBorderWidth: 2.5,
        pointRadius: 6,
        pointHoverRadius: 9,
        fill: true,
        tension: 0.35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      onClick: (e, activeEls) => {
        if (activeEls.length > 0) {
          const dataIndex = activeEls[0].index;
          const clickedKey = chartKeys[dataIndex];
          const currentType = '<?= $filterType ?>';
          const currentMetric = '<?= $filterMetric ?>';

          if (currentType === 'weekly' || currentType === 'monthly') {
            window.location.href = `reports.php?type=daily&date=${clickedKey}&metric=${currentMetric}`;
          } else if (currentType === 'annual') {
            window.location.href = `reports.php?type=monthly&month=${clickedKey}&metric=${currentMetric}`;
          }
        }
      },
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0b152d',
          titleFont: { size: 13, family: "'Plus Jakarta Sans', sans-serif", weight: '700' },
          bodyFont: { size: 13, family: "'Plus Jakarta Sans', sans-serif", weight: '600' },
          padding: 12,
          cornerRadius: 8,
          displayColors: false,
          callbacks: {
            label: function(context) {
              return '<?= $filterMetric === "sales" ? "Revenue" : "Profit" ?>: Rs. ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(226, 232, 240, 0.7)', drawBorder: false },
          ticks: {
            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
            color: '#64748b',
            callback: function(v) { return 'Rs. ' + v.toLocaleString(); }
          }
        },
        x: {
          grid: { display: false, drawBorder: false },
          ticks: {
            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
            color: '#64748b',
            maxRotation: 45
          }
        }
      }
    }
  });
}

function toggleChartType() {
  if (!chartInstance) return;
  currentChartType = (currentChartType === 'line') ? 'bar' : 'line';
  document.getElementById('chartTypeToggleBtn').innerHTML = `<span>🔄 Switch to ${currentChartType === 'line' ? 'Bar Chart' : 'Line Curve'}</span>`;
  chartInstance.destroy();
  initChart();
}

document.addEventListener('DOMContentLoaded', () => {
  initChart();
});
</script>

</body>
</html>
<?php
// Helper function to build URLs with modified query parameters
function buildUrl($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $k => $v) {
        $params[$k] = $v;
    }
    return 'reports.php?' . http_build_query($params);
}
?>

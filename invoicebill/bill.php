<?php
require_once 'db.php';
check_auth();

$nextBill = 1000;
// Check if table exists before querying
$tableExists = $mysqli->query("SHOW TABLES LIKE 'bills'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stmt = $mysqli->prepare("SELECT MAX(CAST(bill_number AS UNSIGNED)) as max_bill FROM bills WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row && $row['max_bill']) {
            $nextBill = intval($row['max_bill']) + 1;
        }
    }
}
if ($nextBill < 1000) $nextBill = 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aslam Malik Store & Barcode Manager</title>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
<style>
/* Reset */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', sans-serif; background: #e2e8f0; color: #333; height: 100vh; display: flex; flex-direction: column; }

/* Header */
.app-header { background: #0f172a; color: white; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10; }
.app-header h1 { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin: 0; }
.app-header img { height: 32px; border-radius: 4px; background: white; padding: 2px; }
.header-info { display: flex; gap: 15px; font-size: 13px; align-items: center; }
.header-info div { display: flex; align-items: center; gap: 5px; color: #cbd5e1; }
.header-info input { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 4px 8px; border-radius: 4px; width: 100px; font-weight: 600; }
.header-buttons-mobile { display: flex; gap: 5px; align-items: center; }

/* Main Content */
.main-content { display: flex; flex: 1; padding: 18px; gap: 18px; overflow: hidden; }

/* Left Panel: Products Manager */
.products-panel { flex: 1.25; min-width: 460px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; }
.panel-header { padding: 12px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 10px; }
.panel-header-top { display: flex; justify-content: space-between; align-items: center; }
.panel-title-wrap { display: flex; align-items: center; gap: 8px; }
.panel-title-wrap h2 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 6px; }
.panel-count-badge { background: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px; }
.panel-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.btn-toggle-add { background: #0f766e; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.2s; }
.btn-toggle-add:hover { background: #115e59; }
.btn-toggle-add.active { background: #475569; }
.btn-panel-action { background: #6366f1; color: white; border: none; padding: 6px 11px; border-radius: 6px; cursor: pointer; font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 5px; transition: background 0.15s; }
.btn-panel-action:hover { background: #4f46e5; }
.btn-panel-action.btn-green { background: #10b981; }
.btn-panel-action.btn-green:hover { background: #059669; }
.btn-panel-action.btn-excel { background: #107c41; }
.btn-panel-action.btn-excel:hover { background: #0b5c30; }
.btn-panel-action.btn-excel-print { background: #0284c7; }
.btn-panel-action.btn-excel-print:hover { background: #0369a1; }
.rs-hide-label { font-size: 12px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 5px; cursor: pointer; background: #fff; padding: 5px 9px; border-radius: 6px; border: 1px solid #cbd5e1; user-select: none; }
.rs-hide-label input { width: 15px; height: 15px; cursor: pointer; margin: 0; }

/* Collapsible Add/Edit Form */
.add-product-container { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s ease; background: #ffffff; border-bottom: 0 solid #e2e8f0; }
.add-product-container.open { max-height: 1600px; padding: 18px 20px; border-bottom: 2px solid #cbd5e1; overflow-y: auto; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
.add-product-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group label { font-size: 12px; font-weight: 700; color: #334155; display: flex; align-items: center; justify-content: space-between; }
.form-group label .label-hint { font-size: 11px; font-weight: normal; color: #64748b; }
.form-group input { padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 7px; font-size: 13.5px; outline: none; background: #f8fafc; transition: all 0.15s; color: #0f172a; }
.form-group input:focus { border-color: #3b82f6; background: #ffffff; box-shadow: 0 0 0 3px rgba(59,130,246,0.18); }
.form-full { grid-column: span 2; }

/* Enhanced Barcode Section inside Add/Edit Form */
.form-barcode-box {
  grid-column: span 2;
  background: #f0f9ff;
  border: 1.5px solid #bae6fd;
  border-radius: 10px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.form-barcode-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.form-barcode-title {
  font-size: 13px;
  font-weight: 700;
  color: #0369a1;
  display: flex;
  align-items: center;
  gap: 6px;
}
.form-barcode-badge {
  font-size: 11px;
  background: #e0f2fe;
  color: #0284c7;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
  border: 1px solid #bae6fd;
}
.barcode-input-row {
  display: flex;
  gap: 8px;
  align-items: center;
}
.barcode-input-row input {
  flex: 1;
  font-family: 'Consolas', 'Courier New', monospace;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
  padding: 9px 12px;
  border: 1.5px solid #93c5fd;
  border-radius: 7px;
  background: #ffffff;
}
.btn-barcode-action {
  padding: 9px 13px;
  border: none;
  border-radius: 7px;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
  transition: all 0.15s;
}
.btn-barcode-gen {
  background: #0284c7;
  color: white;
  box-shadow: 0 2px 6px rgba(2,132,199,0.25);
}
.btn-barcode-gen:hover {
  background: #0369a1;
}
.btn-barcode-scan {
  background: #475569;
  color: white;
}
.btn-barcode-scan:hover {
  background: #334155;
}

/* Live Visual Barcode Preview inside the Form */
.form-live-barcode-card {
  background: #ffffff;
  border: 1px dashed #93c5fd;
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.form-live-barcode-svg-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 48px;
}
.form-live-barcode-svg-wrap svg {
  max-width: 100%;
  height: 46px !important;
  display: block;
}
.form-live-barcode-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-top: 4px;
  font-size: 11.5px;
}
.form-live-barcode-digits {
  font-family: 'Consolas', 'Courier New', monospace;
  font-weight: 700;
  font-size: 13px;
  color: #0f172a;
  letter-spacing: 1px;
}
.form-live-barcode-status {
  font-weight: 600;
  color: #0284c7;
}

/* Action Buttons */
.form-actions-row { grid-column: span 2; display: flex; gap: 10px; margin-top: 6px; }
.btn-add {
  flex: 1;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 12px 18px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(16,185,129,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
}
.btn-add:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(16,185,129,0.4);
}
.btn-cancel-edit {
  display: none;
  background: #64748b;
  color: white;
  border: none;
  padding: 12px 18px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13.5px;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-cancel-edit:hover { background: #475569; }
.btn-reset-form {
  background: #f1f5f9;
  color: #475569;
  border: 1.5px solid #cbd5e1;
  padding: 12px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-reset-form:hover { background: #e2e8f0; color: #0f172a; }

/* Product Picture Uploader Styles */
.product-image-uploader { display: flex; flex-direction: column; gap: 8px; margin-top: 2px; }
.image-upload-dropzone { border: 2px dashed #93c5fd; border-radius: 8px; padding: 12px 16px; text-align: center; cursor: pointer; background: #f0f9ff; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 12px; user-select: none; }
.image-upload-dropzone:hover, .image-upload-dropzone.dragover { border-color: #2563eb; background: #e0f2fe; }
.dropzone-icon { font-size: 26px; line-height: 1; }
.dropzone-text { text-align: left; font-size: 12.5px; color: #1e293b; }
.dropzone-action { color: #2563eb; font-weight: 700; text-decoration: underline; }
.dropzone-hint { font-size: 11px; color: #64748b; margin-top: 2px; }
.image-preview-wrap { display: none; align-items: center; gap: 12px; background: #ffffff; border: 1.5px solid #3b82f6; border-radius: 8px; padding: 8px 12px; box-shadow: 0 2px 6px rgba(59,130,246,0.1); }
.image-preview-wrap img { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; flex-shrink: 0; }
.image-preview-details { flex: 1; min-width: 0; }
.image-preview-name { font-size: 12.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.image-preview-status { font-size: 11.5px; font-weight: 600; color: #16a34a; margin-top: 2px; }
.btn-remove-image { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.btn-remove-image:hover { background: #fecdd3; }

/* Search & Filter Bar */
.search-filter-bar { padding: 10px 18px; background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 8px; }
.search-box-wrap { position: relative; display: flex; align-items: center; }
.search-box-wrap input { width: 100%; padding: 8px 32px 8px 34px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: #f8fafc; transition: all 0.15s; }
.search-box-wrap input:focus { background: #fff; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
.search-icon { position: absolute; left: 10px; font-size: 14px; color: #94a3b8; pointer-events: none; }
.search-clear-btn { position: absolute; right: 8px; background: #e2e8f0; color: #64748b; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; line-height: 1; display: none; align-items: center; justify-content: center; cursor: pointer; }
.search-clear-btn:hover { background: #cbd5e1; color: #1e293b; }
.filter-tabs { display: flex; gap: 6px; align-items: center; overflow-x: auto; }
.filter-tab { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; white-space: nowrap; transition: all 0.15s; }
.filter-tab:hover { background: #e2e8f0; color: #1e293b; }
.filter-tab.active { background: #0f172a; color: white; border-color: #0f172a; }

/* Products List & Cards */
.products-list { flex: 1; overflow-y: auto; padding: 12px 18px; background: #f1f5f9; display: flex; flex-direction: column; gap: 12px; }
.products-list::-webkit-scrollbar { width: 6px; }
.products-list::-webkit-scrollbar-track { background: #f1f5f9; }
.products-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.products-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.product-card { background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; padding: 12px 14px; display: flex; gap: 12px; align-items: stretch; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
.product-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-color: #cbd5e1; }
.product-card.expired { border-left: 4px solid #ef4444; background: #fffafa; }
.product-card.expiring-soon { border-left: 4px solid #f59e0b; background: #fffdf5; }

.product-card-left { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; }
.product-title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
.product-card-title { font-weight: 700; font-size: 14.5px; color: #0f172a; overflow-wrap: anywhere; }
.badge-status { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
.badge-expired { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.badge-expiring { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

.product-badges-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 6px; }
.pill-stock { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px; }
.pill-stock.in-stock { background: #dcfce7; color: #15803d; }
.pill-stock.low-stock { background: #fef3c7; color: #b45309; }
.pill-stock.out-stock { background: #fee2e2; color: #b91c1c; }
.pill-size { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; background: #f1f5f9; color: #475569; }
.pill-expiry { font-size: 11px; color: #64748b; }

.product-financials-row { display: flex; align-items: baseline; gap: 10px; font-size: 12px; margin-bottom: 8px; padding: 5px 8px; background: #f8fafc; border-radius: 6px; border: 1px solid #f1f5f9; }
.financial-sell { font-weight: 700; color: #0f172a; font-size: 13.5px; }
.financial-purchase { color: #64748b; font-size: 11.5px; }
.financial-profit { color: #16a34a; font-weight: 600; font-size: 11px; margin-left: auto; }

.product-card-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.btn-card-action { padding: 5px 9px; border-radius: 5px; font-size: 11.5px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s; }
.btn-add-to-bill { background: #0f766e; color: white; }
.btn-add-to-bill:hover { background: #115e59; }
.btn-card-edit { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.btn-card-edit:hover { background: #e2e8f0; color: #0f172a; }
.btn-card-delete { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
.btn-card-delete:hover { background: #fee2e2; }

/* Product Selection & Edit Modal Styles */
.product-card.product-card-selected { border-color: #0284c7 !important; background: #f0f9ff !important; box-shadow: 0 0 0 2px #38bdf8 !important; }
.product-card-chk-col { display: flex; align-items: center; justify-content: center; padding: 0 4px; }
.product-card-chk-col input[type="checkbox"], .excel-grid-table input[type="checkbox"], .product-selection-bar input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #107c41; }
tr.row-selected-product { background-color: #e0f2fe !important; }
.btn-print-selected { background: #0284c7; color: white; border: none; padding: 4px 8px; border-radius: 5px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: background 0.15s; }
.btn-print-selected:hover { background: #0369a1; }
.btn-excel-selected { background: #0284c7 !important; color: white !important; font-weight: 700 !important; }
.btn-excel-selected:hover { background: #0369a1 !important; }

.product-card-media { flex: 0 0 76px; width: 76px; height: 76px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; align-self: flex-start; cursor: pointer; position: relative; }
.product-card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
.product-card-media:hover img { transform: scale(1.08); }
.product-card-placeholder { font-size: 26px; opacity: 0.35; user-select: none; }

.product-card-right { flex: 0 0 165px; min-width: 155px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #ffffff; padding: 8px 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); text-align: center; }
.barcode-svg-wrap { width: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden; min-height: 44px; background: #ffffff; }
.barcode-svg-wrap svg { max-width: 100%; height: 42px !important; display: block; }
.barcode-number-display { font-family: 'Consolas', 'Courier New', monospace; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; color: #0f172a; margin: 3px 0 6px 0; cursor: pointer; user-select: all; word-break: break-all; padding: 2px 4px; border-radius: 4px; transition: background 0.15s; }
.barcode-number-display:hover { background: #e0f2fe; color: #0369a1; }
.btn-card-print { width: 100%; background: #3b82f6; color: white; border: none; padding: 6px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; transition: background 0.15s; }
.btn-card-print:hover { background: #2563eb; }

/* Empty state */
.empty-products-state { text-align: center; padding: 40px 20px; color: #64748b; }
.empty-products-icon { font-size: 36px; margin-bottom: 8px; opacity: 0.6; }
.empty-products-title { font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 4px; }
.empty-products-desc { font-size: 12.5px; color: #94a3b8; }

.item-product-cell { display: flex; align-items: center; gap: 6px; min-width: 100px; }
.item-receipt-img, .item-product-cell img { 
  width: 30px; 
  height: 30px; 
  object-fit: cover; 
  border-radius: 4px; 
  border: 1px solid #cbd5e1; 
  flex: 0 0 30px; 
  cursor: pointer; 
  background: #f8fafc;
  transition: transform 0.15s, border-color 0.15s;
}
.item-receipt-img:hover, .item-product-cell img:hover { 
  transform: scale(1.18); 
  border-color: #3b82f6; 
  box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
}
.item-product-info { min-width: 0; flex: 1; width: 100%; }
.available-products-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 15px; flex-wrap: wrap; }
.available-products-options { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
.available-products-table-wrap { overflow-x: auto; }
.available-products-table { min-width: 760px; }

/* ==========================================================================
   Excel-Style Product List & Spreadsheet UI System
   ========================================================================== */
.view-mode-toggle {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  background: #f1f5f9;
  padding: 2px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
}
.btn-view-mode {
  padding: 4px 9px;
  border: none;
  border-radius: 4px;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: all 0.15s;
}
.btn-view-mode:hover { color: #0f172a; }
.btn-view-mode.active {
  background: #ffffff;
  color: #107c41;
  font-weight: 700;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.excel-spreadsheet-container {
  width: 100%;
  background: #f3f6f4;
  border: 1.5px solid #d4d4d4;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  font-family: 'Segoe UI', Calibri, -apple-system, BlinkMacSystemFont, Arial, sans-serif;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}
.excel-window-header {
  background: #107c41;
  color: white;
  padding: 8px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #0c5b30;
}
.excel-window-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  font-weight: 700;
  letter-spacing: 0.2px;
}
.excel-window-icon {
  width: 22px;
  height: 22px;
  background: white;
  color: #107c41;
  border-radius: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 900;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.excel-window-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.excel-toolbar {
  background: #f3f2f1;
  padding: 8px 12px;
  border-bottom: 1px solid #e1dfdd;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.excel-toolbar-group {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.btn-excel-tool {
  background: #ffffff;
  color: #323130;
  border: 1px solid #c8c6c4;
  padding: 5px 11px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all 0.15s;
}
.btn-excel-tool:hover {
  background: #e1dfdd;
  border-color: #a19f9d;
}
.btn-excel-tool.btn-excel-primary {
  background: #107c41;
  color: white;
  border-color: #0c5b30;
}
.btn-excel-tool.btn-excel-primary:hover {
  background: #0b5c30;
}
.excel-formula-bar {
  background: #ffffff;
  border-bottom: 1px solid #d4d4d4;
  padding: 4px 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #323130;
}
.excel-name-box {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 3px;
  padding: 2px 8px;
  font-weight: 700;
  color: #0f172a;
  min-width: 50px;
  text-align: center;
  font-family: Consolas, monospace;
}
.excel-fx-symbol {
  font-style: italic;
  font-family: 'Times New Roman', serif;
  font-weight: bold;
  font-size: 14px;
  color: #64748b;
  user-select: none;
}
.excel-formula-input {
  flex: 1;
  font-family: Consolas, 'Segoe UI', monospace;
  font-size: 12px;
  color: #1e293b;
  background: transparent;
  border: none;
  outline: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Excel Grid Canvas & Table (Authentic Spreadsheet Look) */
.excel-grid-wrapper {
  overflow: auto;
  background: #ffffff;
  max-height: 62vh;
  position: relative;
}
.excel-grid-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  font-size: 12px;
  color: #000000;
  font-family: 'Segoe UI', Calibri, -apple-system, sans-serif;
  table-layout: auto;
}
.excel-grid-table thead tr.excel-col-letters-row th {
  background: #f3f2f1;
  color: #605e5c;
  font-size: 10px;
  font-weight: 700;
  text-align: center;
  padding: 2px 4px;
  border: 1px solid #d4d4d4;
  user-select: none;
}
.excel-grid-table thead tr.excel-headers-row th {
  background: #eef2f0;
  color: #201f1e;
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 8px;
  border: 1px solid #d4d4d4;
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 2;
  box-shadow: inset 0 -1px 0 #d4d4d4;
}
.excel-grid-table thead tr.excel-headers-row th.th-excel-green {
  background: #dff0d8;
  color: #107c41;
}
.excel-grid-table tbody td {
  border: 1px solid #d4d4d4;
  padding: 5px 8px;
  white-space: nowrap;
  vertical-align: middle;
  background: #ffffff;
  color: #201f1e;
  font-size: 12px;
}
.excel-grid-table tbody tr:hover td {
  background: #e8f5e9 !important;
}
.excel-grid-table tbody tr.selected td {
  background: #dff6dd !important;
  border-top-color: #107c41 !important;
  border-bottom-color: #107c41 !important;
}
.excel-row-num-cell {
  background: #f3f2f1 !important;
  color: #605e5c !important;
  font-weight: 700 !important;
  text-align: center !important;
  width: 38px !important;
  min-width: 38px !important;
  user-select: none;
  font-size: 11px !important;
  border-right: 1.5px solid #c8c6c4 !important;
}
.excel-grid-table tbody tr.row-out-stock td { background: #fef2f2; }
.excel-grid-table tbody tr.row-low-stock td { background: #fffbeb; }
.excel-grid-table tbody tr.row-expired td { background: #fee2e2; }

.excel-cell-num {
  text-align: right;
  font-variant-numeric: tabular-nums;
  font-family: Consolas, 'Segoe UI', monospace;
  font-weight: 600;
}
.excel-cell-center { text-align: center; }

.excel-stock-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: 700;
}
.excel-stock-in { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.excel-stock-low { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.excel-stock-out { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

/* Embedded Excel Mode inside Left Panel */
.products-list.excel-mode {
  padding: 6px;
  background: #f3f6f4;
  overflow: auto;
}
.products-list.excel-mode .excel-spreadsheet-container {
  box-shadow: none;
  border: 1px solid #cbd5e1;
}

/* Excel Status Bar */
.excel-status-bar {
  background: #107c41;
  color: white;
  padding: 5px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11.5px;
  font-weight: 600;
  border-top: 1px solid #0c5b30;
  user-select: none;
}
.excel-status-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.excel-sheet-tab {
  background: white;
  color: #107c41;
  padding: 2px 10px;
  border-radius: 3px 3px 0 0;
  font-weight: 700;
  font-size: 11px;
}
.excel-status-right {
  display: flex;
  align-items: center;
  gap: 15px;
  font-family: Consolas, 'Segoe UI', monospace;
  font-size: 11px;
}

/* Right Panel: Small Invoice */
.invoice-panel { flex: 1; display: flex; flex-direction: column; align-items: center; overflow-y: auto; }
.scanner-wrapper { width: 380px; margin-bottom: 15px; }
.scanner-wrapper input { width: 100%; padding: 15px; font-size: 16px; border: 2px solid #cbd5e1; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; }
.scanner-wrapper input:focus { border-color: #3b82f6; outline: none; }

.receipt-card { width: 380px; background: white; padding: 16px 12px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 20px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #000000; font-weight: 700; box-sizing: border-box; }
.receipt-header { text-align: center; margin-bottom: 15px; color: #000000; }
.receipt-header img { width: 80px; margin-bottom: 5px; }
.receipt-header h3 { font-size: 18px; font-weight: 900; margin: 0; color: #000000; letter-spacing: 0.5px; }
.receipt-header h5 { font-size: 13.5px; font-weight: 800; margin: 3px 0 0 0; color: #000000; }
.receipt-header div { font-size: 12px; font-weight: 700; color: #000000; }
.receipt-header span { font-weight: 800; color: #000000; }

.invoice-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 10px; color: #000000; font-weight: 700; table-layout: fixed; }
.invoice-table th { border-bottom: 2px solid #000000; padding: 5px 2px; font-weight: 900; color: #000000; }
.invoice-table th.col-desc { text-align: left; width: 40%; }
.invoice-table th.col-size { text-align: center; width: 13%; }
.invoice-table th.col-qty { text-align: center; width: 11%; }
.invoice-table th.col-disc { text-align: center; width: 13%; }
.invoice-table th.col-price { text-align: right; width: 18%; }
.invoice-table th.col-action { text-align: center; width: 5%; }
.invoice-table td { padding: 4px 2px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #000000; font-weight: 700; overflow: visible; }
.invoice-table input { width: 100%; border: none; font-size: 12px; font-weight: 700; background: transparent; outline: none; color: #000000; box-sizing: border-box; min-width: 0; padding: 0; margin: 0; }
.invoice-table input[type="number"]::-webkit-outer-spin-button,
.invoice-table input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.invoice-table input[type="number"] {
  -moz-appearance: textfield;
}
.invoice-table .input-desc { text-align: left; }
.invoice-table .input-size { text-align: center; }
.invoice-table .input-qty { text-align: center; }
.invoice-table .input-item-disc { text-align: center; color: #b45309; font-weight: 800; }
.invoice-table .input-price { text-align: right; font-weight: 800; }
.invoice-table input:focus { background: #f0fdf4; }
.btn-scan-row { font-size: 16px; cursor: pointer; text-decoration: none; display: inline-block; }

.summary-table { width: 100%; font-size: 13px; margin-bottom: 15px; border-top: 2px solid #000000; padding-top: 10px; color: #000000; font-weight: 700; }
.summary-table td { padding: 4px 0; color: #000000; font-weight: 700; vertical-align: middle; }
.summary-table input { width: 95px; text-align: right; border: none; font-weight: 900; font-size: 13px; outline: none; color: #000000; }
.summary-table .editable { border-bottom: 1.5px solid #000000; }

.discount-presets {
  display: inline-flex;
  gap: 2px;
  align-items: center;
  flex-wrap: wrap;
}
.btn-disc-preset {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 1px 5px;
  font-size: 10px;
  font-weight: 700;
  color: #334155;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-disc-preset:hover {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}
.btn-disc-preset.active {
  background: #0f172a;
  color: white;
  border-color: #0f172a;
}

.receipt-footer { text-align: center; font-size: 11px; margin-top: 10px; border-top: 2px dashed #000000; padding-top: 10px; color: #000000; font-weight: 700; }
.receipt-footer p { color: #000000; font-weight: 800; margin: 3px 0; }

.paper-size-container {
  width: 380px;
  margin-bottom: 10px;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 6px rgba(0,0,0,0.03);
  box-sizing: border-box;
}
.paper-size-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}
.paper-size-select {
  padding: 6px 10px;
  border: 1.5px solid #94a3b8;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 700;
  color: #0f172a;
  background-color: #f8fafc;
  outline: none;
  cursor: pointer;
  transition: all 0.15s;
}
.paper-size-select:focus {
  border-color: #3b82f6;
  background-color: #ffffff;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.15);
}

.action-buttons { display: flex; gap: 10px; width: 380px; }
.action-buttons button { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; font-size: 14px; }
.btn-save-bill { background: #10b981; }
.btn-print-bill { background: #6366f1; }

.print-text { display: none; }

/* Modals */
#scanner-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; flex-direction: column; justify-content: center; align-items: center; }
#scanner-video { width: 100%; max-width: 400px; border: 4px solid #3b82f6; border-radius: 12px; }

/* Print Styles */
#print-barcode-area { display: none; }
#print-product-list-container { display: none; }

@media print {
  /* Excel Product List Print Layout */
  body.printing-product-list {
    background: #ffffff !important;
    color: #000000 !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    overflow: visible !important;
    display: block !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    font-family: 'Segoe UI', Calibri, Arial, sans-serif !important;
  }
  body.printing-product-list * {
    color: #000000 !important;
    text-shadow: none !important;
    box-shadow: none !important;
  }
  body.printing-product-list .app-header,
  body.printing-product-list .main-content,
  body.printing-product-list #scanner-container,
  body.printing-product-list #available-products-modal,
  body.printing-product-list #image-lightbox-modal,
  body.printing-product-list #excel-product-list-modal,
  body.printing-product-list #modal-edit-product,
  body.printing-product-list #print-barcode-area,
  body.printing-product-list .excel-print-hide {
    display: none !important;
  }
  body.printing-product-list #print-product-list-container {
    display: block !important;
    width: 100% !important;
    padding: 6mm !important;
    box-sizing: border-box !important;
  }
  body.printing-product-list .excel-print-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 10.5px !important;
    margin-top: 8px !important;
    page-break-inside: auto;
  }
  body.printing-product-list .excel-print-table tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }
  body.printing-product-list .excel-print-table th,
  body.printing-product-list .excel-print-table td {
    border: 1px solid #000000 !important;
    padding: 4px 6px !important;
    color: #000000 !important;
  }
  body.printing-product-list .excel-print-table th {
    background: #e2e8f0 !important;
    font-weight: 800 !important;
    text-align: center !important;
    font-size: 11px !important;
  }
  body.printing-product-list .excel-print-table td.cell-num {
    text-align: right !important;
    font-family: Consolas, monospace !important;
    font-weight: 700 !important;
  }
  body.printing-product-list .excel-print-table tfoot td {
    background: #f1f5f9 !important;
    font-weight: 900 !important;
    border-top: 2px solid #000000 !important;
    border-bottom: 3px double #000000 !important;
  }

  body.printing-barcodes .main-content, 
  body.printing-barcodes .app-header { display: none !important; }
  
  body.printing-barcodes #print-barcode-area { 
    display: flex !important; 
    flex-wrap: wrap; 
    gap: 15px; 
    padding: 0; 
  }
  .barcode-label { 
    width: 200px; 
    padding: 10px; 
    border: 1.5px dashed #000; 
    text-align: center; 
    page-break-inside: avoid; 
    font-family: Arial, sans-serif;
    color: #000000 !important;
  }
  .label-title { font-weight: 900; font-size: 14px; margin-bottom: 5px; color: #000000 !important; }
  .label-desc { font-size: 12px; font-weight: 700; margin-bottom: 5px; color: #000000 !important; }
  .label-price { font-weight: 900; font-size: 16px; margin-top: 5px; color: #000000 !important; }
  
  /* Invoice Print: Crisp, solid black, bold typography for POS-58 thermal printers (58mm x 210mm) */
  @page { 
    size: 58mm 210mm; 
    margin: 0mm; 
  }
  
  body.printing-invoice { 
    background: #ffffff !important; 
    color: #000000 !important; 
    padding: 0 !important; 
    margin: 0 !important; 
    width: 100% !important;
    max-width: 58mm !important;
    height: auto !important; 
    overflow: visible !important; 
    display: block !important; 
    -webkit-print-color-adjust: exact !important; 
    print-color-adjust: exact !important;
    font-family: 'Consolas', 'Courier New', 'Inter', -apple-system, sans-serif !important;
  }
  body.printing-invoice * {
    color: #000000 !important;
    border-color: #000000 !important;
    text-shadow: none !important;
    box-shadow: none !important;
    box-sizing: border-box !important;
  }
  body.printing-invoice .app-header,
  body.printing-invoice .products-panel,
  body.printing-invoice .scanner-wrapper,
  body.printing-invoice .paper-size-container,
  body.printing-invoice .action-buttons,
  body.printing-invoice .print-hide { display: none !important; }
  
  body.printing-invoice .item-receipt-img,
  body.printing-invoice .item-product-cell img,
  body.printing-invoice .invoice-table img {
    display: none !important;
    visibility: hidden !important;
    width: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  body.printing-invoice .item-product-cell {
    display: block !important;
  }
  
  body.printing-invoice .main-content { padding: 0 !important; margin: 0 !important; display: block !important; height: auto !important; overflow: visible !important; }
  body.printing-invoice .invoice-panel { padding: 0 !important; margin: 0 !important; display: block !important; overflow: visible !important; width: 100% !important; height: auto !important; }
  body.printing-invoice .receipt-card { 
    width: 54mm !important; 
    max-width: 58mm !important; 
    margin: 0 auto !important; 
    box-shadow: none !important; 
    padding: 1.5mm 1mm !important; 
    font-size: 10px !important; 
    border: none !important; 
    color: #000000 !important;
    font-weight: 700 !important;
    overflow: hidden !important;
  }
  
  body.printing-invoice .receipt-header {
    text-align: center !important;
    margin-bottom: 6px !important;
    border-bottom: 1.5px dashed #000000 !important;
    padding-bottom: 5px !important;
    color: #000000 !important;
  }
  body.printing-invoice .receipt-header h3 {
    font-size: 14.5px !important;
    font-weight: 900 !important;
    color: #000000 !important;
    margin: 0 0 2px 0 !important;
    letter-spacing: 0.3px !important;
    text-transform: uppercase !important;
  }
  body.printing-invoice .receipt-header h5 {
    font-size: 11.5px !important;
    font-weight: 800 !important;
    color: #000000 !important;
    margin: 1px 0 2px 0 !important;
  }
  body.printing-invoice .receipt-header div,
  body.printing-invoice .receipt-header span {
    font-size: 9.5px !important;
    font-weight: 800 !important;
    color: #000000 !important;
    line-height: 1.25 !important;
  }
  
  body.printing-invoice tr.empty-row { display: none !important; }
  body.printing-invoice .invoice-table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
    margin-bottom: 6px !important;
  }
  body.printing-invoice .invoice-table th { 
    padding: 3px 1px !important; 
    font-size: 9.5px !important; 
    font-weight: 900 !important;
    color: #000000 !important;
    border-top: 1.5px solid #000000 !important;
    border-bottom: 1.5px solid #000000 !important; 
    text-transform: uppercase !important;
    letter-spacing: -0.2px !important;
  }
  body.printing-invoice .invoice-table td { 
    padding: 3px 1px !important; 
    font-size: 10px !important; 
    font-weight: 800 !important;
    color: #000000 !important;
    border-bottom: 1px dashed #000000 !important; 
    word-break: break-word !important; 
    vertical-align: top !important; 
  }
  body.printing-invoice .invoice-table th.col-desc, 
  body.printing-invoice .invoice-table td.col-desc { 
    width: 42% !important; 
    text-align: left !important; 
    word-break: break-word !important; 
    white-space: normal !important; 
  }
  body.printing-invoice .invoice-table th.col-size, 
  body.printing-invoice .invoice-table td.col-size { 
    width: 14% !important; 
    text-align: center !important; 
  }
  body.printing-invoice .invoice-table th.col-qty, 
  body.printing-invoice .invoice-table td.col-qty { 
    width: 11% !important; 
    text-align: center !important; 
  }
  body.printing-invoice .invoice-table th.col-disc, 
  body.printing-invoice .invoice-table td.col-disc { 
    width: 13% !important; 
    text-align: center !important; 
  }
  body.printing-invoice .invoice-table th.col-price, 
  body.printing-invoice .invoice-table td.col-price { 
    width: 20% !important; 
    text-align: right !important; 
    white-space: nowrap !important; 
  }
  
  /* Hide inputs on print and show full crystal-clear text spans */
  body.printing-invoice .invoice-table input,
  body.printing-invoice .summary-table input { 
    display: none !important; 
  }
  body.printing-invoice .print-text { 
    display: block !important; 
    color: #000000 !important; 
  }
  body.printing-invoice .print-desc { 
    text-align: left !important; 
    word-break: break-word !important; 
    white-space: normal !important; 
    overflow-wrap: break-word !important; 
    font-size: 10.5px !important; 
    font-weight: 800 !important; 
    line-height: 1.25 !important; 
  }
  body.printing-invoice .print-size { 
    text-align: center !important; 
    font-size: 10.5px !important; 
    font-weight: 800 !important; 
    line-height: 1.25 !important; 
  }
  body.printing-invoice .print-disc { 
    text-align: center !important; 
    font-size: 9.5px !important; 
    font-weight: 800 !important; 
    line-height: 1.25 !important; 
  }
  body.printing-invoice .print-price { 
    text-align: right !important; 
    font-size: 10.5px !important; 
    font-weight: 900 !important; 
    line-height: 1.25 !important; 
  }
  body.printing-invoice .print-qty { 
    text-align: center !important; 
    font-size: 10.5px !important; 
    font-weight: 800 !important; 
    line-height: 1.25 !important; 
  }
  
  body.printing-invoice .summary-table {
    border-top: 1.5px solid #000000 !important;
    margin-top: 4px !important;
    padding-top: 4px !important;
    width: 100% !important;
  }
  body.printing-invoice .summary-table td { 
    font-size: 10.5px !important; 
    font-weight: 800 !important;
    color: #000000 !important;
    padding: 2px 0 !important;
  }
  body.printing-invoice .summary-table tr.print-hide-row {
    display: none !important;
  }
  body.printing-invoice .summary-table .print-text { 
    font-size: 10.5px !important; 
    font-weight: 900 !important; 
    color: #000000 !important; 
    text-align: right !important; 
    display: inline-block !important; 
  }
  body.printing-invoice #print-grand { 
    font-size: 13.5px !important; 
    font-weight: 900 !important; 
  }
  body.printing-invoice tr:last-child td {
    font-size: 13.5px !important;
    font-weight: 900 !important;
    color: #000000 !important;
    border-top: 1.5px solid #000000 !important;
    padding-top: 3px !important;
  }
  
  body.printing-invoice .receipt-footer {
    border-top: 1.5px dashed #000000 !important;
    margin-top: 8px !important;
    padding-top: 6px !important;
    text-align: center !important;
  }
  body.printing-invoice .receipt-footer p {
    color: #000000 !important;
    font-weight: 900 !important;
    font-size: 9.5px !important;
    margin: 2px 0 !important;
  }
  body.printing-invoice .receipt-footer p:last-child {
    font-size: 8.5px !important;
    font-weight: 700 !important;
    color: #000000 !important;
    margin-top: 4px !important;
  }
}

@media screen and (max-width: 768px) {
  body { height: auto; overflow: visible; }
  .app-header { flex-direction: column; gap: 10px; padding: 15px; text-align: center; }
  .header-info { flex-direction: column; width: 100%; gap: 10px; }
  .header-info div { justify-content: space-between; width: 100%; }
  .header-info input { flex: 1; margin-left: 10px; }
  .header-buttons-mobile { display: flex; flex-wrap: wrap; gap: 5px; width: 100%; justify-content: space-between; }
  .header-info a { margin: 0; flex: 1 1 calc(50% - 5px); text-align: center; padding: 10px; }
  .main-content { flex-direction: column; padding: 10px; overflow: visible; height: auto; }
  .products-panel, .invoice-panel { max-width: 100%; min-width: 0; width: 100%; flex: none; height: auto; }
  .products-list { overflow: visible; max-height: none; overflow-y: visible; }
  .product-card { flex-direction: column; align-items: stretch; gap: 12px; }
  .product-card-right { flex: none; width: 100%; }
  .product-details-layout { grid-template-columns: 1fr; }
  .product-details-image { margin: 0 auto; }
  .add-product-form { grid-template-columns: 1fr; }
  .form-full, .form-actions-row { grid-column: span 1; }
  .panel-header-top { flex-direction: column; align-items: stretch; gap: 10px; }
  .panel-header-actions { width: 100%; justify-content: flex-start; gap: 6px; }
  .scanner-wrapper { width: 100%; }
  .receipt-card { width: 100%; max-width: none; box-shadow: none; border: 1px solid #e2e8f0; overflow-x: auto; padding: 10px; border-radius: 0; margin-bottom: 10px; }
  .action-buttons { flex-direction: column; width: 100%; gap: 10px; }
  .action-buttons button { padding: 15px; }
  #available-products-modal > div { width: 95% !important; padding: 15px !important; }
}
</style>
</head>
<body>

<!-- App Header -->
<div class="app-header">
  <h5>Aslam Software House</h5>
  <div class="header-info">
    <div>Date: <span id="date" style="color:white; font-weight:700;"></span></div>
    <div>Time: <span id="time" style="color:white; font-weight:700;"></span></div>
    <div>Bill #: <input id="bill-number" value="<?php echo $nextBill; ?>" readonly style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #cbd5e1; cursor: not-allowed;"></div>
    <div>Salesman: <input id="salesman" value="Aslam Malik " dir="auto"></div>
    <div class="header-buttons-mobile">
      <button type="button" onclick="openProductListModal()" style="color:black; border:none; background:white  ; padding:6px 11px; border-radius:4px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:background 0.2s;" title="Show Excel-Style Product List & Print">📊 Product List (Excel)</button>
      <a href="reports.php?type=daily&metric=sales" style="color:black; text-decoration:none; background:white; padding:6px 10px; border-radius:4px; font-weight:600; font-size:13px;">📊 Sales Graph</a>
      <a href="reports.php?type=daily" style="color:black; text-decoration:none; background:white; padding:6px 10px; border-radius:4px; font-weight:600; font-size:13px;">📈 Daily</a>
      <a href="reports.php?type=weekly" style="color:black; text-decoration:none; background:white; padding:6px 10px; border-radius:4px; font-weight:600; font-size:13px;">📅 Weekly</a>
      <a href="reports.php?type=monthly" style="color:black; text-decoration:none; background:white; padding:6px 10px; border-radius:4px; font-weight:600; font-size:13px;">📆 Monthly</a>
      <a href="reports.php?type=annual" style="color:black; text-decoration:none; background:white; padding:6px 10px; border-radius:4px; font-weight:600; font-size:13px;">📊 Annual</a>
    </div>
  </div>
</div>

<div class="main-content">
  <datalist id="available-descriptions"></datalist>
  
  <!-- Left Panel: Products & Barcode Manager -->
  <div class="products-panel">
    <div class="panel-header">
      <div class="panel-header-top">
        <div class="panel-title-wrap">
          <h2>📦 Product & Barcode Manager</h2>
          <span class="panel-count-badge" id="product-total-badge">0 Items</span>
        </div>
        <div class="panel-header-actions">
          <button style="background-color: #1706ceff;"  class="btn-toggle-add active" id="btn-toggle-form" onclick="toggleAddForm()" title="Toggle Add/Edit Product Form">
            <span id="btn-toggle-icon">✕</span> <span id="btn-toggle-text">Close Form</span>
          </button>
          <button style="background-color: #1706ceff;" class="btn-panel-action btn-excel" onclick="openProductListModal()" title="Show Excel-Style Product List with Print & Export">📊 Product List</button>
          <button style="background-color: #1706ceff;" class="btn-panel-action btn-excel-print" onclick="printProductList()" title="Print Complete Product List in Excel Format">🖨️ Print List</button>
          <button style="background-color: #1706ceff;" class="btn-panel-action btn-green" onclick="showAvailableProducts()" title="View Inventory Stock">📋 Stock</button>
          <button style="background-color: #1706ceff;"   class="btn-panel-action" onclick="printAllBarcodes()" title="Print All Barcode Labels">🏷️ Barcodes</button>
          <label class="rs-hide-label" title="Hide RS currency text on printed barcodes">
            <input type="checkbox" id="rs-hide-check"> RS Hide
          </label>
        </div>
      </div>
    </div>
    
    <!-- Collapsible Add/Edit Form (Open by default) -->
    <div class="add-product-container open" id="add-product-container">
      <div class="add-product-form">
        <div class="form-group form-full">
          <label for="new-desc">Product Name<span class="label-hint">Item Name / Title</span></label>
          <input type="text" id="new-desc" placeholder="e.g. 1.5mm Copper Cable / W-D47 BLACK" dir="auto">
        </div>
        <div class="form-group">
          <label for="new-size">Size / Variant <span class="label-hint">e.g. 90m / 41 / XL</span></label>
          <input type="text" id="new-size" placeholder="Size or Variant" dir="auto">
        </div>
        <div class="form-group">
          <label for="new-stock">Available Stock <span class="label-hint">Units in inventory</span></label>
          <input id="new-stock" type="number" placeholder="e.g. 50" min="0">
        </div>
        <div class="form-group">
          <label for="new-price">Sell Price (Rs.) * <span class="label-hint">Customer Price</span></label>
          <input id="new-price" type="number" placeholder="e.g. 3500" min="0">
        </div>
        <div class="form-group">
          <label for="new-discount">Discount (%) <span class="label-hint">Default item discount %</span></label>
          <input id="new-discount" type="number" placeholder="e.g. 10" min="0" max="100" step="any">
        </div>
        <div class="form-group">
          <label for="new-purchase-price">Purchase Price (Rs.) <span class="label-hint">Cost Price</span></label>
          <input id="new-purchase-price" type="number" placeholder="e.g. 2500" min="0">
        </div>
        <div class="form-group form-full">
          <label for="new-expire-date">Expire Date <span class="label-hint">Optional for perishable items</span></label>
          <input id="new-expire-date" type="date" title="Expire Date">
        </div>
        
        <!-- Enhanced Barcode Generator Section -->
        <div class="form-barcode-box">
          <div class="form-barcode-header">
            <span class="form-barcode-title">🏷️ Barcode & Label Setup</span>
            <span class="form-barcode-badge" id="form-barcode-type-badge">Code 128 / EAN</span>
          </div>
          <div class="barcode-input-row">
            <input id="new-barcode" placeholder="Enter barcode number or click Auto-Generate" oninput="updateFormBarcodePreview()" autocomplete="off">
            <button type="button" class="btn-barcode-action btn-barcode-gen" onclick="generateRandomFormBarcode()" title="Generate new unique 12-digit barcode">⚡ Auto-Generate</button>
            <button type="button" class="btn-barcode-action btn-barcode-scan" onclick="scanBarcodeToFormField()" title="Scan existing barcode from product using camera">📷 Scan</button>
          </div>
          
          <!-- Live Visual Barcode Preview -->
          <div class="form-live-barcode-card">
            <div class="form-live-barcode-svg-wrap">
              <svg id="form-barcode-svg"></svg>
            </div>
            <div class="form-live-barcode-info">
              <span class="form-live-barcode-digits" id="form-barcode-digits">123456789012</span>
              <span class="form-live-barcode-status" id="form-barcode-status">⚡ Auto-generates on save if blank</span>
            </div>
          </div>
        </div>
        
        <div class="form-group form-full">
          <label>Product Picture <span class="label-hint">Optional Photo for Card & Lightbox</span></label>
          <div class="product-image-uploader">
            <input type="file" id="new-image-file" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="handleProductImageFile(this)">
            <input type="hidden" id="new-image-path" value="">
            
            <div class="image-upload-dropzone" id="image-upload-dropzone" onclick="document.getElementById('new-image-file').click()" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
              <div class="dropzone-icon">📷</div>
              <div class="dropzone-text">
                <span class="dropzone-action">Click to upload product picture</span> or drag & drop image here
                <div class="dropzone-hint">Supports JPG, PNG, WEBP, GIF (Max 10MB)</div>
              </div>
            </div>

            <div class="image-preview-wrap" id="image-preview-wrap">
              <img id="image-preview-elem" src="" alt="Preview" onclick="if(this.src) openLightbox(this.src, 'Selected Product Picture')" title="Click to view full image">
              <div class="image-preview-details">
                <div class="image-preview-name" id="image-preview-name">Selected Picture</div>
                <div class="image-preview-status" id="image-preview-status">✓ Image ready</div>
              </div>
              <button type="button" class="btn-remove-image" onclick="clearProductImage()" title="Remove this picture">✕ Remove</button>
            </div>
          </div>
        </div>
        
        <div class="form-actions-row">
          <button type="button" class="btn-add" id="btn-submit-product" onclick="addProduct()">➕ Save Product & Generate Barcode</button>
          <button type="button" class="btn-cancel-edit" id="btn-cancel-edit" onclick="cancelEditProduct()">✕ Cancel</button>
          <button type="button" class="btn-reset-form" id="btn-reset-form" onclick="resetProductForm()" title="Clear form fields">🔄 Clear</button>
        </div>
      </div>
    </div>
    
    <!-- Search & Filter Bar -->
    <div class="search-filter-bar">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <div class="search-box-wrap" style="flex:1;">
          <span class="search-icon">🔍</span>
          <input type="text" id="search-product" placeholder="Search by name, variant or barcode number..." dir="auto" oninput="handleSearchInput(this)">
          <button class="search-clear-btn" id="search-clear-btn" onclick="clearProductSearch()" title="Clear search">✕</button>
        </div>
        <div class="view-mode-toggle" title="Switch View Mode between Cards and Excel Spreadsheet Grid">
          <button type="button" class="btn-view-mode active" id="btn-view-cards" onclick="setPanelViewMode('cards')" title="Card View">🗂️ Cards</button>
          <button type="button" class="btn-view-mode" id="btn-view-excel" onclick="setPanelViewMode('excel')" title="Excel Spreadsheet Grid View">📊 Excel Grid</button>
        </div>
      </div>
      <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all" onclick="setProductFilter('all', this)">All</button>
        <button class="filter-tab" data-filter="instock" onclick="setProductFilter('instock', this)">In Stock</button>
        <button class="filter-tab" data-filter="lowstock" onclick="setProductFilter('lowstock', this)">Low Stock (≤5)</button>
        <button class="filter-tab" data-filter="expiring" onclick="setProductFilter('expiring', this)">Expiring / Expired</button>
        <button type="button" onclick="printProductList()" style="margin-left:auto; background:#107c41; color:white; border:none; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:background 0.15s;" title="Print All / Filtered Product List in Excel Format">🖨️ Print List</button>
      </div>

      <!-- Multi-Product Selection & Actions Toolbar -->
      <div class="product-selection-bar" id="product-selection-bar" style="display:flex; align-items:center; justify-content:space-between; gap:6px; padding:6px 10px; margin-top:8px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; font-size:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <label style="display:flex; align-items:center; gap:5px; font-weight:700; color:#334155; cursor:pointer; user-select:none; margin:0;">
            <input type="checkbox" id="panel-select-all-chk" onchange="toggleSelectAllProducts(this.checked)" style="width:15px; height:15px; cursor:pointer; accent-color:#107c41;">
            <span>Select All</span>
          </label>
          <span class="selection-badge" id="panel-selected-badge" style="background:#0f172a; color:white; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:700;">0 Selected</span>
        </div>
        <div style="display:flex; align-items:center; gap:5px;">
          <button type="button" class="btn-card-action" onclick="editSelectedProduct()" id="panel-btn-edit-selected" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; font-size:11px; padding:3px 8px; border-radius:5px; font-weight:700;" title="Edit selected product">✏️ Edit Product</button>
          <button type="button" class="btn-card-action btn-print-selected" onclick="printSelectedProducts()" id="panel-btn-print-selected" style="background:#0284c7; color:white; font-size:11px; padding:3px 8px; border-radius:5px; font-weight:700;" title="Print list of selected products only">🖨️ Print Selected (<span id="panel-btn-selected-count">0</span>)</button>
          <button type="button" class="btn-card-action" onclick="clearSelectedProducts()" id="panel-btn-clear-selected" style="background:#ffffff; color:#64748b; border:1px solid #cbd5e1; font-size:11px; padding:3px 6px; border-radius:5px;" title="Clear product selection">✕ Clear</button>
        </div>
      </div>
    </div>
    
    <div class="products-list" id="products-list">
      <!-- Injected by JS -->
    </div>
  </div>

  <!-- Right Panel: Small Invoice Bill -->
  <div class="invoice-panel">
    <div class="scanner-wrapper">
      <input id="global-scanner" type="text" placeholder="📱 Scan Barcode Here..." onchange="handleGlobalScan(this)" oninput="handleGlobalScanInput(this)" autofocus autocomplete="off">
    </div>
    
    <div class="receipt-card" id="receipt-card">
      <div class="receipt-header">
        <h3>ASLAM POS SOFTWARE</h3>
        <h5>0370-1937483</h5>
        <div style="font-size:12px; margin-top:5px;">
          Date: <span id="print-date"></span> | Time: <span id="print-time"></span><br>
          Bill #: <span id="print-bill-number"></span> | Salesman: <span id="print-salesman"></span>
        </div>
      </div>
      
      <table class="invoice-table" id="items">
        <thead>
          <tr>
            <th class="col-desc" style="width:35%">Description</th>
            <th class="col-size" style="width:13%">Size</th>
            <th class="col-qty" style="width:11%">Qty</th>
            <th class="col-disc" style="width:13%">Disc%</th>
            <th class="col-price" style="width:35%">Price</th>
            <th class="col-action print-hide" style="width:5%"></th>
          </tr>
        </thead>
        <tbody>
          <tr data-barcode="">
            <td class="col-desc">
              <div class="item-product-cell">
                <div class="item-product-info">
                  <input class="input-desc" placeholder="Desc" list="available-descriptions" oninput="checkEmptyRow(this)">
                  <span class="print-text print-desc"></span>
                </div>
              </div>
            </td>
            <td class="col-size">
              <input class="input-size" placeholder="-">
              <span class="print-text print-size"></span>
            </td>
            <td class="col-qty">
              <input class="input-qty" type="number" value="1" min="1" oninput="calc()">
              <span class="print-text print-qty"></span>
            </td>
            <td class="col-disc">
              <input class="input-item-disc" type="number" min="0" max="100" step="any" placeholder="0%" oninput="calc()" title="Discount % for this item">
              <span class="print-text print-disc">-</span>
            </td>
            <td class="col-price">
              <input class="input-price" type="number" oninput="calc()">
              <span class="print-text print-price"></span>
            </td>
            <td class="col-action print-hide">
              <span class="btn-scan-row" title="Scan with Camera" onclick="scanBarcode(this)">📷</span>
              <span class="btn-scan-row" title="Remove Item" style="color:#ef4444; margin-left:3px;" onclick="removeRow(this)">🗑️</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="print-hide" style="text-align:center; margin-bottom:10px;">
        <button onclick="addRow()" style="border:none; background:#e2e8f0; padding:5px 10px; border-radius:4px; font-size:12px; cursor:pointer;">+ Add Row</button>
      </div>
      
      <table class="summary-table">
        <tr>
          <td>Total Items: <span id="itemCount">0</span></td>
          <td style="text-align:right">Sub Total</td>
          <td>
            <input id="sub" readonly value="0.00">
            <span class="print-text" id="print-sub">0.00</span>
          </td>
        </tr>
        <tr id="discount-row">
          <td class="print-hide">
            <div class="discount-presets">
              <button type="button" class="btn-disc-preset active" onclick="setDiscount(0)">0%</button>
              <button type="button" class="btn-disc-preset" onclick="setDiscount(5)">5%</button>
              <button type="button" class="btn-disc-preset" onclick="setDiscount(10)">10%</button>
              <button type="button" class="btn-disc-preset" onclick="setDiscount(15)">15%</button>
              <button type="button" class="btn-disc-preset" onclick="setDiscount(20)">20%</button>
            </div>
          </td>
          <td style="text-align:right">Discount (%)</td>
          <td>
            <div class="print-hide" style="display:flex; align-items:center; justify-content:flex-end; gap:2px;">
              <input id="discount" class="editable" type="number" min="0" max="100" step="any" placeholder="0" value="0" oninput="calc()" style="width:45px; text-align:right; font-weight:bold;">
              <span style="font-weight:700; font-size:12px;">%</span>
            </div>
            <span class="print-text" id="print-discount">0.00</span>
          </td>
        </tr>
        <tr id="discount-amount-row" class="print-hide" style="display:none;">
          <td></td>
          <td style="text-align:right; font-size:11px; color:#64748b;">Disc. Amount</td>
          <td>
            <input id="discount-amount" readonly value="0.00" style="color:#ef4444; font-size:12px;">
          </td>
        </tr>
        <tr>
          <td class="print-hide"></td>
          <td style="text-align:right; font-weight:bold; font-size:15px;">Grand Total</td>
          <td>
            <input id="grand" readonly value="0.00" style="color:black; font-size:15px;">
            <span class="print-text" id="print-grand" style="font-weight:900; font-size:13.5px;">0.00</span>
          </td>
        </tr>
        <tr>
          <td class="print-hide"></td>
          <td style="text-align:right">Payment</td>
          <td>
            <input id="payment" class="editable" type="number" min="0" placeholder="0.00" oninput="calc()">
            <span class="print-text" id="print-payment">0.00</span>
          </td>
        </tr>
        <tr>
          <td class="print-hide"></td>
          <td style="text-align:right">Change</td>
          <td>
            <input id="change" readonly value="0.00">
            <span class="print-text" id="print-change">0.00</span>
          </td>
        </tr>
      </table>
      
      <div class="receipt-footer">
        <p style="font-weight:900; font-size:12px; color:#000000; margin-bottom:4px;">THANK YOU FOR YOUR KIND VISIT</p>
        <p style="margin-top:6px; font-size:10px; font-weight:700; color:#000000;">Software by Aslam Malik 0370-1937483</p>
      </div>
    </div>
    
    <div class="paper-size-container print-hide">
      <label class="paper-size-label" for="paper-size-select">
        <span>📄 Paper Size:</span>
      </label>
      <select id="paper-size-select" class="paper-size-select" onchange="applyPaperSize(this.value)" title="Select printer paper size">
        <option value="58x210" selected>58 x 210 mm (POS-58 210mm Roll)</option>
        <option value="58x297">58 x 297 mm (POS-58 297mm Roll)</option>
        <option value="58xauto">58mm x Auto (Continuous)</option>
        <option value="80x210">80 x 210 mm (POS-80 210mm Roll)</option>
        <option value="80x297">80 x 297 mm (POS-80 297mm Roll)</option>
        <option value="80xauto">80mm x Auto (Continuous)</option>
        <option value="a4">A4 (210 x 297 mm)</option>
        <option value="a5">A5 (148 x 210 mm)</option>
      </select>
    </div>

    <div class="action-buttons print-hide">
      <button class="btn-save-bill" onclick="saveBill()">💾 Save Bill</button>
      <button class="btn-print-bill" onclick="printInvoice()">🖨️ Print Bill</button>
    </div>
  </div>
  
</div>

<div id="print-barcode-area"></div>

<div id="scanner-container">
  <h2 style="color:white; margin-bottom:20px; font-family:'Inter', sans-serif;">Point Camera at Barcode</h2>
  <video id="scanner-video"></video>
  <button onclick="stopScanning()" style="margin-top:20px; background:#ef4444; color:white; border:none; padding:12px 24px; border-radius:8px; font-weight:600; cursor:pointer;">Close Camera</button>
</div>

<div id="available-products-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
  <div style="background:white; padding:20px; border-radius:12px; width:700px; max-width:95vw; max-height:80vh; overflow-y:auto;">
    <div class="available-products-header">
      <h2 style="font-size:20px; color:#0f172a; margin:0;">📋 Available Products (In Stock)</h2>
      <div class="available-products-options">
        <label style="font-size:14px; font-weight:600; color:#475569; display:flex; align-items:center; gap:5px; cursor:pointer;">
          <input type="checkbox" id="show-purchase-check" onchange="showAvailableProducts()" style="width:16px; height:16px; cursor:pointer;"> Show Purchase Price
        </label>
        <label style="font-size:14px; font-weight:600; color:#475569; display:flex; align-items:center; gap:5px; cursor:pointer;">
          <input type="checkbox" id="show-profit-check" onchange="showAvailableProducts()" style="width:16px; height:16px; cursor:pointer;"> Show Profit
        </label>
        <label style="font-size:14px; font-weight:600; color:#475569; display:flex; align-items:center; gap:5px; cursor:pointer;">
          <input type="checkbox" id="show-expiring-check" onchange="showAvailableProducts()" style="width:16px; height:16px; cursor:pointer;"> Expiring (< 1 Month)
        </label>
        <label style="font-size:14px; font-weight:600; color:#475569; display:flex; align-items:center; gap:5px; cursor:pointer;">
          <input type="checkbox" id="show-safe-check" onchange="showAvailableProducts()" style="width:16px; height:16px; cursor:pointer;"> Expiring (1-2 Months)
        </label>
        <button onclick="document.getElementById('available-products-modal').style.display='none'" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:600; cursor:pointer;">Close</button>
      </div>
    </div>
    <div class="available-products-table-wrap">
    <table class="table available-products-table" style="width:100%; text-align:left; border-collapse:collapse; font-size:14px;">
      <thead>
        <tr>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b; width:52px; text-align:center;">Photo</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Barcode</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Description</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Size</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Stock</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Exp Date</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Price</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b;">Disc%</th>
          <th id="th-purchase-price" style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b; display:none;">Purchase Price</th>
          <th id="th-profit" style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b; display:none;">Profit</th>
          <th style="border-bottom:2px solid #e2e8f0; padding:10px 5px; color:#64748b; text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody id="available-products-tbody">
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Image Preview Lightbox Modal -->
<div id="image-lightbox-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.85); z-index:3000; align-items:center; justify-content:center; flex-direction:column; backdrop-filter:blur(4px);" onclick="closeLightbox()">
  <div style="position:relative; max-width:90vw; max-height:85vh; display:flex; flex-direction:column; align-items:center;" onclick="event.stopPropagation()">
    <button type="button" onclick="closeLightbox()" style="position:absolute; top:-38px; right:0; background:#ef4444; color:white; border:none; border-radius:50%; width:32px; height:32px; font-size:16px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.3);">✕</button>
    <img id="lightbox-img" src="" alt="Product Picture" style="max-width:90vw; max-height:75vh; border-radius:8px; box-shadow:0 12px 36px rgba(0,0,0,0.5); object-fit:contain; background:#ffffff; border:2px solid #e2e8f0;">
    <div id="lightbox-caption" style="color:white; margin-top:10px; font-size:15px; font-weight:600; text-align:center; max-width:80vw; text-shadow:0 1px 3px rgba(0,0,0,0.8);"></div>
  </div>
</div>

<!-- Dedicated Edit Product Modal -->
<div id="modal-edit-product" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.72); z-index:3500; align-items:center; justify-content:center; backdrop-filter:blur(4px);" onclick="closeEditProductModal(event)">
  <div class="edit-product-modal-card" style="background:#ffffff; border-radius:12px; width:720px; max-width:95vw; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); border:1px solid #cbd5e1; display:flex; flex-direction:column;" onclick="event.stopPropagation()">
    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
      <div style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:22px;">✏️</span>
        <div>
          <h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a;">Edit Product Details</h3>
          <div style="font-size:12px; color:#64748b;">Update product details, pricing, available stock and barcode</div>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span id="edit-modal-barcode-pill" style="font-family:Consolas, monospace; font-size:11.5px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:12px; border:1px solid #bae6fd;"></span>
        <button type="button" onclick="closeEditProductModal()" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; width:30px; height:30px; border-radius:6px; font-size:14px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>
      </div>
    </div>
    
    <!-- Body Form -->
    <div style="padding:18px 20px; display:grid; grid-template-columns:1fr 1fr; gap:14px;">
      <input type="hidden" id="edit-modal-old-barcode">
      
      <div class="form-group" style="grid-column: span 2;">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Product Description / Item Name *</label>
        <input type="text" id="edit-modal-desc" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13.5px; outline:none;" dir="auto" placeholder="Product Title / Description">
      </div>
      
      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Size / Variant</label>
        <input type="text" id="edit-modal-size" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;" dir="auto" placeholder="e.g. 41 / XL / 90m">
      </div>
      
      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Stock Quantity</label>
        <input type="number" id="edit-modal-stock" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;" min="0">
      </div>

      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Sell Price (Rs.) *</label>
        <input type="number" id="edit-modal-price" oninput="updateEditModalCalculations()" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;" min="0">
      </div>

      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Item Discount (%)</label>
        <input type="number" id="edit-modal-discount" oninput="updateEditModalCalculations()" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;" min="0" max="100" placeholder="0">
      </div>

      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Cost / Purchase Price (Rs.)</label>
        <input type="number" id="edit-modal-purchase-price" oninput="updateEditModalCalculations()" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;" min="0">
      </div>

      <div class="form-group">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Expiry Date</label>
        <input type="date" id="edit-modal-expire-date" style="width:100%; padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:6px; font-size:13px; outline:none;">
      </div>

      <!-- Financial margin preview summary box -->
      <div style="grid-column: span 2; display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12.5px;">
        <div>Final Sell Price: <strong id="edit-modal-calc-sell" style="color:#0f172a;">Rs. 0</strong></div>
        <div>Cost Price: <strong id="edit-modal-calc-cost" style="color:#b91c1c;">Rs. 0</strong></div>
        <div>Profit Margin / Unit: <strong id="edit-modal-calc-profit" style="color:#15803d;">+Rs. 0</strong></div>
      </div>

      <!-- Barcode section -->
      <div style="grid-column: span 2; border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#fcfcfc;">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Barcode Number</label>
        <div style="display:flex; gap:8px; margin-bottom:8px;">
          <input type="text" id="edit-modal-barcode" oninput="updateEditModalBarcodePreview()" style="flex:1; padding:7px 10px; border:1.5px solid #cbd5e1; border-radius:6px; font-family:Consolas, monospace; font-size:13px; font-weight:700;">
          <button type="button" onclick="generateRandomEditModalBarcode()" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:6px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer;">⚡ Auto-Gen</button>
        </div>
        <div style="display:flex; align-items:center; justify-content:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px; padding:6px; min-height:46px;">
          <svg id="edit-modal-barcode-svg" style="max-height:42px;"></svg>
        </div>
      </div>

      <!-- Picture section -->
      <div style="grid-column: span 2;">
        <label style="font-size:12.5px; font-weight:700; color:#334155; display:block; margin-bottom:4px;">Product Picture</label>
        <div style="display:flex; align-items:center; gap:12px;">
          <div id="edit-modal-image-preview-wrap" style="width:60px; height:60px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; display:flex; align-items:center; justify-content:center; overflow:hidden;">
            <img id="edit-modal-image-preview" src="" style="width:100%; height:100%; object-fit:cover; display:none;">
            <span id="edit-modal-image-placeholder" style="font-size:22px; opacity:0.4;">📷</span>
          </div>
          <div style="display:flex; flex-direction:column; gap:4px;">
            <input type="file" id="edit-modal-image-file" accept="image/*" style="display:none;" onchange="handleEditModalImageFile(this)">
            <input type="hidden" id="edit-modal-image-path" value="">
            <div style="display:flex; gap:6px;">
              <button type="button" onclick="document.getElementById('edit-modal-image-file').click()" style="padding:5px 10px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:5px; font-size:11.5px; font-weight:600; cursor:pointer;">📁 Choose Picture</button>
              <button type="button" onclick="clearEditModalImage()" style="padding:5px 10px; background:#fff1f2; border:1px solid #fecdd3; color:#e11d48; border-radius:5px; font-size:11.5px; font-weight:600; cursor:pointer;">✕ Remove</button>
            </div>
            <div id="edit-modal-image-status" style="font-size:11px; color:#64748b;">Optional photo for product card and receipts</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer Actions -->
    <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px; padding:14px 20px; border-top:1px solid #e2e8f0; background:#f8fafc;">
      <button type="button" onclick="closeEditProductModal()" style="padding:8px 16px; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600; color:#475569; cursor:pointer;">✕ Cancel</button>
      <button type="button" onclick="saveEditProductModal()" style="padding:8px 20px; background:linear-gradient(135deg, #107c41 0%, #0b5a2f 100%); border:none; border-radius:6px; font-size:13px; font-weight:700; color:white; cursor:pointer; box-shadow:0 2px 6px rgba(16,124,65,0.25);">💾 Save Changes</button>
    </div>
  </div>
</div>

<!-- Full Excel-Style Product List Modal -->
<div id="excel-product-list-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:2500; align-items:center; justify-content:center; backdrop-filter:blur(3px);" onclick="closeProductListModal(event)">
  <div class="excel-modal-card" style="background:#f3f6f4; border:2px solid #107c41; border-radius:10px; width:1240px; max-width:96vw; max-height:92vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 45px rgba(0,0,0,0.35);" onclick="event.stopPropagation()">
    
    <!-- Excel Window Title Bar (Green) -->
    <div class="excel-window-header">
      <div class="excel-window-title">
        <div class="excel-window-icon">X</div>
        <span>Microsoft Excel — Products_Master_Inventory.xlsx [Sheet1]</span>
        <span style="background:rgba(255,255,255,0.2); padding:1px 8px; border-radius:10px; font-size:11px; margin-left:8px;" id="excel-modal-count-badge">0 items</span>
      </div>
      <div class="excel-window-actions">
        <button type="button" onclick="printSelectedProducts()" class="btn-excel-tool btn-excel-selected" id="modal-btn-print-selected" style="background:#0284c7; color:#ffffff; font-weight:700; display:inline-flex; align-items:center; gap:5px;" title="Print only selected products in Excel format">
          🖨️ Print Selected (<span id="modal-selected-count-badge">0</span>)
        </button>
        <button type="button" onclick="printProductList()" class="btn-excel-tool btn-excel-primary" title="Print this complete product list in Excel format">
          🖨️ Print All List
        </button>
        <button type="button" onclick="exportProductListCSV()" class="btn-excel-tool" style="background:#ffffff; color:#107c41; font-weight:700;" title="Export and download as Excel CSV file">
          📥 Export CSV / Excel
        </button>
        <button type="button" onclick="closeProductListModal()" style="background:#ef4444; color:white; border:none; width:28px; height:28px; border-radius:4px; font-size:14px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.15s;" title="Close Excel View">✕</button>
      </div>
    </div>

    <!-- Excel Ribbon & Control Bar -->
    <div class="excel-toolbar">
      <div class="excel-toolbar-group">
        <span style="font-size:12px; font-weight:700; color:#107c41; display:flex; align-items:center; gap:4px;">
          📊 Ribbon:
        </span>
        <div style="position:relative; width:230px;">
          <input type="text" id="excel-search-input" placeholder="Search in spreadsheet..." oninput="handleExcelModalSearch(this.value)" style="width:100%; padding:5px 8px 5px 24px; border:1px solid #c8c6c4; border-radius:4px; font-size:12px; outline:none; background:#ffffff;">
          <span style="position:absolute; left:7px; top:5px; font-size:12px; color:#605e5c;">🔍</span>
        </div>
        <div class="filter-tabs" style="gap:4px;">
          <button type="button" class="filter-tab active" data-excel-filter="all" onclick="setExcelModalFilter('all', this)">All</button>
          <button type="button" class="filter-tab" data-excel-filter="instock" onclick="setExcelModalFilter('instock', this)">In Stock</button>
          <button type="button" class="filter-tab" data-excel-filter="lowstock" onclick="setExcelModalFilter('lowstock', this)">Low Stock (≤5)</button>
          <button type="button" class="filter-tab" data-excel-filter="outstock" onclick="setExcelModalFilter('outstock', this)">Out of Stock</button>
          <button type="button" class="filter-tab" data-excel-filter="expiring" onclick="setExcelModalFilter('expiring', this)">Expiring Soon</button>
        </div>
      </div>
      
      <!-- Selection & Edit Tools -->
      <div class="excel-toolbar-group" style="font-size:12px; display:flex; align-items:center; gap:6px;">
        <button type="button" class="btn-excel-tool" onclick="editSelectedProduct()" id="excel-ribbon-btn-edit" style="background:#fef3c7; color:#92400e; font-weight:700; border:1px solid #fde68a;" title="Edit details of selected product">✏️ Edit Product</button>
        <button type="button" class="btn-excel-tool" onclick="toggleSelectAllExcelProducts(true)" style="padding:3px 7px; font-size:11.5px;">☑️ Select All</button>
        <button type="button" class="btn-excel-tool" onclick="clearSelectedProducts()" style="padding:3px 7px; font-size:11.5px;">⬜ Deselect</button>
        <span id="excel-selected-counter" style="font-size:11.5px; font-weight:700; color:#0284c7; margin-left:4px;">0 Selected</span>
      </div>

      <div class="excel-toolbar-group" style="font-size:12px;">
        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; color:#323130; font-weight:600;">
          <input type="checkbox" id="excel-chk-cost" checked onchange="toggleExcelColumnVisibility()"> Cost Price
        </label>
        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; color:#323130; font-weight:600;">
          <input type="checkbox" id="excel-chk-profit" checked onchange="toggleExcelColumnVisibility()"> Profit / Margin
        </label>
        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; color:#323130; font-weight:600;">
          <input type="checkbox" id="excel-chk-barcode" checked onchange="toggleExcelColumnVisibility()"> Barcode
        </label>
        <label style="display:flex; align-items:center; gap:4px; cursor:pointer; color:#323130; font-weight:600;">
          <input type="checkbox" id="excel-chk-photos" checked onchange="toggleExcelColumnVisibility()"> Photos
        </label>
      </div>
    </div>

    <!-- Excel Formula Bar -->
    <div class="excel-formula-bar">
      <div class="excel-name-box" id="excel-active-cell">A1</div>
      <div class="excel-fx-symbol">fx</div>
      <input type="text" class="excel-formula-input" id="excel-formula-val" readonly value="=PRODUCT_TABLE(Range: A1:L100)">
    </div>

    <!-- Excel Spreadsheet Table (Scrollable Canvas) -->
    <div class="excel-grid-wrapper" id="excel-modal-grid-wrapper">
      <table class="excel-grid-table" id="excel-modal-table">
        <thead>
          <tr class="excel-col-letters-row">
            <th class="excel-print-hide" style="width:34px;">☑</th>
            <th style="width:38px;"></th>
            <th class="col-barcode-cell">A</th>
            <th class="col-photo-cell" style="width:42px;">B</th>
            <th>C</th>
            <th>D</th>
            <th>E</th>
            <th>F</th>
            <th class="col-cost-cell">G</th>
            <th>H</th>
            <th class="col-profit-cell">I</th>
            <th>J</th>
            <th>K</th>
            <th>L</th>
            <th class="excel-print-hide">M</th>
          </tr>
          <tr class="excel-headers-row">
            <th class="excel-print-hide excel-cell-center" style="width:34px;"><input type="checkbox" id="excel-modal-select-all" onchange="toggleSelectAllExcelProducts(this.checked)" title="Select/Deselect All Products" style="width:15px; height:15px; cursor:pointer; accent-color:#107c41;"></th>
            <th class="excel-row-num-cell">#</th>
            <th class="col-barcode-cell">Barcode</th>
            <th class="col-photo-cell" style="width:42px;">Photo</th>
            <th style="text-align:left;">Product Description / Item Name</th>
            <th>Size / Variant</th>
            <th class="th-excel-green">Stock Qty</th>
            <th>Sell Price (Rs.)</th>
            <th class="col-cost-cell">Cost Price (Rs.)</th>
            <th>Disc %</th>
            <th class="col-profit-cell">Profit / Unit</th>
            <th>Total Retail Val</th>
            <th>Expire Date</th>
            <th>Stock Alert</th>
            <th class="excel-print-hide" style="text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody id="excel-modal-tbody">
          <!-- Populated by renderExcelModalTable() -->
        </tbody>
        <tfoot id="excel-modal-tfoot" style="position:sticky; bottom:0; background:#f3f2f1; z-index:3;">
          <!-- Populated by renderExcelModalTable() -->
        </tfoot>
      </table>
    </div>

    <!-- Excel Status Bar (Bottom) -->
    <div class="excel-status-bar">
      <div class="excel-status-left">
        <span>READY 🟢</span>
        <div class="excel-sheet-tab">📋 Sheet1 - Products_Master</div>
      </div>
      <div class="excel-status-right" id="excel-modal-status-stats">
        <span>COUNT: 0</span>
        <span>TOTAL STOCK: 0</span>
        <span>TOTAL COST: Rs. 0</span>
        <span>TOTAL RETAIL: Rs. 0</span>
        <span>EST. PROFIT: Rs. 0</span>
      </div>
    </div>
  </div>
</div>

<!-- Printable Product List Container (Hidden on screen, populated dynamically for @media print) -->
<div id="print-product-list-container"></div>




<script>
// Logic
let products = JSON.parse(localStorage.getItem('products')) || {
  "123456789012": { description: "W-D47 BLACK", size: "41", price: "3499", purchasePrice: "2000", stock: 10 },
  "123456789013": { description: "W-D47 BROWN", size: "42", price: "3499", purchasePrice: "2000", stock: 10 }
};
let currentScanRow = null;

// Clock
setInterval(() => {
  let now = new Date();
  document.getElementById("date").innerText = now.toLocaleDateString('en-GB');
  document.getElementById("time").innerText = now.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', second:'2-digit', hour12: true});
  
  // Sync print header fields
  document.getElementById('print-date').innerText = document.getElementById('date').innerText;
  document.getElementById('print-time').innerText = document.getElementById('time').innerText;
  document.getElementById('print-bill-number').innerText = document.getElementById('bill-number').value;
  document.getElementById('print-salesman').innerText = document.getElementById('salesman').value;
}, 1000);

let samplePreviewBarcode = "123456789012";
let isScanningForFormField = false;
let currentProductFilter = 'all';
let currentExcelModalFilter = 'all';
let currentPanelViewMode = localStorage.getItem('product_panel_view_mode') || 'cards';

// Multi-product selection state (Set of barcodes)
let selectedProducts = new Set();

function updateSelectionUI() {
  const count = selectedProducts.size;

  // Panel toolbar
  const badge = document.getElementById('panel-selected-badge');
  if (badge) badge.innerText = count + (count === 1 ? ' Selected' : ' Selected');
  const countSpan = document.getElementById('panel-btn-selected-count');
  if (countSpan) countSpan.innerText = count;

  // Excel modal counters
  const modalBadge = document.getElementById('modal-selected-count-badge');
  if (modalBadge) modalBadge.innerText = count;
  const excelCounter = document.getElementById('excel-selected-counter');
  if (excelCounter) excelCounter.innerText = count + ' Selected';

  // Select-all checkbox state for panel
  const panelSelectAll = document.getElementById('panel-select-all-chk');
  if (panelSelectAll) {
    const visibleBarcodes = getVisibleProductBarcodes();
    if (visibleBarcodes.length > 0 && visibleBarcodes.every(b => selectedProducts.has(b))) {
      panelSelectAll.checked = true;
      panelSelectAll.indeterminate = false;
    } else if (count > 0 && visibleBarcodes.some(b => selectedProducts.has(b))) {
      panelSelectAll.checked = false;
      panelSelectAll.indeterminate = true;
    } else {
      panelSelectAll.checked = false;
      panelSelectAll.indeterminate = false;
    }
  }

  // Excel modal select-all checkbox
  const excelSelectAll = document.getElementById('excel-modal-select-all');
  if (excelSelectAll) {
    const excelRows = Array.from(document.querySelectorAll('#excel-modal-tbody tr[data-barcode]'));
    if (excelRows.length > 0 && excelRows.every(r => selectedProducts.has(r.getAttribute('data-barcode')))) {
      excelSelectAll.checked = true;
      excelSelectAll.indeterminate = false;
    } else if (excelRows.some(r => selectedProducts.has(r.getAttribute('data-barcode')))) {
      excelSelectAll.checked = false;
      excelSelectAll.indeterminate = true;
    } else {
      excelSelectAll.checked = false;
      excelSelectAll.indeterminate = false;
    }
  }
}

function getVisibleProductBarcodes() {
  // Get currently visible barcodes in card view
  const cards = document.querySelectorAll('#products-list .product-card[data-barcode]');
  return Array.from(cards).map(c => c.getAttribute('data-barcode'));
}

function toggleProductSelection(barcode, checked) {
  if (checked) {
    selectedProducts.add(barcode);
  } else {
    selectedProducts.delete(barcode);
  }
  // Update card visual
  document.querySelectorAll(`.product-card[data-barcode="${CSS.escape(barcode)}"]`).forEach(card => {
    card.classList.toggle('product-card-selected', checked);
    const chk = card.querySelector('.product-card-select-chk');
    if (chk) chk.checked = checked;
  });
  // Update excel row visual
  document.querySelectorAll(`#excel-modal-tbody tr[data-barcode="${CSS.escape(barcode)}"]`).forEach(row => {
    row.classList.toggle('row-selected-product', checked);
    const chk = row.querySelector('.excel-row-select-chk');
    if (chk) chk.checked = checked;
  });
  updateSelectionUI();
}

function toggleSelectAllProducts(checked) {
  const visibleBarcodes = getVisibleProductBarcodes();
  visibleBarcodes.forEach(b => toggleProductSelection(b, checked));
  updateSelectionUI();
}

function toggleSelectAllExcelProducts(checked) {
  const excelRows = document.querySelectorAll('#excel-modal-tbody tr[data-barcode]');
  excelRows.forEach(row => {
    const bc = row.getAttribute('data-barcode');
    if (bc) toggleProductSelection(bc, checked);
  });
  updateSelectionUI();
}

function clearSelectedProducts() {
  selectedProducts.forEach(b => toggleProductSelection(b, false));
  selectedProducts.clear();
  updateSelectionUI();
}

function editSelectedProduct() {
  if (selectedProducts.size === 0) {
    alert('Please select a product to edit first.');
    return;
  }
  if (selectedProducts.size > 1) {
    alert('Please select only ONE product at a time to edit.');
    return;
  }
  const barcode = Array.from(selectedProducts)[0];
  openEditProductModal(barcode);
}

// =============================================
// DEDICATED EDIT PRODUCT MODAL FUNCTIONS
// =============================================
function openEditProductModal(barcode) {
  const p = products[barcode];
  if (!p) { alert('Product not found!'); return; }

  document.getElementById('edit-modal-old-barcode').value = barcode;
  document.getElementById('edit-modal-desc').value = p.description || '';
  document.getElementById('edit-modal-size').value = p.size || '';
  document.getElementById('edit-modal-stock').value = p.stock || 0;
  document.getElementById('edit-modal-price').value = p.price || '';
  document.getElementById('edit-modal-discount').value = (p.discount && parseFloat(p.discount) > 0) ? p.discount : '';
  document.getElementById('edit-modal-purchase-price').value = p.purchasePrice || '';
  document.getElementById('edit-modal-expire-date').value = p.expireDate || '';
  document.getElementById('edit-modal-barcode').value = barcode;
  document.getElementById('edit-modal-image-path').value = p.image || '';

  // Barcode pill
  const pill = document.getElementById('edit-modal-barcode-pill');
  if (pill) pill.innerText = '📋 ' + barcode;

  // Image preview
  if (p.image) {
    document.getElementById('edit-modal-image-preview').src = p.image;
    document.getElementById('edit-modal-image-preview').style.display = 'block';
    document.getElementById('edit-modal-image-placeholder').style.display = 'none';
    document.getElementById('edit-modal-image-status').innerText = '✓ Picture loaded';
  } else {
    clearEditModalImage();
  }

  updateEditModalCalculations();
  updateEditModalBarcodePreview();

  const modal = document.getElementById('modal-edit-product');
  if (modal) modal.style.display = 'flex';
}

function closeEditProductModal(e) {
  if (e && e.target && e.target.id !== 'modal-edit-product') return;
  const modal = document.getElementById('modal-edit-product');
  if (modal) modal.style.display = 'none';
}

function updateEditModalCalculations() {
  const sell = parseFloat(document.getElementById('edit-modal-price')?.value) || 0;
  const disc = parseFloat(document.getElementById('edit-modal-discount')?.value) || 0;
  const cost = parseFloat(document.getElementById('edit-modal-purchase-price')?.value) || 0;
  const finalSell = disc > 0 ? sell * (1 - disc / 100) : sell;
  const profit = finalSell - cost;
  const sellEl = document.getElementById('edit-modal-calc-sell');
  const costEl = document.getElementById('edit-modal-calc-cost');
  const profitEl = document.getElementById('edit-modal-calc-profit');
  if (sellEl) sellEl.innerText = 'Rs. ' + finalSell.toLocaleString();
  if (costEl) costEl.innerText = 'Rs. ' + cost.toLocaleString();
  if (profitEl) {
    profitEl.innerText = (profit >= 0 ? '+' : '') + 'Rs. ' + profit.toLocaleString();
    profitEl.style.color = profit >= 0 ? '#15803d' : '#dc2626';
  }
}

function updateEditModalBarcodePreview() {
  const val = (document.getElementById('edit-modal-barcode')?.value || '').trim();
  const svg = document.getElementById('edit-modal-barcode-svg');
  if (!svg || !val) return;
  try {
    JsBarcode(svg, val, { format: 'CODE128', width: 1.5, height: 38, displayValue: false, margin: 3 });
  } catch (e) {
    svg.innerHTML = '';
  }
}

function generateRandomEditModalBarcode() {
  const code = Math.floor(100000000000 + Math.random() * 900000000000).toString();
  const input = document.getElementById('edit-modal-barcode');
  if (input) { input.value = code; updateEditModalBarcodePreview(); }
}

function handleEditModalImageFile(input) {
  const file = input.files && input.files[0];
  if (!file) return;
  const statusEl = document.getElementById('edit-modal-image-status');
  if (statusEl) statusEl.innerText = '⏳ Processing...';
  const reader = new FileReader();
  reader.onload = function(e) {
    const dataUrl = e.target.result;
    document.getElementById('edit-modal-image-preview').src = dataUrl;
    document.getElementById('edit-modal-image-preview').style.display = 'block';
    document.getElementById('edit-modal-image-placeholder').style.display = 'none';
    document.getElementById('edit-modal-image-path').value = dataUrl;
    if (statusEl) statusEl.innerText = '✓ Picture ready';
    // Try server upload
    const formData = new FormData();
    formData.append('product_image', file);
    fetch('upload_image.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data && data.success && data.url) {
          document.getElementById('edit-modal-image-path').value = data.url;
          document.getElementById('edit-modal-image-preview').src = data.url;
          if (statusEl) statusEl.innerText = '✓ Uploaded to server';
        }
      })
      .catch(() => {/* fallback to dataUrl already set */ });
  };
  reader.readAsDataURL(file);
}

function clearEditModalImage() {
  document.getElementById('edit-modal-image-preview').src = '';
  document.getElementById('edit-modal-image-preview').style.display = 'none';
  document.getElementById('edit-modal-image-placeholder').style.display = '';
  document.getElementById('edit-modal-image-path').value = '';
  document.getElementById('edit-modal-image-status').innerText = 'Optional photo for product card and receipts';
  const fileInput = document.getElementById('edit-modal-image-file');
  if (fileInput) fileInput.value = '';
}

function saveEditProductModal() {
  const oldBarcode = document.getElementById('edit-modal-old-barcode').value.trim();
  const desc = document.getElementById('edit-modal-desc').value.trim();
  const size = document.getElementById('edit-modal-size').value.trim();
  const stock = parseInt(document.getElementById('edit-modal-stock').value) || 0;
  const price = document.getElementById('edit-modal-price').value.trim();
  const discount = parseFloat(document.getElementById('edit-modal-discount').value) || 0;
  const purchasePrice = document.getElementById('edit-modal-purchase-price').value.trim() || 0;
  const expireDate = document.getElementById('edit-modal-expire-date').value.trim();
  const newBarcode = document.getElementById('edit-modal-barcode').value.trim() || oldBarcode;
  const image = document.getElementById('edit-modal-image-path').value.trim();

  if (!desc || !price) { alert('Product Description and Sell Price are required!'); return; }

  if (newBarcode !== oldBarcode) { delete products[oldBarcode]; }
  products[newBarcode] = { description: desc, size, price, discount, purchasePrice, expireDate, stock, image };
  localStorage.setItem('products', JSON.stringify(products));

  // Update selection set if barcode changed
  if (newBarcode !== oldBarcode && selectedProducts.has(oldBarcode)) {
    selectedProducts.delete(oldBarcode);
    selectedProducts.add(newBarcode);
  }

  closeEditProductModal();
  renderProducts();
  renderExcelModalTable && renderExcelModalTable();
  updateSelectionUI();
  alert('✅ Product updated successfully!');
}

// =============================================
// PRINT SELECTED PRODUCTS
// =============================================
function printSelectedProducts() {
  if (selectedProducts.size === 0) {
    alert('Please select at least one product to print.');
    return;
  }

  const printArea = document.getElementById('print-product-list-container');
  if (!printArea) return;

  // ── Read column visibility from the Excel Modal checkboxes ──────────────
  const showBarcode  = document.getElementById('excel-chk-barcode')?.checked !== false;
  const showCost     = document.getElementById('excel-chk-cost')?.checked    !== false;
  const showProfit   = document.getElementById('excel-chk-profit')?.checked  !== false;
  // ────────────────────────────────────────────────────────────────────────

  let rowsHtml = '';
  let rowIdx = 1;
  let totalStock = 0, totalCostVal = 0, totalRetailVal = 0, totalProfitVal = 0;

  const today = new Date(); today.setHours(0,0,0,0);
  const nextMonth = new Date(today); nextMonth.setMonth(nextMonth.getMonth() + 1);

  for (let barcode of selectedProducts) {
    const p = products[barcode];
    if (!p) continue;
    const stockNum = parseInt(p.stock) || 0;
    let isExpired = false, isExpiringSoon = false;
    if (p.expireDate) {
      const exp = new Date(p.expireDate);
      if (exp <= today) isExpired = true;
      else if (exp <= nextMonth) isExpiringSoon = true;
    }
    const sellPrice = parseFloat(p.price) || 0;
    const itemDisc  = parseFloat(p.discount) || 0;
    const discountedSellPrice = itemDisc > 0 ? sellPrice * (1 - itemDisc / 100) : sellPrice;
    const costPrice  = parseFloat(p.purchasePrice) || 0;
    const unitProfit = discountedSellPrice - costPrice;
    const totalLineRetail = stockNum * discountedSellPrice;
    const totalLineCost   = stockNum * costPrice;
    const totalLineProfit = stockNum * unitProfit;

    totalStock += stockNum; totalCostVal += totalLineCost;
    totalRetailVal += totalLineRetail; totalProfitVal += totalLineProfit;

    let statusText = stockNum <= 0 ? 'Out of Stock' : (stockNum <= 5 ? `Low Stock (${stockNum})` : 'In Stock');
    if (isExpired) statusText = 'EXPIRED';
    else if (isExpiringSoon && statusText === 'In Stock') statusText = 'Expiring Soon';

    rowsHtml += `<tr>
      <td style="text-align:center; font-weight:bold; width:30px;">${rowIdx++}</td>
      ${showBarcode ? `<td style="text-align:center; padding:3px 4px;"><svg class="print-barcode-svg" data-barcode="${escapeHtml(barcode)}"></svg></td>` : ''}
      <td><strong>${escapeHtml(p.description)}</strong></td>
      <td style="text-align:center;">${escapeHtml(p.size || '-')}</td>
      <td class="cell-num" style="font-weight:bold;">${stockNum}</td>
      <td class="cell-num">Rs. ${discountedSellPrice.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2})}</td>
      ${showCost   ? `<td class="cell-num">Rs. ${costPrice.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2})}</td>` : ''}
      <td style="text-align:center;">${itemDisc > 0 ? (itemDisc + '%') : '-'}</td>
      ${showProfit ? `<td class="cell-num" style="color:#059669;">Rs. ${unitProfit.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2})}</td>` : ''}
      <td class="cell-num" style="font-weight:bold;">Rs. ${totalLineRetail.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2})}</td>
      <td style="text-align:center;">${p.expireDate || '-'}</td>
      <td style="text-align:center; font-weight:bold;">${statusText}</td>
    </tr>`;
  }

  // Build dynamic column count for "no-results" colspan
  let colCount = 9; // always: #, Description, Variant, Stock, Sell, Disc%, Total, Expiry, Status
  if (showBarcode) colCount++;
  if (showCost)    colCount++;
  if (showProfit)  colCount++;

  // Build header row conditionally
  const theadHtml = `<tr>
    <th style="width:30px;">#</th>
    ${showBarcode ? '<th style="width:130px;">Barcode</th>' : ''}
    <th style="text-align:left;">Description</th>
    <th style="width:65px;">Variant</th>
    <th style="width:50px;">Stock</th>
    <th style="width:85px;">Sell Price</th>
    ${showCost   ? '<th style="width:85px;">Cost Price</th>' : ''}
    <th style="width:50px;">Disc%</th>
    ${showProfit ? '<th style="width:80px;">Profit/Unit</th>' : ''}
    <th style="width:95px;">Total Retail</th>
    <th style="width:75px;">Expiry</th>
    <th style="width:75px;">Status</th>
  </tr>`;

  // Build tfoot totals row
  // Count leading cols before Stock: #, [Barcode], Description, Variant = 3 or 4
  const leadingCols = 3 + (showBarcode ? 1 : 0);
  const tfootHtml = `<tr>
    <td colspan="${leadingCols}" style="text-align:right; font-weight:bold; font-size:11.5px;">TOTALS:</td>
    <td class="cell-num" style="font-weight:900;">${totalStock.toLocaleString()}</td>
    <td></td><!-- sell -->
    ${showCost   ? `<td class="cell-num" style="font-weight:900;">Rs. ${totalCostVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>` : ''}
    <td></td><!-- disc -->
    ${showProfit ? `<td class="cell-num" style="font-weight:900; color:#059669;">Rs. ${totalProfitVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>` : ''}
    <td class="cell-num" style="font-weight:900;">Rs. ${totalRetailVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
    <td colspan="2"></td>
  </tr>`;

  // Visibility note shown in print header
  const hiddenNote = [];
  if (!showBarcode) hiddenNote.push('Barcode');
  if (!showCost)    hiddenNote.push('Cost Price');
  if (!showProfit)  hiddenNote.push('Profit');
  const hiddenText = hiddenNote.length ? `<span style="font-size:10.5px; color:#b91c1c; font-weight:600;">Hidden columns: ${hiddenNote.join(', ')}</span>` : '';

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-GB');
  const timeStr = now.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', second:'2-digit', hour12: true});
  const salesmanName = document.getElementById('salesman')?.value || 'Admin';

  printArea.innerHTML = `
    <div style="font-family:'Segoe UI', Calibri, Arial, sans-serif; color:#000; padding:10px 0;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2.5px solid #107c41; padding-bottom:8px; margin-bottom:12px;">
        <div>
          <h1 style="font-size:20px; font-weight:900; color:#107c41; margin:0 0 4px 0; text-transform:uppercase; letter-spacing:0.5px;">Aslam Malik Store — Selected Products Report</h1>
          <div style="font-size:12.5px; font-weight:700; color:#333;">${selectedProducts.size} Selected Products &nbsp; ${hiddenText}</div>
        </div>
        <div style="text-align:right; font-size:11.5px; font-weight:600; color:#444; line-height:1.4;">
          <div><strong>Date:</strong> ${dateStr} | <strong>Time:</strong> ${timeStr}</div>
          <div><strong>Printed By:</strong> ${escapeHtml(salesmanName)}</div>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-bottom:12px; font-size:11px;">
        <div style="flex:1; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;"><div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Selected</div><div style="font-size:14px; font-weight:900;">${rowIdx - 1} Items</div></div>
        <div style="flex:1; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;"><div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Stock</div><div style="font-size:14px; font-weight:900; color:#15803d;">${totalStock.toLocaleString()} Units</div></div>
        ${showCost   ? `<div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;"><div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Cost</div><div style="font-size:14px; font-weight:900;">Rs. ${totalCostVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div></div>` : ''}
        <div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;"><div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Retail</div><div style="font-size:14px; font-weight:900; color:#107c41;">Rs. ${totalRetailVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div></div>
        ${showProfit ? `<div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;"><div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Est. Profit</div><div style="font-size:14px; font-weight:900; color:#059669;">Rs. ${totalProfitVal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div></div>` : ''}
      </div>
      <table class="excel-print-table">
        <thead>${theadHtml}</thead>
        <tbody>${rowsHtml || `<tr><td colspan="${colCount}" style="text-align:center; padding:15px;">No products selected.</td></tr>`}</tbody>
        <tfoot>${tfootHtml}</tfoot>
      </table>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:10px; color:#666; border-top:1px solid #ccc; padding-top:6px;">
        <div>Software by Aslam Malik 0370-1937483 — POS Billing &amp; Barcode Manager</div>
        <div>Selected Products Report — Generated from Live Database</div>
      </div>
    </div>`;

  // ── Render actual barcode SVGs in the print area ─────────────────────
  if (showBarcode) {
    printArea.querySelectorAll('.print-barcode-svg').forEach(svg => {
      const code = svg.getAttribute('data-barcode');
      if (code) {
        try {
          JsBarcode(svg, code, {
            format: 'CODE128',
            width: 1.2,
            height: 30,
            displayValue: true,
            fontSize: 10,
            font: 'monospace',
            textMargin: 1,
            margin: 2
          });
        } catch (e) {
          svg.outerHTML = `<span style="font-family:monospace; font-size:11px;">${code}</span>`;
        }
      }
    });
  }
  // ─────────────────────────────────────────────────────────────────────

  let styleElem = document.getElementById('dynamic-product-list-print-style');
  if (!styleElem) {
    styleElem = document.createElement('style');
    styleElem.id = 'dynamic-product-list-print-style';
    document.head.appendChild(styleElem);
  }
  styleElem.innerHTML = `@media print { @page { size: landscape; margin: 8mm; } }`;

  document.body.classList.remove('printing-barcodes', 'printing-invoice');
  document.body.classList.add('printing-product-list');
  window.print();
}

function generateRandomFormBarcode() {
  // Generate unique 12-digit numeric barcode
  let code = Math.floor(100000000000 + Math.random() * 900000000000).toString();
  const input = document.getElementById('new-barcode');
  if (input) {
    input.value = code;
    input.focus();
  }
  updateFormBarcodePreview();
}

function updateFormBarcodePreview() {
  const barcodeInput = document.getElementById('new-barcode');
  const svgElem = document.getElementById('form-barcode-svg');
  const digitsElem = document.getElementById('form-barcode-digits');
  const statusElem = document.getElementById('form-barcode-status');
  const typeBadge = document.getElementById('form-barcode-type-badge');
  
  if (!svgElem) return;
  
  const val = barcodeInput ? barcodeInput.value.trim() : '';
  const displayCode = val || samplePreviewBarcode;
  
  try {
    JsBarcode(svgElem, displayCode, {
      format: "CODE128",
      width: 1.6,
      height: 42,
      displayValue: false,
      margin: 3
    });
    
    if (digitsElem) digitsElem.innerText = displayCode;
    if (statusElem) {
      if (val) {
        statusElem.innerHTML = `<span style="color:#059669;">✓ Custom Barcode set: ${escapeHtml(val)}</span>`;
        if (typeBadge) typeBadge.innerText = 'Custom Barcode';
      } else {
        statusElem.innerHTML = `<span style="color:#0284c7;">⚡ Auto-generates on save if left blank</span>`;
        if (typeBadge) typeBadge.innerText = 'Auto-Generate Ready';
      }
    }
  } catch (err) {
    if (digitsElem) digitsElem.innerText = val || 'Invalid Format';
    if (statusElem) statusElem.innerHTML = `<span style="color:#ef4444;">⚠️ Unsupported barcode format</span>`;
  }
}

function scanBarcodeToFormField() {
  isScanningForFormField = true;
  document.getElementById('scanner-container').style.display = 'flex';
  
  Quagga.init({
    inputStream: { name: "Live", type: "LiveStream", target: document.querySelector('#scanner-video'), constraints: { width: 640, height: 480, facingMode: "environment" } },
    decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"] }
  }, function(err) {
    if (err) { alert("Camera failed."); stopScanning(); return; }
    Quagga.start();
  });
  
  Quagga.onDetected(function(result) {
    let code = result.codeResult.code;
    stopScanning();
    if (isScanningForFormField) {
      const barcodeInput = document.getElementById('new-barcode');
      if (barcodeInput) {
        barcodeInput.value = code;
        updateFormBarcodePreview();
      }
      isScanningForFormField = false;
    } else {
      populateProduct(code);
    }
  });
}

function copyBarcodeToClipboard(barcode, btnElem) {
  navigator.clipboard.writeText(barcode).then(() => {
    if (btnElem) {
      let originalText = btnElem.innerHTML;
      btnElem.innerHTML = '✓ Copied!';
      btnElem.style.background = '#dcfce7';
      btnElem.style.color = '#15803d';
      setTimeout(() => {
        btnElem.innerHTML = originalText;
        btnElem.style.background = '';
        btnElem.style.color = '';
      }, 1500);
    } else {
      alert('Barcode copied: ' + barcode);
    }
  }).catch(() => {
    alert('Barcode: ' + barcode);
  });
}

function resetProductForm() {
  const desc = document.getElementById('new-desc');
  const size = document.getElementById('new-size');
  const stock = document.getElementById('new-stock');
  const price = document.getElementById('new-price');
  const discount = document.getElementById('new-discount');
  const purchasePrice = document.getElementById('new-purchase-price');
  const expireDate = document.getElementById('new-expire-date');
  const barcode = document.getElementById('new-barcode');
  
  if (desc) desc.value = '';
  if (size) size.value = '';
  if (stock) stock.value = '';
  if (price) price.value = '';
  if (discount) discount.value = '';
  if (purchasePrice) purchasePrice.value = '';
  if (expireDate) expireDate.value = '';
  if (barcode) barcode.value = '';
  clearProductImage();
  updateFormBarcodePreview();
  
  const submitBtn = document.getElementById('btn-submit-product');
  if (submitBtn) {
    submitBtn.innerHTML = "➕ Save Product & Generate Barcode";
    submitBtn.style.background = "linear-gradient(135deg, #10b981 0%, #059669 100%)";
    submitBtn.onclick = addProduct;
  }
  
  const cancelBtn = document.getElementById('btn-cancel-edit');
  if (cancelBtn) cancelBtn.style.display = 'none';
}

function toggleAddForm(forceState, isEdit = false) {
  const container = document.getElementById('add-product-container');
  const btn = document.getElementById('btn-toggle-form');
  const icon = document.getElementById('btn-toggle-icon');
  const text = document.getElementById('btn-toggle-text');
  
  const isOpen = typeof forceState === 'boolean' ? forceState : !container.classList.contains('open');
  if (isOpen) {
    if (!isEdit && !container.classList.contains('open')) {
      resetProductForm();
    }
    container.classList.add('open');
    btn.classList.add('active');
    if (icon) icon.innerText = '✕';
    if (text) text.innerText = 'Close Form';
    setTimeout(() => {
      const descInput = document.getElementById('new-desc');
      if (descInput) descInput.focus();
    }, 100);
  } else {
    container.classList.remove('open');
    btn.classList.remove('active');
    if (icon) icon.innerText = '➕';
    if (text) text.innerText = 'New Product';
    resetProductForm();
  }
}

function handleSearchInput(input) {
  const clearBtn = document.getElementById('search-clear-btn');
  if (clearBtn) {
    clearBtn.style.display = input.value.trim().length > 0 ? 'inline-flex' : 'none';
  }
  renderProducts();
}

function clearProductSearch() {
  const searchInput = document.getElementById('search-product');
  if (searchInput) {
    searchInput.value = '';
    searchInput.focus();
  }
  const clearBtn = document.getElementById('search-clear-btn');
  if (clearBtn) clearBtn.style.display = 'none';
  renderProducts();
}

function setProductFilter(filterType, tabElem) {
  currentProductFilter = filterType;
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  if (tabElem) tabElem.classList.add('active');
  renderProducts();
}

// Product Picture & Lightbox Functions
function handleProductImageFile(input) {
  const file = input.files && input.files[0];
  if (!file) return;
  processSelectedImage(file);
}

function handleDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropzone = document.getElementById('image-upload-dropzone');
  if (dropzone) dropzone.classList.add('dragover');
}

function handleDragLeave(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropzone = document.getElementById('image-upload-dropzone');
  if (dropzone) dropzone.classList.remove('dragover');
}

function handleDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropzone = document.getElementById('image-upload-dropzone');
  if (dropzone) dropzone.classList.remove('dragover');
  
  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
    const file = e.dataTransfer.files[0];
    if (file.type.startsWith('image/')) {
      processSelectedImage(file);
    } else {
      alert('Please drop a valid image file (JPG, PNG, WEBP, GIF).');
    }
  }
}

function processSelectedImage(file) {
  const statusElem = document.getElementById('image-preview-status');
  const nameElem = document.getElementById('image-preview-name');
  if (statusElem) statusElem.innerText = '⏳ Uploading / Processing image...';
  if (nameElem) nameElem.innerText = file.name;

  const reader = new FileReader();
  reader.onload = function(e) {
    const localDataUrl = e.target.result;
    setProductImagePreview(localDataUrl, file.name);
    uploadProductImageToServer(file, localDataUrl);
  };
  reader.readAsDataURL(file);
}

function uploadProductImageToServer(file, fallbackDataUrl) {
  const formData = new FormData();
  formData.append('product_image', file);
  
  fetch('upload_image.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success' && data.file_path) {
      document.getElementById('new-image-path').value = data.file_path;
      const statusElem = document.getElementById('image-preview-status');
      if (statusElem) statusElem.innerText = '✓ Uploaded successfully';
    } else {
      compressAndFallback(file, fallbackDataUrl);
    }
  })
  .catch(err => {
    compressAndFallback(file, fallbackDataUrl);
  });
}

function compressAndFallback(file, originalDataUrl) {
  const img = new Image();
  img.onload = function() {
    const maxDim = 320;
    let w = img.width;
    let h = img.height;
    if (w > maxDim || h > maxDim) {
      if (w > h) {
        h = Math.round((h * maxDim) / w);
        w = maxDim;
      } else {
        w = Math.round((w * maxDim) / h);
        h = maxDim;
      }
    }
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, w, h);
    const compressedUrl = canvas.toDataURL('image/jpeg', 0.8);
    document.getElementById('new-image-path').value = compressedUrl;
    const statusElem = document.getElementById('image-preview-status');
    if (statusElem) statusElem.innerText = '✓ Ready (Stored locally)';
  };
  img.onerror = function() {
    document.getElementById('new-image-path').value = originalDataUrl;
  };
  img.src = originalDataUrl;
}

function setProductImagePreview(src, name) {
  const previewWrap = document.getElementById('image-preview-wrap');
  const dropzone = document.getElementById('image-upload-dropzone');
  const previewElem = document.getElementById('image-preview-elem');
  const nameElem = document.getElementById('image-preview-name');
  const pathInput = document.getElementById('new-image-path');
  
  if (pathInput) pathInput.value = src || '';
  if (previewElem) previewElem.src = src || '';
  if (nameElem && name) nameElem.innerText = name;
  if (previewWrap) previewWrap.style.display = src ? 'flex' : 'none';
  if (dropzone) dropzone.style.display = src ? 'none' : 'flex';
}

function clearProductImage() {
  const pathInput = document.getElementById('new-image-path');
  const fileInput = document.getElementById('new-image-file');
  const previewWrap = document.getElementById('image-preview-wrap');
  const dropzone = document.getElementById('image-upload-dropzone');
  const previewElem = document.getElementById('image-preview-elem');
  const nameElem = document.getElementById('image-preview-name');
  const statusElem = document.getElementById('image-preview-status');
  
  if (pathInput) pathInput.value = '';
  if (fileInput) fileInput.value = '';
  if (previewElem) previewElem.src = '';
  if (nameElem) nameElem.innerText = 'Selected Picture';
  if (statusElem) statusElem.innerText = '✓ Image ready';
  if (previewWrap) previewWrap.style.display = 'none';
  if (dropzone) dropzone.style.display = 'flex';
}

function openLightbox(src, caption) {
  if (!src) return;
  const modal = document.getElementById('image-lightbox-modal');
  const img = document.getElementById('lightbox-img');
  const cap = document.getElementById('lightbox-caption');
  if (img) img.src = src;
  if (cap) cap.innerText = caption || '';
  if (modal) modal.style.display = 'flex';
}

function closeLightbox() {
  const modal = document.getElementById('image-lightbox-modal');
  if (modal) modal.style.display = 'none';
}

function cancelEditProduct() {
  resetProductForm();
  toggleAddForm(false);
}

// ==========================================================================
// Excel-Style Product List & View Switcher System
// ==========================================================================
function setPanelViewMode(mode) {
  currentPanelViewMode = mode;
  localStorage.setItem('product_panel_view_mode', mode);
  const btnCards = document.getElementById('btn-view-cards');
  const btnExcel = document.getElementById('btn-view-excel');
  if (btnCards) btnCards.classList.toggle('active', mode === 'cards');
  if (btnExcel) btnExcel.classList.toggle('active', mode === 'excel');
  renderProducts();
}

function openProductListModal() {
  const modal = document.getElementById('excel-product-list-modal');
  if (!modal) return;
  modal.style.display = 'flex';
  renderExcelModalTable();
  const searchInput = document.getElementById('excel-search-input');
  if (searchInput) {
    searchInput.value = '';
    searchInput.focus();
  }
}

function closeProductListModal(e) {
  if (e && e.target && e.target.id !== 'excel-product-list-modal') {
    return;
  }
  const modal = document.getElementById('excel-product-list-modal');
  if (modal) modal.style.display = 'none';
}

function handleExcelModalSearch(query) {
  renderExcelModalTable();
}

function setExcelModalFilter(filterType, btnElem) {
  currentExcelModalFilter = filterType;
  document.querySelectorAll('[data-excel-filter]').forEach(b => b.classList.remove('active'));
  if (btnElem) btnElem.classList.add('active');
  renderExcelModalTable();
}

function toggleExcelColumnVisibility() {
  const showCost = document.getElementById('excel-chk-cost')?.checked !== false;
  const showProfit = document.getElementById('excel-chk-profit')?.checked !== false;
  const showBarcode = document.getElementById('excel-chk-barcode')?.checked !== false;
  const showPhotos = document.getElementById('excel-chk-photos')?.checked !== false;
  
  document.querySelectorAll('.col-cost-cell').forEach(el => el.style.display = showCost ? '' : 'none');
  document.querySelectorAll('.col-profit-cell').forEach(el => el.style.display = showProfit ? '' : 'none');
  document.querySelectorAll('.col-barcode-cell').forEach(el => el.style.display = showBarcode ? '' : 'none');
  document.querySelectorAll('.col-photo-cell').forEach(el => el.style.display = showPhotos ? '' : 'none');
}

function selectExcelRow(rowElem, p, barcode, rowIdx) {
  if (!rowElem) return;
  const table = rowElem.closest('table');
  if (table) {
    table.querySelectorAll('tbody tr').forEach(r => r.classList.remove('selected'));
  }
  rowElem.classList.add('selected');
  
  const cellBox = document.getElementById('excel-active-cell');
  if (cellBox) cellBox.innerText = 'C' + (rowIdx + 1);
  
  const formulaInput = document.getElementById('excel-formula-val');
  if (formulaInput && p) {
    const sellPrice = parseFloat(p.price) || 0;
    const costPrice = parseFloat(p.purchasePrice) || 0;
    const profit = sellPrice - costPrice;
    formulaInput.value = `=PRODUCT("${p.description || ''}", Code="${barcode}", Stock=${p.stock || 0}, Sell=Rs.${sellPrice.toLocaleString()}, Cost=Rs.${costPrice.toLocaleString()}, Profit=Rs.${profit.toLocaleString()})`;
  }
}

function renderExcelModalTable() {
  const tbody = document.getElementById('excel-modal-tbody');
  const tfoot = document.getElementById('excel-modal-tfoot');
  const countBadge = document.getElementById('excel-modal-count-badge');
  const statsElem = document.getElementById('excel-modal-status-stats');
  if (!tbody) return;
  
  tbody.innerHTML = '';
  const searchQuery = (document.getElementById('excel-search-input')?.value || "").trim().toLowerCase();
  
  const today = new Date();
  today.setHours(0,0,0,0);
  const nextMonth = new Date(today);
  nextMonth.setMonth(nextMonth.getMonth() + 1);

  let rowIdx = 1;
  let totalStock = 0;
  let totalCostVal = 0;
  let totalRetailVal = 0;
  let totalProfitVal = 0;

  const totalBarcodes = Object.keys(products);

  for (let barcode of totalBarcodes) {
    let p = products[barcode];
    if (!p) continue;

    let stockNum = parseInt(p.stock) || 0;
    let isExpired = false;
    let isExpiringSoon = false;
    if (p.expireDate) {
      let expDate = new Date(p.expireDate);
      if (expDate <= today) isExpired = true;
      else if (expDate <= nextMonth) isExpiringSoon = true;
    }

    // Filter
    if (currentExcelModalFilter === 'instock' && stockNum <= 0) continue;
    if (currentExcelModalFilter === 'lowstock' && (stockNum <= 0 || stockNum > 5)) continue;
    if (currentExcelModalFilter === 'outstock' && stockNum > 0) continue;
    if (currentExcelModalFilter === 'expiring' && !isExpired && !isExpiringSoon) continue;

    // Search query
    if (searchQuery) {
      const descMatch = (p.description || "").toLowerCase().includes(searchQuery);
      const sizeMatch = (p.size || "").toLowerCase().includes(searchQuery);
      const barcodeMatch = barcode.toLowerCase().includes(searchQuery);
      if (!descMatch && !sizeMatch && !barcodeMatch) continue;
    }

    const sellPrice = parseFloat(p.price) || 0;
    const itemDisc = parseFloat(p.discount) || 0;
    const discountedSellPrice = itemDisc > 0 ? (sellPrice * (1 - itemDisc / 100)) : sellPrice;
    const costPrice = parseFloat(p.purchasePrice) || 0;
    const unitProfit = discountedSellPrice - costPrice;
    const totalLineRetail = stockNum * discountedSellPrice;
    const totalLineCost = stockNum * costPrice;
    const totalLineProfit = stockNum * unitProfit;

    totalStock += stockNum;
    totalCostVal += totalLineCost;
    totalRetailVal += totalLineRetail;
    totalProfitVal += totalLineProfit;

    let rowClass = '';
    let stockBadgeClass = 'excel-stock-in';
    let stockStatusText = 'In Stock';
    if (stockNum <= 0) {
      rowClass = 'row-out-stock';
      stockBadgeClass = 'excel-stock-out';
      stockStatusText = 'Out of Stock';
    } else if (stockNum <= 5) {
      rowClass = 'row-low-stock';
      stockBadgeClass = 'excel-stock-low';
      stockStatusText = `Low Stock (${stockNum})`;
    }
    if (isExpired) {
      rowClass = 'row-expired';
      stockStatusText = 'EXPIRED';
    } else if (isExpiringSoon && stockStatusText === 'In Stock') {
      stockStatusText = 'Expiring Soon';
    }

    const isRowSelected = selectedProducts.has(barcode);
    const tr = document.createElement('tr');
    tr.className = rowClass + (isRowSelected ? ' row-selected-product' : '');
    tr.setAttribute('data-barcode', barcode);
    const currentRowIdx = rowIdx;
    tr.onclick = function(e) {
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
        selectExcelRow(this, p, barcode, currentRowIdx);
      }
    };
    tr.ondblclick = function() { 
      populateProduct(barcode); 
      document.getElementById('excel-product-list-modal').style.display = 'none'; 
    };

    tr.innerHTML = `
      <td class="excel-print-hide excel-cell-center" style="width:34px;">
        <input type="checkbox" class="excel-row-select-chk" ${isRowSelected ? 'checked' : ''}
          onclick="event.stopPropagation(); toggleProductSelection('${escapeJs(barcode)}', this.checked)"
          title="Select this product" style="width:15px; height:15px; cursor:pointer; accent-color:#107c41;">
      </td>
      <td class="excel-row-num-cell">${rowIdx}</td>
      <td class="col-barcode-cell excel-cell-center" style="font-family:Consolas, monospace; font-weight:700;">${escapeHtml(barcode)}</td>
      <td class="col-photo-cell excel-cell-center">
        ${p.image ? `<img src="${escapeHtml(p.image)}" style="width:28px; height:28px; object-fit:cover; border-radius:3px; border:1px solid #cbd5e1; cursor:pointer;" onclick="event.stopPropagation(); openLightbox('${escapeJs(p.image)}', '${escapeJs(p.description)}')" title="Click to view photo">` : `<span style="color:#a19f9d;">—</span>`}
      </td>
      <td style="font-weight:700; color:#0f172a;" dir="auto">${escapeHtml(p.description)}</td>
      <td class="excel-cell-center">${escapeHtml(p.size || '-')}</td>
      <td class="excel-cell-num"><span class="excel-stock-badge ${stockBadgeClass}">${stockNum}</span></td>
      <td class="excel-cell-num">Rs. ${discountedSellPrice.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
      <td class="col-cost-cell excel-cell-num" style="color:#b91c1c;">Rs. ${costPrice.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
      <td class="excel-cell-center" style="color:#b45309; font-weight:700;">${itemDisc > 0 ? (itemDisc + '%') : '-'}</td>
      <td class="col-profit-cell excel-cell-num" style="color:#15803d; font-weight:700;">Rs. ${unitProfit.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
      <td class="excel-cell-num" style="font-weight:700;">Rs. ${totalLineRetail.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
      <td class="excel-cell-center" style="color:${isExpired ? '#dc2626' : (isExpiringSoon ? '#d97706' : '#605e5c')}; font-weight:${(isExpired || isExpiringSoon) ? 'bold' : 'normal'};">${p.expireDate || '-'}</td>
      <td class="excel-cell-center"><span style="font-size:11px; font-weight:700; color:${isExpired ? '#dc2626' : (stockNum <= 0 ? '#b91c1c' : (stockNum <= 5 ? '#b45309' : '#15803d'))};">${stockStatusText}</span></td>
      <td class="excel-print-hide excel-cell-center">
        <button class="btn-card-action btn-add-to-bill" onclick="event.stopPropagation(); populateProduct('${escapeJs(barcode)}'); document.getElementById('excel-product-list-modal').style.display='none';" style="padding:3px 7px; font-size:11px;" title="Add to current bill">🛒 Add</button>
        <button class="btn-card-action btn-card-edit" onclick="event.stopPropagation(); openEditProductModal('${escapeJs(barcode)}');" style="padding:3px 6px; font-size:11px;" title="Edit product in modal">✏️ Edit</button>
      </td>
    `;
    tbody.appendChild(tr);
    rowIdx++;
  }

  const itemsCount = rowIdx - 1;
  if (countBadge) countBadge.innerText = `${itemsCount} item${itemsCount === 1 ? '' : 's'}`;

  if (itemsCount === 0) {
    tbody.innerHTML = `<tr><td colspan="14" style="text-align:center; padding:30px 10px; color:#605e5c; font-size:13px;">No products match your filter or search criteria.</td></tr>`;
    if (tfoot) tfoot.innerHTML = '';
  } else if (tfoot) {
    tfoot.innerHTML = `
      <tr style="border-top:2px solid #107c41; font-weight:700;">
        <td class="excel-row-num-cell" style="background:#e1dfdd !important;">Σ</td>
        <td class="col-barcode-cell"></td>
        <td class="col-photo-cell"></td>
        <td style="font-weight:900; color:#107c41;">TOTALS (${itemsCount} Products)</td>
        <td></td>
        <td class="excel-cell-num" style="font-weight:900; color:#15803d;">${totalStock.toLocaleString()}</td>
        <td class="excel-cell-num"></td>
        <td class="col-cost-cell excel-cell-num" style="font-weight:900; color:#b91c1c;">Rs. ${totalCostVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td></td>
        <td class="col-profit-cell excel-cell-num" style="font-weight:900; color:#15803d;">Rs. ${totalProfitVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td class="excel-cell-num" style="font-weight:900; color:#107c41;">Rs. ${totalRetailVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td colspan="3"></td>
      </tr>
    `;
  }

  if (statsElem) {
    statsElem.innerHTML = `
      <span>COUNT: ${itemsCount}</span>
      <span>TOTAL STOCK: ${totalStock.toLocaleString()}</span>
      <span>TOTAL COST: Rs. ${totalCostVal.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</span>
      <span>TOTAL RETAIL: Rs. ${totalRetailVal.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</span>
      <span>EST. PROFIT: Rs. ${totalProfitVal.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</span>
    `;
  }

  toggleExcelColumnVisibility();
}

function renderPanelExcelGrid(list, visibleItems, searchQuery) {
  let rowsHtml = '';
  let rowIdx = 1;
  let totalStock = 0;
  let totalRetailVal = 0;
  let totalCostVal = 0;
  let totalProfitVal = 0;

  for (let item of visibleItems) {
    let barcode = item.barcode;
    let p = item.p;
    let stockNum = item.stockNum;
    let sellPrice = parseFloat(p.price) || 0;
    let itemDisc = parseFloat(p.discount) || 0;
    let discountedSellPrice = itemDisc > 0 ? (sellPrice * (1 - itemDisc / 100)) : sellPrice;
    let costPrice = parseFloat(p.purchasePrice) || 0;
    let profit = discountedSellPrice - costPrice;
    let totalLineRetail = stockNum * discountedSellPrice;

    totalStock += stockNum;
    totalRetailVal += totalLineRetail;
    totalCostVal += (stockNum * costPrice);
    totalProfitVal += (stockNum * profit);

    let stockBadgeClass = 'excel-stock-in';
    let rowClass = '';
    if (stockNum <= 0) {
      stockBadgeClass = 'excel-stock-out';
      rowClass = 'row-out-stock';
    } else if (stockNum <= 5) {
      stockBadgeClass = 'excel-stock-low';
      rowClass = 'row-low-stock';
    }
    if (item.isExpired) rowClass = 'row-expired';

    rowsHtml += `
      <tr class="${rowClass}" onclick="selectPanelExcelRow(this, '${escapeJs(p.description)}', '${escapeJs(barcode)}', ${stockNum}, ${discountedSellPrice}, ${costPrice}, ${profit}, ${rowIdx})" ondblclick="populateProduct('${escapeJs(barcode)}')">
        <td class="excel-row-num-cell">${rowIdx}</td>
        <td class="excel-cell-center" style="font-family:Consolas, monospace; font-size:11px; font-weight:700;">${escapeHtml(barcode)}</td>
        <td style="font-weight:700; color:#0f172a; max-width:180px; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(p.description)}" dir="auto">${escapeHtml(p.description)}</td>
        <td class="excel-cell-center" style="font-size:11px;">${escapeHtml(p.size || '-')}</td>
        <td class="excel-cell-num"><span class="excel-stock-badge ${stockBadgeClass}">${stockNum}</span></td>
        <td class="excel-cell-num" style="font-weight:700;">Rs. ${discountedSellPrice.toLocaleString()}</td>
        <td class="excel-cell-num" style="color:#b91c1c; font-size:11px;">Rs. ${costPrice.toLocaleString()}</td>
        <td class="excel-cell-num" style="color:#15803d; font-weight:700; font-size:11px;">Rs. ${profit.toLocaleString()}</td>
        <td class="excel-print-hide excel-cell-center">
          <button class="btn-card-action btn-add-to-bill" onclick="event.stopPropagation(); populateProduct('${escapeJs(barcode)}')" style="padding:2px 6px; font-size:11px;" title="Add to Bill">🛒</button>
          <button class="btn-card-action btn-card-edit" onclick="event.stopPropagation(); editProduct('${escapeJs(barcode)}')" style="padding:2px 5px; font-size:11px;" title="Edit Product">✏️</button>
          <button class="btn-card-action" onclick="event.stopPropagation(); printBarcode('${escapeJs(barcode)}')" style="padding:2px 5px; font-size:11px; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;" title="Print Barcode">🏷️</button>
        </td>
      </tr>
    `;
    rowIdx++;
  }

  const itemsCount = rowIdx - 1;

  list.innerHTML = `
    <div class="excel-spreadsheet-container">
      <div class="excel-formula-bar" style="background:#f8fafc;">
        <div class="excel-name-box" id="panel-excel-cell">A1</div>
        <div class="excel-fx-symbol">fx</div>
        <input type="text" class="excel-formula-input" id="panel-excel-formula" readonly value="=PRODUCT_GRID(Count: ${itemsCount}, Mode: Excel_Grid_View)">
        <button type="button" onclick="printProductList()" class="btn-excel-tool btn-excel-primary" style="padding:2px 8px; font-size:11px; margin-left:auto;" title="Print Product List">🖨️ Print List</button>
        <button type="button" onclick="openProductListModal()" class="btn-excel-tool" style="padding:2px 8px; font-size:11px;" title="Open Full Screen Excel Window">⧉ Full Excel</button>
      </div>
      <div class="excel-grid-wrapper" style="max-height: calc(100vh - 350px);">
        <table class="excel-grid-table">
          <thead>
            <tr class="excel-col-letters-row">
              <th style="width:34px;"></th>
              <th>A</th>
              <th>B</th>
              <th>C</th>
              <th>D</th>
              <th>E</th>
              <th>F</th>
              <th>G</th>
              <th class="excel-print-hide">H</th>
            </tr>
            <tr class="excel-headers-row">
              <th class="excel-row-num-cell">#</th>
              <th>Barcode</th>
              <th style="text-align:left;">Description</th>
              <th>Size</th>
              <th class="th-excel-green">Stock</th>
              <th>Sell (Rs)</th>
              <th>Cost (Rs)</th>
              <th>Profit</th>
              <th class="excel-print-hide" style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml || `<tr><td colspan="9" style="text-align:center; padding:25px 10px; color:#605e5c;">No products match current filter.</td></tr>`}
          </tbody>
          ${itemsCount > 0 ? `
            <tfoot style="position:sticky; bottom:0; background:#f3f2f1; z-index:2; border-top:2px solid #107c41;">
              <tr style="font-weight:700;">
                <td class="excel-row-num-cell" style="background:#e1dfdd !important;">Σ</td>
                <td></td>
                <td style="font-weight:900; color:#107c41;">TOTALS (${itemsCount})</td>
                <td></td>
                <td class="excel-cell-num" style="font-weight:900; color:#15803d;">${totalStock.toLocaleString()}</td>
                <td class="excel-cell-num" style="font-weight:900;">Rs. ${totalRetailVal.toLocaleString()}</td>
                <td class="excel-cell-num" style="font-weight:900; color:#b91c1c; font-size:11px;">Rs. ${totalCostVal.toLocaleString()}</td>
                <td class="excel-cell-num" style="font-weight:900; color:#15803d; font-size:11px;">Rs. ${totalProfitVal.toLocaleString()}</td>
                <td></td>
              </tr>
            </tfoot>
          ` : ''}
        </table>
      </div>
      <div class="excel-status-bar" style="padding:4px 10px; font-size:11px;">
        <div class="excel-status-left">
          <span>READY 🟢</span>
          <span style="font-size:10.5px; opacity:0.9;">Double-click row to add to bill</span>
        </div>
        <div class="excel-status-right" style="gap:10px; font-size:10.5px;">
          <span>COUNT: ${itemsCount}</span>
          <span>STOCK: ${totalStock}</span>
          <span>RETAIL: Rs. ${totalRetailVal.toLocaleString()}</span>
          <span>PROFIT: Rs. ${totalProfitVal.toLocaleString()}</span>
        </div>
      </div>
    </div>
  `;
}

function selectPanelExcelRow(rowElem, desc, barcode, stock, sell, cost, profit, rowIdx) {
  if (!rowElem) return;
  const table = rowElem.closest('table');
  if (table) table.querySelectorAll('tbody tr').forEach(r => r.classList.remove('selected'));
  rowElem.classList.add('selected');

  const cellBox = document.getElementById('panel-excel-cell');
  if (cellBox) cellBox.innerText = 'B' + (rowIdx + 1);

  const formulaInput = document.getElementById('panel-excel-formula');
  if (formulaInput) {
    formulaInput.value = `=PRODUCT("${desc}", Barcode="${barcode}", Stock=${stock}, Sell=Rs.${sell.toLocaleString()}, Cost=Rs.${cost.toLocaleString()}, Profit=Rs.${profit.toLocaleString()})`;
  }
}

// Print Product List (Authentic Excel Spreadsheet Format)
function printProductList(filterOverride) {
  const printArea = document.getElementById('print-product-list-container');
  if (!printArea) return;
  
  const activeFilter = filterOverride || (document.getElementById('excel-product-list-modal')?.style.display === 'flex' ? currentExcelModalFilter : currentProductFilter) || 'all';
  const searchQuery = ((document.getElementById('excel-product-list-modal')?.style.display === 'flex' ? document.getElementById('excel-search-input')?.value : document.getElementById('search-product')?.value) || "").trim().toLowerCase();

  // ── Read column visibility from the Excel Modal checkboxes ──────────────
  const showBarcode = document.getElementById('excel-chk-barcode')?.checked !== false;
  const showCost    = document.getElementById('excel-chk-cost')?.checked    !== false;
  const showProfit  = document.getElementById('excel-chk-profit')?.checked  !== false;
  // ────────────────────────────────────────────────────────────────────────
  
  let rowsHtml = '';
  let rowIdx = 1;
  let totalStock = 0;
  let totalCostVal = 0;
  let totalRetailVal = 0;
  let totalProfitVal = 0;
  
  const today = new Date();
  today.setHours(0,0,0,0);
  const nextMonth = new Date(today);
  nextMonth.setMonth(nextMonth.getMonth() + 1);

  for (let barcode in products) {
    let p = products[barcode];
    if (!p) continue;
    
    let stockNum = parseInt(p.stock) || 0;
    let isExpired = false;
    let isExpiringSoon = false;
    if (p.expireDate) {
      let exp = new Date(p.expireDate);
      if (exp <= today) isExpired = true;
      else if (exp <= nextMonth) isExpiringSoon = true;
    }
    
    if (activeFilter === 'instock' && stockNum <= 0) continue;
    if (activeFilter === 'lowstock' && (stockNum <= 0 || stockNum > 5)) continue;
    if (activeFilter === 'outstock' && stockNum > 0) continue;
    if (activeFilter === 'expiring' && !isExpired && !isExpiringSoon) continue;
    
    if (searchQuery) {
      const descMatch = (p.description || "").toLowerCase().includes(searchQuery);
      const sizeMatch = (p.size || "").toLowerCase().includes(searchQuery);
      const barcodeMatch = barcode.toLowerCase().includes(searchQuery);
      if (!descMatch && !sizeMatch && !barcodeMatch) continue;
    }
    
    const sellPrice = parseFloat(p.price) || 0;
    const itemDisc = parseFloat(p.discount) || 0;
    const discountedSellPrice = itemDisc > 0 ? (sellPrice * (1 - itemDisc / 100)) : sellPrice;
    const costPrice = parseFloat(p.purchasePrice) || 0;
    const unitProfit = discountedSellPrice - costPrice;
    const totalLineRetail = stockNum * discountedSellPrice;
    const totalLineCost = stockNum * costPrice;
    const totalLineProfit = stockNum * unitProfit;
    
    totalStock += stockNum;
    totalCostVal += totalLineCost;
    totalRetailVal += totalLineRetail;
    totalProfitVal += totalLineProfit;
    
    let statusText = 'In Stock';
    if (stockNum <= 0) statusText = 'Out of Stock';
    else if (stockNum <= 5) statusText = `Low Stock (${stockNum})`;
    if (isExpired) statusText = 'EXPIRED';
    else if (isExpiringSoon && statusText === 'In Stock') statusText = 'Expiring Soon';

    rowsHtml += `
      <tr>
        <td style="text-align:center; font-weight:bold; width:30px;">${rowIdx++}</td>
        ${showBarcode ? `<td style="text-align:center; padding:3px 4px;"><svg class="print-barcode-svg" data-barcode="${escapeHtml(barcode)}"></svg></td>` : ''}
        <td><strong>${escapeHtml(p.description)}</strong></td>
        <td style="text-align:center;">${escapeHtml(p.size || '-')}</td>
        <td class="cell-num" style="font-weight:bold;">${stockNum}</td>
        <td class="cell-num">Rs. ${discountedSellPrice.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
        ${showCost   ? `<td class="cell-num">Rs. ${costPrice.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>` : ''}
        <td style="text-align:center;">${itemDisc > 0 ? (itemDisc + '%') : '-'}</td>
        ${showProfit ? `<td class="cell-num" style="color:#059669;">Rs. ${unitProfit.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>` : ''}
        <td class="cell-num" style="font-weight:bold;">Rs. ${totalLineRetail.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
        <td style="text-align:center;">${p.expireDate || '-'}</td>
        <td style="text-align:center; font-weight:bold;">${statusText}</td>
      </tr>
    `;
  }
  
  // ── Build dynamic column count ──────────────────────────────────────────
  let colCount = 9; // #, Description, Variant, Stock, Sell, Disc%, Total, Expiry, Status
  if (showBarcode) colCount++;
  if (showCost)    colCount++;
  if (showProfit)  colCount++;

  const leadingCols = 3 + (showBarcode ? 1 : 0);

  // Hidden-columns note
  const hiddenNote = [];
  if (!showBarcode) hiddenNote.push('Barcode');
  if (!showCost)    hiddenNote.push('Cost Price');
  if (!showProfit)  hiddenNote.push('Profit');
  const hiddenText = hiddenNote.length ? `<span style="font-size:10.5px; color:#b91c1c; font-weight:600;">Hidden columns: ${hiddenNote.join(', ')}</span>` : '';

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-GB');
  const timeStr = now.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', second:'2-digit', hour12: true});
  const salesmanName = document.getElementById('salesman')?.value || 'Admin';

  printArea.innerHTML = `
    <div style="font-family:'Segoe UI', Calibri, Arial, sans-serif; color:#000000; padding:10px 0;">
      <!-- Report Header -->
      <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2.5px solid #107c41; padding-bottom:8px; margin-bottom:12px;">
        <div>
          <h1 style="font-size:20px; font-weight:900; color:#107c41; margin:0 0 4px 0; text-transform:uppercase; letter-spacing:0.5px;">Aslam Malik Store — Product Inventory Report</h1>
          <div style="font-size:12.5px; font-weight:700; color:#333333;">Master Stock & Price List (Excel Spreadsheet Format) &nbsp; ${hiddenText}</div>
        </div>
        <div style="text-align:right; font-size:11.5px; font-weight:600; color:#444444; line-height:1.4;">
          <div><strong>Date:</strong> ${dateStr} | <strong>Time:</strong> ${timeStr}</div>
          <div><strong>Printed By:</strong> ${escapeHtml(salesmanName)}</div>
          <div><strong>Filter:</strong> ${escapeHtml(activeFilter.toUpperCase())} ${searchQuery ? `(Search: "${escapeHtml(searchQuery)}")` : ''}</div>
        </div>
      </div>

      <!-- Inventory KPI Summary Cards -->
      <div style="display:flex; gap:10px; margin-bottom:12px; font-size:11px;">
        <div style="flex:1; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;">
          <div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Products</div>
          <div style="font-size:14px; font-weight:900;">${rowIdx - 1} Items</div>
        </div>
        <div style="flex:1; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;">
          <div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Stock Units</div>
          <div style="font-size:14px; font-weight:900; color:#15803d;">${totalStock.toLocaleString()} Units</div>
        </div>
        ${showCost ? `<div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;">
          <div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Inventory Cost</div>
          <div style="font-size:14px; font-weight:900;">Rs. ${totalCostVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
        </div>` : ''}
        <div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;">
          <div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Total Retail Valuation</div>
          <div style="font-size:14px; font-weight:900; color:#107c41;">Rs. ${totalRetailVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
        </div>
        ${showProfit ? `<div style="flex:1.2; border:1px solid #000; padding:6px 10px; background:#f8fafc; border-radius:4px;">
          <div style="font-size:10px; color:#555; text-transform:uppercase; font-weight:bold;">Est. Total Profit</div>
          <div style="font-size:14px; font-weight:900; color:#059669;">Rs. ${totalProfitVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
        </div>` : ''}
      </div>

      <!-- Excel Printable Table -->
      <table class="excel-print-table">
        <thead>
          <tr>
            <th style="width:30px;">#</th>
            ${showBarcode ? '<th style="width:130px;">Barcode</th>' : ''}
            <th style="text-align:left;">Description</th>
            <th style="width:65px;">Variant</th>
            <th style="width:50px;">Stock</th>
            <th style="width:85px;">Sell Price</th>
            ${showCost   ? '<th style="width:85px;">Cost Price</th>' : ''}
            <th style="width:50px;">Disc%</th>
            ${showProfit ? '<th style="width:80px;">Profit/Unit</th>' : ''}
            <th style="width:95px;">Total Retail</th>
            <th style="width:75px;">Expiry</th>
            <th style="width:75px;">Status</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml || `<tr><td colspan="${colCount}" style="text-align:center; padding:15px;">No products match the selected criteria.</td></tr>`}
        </tbody>
        <tfoot>
          <tr>
            <td colspan="${leadingCols}" style="text-align:right; font-weight:bold; font-size:11.5px;">TOTALS:</td>
            <td class="cell-num" style="font-weight:900; font-size:11.5px;">${totalStock.toLocaleString()}</td>
            <td></td>
            ${showCost   ? `<td class="cell-num" style="font-weight:900; font-size:11.5px;">Rs. ${totalCostVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>` : ''}
            <td></td>
            ${showProfit ? `<td class="cell-num" style="font-weight:900; font-size:11.5px; color:#059669;">Rs. ${totalProfitVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>` : ''}
            <td class="cell-num" style="font-weight:900; font-size:11.5px;">Rs. ${totalRetailVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>

      <!-- Footer Note -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:10px; color:#666; border-top:1px solid #ccc; padding-top:6px;">
        <div>Software by Aslam Malik 0370-1937483 — POS Billing & Barcode Manager</div>
        <div>Page 1 of 1 — Generated from Live Database</div>
      </div>
    </div>
  `;

  // ── Render actual barcode SVGs in the print area ─────────────────────
  if (showBarcode) {
    printArea.querySelectorAll('.print-barcode-svg').forEach(svg => {
      const code = svg.getAttribute('data-barcode');
      if (code) {
        try {
          JsBarcode(svg, code, {
            format: 'CODE128',
            width: 1.2,
            height: 30,
            displayValue: true,
            fontSize: 10,
            font: 'monospace',
            textMargin: 1,
            margin: 2
          });
        } catch (e) {
          svg.outerHTML = `<span style="font-family:monospace; font-size:11px;">${code}</span>`;
        }
      }
    });
  }
  // ─────────────────────────────────────────────────────────────────────

  let styleElem = document.getElementById('dynamic-product-list-print-style');
  if (!styleElem) {
    styleElem = document.createElement('style');
    styleElem.id = 'dynamic-product-list-print-style';
    document.head.appendChild(styleElem);
  }
  styleElem.innerHTML = `
    @media print {
      @page { size: landscape; margin: 8mm; }
    }
  `;

  document.body.classList.remove('printing-barcodes', 'printing-invoice');
  document.body.classList.add('printing-product-list');

  window.print();
}

function exportProductListCSV() {
  let csv = "\uFEFF"; // UTF-8 BOM so Excel opens with proper characters
  csv += "Row,Barcode,Product Description,Size/Variant,Stock Qty,Sell Price (Rs),Cost Price (Rs),Discount %,Profit Per Unit (Rs),Total Retail Valuation (Rs),Expiry Date,Status\n";
  
  let rowIdx = 1;
  for (let barcode in products) {
    let p = products[barcode];
    if (!p) continue;
    
    let stockNum = parseInt(p.stock) || 0;
    let sellPrice = parseFloat(p.price) || 0;
    let itemDisc = parseFloat(p.discount) || 0;
    let discountedSellPrice = itemDisc > 0 ? (sellPrice * (1 - itemDisc / 100)) : sellPrice;
    let costPrice = parseFloat(p.purchasePrice) || 0;
    let unitProfit = discountedSellPrice - costPrice;
    let totalLineRetail = stockNum * discountedSellPrice;
    
    let statusText = 'In Stock';
    if (stockNum <= 0) statusText = 'Out of Stock';
    else if (stockNum <= 5) statusText = 'Low Stock';
    
    let desc = '"' + (p.description || '').replace(/"/g, '""') + '"';
    let size = '"' + (p.size || '').replace(/"/g, '""') + '"';
    
    csv += `${rowIdx++},"${barcode}",${desc},${size},${stockNum},${discountedSellPrice.toFixed(2)},${costPrice.toFixed(2)},${itemDisc}%,${unitProfit.toFixed(2)},${totalLineRetail.toFixed(2)},"${p.expireDate || '-'}",${statusText}\n`;
  }
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const todayStr = new Date().toISOString().slice(0, 10);
  a.download = `Products_Inventory_Excel_${todayStr}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

window.addEventListener('afterprint', function() {
  document.body.classList.remove('printing-barcodes', 'printing-invoice', 'printing-product-list');
});

// Initialize Products List
function renderProducts() {
  const list = document.getElementById('products-list');
  const datalist = document.getElementById('available-descriptions');
  const searchQuery = (document.getElementById('search-product')?.value || "").trim().toLowerCase();
  
  if (datalist) datalist.innerHTML = '';
  if (!list) return;
  list.innerHTML = '';
  
  const totalBarcodes = Object.keys(products);
  const totalBadge = document.getElementById('product-total-badge');
  if (totalBadge) totalBadge.innerText = totalBarcodes.length + (totalBarcodes.length === 1 ? ' Item' : ' Items');
  
  // Sync view switcher buttons state
  const btnCards = document.getElementById('btn-view-cards');
  const btnExcel = document.getElementById('btn-view-excel');
  if (btnCards) btnCards.classList.toggle('active', currentPanelViewMode === 'cards');
  if (btnExcel) btnExcel.classList.toggle('active', currentPanelViewMode === 'excel');

  let visibleProducts = [];
  const today = new Date();
  today.setHours(0,0,0,0);
  const nextMonth = new Date(today);
  nextMonth.setMonth(nextMonth.getMonth() + 1);

  for (let barcode of totalBarcodes) {
    let p = products[barcode];
    if (!p) continue;
    
    // Populate description datalist for receipt autocomplete
    if (datalist && p.stock > 0) {
      let option = document.createElement('option');
      option.value = p.description;
      datalist.appendChild(option);
    }
    
    // Expiry calculations
    let isExpired = false;
    let isExpiringSoon = false;
    if (p.expireDate) {
      let expDate = new Date(p.expireDate);
      if (expDate <= today) isExpired = true;
      else if (expDate <= nextMonth) isExpiringSoon = true;
    }
    
    // Filter by Tab
    let stockNum = parseInt(p.stock) || 0;
    if (currentProductFilter === 'instock' && stockNum <= 0) continue;
    if (currentProductFilter === 'lowstock' && (stockNum <= 0 || stockNum > 5)) continue;
    if (currentProductFilter === 'expiring' && !isExpired && !isExpiringSoon) continue;
    
    // Filter by Search Query
    if (searchQuery) {
      const descMatch = (p.description || "").toLowerCase().includes(searchQuery);
      const sizeMatch = (p.size || "").toLowerCase().includes(searchQuery);
      const barcodeMatch = barcode.toLowerCase().includes(searchQuery);
      if (!descMatch && !sizeMatch && !barcodeMatch) {
        continue;
      }
    }
    
    visibleProducts.push({
      barcode,
      p,
      stockNum,
      isExpired,
      isExpiringSoon
    });
  }

  // Branch on View Mode
  if (currentPanelViewMode === 'excel') {
    list.classList.add('excel-mode');
    renderPanelExcelGrid(list, visibleProducts, searchQuery);
    return;
  } else {
    list.classList.remove('excel-mode');
  }

  // Cards View
  for (let item of visibleProducts) {
    let barcode = item.barcode;
    let p = item.p;
    let stockNum = item.stockNum;
    let isExpired = item.isExpired;
    let isExpiringSoon = item.isExpiringSoon;

    const sellPrice = parseFloat(p.price) || 0;
    const itemDisc = parseFloat(p.discount) || 0;
    const discountedSellPrice = itemDisc > 0 ? (sellPrice * (1 - itemDisc / 100)) : sellPrice;
    const purchasePrice = parseFloat(p.purchasePrice) || 0;
    const profit = discountedSellPrice - purchasePrice;
    
    let stockClass = 'in-stock';
    let stockLabel = `Stock: ${stockNum}`;
    if (stockNum <= 0) {
      stockClass = 'out-stock';
      stockLabel = 'Out of Stock';
    } else if (stockNum <= 5) {
      stockClass = 'low-stock';
      stockLabel = `Low Stock: ${stockNum}`;
    }
    
    let card = document.createElement('div');
    const isCardSelected = selectedProducts.has(barcode);
    card.className = 'product-card' + (isExpired ? ' expired' : (isExpiringSoon ? ' expiring-soon' : '')) + (isCardSelected ? ' product-card-selected' : '');
    card.setAttribute('data-barcode', barcode);
    
    const hasImage = Boolean(p.image);
    card.innerHTML = `
      <!-- Selection Checkbox -->
      <div class="product-card-chk-col" style="display:flex; align-items:flex-start; padding-top:2px;">
        <input type="checkbox" class="product-card-select-chk" ${isCardSelected ? 'checked' : ''}
          onclick="event.stopPropagation(); toggleProductSelection('${escapeJs(barcode)}', this.checked)"
          title="Select this product" style="width:16px; height:16px; cursor:pointer; accent-color:#107c41; margin-top:2px;">
      </div>
      <div class="product-card-media" onclick="${hasImage ? `openLightbox('${escapeJs(p.image)}', '${escapeJs(p.description)}')` : `openEditProductModal('${escapeJs(barcode)}')`}" title="${hasImage ? 'Click to view full photo' : 'Click to edit product'}">
        ${hasImage ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.description)}" loading="lazy">` : `<div class="product-card-placeholder" title="No photo">📷</div>`}
      </div>
      <div class="product-card-left">
        <div>
          <div class="product-title-row">
            <span class="product-card-title">${escapeHtml(p.description)}</span>
            ${isExpired ? '<span class="badge-status badge-expired">Expired</span>' : (isExpiringSoon ? '<span class="badge-status badge-expiring">Expiring Soon</span>' : '')}
            ${itemDisc > 0 ? `<span class="badge-status" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">🏷️ ${itemDisc}% OFF</span>` : ''}
          </div>
          
          <div class="product-badges-row">
            <span class="pill-stock ${stockClass}">● ${stockLabel}</span>
            <span class="pill-size">Size: ${escapeHtml(p.size || '-')}</span>
            <span class="pill-expiry" style="color:${isExpired ? '#dc2626' : (isExpiringSoon ? '#d97706' : '#64748b')}; font-weight:${(isExpired || isExpiringSoon) ? '600' : 'normal'};">
              📅 ${p.expireDate || 'No Expiry'}
            </span>
          </div>
          
          <div class="product-financials-row">
            <div>Sell: <span class="financial-sell">Rs. ${discountedSellPrice.toLocaleString()}</span> ${itemDisc > 0 ? `<span style="font-size:11px; text-decoration:line-through; color:#94a3b8;">Rs. ${sellPrice.toLocaleString()}</span>` : ''}</div>
            <div class="financial-purchase">Cost: Rs. ${purchasePrice.toLocaleString()}</div>
            ${purchasePrice > 0 ? `<div class="financial-profit" title="Estimated profit per unit">+Rs. ${profit.toLocaleString()} Profit</div>` : ''}
          </div>
        </div>
        
        <div class="product-card-actions">
          <button class="btn-card-action btn-add-to-bill" onclick="populateProduct('${escapeJs(barcode)}')" title="Add this item directly to current bill">
            🛒 Add to Bill
          </button>
          <button class="btn-card-action btn-card-edit" onclick="openEditProductModal('${escapeJs(barcode)}')" title="Edit product in popup modal">
            ✏️ Edit
          </button>
          <button class="btn-card-action btn-card-delete" onclick="deleteProduct('${escapeJs(barcode)}')" title="Delete this product">
            🗑️ Delete
          </button>
        </div>
      </div>
      
      <div class="product-card-right">
        <div class="barcode-svg-wrap">
          <svg class="barcode-svg-element"></svg>
        </div>
        <div class="barcode-number-display" title="Click to copy barcode" onclick="copyBarcodeToClipboard('${escapeJs(barcode)}', this)">📋 ${barcode}</div>
        <button class="btn-card-print" onclick="printBarcode('${escapeJs(barcode)}')" title="Print barcode label">
          🖨️ Print Label
        </button>
      </div>
    `;
    
    list.appendChild(card);
    
    // Generate barcode directly on the DOM SVG element to avoid selector syntax issues
    const svgElem = card.querySelector('.barcode-svg-element');
    if (svgElem) {
      try {
        JsBarcode(svgElem, barcode, {
          format: "CODE128",
          width: 1.5,
          height: 38,
          displayValue: false,
          margin: 3
        });
      } catch (err) {
        console.warn("JsBarcode error for code " + barcode, err);
        svgElem.parentElement.innerHTML = `<div style="font-size:11px; color:#ef4444; padding:8px 0;">Barcode Preview Unavailable</div>`;
      }
    }
  }
  
  if (visibleProducts.length === 0) {
    list.innerHTML = `
      <div class="empty-products-state">
        <div class="empty-products-icon">📦</div>
        <div class="empty-products-title">No products found</div>
        <div class="empty-products-desc">${searchQuery ? 'No items match your search term.' : (currentProductFilter !== 'all' ? 'No products in this filter category.' : 'Use the form above to add your first product and generate its barcode.')}</div>
      </div>
    `;
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escapeJs(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}

renderProducts();
updateFormBarcodePreview();

function addProduct() {
  let desc = document.getElementById('new-desc').value.trim();
  let size = document.getElementById('new-size').value.trim();
  let stock = document.getElementById('new-stock').value.trim() || 0;
  let price = document.getElementById('new-price').value.trim();
  let discount = document.getElementById('new-discount') ? document.getElementById('new-discount').value.trim() : 0;
  let purchasePrice = document.getElementById('new-purchase-price').value.trim() || 0;
  let expireDate = document.getElementById('new-expire-date').value.trim();
  let barcode = document.getElementById('new-barcode').value.trim();
  let image = document.getElementById('new-image-path').value.trim();
  
  if(!desc || !price) {
    alert("Product Description and Sell Price are required!");
    const descInput = document.getElementById('new-desc');
    if (!desc && descInput) descInput.focus();
    else if (!price) document.getElementById('new-price').focus();
    return;
  }
  
  if(!barcode) {
    // Generate 12 digit barcode
    barcode = Math.floor(100000000000 + Math.random() * 900000000000).toString();
  }
  
  products[barcode] = { 
    description: desc, 
    size: size, 
    price: price, 
    discount: parseFloat(discount) || 0,
    purchasePrice: purchasePrice, 
    expireDate: expireDate, 
    stock: parseInt(stock),
    image: image
  };
  localStorage.setItem('products', JSON.stringify(products));
  
  resetProductForm();
  renderProducts();
  
  // Highlight created item or search box
  const searchInput = document.getElementById('search-product');
  if (searchInput) searchInput.focus();
}

function deleteProduct(barcode) {
  const p = products[barcode];
  const name = p ? p.description : barcode;
  if (confirm(`Are you sure you want to delete "${name}" (Barcode: ${barcode})?`)) {
    delete products[barcode];
    localStorage.setItem('products', JSON.stringify(products));
    renderProducts();
  }
}

function editProduct(barcode) {
  let p = products[barcode];
  if (!p) return;
  
  document.getElementById('new-desc').value = p.description || '';
  document.getElementById('new-size').value = p.size || '';
  document.getElementById('new-stock').value = p.stock || 0;
  document.getElementById('new-price').value = p.price || '';
  if (document.getElementById('new-discount')) document.getElementById('new-discount').value = (p.discount && parseFloat(p.discount) > 0) ? p.discount : '';
  document.getElementById('new-purchase-price').value = p.purchasePrice || '';
  document.getElementById('new-expire-date').value = p.expireDate || '';
  document.getElementById('new-barcode').value = barcode;
  
  updateFormBarcodePreview();
  
  if (p.image) {
    setProductImagePreview(p.image, p.description || 'Current Picture');
  } else {
    clearProductImage();
  }
  
  const submitBtn = document.getElementById('btn-submit-product');
  submitBtn.innerHTML = "💾 Update Product Details";
  submitBtn.style.background = "linear-gradient(135deg, #f59e0b 0%, #d97706 100%)";
  submitBtn.onclick = function() { updateProduct(barcode); };
  
  const cancelBtn = document.getElementById('btn-cancel-edit');
  if (cancelBtn) cancelBtn.style.display = 'inline-block';
  
  toggleAddForm(true, true);
  
  const container = document.getElementById('add-product-container');
  if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateProduct(oldBarcode) {
  let desc = document.getElementById('new-desc').value.trim();
  let size = document.getElementById('new-size').value.trim();
  let stock = document.getElementById('new-stock').value.trim() || 0;
  let price = document.getElementById('new-price').value.trim();
  let discount = document.getElementById('new-discount') ? document.getElementById('new-discount').value.trim() : 0;
  let purchasePrice = document.getElementById('new-purchase-price').value.trim() || 0;
  let expireDate = document.getElementById('new-expire-date').value.trim();
  let newBarcode = document.getElementById('new-barcode').value.trim();
  let image = document.getElementById('new-image-path').value.trim();
  
  if(!desc || !price) {
    alert("Product Description and Sell Price are required!");
    return;
  }
  
  if(!newBarcode) {
    newBarcode = oldBarcode;
  }

  // If barcode changed, delete old one
  if(newBarcode !== oldBarcode) {
    delete products[oldBarcode];
  }
  
  products[newBarcode] = { 
    description: desc, 
    size: size, 
    price: price, 
    discount: parseFloat(discount) || 0,
    purchasePrice: purchasePrice, 
    expireDate: expireDate, 
    stock: parseInt(stock),
    image: image
  };
  localStorage.setItem('products', JSON.stringify(products));
  
  cancelEditProduct();
  renderProducts();
}

// Print Barcodes

function printBarcode(barcode) {
   let area = document.getElementById('print-barcode-area');
   area.innerHTML = '';
   let p = products[barcode];
   let hideRS = document.getElementById('rs-hide-check').checked;
   
   let label = document.createElement('div');
   label.className = 'barcode-label';
   label.innerHTML = `
      ${hideRS ? '' : `<div class="label-title"></div>`}
      ${hideRS ? '' : `<div class="label-desc">${p.description} - Size: ${p.size}</div>`}
      <svg id="print-svg-${barcode}"></svg>
      ${hideRS ? '' : `<div class="label-price">Rs. ${p.price}</div>`}
   `;
   area.appendChild(label);
   
   JsBarcode("#print-svg-" + barcode, barcode, {
      format: "CODE128",
      width: 2,
      height: 40,
      displayValue: true,
      fontSize: 14
   });

   document.body.classList.remove('printing-invoice');
   document.body.classList.add('printing-barcodes');
   window.print();
}

function printAllBarcodes() {
   let area = document.getElementById('print-barcode-area');
   area.innerHTML = '';
   let hideRS = document.getElementById('rs-hide-check').checked;
   
   for (let barcode in products) {
       let p = products[barcode];
       let label = document.createElement('div');
       label.className = 'barcode-label';
       label.innerHTML = `
          ${hideRS ? '' : `<div class="label-title"></div>`}
          ${hideRS ? '' : `<div class="label-desc">${p.description} - Size: ${p.size}</div>`}
          <svg id="print-svg-all-${barcode}"></svg>
          ${hideRS ? '' : `<div class="label-price">Rs. ${p.price}</div>`}
       `;
       area.appendChild(label);
       
       JsBarcode("#print-svg-all-" + barcode, barcode, {
          format: "CODE128",
          width: 2,
          height: 40,
          displayValue: true,
          fontSize: 14
       });
   }

   document.body.classList.remove('printing-invoice');
   document.body.classList.add('printing-barcodes');
   window.print();
}

function showAvailableProducts() {
  const tbody = document.getElementById('available-products-tbody');
  tbody.innerHTML = '';
  let count = 0;
  let showPurchase = document.getElementById('show-purchase-check').checked;
  let showProfit = document.getElementById('show-profit-check').checked;
  let showExpiring = document.getElementById('show-expiring-check') ? document.getElementById('show-expiring-check').checked : false;
  let showSafe = document.getElementById('show-safe-check') ? document.getElementById('show-safe-check').checked : false;
  
  if (showPurchase) {
      document.getElementById('th-purchase-price').style.display = 'table-cell';
  } else {
      document.getElementById('th-purchase-price').style.display = 'none';
  }

  if (showProfit) {
      document.getElementById('th-profit').style.display = 'table-cell';
  } else {
      document.getElementById('th-profit').style.display = 'none';
  }
  
  let totalStock = 0;
  let totalPurchaseValue = 0;
  let totalSellValue = 0;
  let totalProfitValue = 0;
  
  for (let barcode in products) {
    let p = products[barcode];
    if (p.stock > 0) {
      let isExpired = false;
      let isExpiringSoon = false;
      if (p.expireDate) {
          let expDate = new Date(p.expireDate);
          let today = new Date();
          today.setHours(0,0,0,0);
          if (expDate <= today) {
              isExpired = true;
          } else {
              let oneMonthFromNow = new Date(today);
              oneMonthFromNow.setMonth(oneMonthFromNow.getMonth() + 1);
              if (expDate <= oneMonthFromNow) {
                  isExpiringSoon = true;
              }
          }
      }
      
      if (showExpiring && (!p.expireDate || (!isExpired && !isExpiringSoon))) {
          continue;
      }
      
      let is1to2Months = false;
      if (p.expireDate && !isExpired && !isExpiringSoon) {
          let expDate = new Date(p.expireDate);
          let twoMonthsFromNow = new Date();
          twoMonthsFromNow.setHours(0,0,0,0);
          twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2);
          if (expDate <= twoMonthsFromNow) {
              is1to2Months = true;
          }
      }
      
      if (showSafe && !is1to2Months) {
          continue;
      }

      count++;
      totalStock += parseInt(p.stock);
      let purchaseVal = parseFloat(p.purchasePrice) || 0;
      let sellVal = parseFloat(p.price) || 0;
      let profitVal = sellVal - purchaseVal;
      
      totalPurchaseValue += parseInt(p.stock) * purchaseVal;
      totalSellValue += parseInt(p.stock) * sellVal;
      totalProfitValue += parseInt(p.stock) * profitVal;

      let tr = document.createElement('tr');
      if (isExpired) {
          tr.style.backgroundColor = '#fef2f2';
      } else if (isExpiringSoon) {
          tr.style.backgroundColor = '#fffbeb';
      }
      tr.innerHTML = `
        <td style="border-bottom:1px solid #e2e8f0; padding:6px 5px; text-align:center;">
          ${p.image ? `<img src="${escapeHtml(p.image)}" style="width:34px; height:34px; object-fit:cover; border-radius:4px; border:1px solid #cbd5e1; cursor:pointer;" onclick="openLightbox('${escapeJs(p.image)}', '${escapeJs(p.description)}')" title="Click to view full photo">` : `<span style="color:#94a3b8; font-size:11px;">—</span>`}
        </td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px;"><strong>${barcode}</strong> ${isExpired ? '<span style="color:#ef4444; font-size:10px; border:1px solid #ef4444; padding:1px 3px; border-radius:3px; margin-left:5px; font-weight:bold;">EXPIRED</span>' : (isExpiringSoon ? '<span style="color:#f59e0b; font-size:10px; border:1px solid #f59e0b; padding:1px 3px; border-radius:3px; margin-left:5px; font-weight:bold;">EXPIRING SOON</span>' : '')}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:${isExpired ? '#b91c1c' : (isExpiringSoon ? '#d97706' : '')};">${p.description}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px;">${p.size}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:#16a34a; font-weight:600;">${p.stock}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:${isExpired ? '#ef4444' : (isExpiringSoon ? '#f59e0b' : '#64748b')}; font-weight:${(isExpired || isExpiringSoon) ? 'bold' : 'normal'};">${p.expireDate || '-'}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px;">Rs. ${p.price}</td>
        <td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:#b45309; font-weight:600;">${p.discount && parseFloat(p.discount) > 0 ? (p.discount + '%') : '-'}</td>
        ${showPurchase ? `<td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:#dc2626; font-weight:600;">Rs. ${purchaseVal}</td>` : '<td style="display:none;"></td>'}
        ${showProfit ? `<td style="border-bottom:1px solid #e2e8f0; padding:10px 5px; color:#16a34a; font-weight:600;">Rs. ${profitVal.toFixed(2)}</td>` : '<td style="display:none;"></td>'}
        <td style="border-bottom:1px solid #e2e8f0; padding:6px 5px; text-align:center;">
          <button class="btn-card-action btn-add-to-bill" onclick="document.getElementById('available-products-modal').style.display='none'; populateProduct('${escapeJs(barcode)}');" style="padding:4px 8px; font-size:11.5px;" title="Add to current bill">🛒 Add</button>
        </td>
      `;
      tbody.appendChild(tr);
    }
  }
  
  if(count === 0) {
      let cols = 8;
      if (showPurchase) cols++;
      if (showProfit) cols++;
      tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center; padding:20px; color:#64748b;">No available products in stock.</td></tr>`;
      let tfoot = document.getElementById('available-products-tfoot');
      if(tfoot) tfoot.remove();
  } else {
      let tfoot = document.getElementById('available-products-tfoot');
      if (!tfoot) {
          tfoot = document.createElement('tfoot');
          tfoot.id = 'available-products-tfoot';
          document.querySelector('#available-products-modal table').appendChild(tfoot);
      }
      tfoot.innerHTML = `
        <tr>
          <th colspan="4" style="border-top:2px solid #e2e8f0; padding:15px 5px; text-align:right; font-size:16px;">Total Inventory Value:</th>
          <th style="border-top:2px solid #e2e8f0; padding:15px 5px; color:#16a34a; font-size:16px;">${totalStock}</th>
          <th style="border-top:2px solid #e2e8f0; padding:15px 5px;"></th>
          <th style="border-top:2px solid #e2e8f0; padding:15px 5px; font-size:16px;">Rs. ${totalSellValue.toFixed(2)}</th>
          ${showPurchase ? `<th style="border-top:2px solid #e2e8f0; padding:15px 5px; color:#dc2626; font-size:16px;">Rs. ${totalPurchaseValue.toFixed(2)}</th>` : '<th style="display:none;"></th>'}
          ${showProfit ? `<th style="border-top:2px solid #e2e8f0; padding:15px 5px; color:#16a34a; font-size:16px;">Rs. ${totalProfitValue.toFixed(2)}</th>` : '<th style="display:none;"></th>'}
          <th style="border-top:2px solid #e2e8f0; padding:15px 5px;"></th>
        </tr>
      `;
  }
  
  document.getElementById('available-products-modal').style.display = 'flex';
}

// Invoice Logic Helper to update product image thumbnail in the bill row
function updateRowProductImage(row, imageSrc, description) {
  if (!row || !row.cells || !row.cells[0]) return;
  const cell = row.cells[0].querySelector('.item-product-cell');
  if (!cell) return;
  let existingImg = cell.querySelector('img');
  if (imageSrc) {
    if (!existingImg) {
      existingImg = document.createElement('img');
      existingImg.className = 'item-receipt-img';
      cell.insertBefore(existingImg, cell.firstChild);
    }
    existingImg.src = imageSrc;
    existingImg.alt = description || 'Product';
    existingImg.title = 'Click to view photo: ' + (description || '');
    existingImg.onclick = function(e) {
      e.stopPropagation();
      openLightbox(imageSrc, description || 'Product Picture');
    };
  } else if (existingImg) {
    existingImg.remove();
  }
}

function addRow(){
  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  let row = table.insertRow();
  row.dataset.barcode = ""; // Initialize empty barcode
  row.dataset.image = ""; // Store item image URL
  row.innerHTML = `
    <td class="col-desc">
      <div class="item-product-cell">
        <div class="item-product-info">
          <input class="input-desc" placeholder="Desc" list="available-descriptions" oninput="checkEmptyRow(this)">
          <span class="print-text print-desc"></span>
        </div>
      </div>
    </td>
    <td class="col-size">
      <input class="input-size" placeholder="-">
      <span class="print-text print-size"></span>
    </td>
    <td class="col-qty">
      <input class="input-qty" type="number" value="1" min="1" oninput="calc()">
      <span class="print-text print-qty"></span>
    </td>
    <td class="col-disc">
      <input class="input-item-disc" type="number" min="0" max="100" step="any" placeholder="0%" oninput="calc()" title="Discount % for this item">
      <span class="print-text print-disc">-</span>
    </td>
    <td class="col-price">
      <input class="input-price" type="number" oninput="calc()">
      <span class="print-text print-price"></span>
    </td>
    <td class="col-action print-hide">
      <span class="btn-scan-row" title="Scan with Camera" onclick="scanBarcode(this)">📷</span>
      <span class="btn-scan-row" title="Remove Item" style="color:#ef4444; margin-left:3px;" onclick="removeRow(this)">🗑️</span>
    </td>
  `;
  calc();
}

function removeRow(btn) {
  let row = btn.closest('tr');
  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  if (table.rows.length > 1) {
    row.remove();
    calc();
  } else {
    // Just clear the row if it's the only one left
    row.dataset.barcode = "";
    row.dataset.image = "";
    let descInput = row.querySelector('.input-desc');
    let sizeInput = row.querySelector('.input-size');
    let qtyInput = row.querySelector('.input-qty');
    let discInput = row.querySelector('.input-item-disc');
    let priceInput = row.querySelector('.input-price');
    if (descInput) descInput.value = "";
    if (sizeInput) sizeInput.value = "";
    if (qtyInput) qtyInput.value = "1";
    if (discInput) discInput.value = "";
    if (priceInput) priceInput.value = "";
    updateRowProductImage(row, null);
    calc();
  }
}

function checkEmptyRow(input) {
  let val = input.value.trim();
  let row = input.closest('tr');
  
  if (products[val]) {
     let barcode = val;
     input.value = ''; // Clear barcode from description input
     currentScanRow = row;
     populateProduct(barcode);
     return;
  }
  
  if (val.length > 0) {
      for (let b in products) {
          if (products[b].description && products[b].description.toLowerCase() === val.toLowerCase()) {
              input.value = '';
              currentScanRow = row;
              populateProduct(b);
              return;
          }
      }
  } else {
      // If cleared, remove image thumbnail from this row
      row.dataset.barcode = "";
      row.dataset.image = "";
      updateRowProductImage(row, null);
  }

  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  if(row === table.rows[table.rows.length - 1] && val !== '') {
    addRow();
  }
}

function setDiscount(val) {
  const discInput = document.getElementById('discount');
  if (discInput) {
    discInput.value = val;
    calc();
  }
}

function syncPrintValues() {
  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  for(let i=0; i<table.rows.length; i++) {
    let row = table.rows[i];
    let descInput = row.querySelector('.input-desc');
    let sizeInput = row.querySelector('.input-size');
    let qtyInput = row.querySelector('.input-qty');
    let discInput = row.querySelector('.input-item-disc');
    let priceInput = row.querySelector('.input-price');

    let descPrint = row.querySelector('.print-desc');
    let sizePrint = row.querySelector('.print-size');
    let qtyPrint = row.querySelector('.print-qty');
    let discPrint = row.querySelector('.print-disc');
    let pricePrint = row.querySelector('.print-price');

    let itemDisc = discInput ? (parseFloat(discInput.value) || 0) : 0;

    if (descPrint && descInput) descPrint.textContent = descInput.value.trim();
    if (sizePrint && sizeInput) sizePrint.textContent = sizeInput.value.trim() || '-';
    if (qtyPrint && qtyInput) qtyPrint.textContent = qtyInput.value.trim() || '1';
    if (discPrint) discPrint.textContent = itemDisc > 0 ? (itemDisc + '%') : '-';
    if (pricePrint && priceInput) pricePrint.textContent = priceInput.value.trim() ? parseFloat(priceInput.value).toFixed(2) : '';
  }

  const sub = document.getElementById("sub");
  const disc = document.getElementById("discount");
  const pay = document.getElementById("payment");
  const chg = document.getElementById("change");
  const grd = document.getElementById("grand");

  const subVal = parseFloat(sub ? sub.value : 0) || 0;
  const discVal = parseFloat(disc ? disc.value : 0) || 0;
  const discAmt = (subVal * discVal) / 100;

  const discRow = document.getElementById("discount-row");
  const printDisc = document.getElementById("print-discount");

  if (document.getElementById("print-sub")) document.getElementById("print-sub").textContent = sub ? sub.value : '0.00';
  if (document.getElementById("print-payment")) document.getElementById("print-payment").textContent = pay ? pay.value : '0.00';
  if (document.getElementById("print-change")) document.getElementById("print-change").textContent = chg ? chg.value : '0.00';
  if (document.getElementById("print-grand")) document.getElementById("print-grand").textContent = grd ? grd.value : '0.00';

  if (discVal > 0) {
    if (discRow) discRow.classList.remove("print-hide-row");
    if (printDisc) printDisc.textContent = `-${discAmt.toFixed(2)} (${discVal}%)`;
  } else {
    if (discRow) discRow.classList.add("print-hide-row");
    if (printDisc) printDisc.textContent = `0.00`;
  }
}

function calc(){
  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  let subtotal = 0;
  let count = 0;

  for(let i=0; i<table.rows.length; i++){
    let row = table.rows[i];
    let descInput = row.querySelector('.input-desc');
    let priceInput = row.querySelector('.input-price');
    let qtyInput = row.querySelector('.input-qty');
    let discInput = row.querySelector('.input-item-disc');

    let desc = descInput ? descInput.value.trim() : '';
    let price = priceInput ? (parseFloat(priceInput.value) || 0) : 0;
    let qty = qtyInput ? (parseInt(qtyInput.value, 10) || 0) : 0;
    let itemDisc = discInput ? (parseFloat(discInput.value) || 0) : 0;
    if (itemDisc < 0) itemDisc = 0;
    if (itemDisc > 100) itemDisc = 100;
    
    let rawAmount = price * qty;
    let itemDiscAmt = (rawAmount * itemDisc) / 100;
    let itemAmount = Math.max(0, rawAmount - itemDiscAmt);

    subtotal += itemAmount;
    if(desc || price > 0) count++;
  }

  let discInput = document.getElementById("discount");
  let discPercent = parseFloat(discInput ? discInput.value : 0) || 0;
  if (discPercent < 0) discPercent = 0;
  if (discPercent > 100) discPercent = 100;

  let discountAmount = (subtotal * discPercent) / 100;
  let grandTotal = Math.max(0, subtotal - discountAmount);

  // Update preset button active state
  document.querySelectorAll('.btn-disc-preset').forEach(btn => {
    btn.classList.toggle('active', parseFloat(btn.textContent) === discPercent);
  });

  const discAmtRow = document.getElementById('discount-amount-row');
  const discAmtInput = document.getElementById('discount-amount');
  if (discAmtRow && discAmtInput) {
    if (discPercent > 0) {
      discAmtRow.style.display = '';
      discAmtInput.value = '-' + discountAmount.toFixed(2);
    } else {
      discAmtRow.style.display = 'none';
      discAmtInput.value = '0.00';
    }
  }

  document.getElementById("sub").value = subtotal.toFixed(2);
  document.getElementById("grand").value = grandTotal.toFixed(2);
  document.getElementById("itemCount").innerText = count;

  if (document.activeElement && document.activeElement.id !== 'payment') {
      document.getElementById("payment").value = grandTotal.toFixed(2);
  }

  let pay = parseFloat(document.getElementById("payment").value) || 0;
  if(pay > 0 && pay >= grandTotal) {
    document.getElementById("change").value = (pay - grandTotal).toFixed(2);
  } else {
    document.getElementById("change").value = "0.00";
  }

  syncPrintValues();
}

function scanBarcode(button) {
  currentScanRow = button.closest('tr');
  document.getElementById('scanner-container').style.display = 'flex';
  
  Quagga.init({
    inputStream: { name: "Live", type: "LiveStream", target: document.querySelector('#scanner-video'), constraints: { width: 640, height: 480, facingMode: "environment" } },
    decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"] }
  }, function(err) {
    if (err) { alert("Camera failed."); stopScanning(); return; }
    Quagga.start();
  });
  
  Quagga.onDetected(function(result) {
    let code = result.codeResult.code;
    stopScanning();
    populateProduct(code);
  });
}

function stopScanning() {
  try { Quagga.stop(); } catch(e){}
  document.getElementById('scanner-container').style.display = 'none';
}

function populateProduct(barcode) {
  if (products[barcode]) {
    let product = products[barcode];
    let stock = parseInt(product.stock) || 0;
    
    if (stock <= 0) {
      alert("Out of Stock! Product: " + product.description);
      if (currentScanRow) {
        let descInput = currentScanRow.querySelector('.input-desc');
        if (descInput) descInput.value = "";
      }
      return;
    }

    let table = document.getElementById("items").getElementsByTagName('tbody')[0];
    
    // First, check if this barcode is already in the bill
    for (let i = 0; i < table.rows.length; i++) {
      if (table.rows[i].dataset.barcode === barcode) {
        // Item exists, just increment quantity
        let qtyInput = table.rows[i].querySelector('.input-qty');
        let currentQty = parseInt((qtyInput ? qtyInput.value : 0) || 0);
        if (currentQty + 1 > stock) {
          alert("Cannot add more! Only " + stock + " in stock.");
          if (currentScanRow && currentScanRow !== table.rows[i]) {
            let descInput = currentScanRow.querySelector('.input-desc');
            if (descInput) descInput.value = "";
          }
          return;
        }
        if (qtyInput) qtyInput.value = currentQty + 1;
        // Ensure image is up to date
        table.rows[i].dataset.image = product.image || "";
        updateRowProductImage(table.rows[i], product.image, product.description);
        calc();
        return;
      }
    }

    let targetRow = currentScanRow;
    
    if (!targetRow) {
        let lastRow = table.rows[table.rows.length - 1];
        let lastDesc = lastRow ? lastRow.querySelector('.input-desc') : null;
        let lastPrice = lastRow ? lastRow.querySelector('.input-price') : null;
        if (lastRow && (!lastDesc || !lastDesc.value) && (!lastPrice || !lastPrice.value)) {
            targetRow = lastRow;
        } else {
            addRow();
            table = document.getElementById("items").getElementsByTagName('tbody')[0];
            targetRow = table.rows[table.rows.length - 1];
        }
    }
    
    // Set data-barcode and data-image attribute to identify this row
    targetRow.dataset.barcode = barcode;
    targetRow.dataset.image = product.image || "";
    let descInput = targetRow.querySelector('.input-desc');
    let sizeInput = targetRow.querySelector('.input-size');
    let qtyInput = targetRow.querySelector('.input-qty');
    let discInput = targetRow.querySelector('.input-item-disc');
    let priceInput = targetRow.querySelector('.input-price');

    if (descInput) descInput.value = product.description;
    if (sizeInput) sizeInput.value = product.size || '';
    if (qtyInput) qtyInput.value = "1";
    if (discInput) discInput.value = (product.discount && parseFloat(product.discount) > 0) ? product.discount : '';
    if (priceInput) priceInput.value = product.price || '';
    
    // Update thumbnail in receipt item cell
    updateRowProductImage(targetRow, product.image, product.description);
    
    calc();
    if (targetRow === table.rows[table.rows.length - 1]) {
        addRow();
    }
    
  } else {
    if (currentScanRow) {
      let descInput = currentScanRow.querySelector('.input-desc');
      if (descInput) descInput.value = "Unknown: " + barcode;
    }
    alert("Product not found! Barcode: " + barcode);
  }
}

function handleGlobalScan(inputElem) {
  let barcode = inputElem.value.trim();
  inputElem.value = ''; 
  if(!barcode) return;
  
  currentScanRow = null; 
  populateProduct(barcode);
  inputElem.focus();
}

function handleGlobalScanInput(inputElem) {
  let barcode = inputElem.value.trim();
  if (products[barcode]) {
    inputElem.value = '';
    currentScanRow = null;
    populateProduct(barcode);
  }
}

function applyPaperSize(sizeKey) {
  if (!sizeKey) {
    sizeKey = localStorage.getItem('printer_paper_size') || '58x210';
  }
  localStorage.setItem('printer_paper_size', sizeKey);
  
  const selectElem = document.getElementById('paper-size-select');
  if (selectElem && selectElem.value !== sizeKey) {
    selectElem.value = sizeKey;
  }
  
  let styleElem = document.getElementById('dynamic-paper-size-style');
  if (!styleElem) {
    styleElem = document.createElement('style');
    styleElem.id = 'dynamic-paper-size-style';
    document.head.appendChild(styleElem);
  }
  
  let css = '';
  switch(sizeKey) {
    case '58x210':
      css = `
        @media print {
          @page { size: 58mm 210mm; margin: 0mm; }
          body.printing-invoice { max-width: 58mm !important; }
          body.printing-invoice .receipt-card { width: 54mm !important; max-width: 58mm !important; font-size: 10px !important; }
        }
      `;
      break;
    case '58x297':
      css = `
        @media print {
          @page { size: 58mm 297mm; margin: 0mm; }
          body.printing-invoice { max-width: 58mm !important; }
          body.printing-invoice .receipt-card { width: 54mm !important; max-width: 58mm !important; font-size: 10px !important; }
        }
      `;
      break;
    case '58xauto':
      css = `
        @media print {
          @page { size: 58mm auto; margin: 0mm; }
          body.printing-invoice { max-width: 58mm !important; }
          body.printing-invoice .receipt-card { width: 54mm !important; max-width: 58mm !important; font-size: 10px !important; }
        }
      `;
      break;
    case '80x210':
      css = `
        @media print {
          @page { size: 80mm 210mm; margin: 0mm; }
          body.printing-invoice { max-width: 80mm !important; }
          body.printing-invoice .receipt-card { width: 75mm !important; max-width: 80mm !important; font-size: 12px !important; }
        }
      `;
      break;
    case '80x297':
      css = `
        @media print {
          @page { size: 80mm 297mm; margin: 0mm; }
          body.printing-invoice { max-width: 80mm !important; }
          body.printing-invoice .receipt-card { width: 75mm !important; max-width: 80mm !important; font-size: 12px !important; }
        }
      `;
      break;
    case '80xauto':
      css = `
        @media print {
          @page { size: 80mm auto; margin: 0mm; }
          body.printing-invoice { max-width: 80mm !important; }
          body.printing-invoice .receipt-card { width: 75mm !important; max-width: 80mm !important; font-size: 12px !important; }
        }
      `;
      break;
    case 'a4':
      css = `
        @media print {
          @page { size: A4 portrait; margin: 8mm; }
          body.printing-invoice { max-width: 100% !important; }
          body.printing-invoice .receipt-card { width: 100% !important; max-width: 190mm !important; font-size: 13px !important; }
        }
      `;
      break;
    case 'a5':
      css = `
        @media print {
          @page { size: A5 portrait; margin: 6mm; }
          body.printing-invoice { max-width: 100% !important; }
          body.printing-invoice .receipt-card { width: 100% !important; max-width: 135mm !important; font-size: 12px !important; }
        }
      `;
      break;
    default:
      css = `
        @media print {
          @page { size: 58mm 210mm; margin: 0mm; }
          body.printing-invoice { max-width: 58mm !important; }
          body.printing-invoice .receipt-card { width: 54mm !important; max-width: 58mm !important; font-size: 10px !important; }
        }
      `;
  }
  styleElem.innerHTML = css;
}

function printInvoice() {
  calc();
  syncPrintValues();
  // Apply the selected paper size
  const paperSelect = document.getElementById('paper-size-select');
  applyPaperSize(paperSelect ? paperSelect.value : '58x210');

  // Hide empty rows
  let table = document.getElementById("items").getElementsByTagName('tbody')[0];
  for(let i=0; i<table.rows.length; i++) {
    let descInput = table.rows[i].querySelector('.input-desc');
    let desc = descInput ? descInput.value.trim() : '';
    if(!desc) table.rows[i].classList.add("empty-row");
    else table.rows[i].classList.remove("empty-row");
  }
  
  document.body.classList.remove('printing-barcodes');
  document.body.classList.add('printing-invoice');
  window.print();
}

function saveBill(){
  const items = [];
  const tbody = document.getElementById("items").getElementsByTagName('tbody')[0];
  const rows = Array.from(tbody.querySelectorAll('tr'));
  let totalCost = 0;
  let hasError = false;

  const discInput = document.getElementById('discount');
  const discPercent = parseFloat(discInput ? discInput.value : 0) || 0;

  rows.forEach(row => {
    const descInput = row.querySelector('.input-desc');
    const sizeInput = row.querySelector('.input-size');
    const qtyInput = row.querySelector('.input-qty');
    const itemDiscInput = row.querySelector('.input-item-disc');
    const priceInput = row.querySelector('.input-price');

    const description = descInput ? descInput.value.trim() : '';
    const size = sizeInput ? sizeInput.value.trim() : '';
    const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 0) : 0;
    const price = priceInput ? (parseFloat(priceInput.value) || 0) : 0;
    const itemDiscountPercent = itemDiscInput ? (parseFloat(itemDiscInput.value) || 0) : 0;
    
    const itemRaw = price * qty;
    const itemDiscAmt = (itemRaw * itemDiscountPercent) / 100;
    const amount = Math.max(0, itemRaw - itemDiscAmt);
    
    const barcode = row.dataset.barcode;
    
    if (description && !barcode) {
        hasError = true;
        alert(`Product '${description}' is not a recognized available product. Please scan or select from the available products list.`);
        return;
    }

    let pPrice = 0;
    if (barcode && products[barcode]) {
        pPrice = parseFloat(products[barcode].purchasePrice) || 0;
    }
    const itemProfit = amount - (pPrice * qty);
    let image = row.dataset.image || '';
    if (!image && barcode && products[barcode]) {
        image = products[barcode].image || '';
    }

    if(description || price > 0) {
        items.push({ 
          description, 
          size, 
          price, 
          quantity: qty, 
          discountPercent: itemDiscountPercent, 
          amount, 
          purchasePrice: pPrice, 
          profit: itemProfit, 
          image 
        });
        totalCost += (pPrice * qty);
    }
  });
  
  if(hasError) return;
  
  if(items.length === 0) { alert("Cannot save empty bill."); return; }

  const subTotal = parseFloat(document.getElementById('sub').value) || 0;
  const discountAmount = (subTotal * discPercent) / 100;
  const grandTotal = parseFloat(document.getElementById('grand').value) || 0;
  const totalProfit = grandTotal - totalCost;

  const bill = {
    billNumber: document.getElementById('bill-number').value.trim(),
    date: document.getElementById('date').innerText,
    time: document.getElementById('time').innerText,
    salesman: document.getElementById('salesman').value.trim(),
    items,
    subTotal: subTotal,
    discountPercent: discPercent,
    discountAmount: discountAmount,
    payment: parseFloat(document.getElementById('payment').value) || 0,
    change: parseFloat(document.getElementById('change').value) || 0,
    grandTotal: grandTotal,
    totalProfit: totalProfit
  };

  fetch('save_bill.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(bill)
  })
  .then(async res => {
    const text = await res.text();
    try {
      const data = JSON.parse(text);
      if(data.status === 'success') {
        alert(`Saved! (ID: ${data.bill_id})`);
        
        // Deduct from stock
        rows.forEach(row => {
          const barcode = row.dataset.barcode;
          const qtyInput = row.querySelector('.input-qty');
          const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 0) : 0;
          if (barcode && products[barcode] && qty > 0) {
            products[barcode].stock = (parseInt(products[barcode].stock) || 0) - qty;
          }
        });
        localStorage.setItem('products', JSON.stringify(products));
        renderProducts();

        let currentBill = parseInt(document.getElementById('bill-number').value);
        if(!isNaN(currentBill)) {
          document.getElementById('bill-number').value = currentBill + 1;
        }

        // Reset discount back to 0
        setDiscount(0);
      }
      else throw new Error(data.error);
    } catch(e) { alert("Saved, but error reading response: " + text); }
  })
  .catch(err => alert('Network error or server down.'));
}


// Global Barcode Scanner Listener
let barcodeBuffer = '';
let barcodeTimer = null;
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeProductListModal();
    if (typeof closeLightbox === 'function') closeLightbox();
    const availModal = document.getElementById('available-products-modal');
    if (availModal) availModal.style.display = 'none';
    return;
  }

  // Ignore if user is focused on ANY input field or textarea (the input's own events will handle it)
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
    return;
  }

  // Print shortcut: press 'p' to print bill
  if (e.key.toLowerCase() === 'p' && !e.ctrlKey && !e.altKey && !e.metaKey && barcodeBuffer === '') {
    e.preventDefault();
    printInvoice();
    return;
  }
  
  if (e.key === 'Enter') {
    if (barcodeBuffer.length > 3) {
      currentScanRow = null;
      populateProduct(barcodeBuffer);
      barcodeBuffer = '';
      e.preventDefault();
    }
  } else if (e.key.length === 1) { 
    barcodeBuffer += e.key;
    clearTimeout(barcodeTimer);
    barcodeTimer = setTimeout(() => {
      barcodeBuffer = '';
    }, 150); // Scanners type very fast, clear if slow typing
  }
});

calc();
applyPaperSize();
</script>
</body>
</html>

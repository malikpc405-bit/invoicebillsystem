# Malik Shoes Invoice Billing System

A modern, responsive invoice billing system with barcode scanner support for Malik Shoes store.

## Features

- **Barcode Scanning**: Scan product barcodes using device camera
- **Product Database**: Predefined product list with automatic lookup
- **Auto-Enter**: Press Enter in barcode field to add products
- **Quantity Handling**: Automatic quantity and total calculations
- **Responsive Design**: Works on desktop and mobile devices
- **Print Support**: Print-ready invoice layout
- **Auto-Save**: Bills are automatically saved to browser storage

## Setup Instructions

### 1. Install XAMPP / Apache + PHP
Install XAMPP from: https://www.apachefriends.org/index.html

### 2. Copy files to your XAMPP `htdocs` folder
Place `malik.html`, `save_bill.php`, and the `img/` folder in the same project directory under `htdocs`.

### 3. Start Apache and MySQL
Use XAMPP Control Panel to start `Apache` and `MySQL`.

### 4. Open in Browser
Navigate to: `http://localhost/your-folder-name/malik.html`

## How to Use

### Adding Products
1. **Scan Barcode**: Click "📱 Scan Barcode" and point camera at product barcode
2. **Manual Entry**: Type barcode number and press Enter
3. **Manual Add**: Click "➕ Add Item" to add products manually

### Product Database
The system includes sample products. Barcodes are in format: `123456789012` to `123456789021`

### Bill Management
- Bills are automatically saved every 30 seconds
- Click "🖨️ Print Bill" to print the invoice
- All data is stored in browser localStorage

## Camera Access

For barcode scanning to work:
- Must use `http://localhost` (not file://)
- Allow camera permission when prompted
- Use a modern browser (Chrome, Firefox, Edge, Safari)

## Troubleshooting

**Camera not working:**
- Ensure you're accessing via `http://localhost:8000`
- Allow camera permission in browser
- Try refreshing the page

**Products not found:**
- Check barcode format
- Add products manually using "➕ Add Item"

**Server not starting:**
- Install Python 3.x
- Run as administrator if needed

## Customization

### Adding Products
Edit the `products` object in `billing_system.html`:

```javascript
const products = {
    "BARCODE_NUMBER": { name: "PRODUCT_NAME", size: "SIZE", price: PRICE },
    // Add more products...
};
```

### Styling
Modify the CSS in the `<style>` section for custom appearance.

## Files

- `billing_system.html` - Main application
- `server.py` - HTTP server for camera access
- `run_server.bat` - Windows batch file to start server
- `README.md` - This documentation

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## License

This project is for Malik Shoes store use.
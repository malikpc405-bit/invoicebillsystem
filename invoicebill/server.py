#!/usr/bin/env python3
"""
Simple HTTP Server for Malik Shoes Billing System
Run this script to serve the HTML files with camera access support.
"""

import http.server
import socketserver
import os
from urllib.parse import unquote

class CustomHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        # Add CORS headers for camera access
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        super().end_headers()

    def do_GET(self):
        # Handle root path
        if self.path == '/':
            self.path = '/billing_system.html'
        return super().do_GET()

if __name__ == '__main__':
    PORT = 8000
    os.chdir(os.path.dirname(os.path.abspath(__file__)))

    with socketserver.TCPServer(("", PORT), CustomHTTPRequestHandler) as httpd:
        print(f"Server started at http://localhost:{PORT}")
        print("Open billing_system.html in your browser")
        print("Press Ctrl+C to stop the server")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nServer stopped")
            httpd.shutdown()
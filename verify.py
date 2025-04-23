from flask import Flask, request, jsonify, send_from_directory
import os
from werkzeug.utils import secure_filename
from datetime import datetime, timedelta
import sqlite3

app = Flask(__name__)
app.config['UPLOAD_FOLDER'] = 'receipts'
app.config['ALLOWED_EXTENSIONS'] = {'png', 'jpg', 'jpeg', 'pdf'}

# Database setup
def get_db():
    conn = sqlite3.connect('subscriptions.db')
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    with get_db() as conn:
        conn.execute('''
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            transaction_id TEXT NOT NULL,
            receipt_path TEXT NOT NULL,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            status TEXT DEFAULT 'pending',
            amount REAL NOT NULL,
            plan TEXT NOT NULL
        )
        ''')
        conn.commit()

# Routes
@app.route('/')
def index():
    return send_from_directory('static', 'payment.html')

@app.route('/get_username')
def get_username():
    # In a real app, you'd get this from the session
    return jsonify({'username': 'current_user'})

@app.route('/submit_payment', methods=['POST'])
def submit_payment():
    if 'receipt' not in request.files:
        return jsonify({'success': False, 'message': 'No receipt file'})
    
    receipt = request.files['receipt']
    transaction_id = request.form.get('transaction_id')
    username = request.form.get('username')
    plan = request.form.get('plan')
    
    if not all([receipt, transaction_id, username, plan]):
        return jsonify({'success': False, 'message': 'Missing required fields'})
    
    if receipt.filename == '':
        return jsonify({'success': False, 'message': 'No selected file'})
    
    if receipt and allowed_file(receipt.filename):
        filename = secure_filename(f"{username}_{transaction_id}_{receipt.filename}")
        receipt_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        receipt.save(receipt_path)
        
        # Set amount based on plan
        amount = 0
        if plan == 'plus':
            amount = 1
        elif plan == 'pro':
            amount = 200
        
        # Calculate expiration date (1 month from now)
        expires_at = datetime.now() + timedelta(days=30)
        
        # Store in database
        with get_db() as conn:
            conn.execute('''
            INSERT INTO subscriptions (username, transaction_id, receipt_path, expires_at, amount, plan)
            VALUES (?, ?, ?, ?, ?, ?)
            ''', (username, transaction_id, receipt_path, expires_at, amount, plan))
            conn.commit()
        
        return jsonify({'success': True, 'message': 'Payment submitted for verification'})
    
    return jsonify({'success': False, 'message': 'Invalid file type'})

@app.route('/logout')
def logout():
    # In a real app, you'd clear the session here
    return jsonify({'success': True})

@app.route('/thank_you')
def thank_you():
    return "Thank you for your payment! Admin will verify it shortly."

# Helper functions
def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in app.config['ALLOWED_EXTENSIONS']

if __name__ == '__main__':
    init_db()
    if not os.path.exists(app.config['UPLOAD_FOLDER']):
        os.makedirs(app.config['UPLOAD_FOLDER'])
    app.run(debug=True)
from flask import Flask, request, jsonify, send_from_directory
import os
from werkzeug.utils import secure_filename
import pymysql
from datetime import datetime, timedelta

app = Flask(__name__)

# Configuration
app.config['UPLOAD_FOLDER'] = 'static/uploads'
app.config['ALLOWED_EXTENSIONS'] = {'jpg', 'jpeg', 'png', 'pdf'}
app.config['MAX_CONTENT_LENGTH'] = 5 * 1024 * 1024  # 5MB limit
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'rajkumar@123',
    'database': 'hackathon',
    'cursorclass': pymysql.cursors.DictCursor
}

def get_db_connection():
    return pymysql.connect(**db_config)

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in app.config['ALLOWED_EXTENSIONS']

@app.route('/submit_payment', methods=['POST'])
def submit_payment():
    if 'receipt' not in request.files:
        return jsonify({'success': False, 'message': 'No file uploaded'})
    
    file = request.files['receipt']
    if file.filename == '':
        return jsonify({'success': False, 'message': 'No selected file'})
    
    transaction_id = request.form.get('transaction_id')
    username = request.form.get('username')
    email = request.form.get('email')
    
    if not all([transaction_id, username, email]):
        return jsonify({'success': False, 'message': 'Missing required fields'})
    
    if not allowed_file(file.filename):
        return jsonify({'success': False, 'message': 'Allowed file types: jpg, jpeg, png, pdf'})
    
    # Secure filename and save
    filename = secure_filename(file.filename)
    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
    file.save(filepath)
    
    # Save to database
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        cursor.execute("""
            INSERT INTO payments 
            (username, email, transaction_id, amount, upi_method, receipt_filename, status)
            VALUES (%s, %s, %s, %s, %s, %s, 'pending')
        """, (
            username, 
            email, 
            transaction_id, 
            1.00,  # Fixed amount of ₹1
            request.form.get('upi_method', 'Any UPI App'),
            filename
        ))
        conn.commit()
        return jsonify({
            'success': True,
            'message': 'Payment submitted successfully! Admin will verify shortly.'
        })
    except Exception as e:
        conn.rollback()
        return jsonify({'success': False, 'message': str(e)})
    finally:
        conn.close()

@app.route('/check_access/<username>')
def check_access(username):
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        cursor.execute("""
            SELECT status, access_expires_at 
            FROM payments 
            WHERE username = %s AND status = 'verified'
            ORDER BY created_at DESC LIMIT 1
        """, (username,))
        
        result = cursor.fetchone()
        if result:
            expires_at = result['access_expires_at']
            remaining_days = (expires_at - datetime.now()).days if expires_at else 0
            return jsonify({
                'has_access': True,
                'expires_at': expires_at.strftime('%Y-%m-%d %H:%M:%S'),
                'remaining_days': remaining_days
            })
        return jsonify({
            'has_access': False,
            'message': 'No active subscription found'
        })
    except Exception as e:
        return jsonify({'error': str(e)})
    finally:
        conn.close()

@app.route('/uploads/<filename>')
def uploaded_file(filename):
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)

if __name__== '__main__':
    app.run(host='0.0.0.0', port=5007,debug=True)

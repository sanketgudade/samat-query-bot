from flask import Flask, render_template, request, jsonify, session, redirect, url_for, send_from_directory
import google.generativeai as genai
import re
import os
from flask_cors import CORS
import pymysql
from datetime import datetime, timedelta
import json

app = Flask(__name__)
app.secret_key = os.environ.get('FLASK_SECRET_KEY', 'your_secret_key_here')
app.config['SESSION_COOKIE_NAME'] = 'flask_session'
app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(minutes=30)

# Configure CORS with specific origins
CORS(app, resources={
    r"/php_login": {"origins": ["http://localhost", "http://127.0.0.1"]},
    r"/api/*": {"origins": "*"},
    r"/static/*": {"origins": "*"},
    r"/get_username": {"origins": "*"},
    r"/logout": {"origins": "*"},
    r"/check_session": {"origins": "*"},
    r"/pricing": {"origins": "*"},
    r"/payment": {"origins": "*"},
    r"/submit_payment": {"origins": "*"},  # Add this line
    r"/check_access": {"origins": "*"},    # Add this line
    r"/verify-payment": {"origins": "*"},
    r"/AdvanceSQB": {"origins": "*"}
})
CORS(app, resources={
    r"/get_username": {
        "origins": ["http://localhost", "http://127.0.0.1"],
        "supports_credentials": True,
        "methods": ["GET"]
    },
    r"/check_access/*": {
        "origins": ["http://localhost", "http://127.0.0.1"],
        "supports_credentials": True,
        "methods": ["GET"]
    },
    r"/logout": {
        "origins": ["http://localhost", "http://127.0.0.1"],
        "supports_credentials": True,
        "methods": ["GET"]
    }
}, supports_credentials=True)

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'rajkumar@123@',
    'database': 'hackathon',
    'cursorclass': pymysql.cursors.DictCursor
}

# Configure Gemini AI
genai.configure(api_key=os.environ.get('GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY'))
model = genai.GenerativeModel('gemini-2.0-flash')

def get_db_connection():
    return pymysql.connect(**db_config)

@app.route('/')
def home():
    if 'user_name' not in session:
        return redirect('http://localhost/hack/index.html')
    return render_template('chatbot.html')

@app.route('/pricing')
def pricing():
    if 'user_name' not in session:
        return redirect('http://localhost/hack/index.html')
    return render_template('pricing.html')

@app.route('/payment')
def payment():
    if 'user_name' not in session:
        return redirect('http://localhost/hack/index.html')
    return render_template('payment_pro.html')

@app.route('/AdvanceSQB')
def advance_sqb():
    if 'user_name' not in session:
        return redirect('http://localhost/hack/index.html')
    return render_template('AdvanceSQB.html')

@app.route('/static/<path:filename>')
def static_files(filename):
    return send_from_directory('static', filename)

@app.route('/php_login', methods=['GET'])
def php_login():
    user_name = request.args.get('user_name')
    redirect_url = request.args.get('redirect')
    if not user_name:
        return jsonify({'error': 'Username is required'}), 400

    # Store user in session
    session['user_name'] = user_name
    session['user_id'] = user_name  # Using username as ID for simplicity

    # Redirect to the appropriate page
    if redirect_url == 'pricing':
        return redirect(url_for('pricing'))
    elif redirect_url == 'payment':
        return redirect(url_for('payment'))
    elif redirect_url == 'AdvanceSQB':
        return redirect(url_for('advance_sqb'))
    else:
        return redirect(url_for('home'))

@app.route('/get_username')
def get_username():
    if 'user_name' in session:
        return jsonify({'username': session['user_name']})
    return jsonify({'error': 'Not logged in'}), 401

@app.route('/check_session')
def check_session():
    return jsonify({
        'logged_in': 'user_name' in session,
        'username': session.get('user_name', None)
    })
@app.route('/api/save_chat', methods=['POST'])
def save_chat():
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    data = request.json
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """INSERT INTO chat_history 
                     (user_id, username, user_message, ai_response, is_sql_query, timestamp) 
                     VALUES (%s, %s, %s, %s, %s, NOW())"""
            cursor.execute(sql, (
                session['user_name'],
                session['user_name'],
                data['user_message'],
                data['ai_response'],
                data.get('is_sql_query', False)
            ))
        connection.commit()
        return jsonify({'success': True, 'id': cursor.lastrowid})
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        if connection:
            connection.close()

@app.route('/api/get_chat_history')
def get_chat_history():
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """SELECT id, user_message, ai_response, timestamp 
                     FROM chat_history 
                     WHERE user_id = %s 
                     ORDER BY timestamp DESC 
                     LIMIT 200"""
            cursor.execute(sql, (session['user_name'],))
            results = cursor.fetchall()
        
        cleaned_results = []
        for item in results:
            try:
                ai_response = json.loads(item['ai_response'])
                cleaned_results.append({
                    'id': item['id'],
                    'user_message': item['user_message'],
                    'ai_response': ai_response.get('response', ''),
                    'timestamp': item['timestamp']
                })
            except json.JSONDecodeError:
                cleaned_results.append({
                    'id': item['id'],
                    'user_message': item['user_message'],
                    'ai_response': item['ai_response'],
                    'timestamp': item['timestamp']
                })
        
        return jsonify({'history': cleaned_results})
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        if connection:
            connection.close()

@app.route('/api/get_chat_history_item')
def get_chat_history_item():
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    item_id = request.args.get('id')
    if not item_id:
        return jsonify({'error': 'Item ID required'}), 400
    
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """SELECT * FROM chat_history 
                     WHERE id = %s AND user_id = %s"""
            cursor.execute(sql, (item_id, session['user_name']))
            result = cursor.fetchone()
        
        if result:
            try:
                ai_response = json.loads(result['ai_response'])
                return jsonify({
                    'item': {
                        'user_message': result['user_message'],
                        'ai_response': ai_response.get('response', ''),
                        'sql': ai_response.get('sql'),
                        'explanation': ai_response.get('explanation')
                    }
                })
            except json.JSONDecodeError:
                return jsonify({
                    'item': {
                        'user_message': result['user_message'],
                        'ai_response': result['ai_response']
                    }
                })
        return jsonify({'error': 'Item not found'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        if connection:
            connection.close()

@app.route('/api/chat', methods=['POST'])
def chat():
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    user_message = request.json.get('message', '').strip()
    if not user_message:
        return jsonify({'response': "Please enter a query."})


    prompt = f"""
    - Generate optimized SQL queries based on user requests, including CREATE DATABASE and CREATE TABLE syntax.
    - If the user provides an existing SQL query, optimize it for efficiency.
    - If the request is unrelated to SQL, respond with: "I can only help with SQL queries."
    - If the user says ok,bye , great,good ,hello, hi, good morning, good afternoon, good evening, or good night, respond with a friendly greeting.
    - If the user provides table names and column names, use those. Otherwise, make reasonable assumptions.
    - Allow all sql queries, including CREATE DATABASE and CREATE TABLE.
    - Provide SQL queries without any markdown formatting (no    or ''').

    For SQL queries, provide:
    1. The optimized SQL query (without markdown formatting)
    2. A clear explanation of what the query does (only if needed)
    3. Any important notes about the query (only if needed)

    Format your response like this:
    SQL: [the SQL query here]
    Explanation: [detailed explanation here] (only if explanation is needed)

    User: {user_message}
    """

    try:
        response = model.generate_content(prompt)
        ai_response = response.text.strip()

        # Extract SQL and explanation
        sql_match = re.search(r"SQL:(.+?)(?=Explanation:|$)", ai_response, re.DOTALL | re.IGNORECASE)
        explanation_match = re.search(r"Explanation:(.+?)(?=Notes:|$)", ai_response, re.DOTALL | re.IGNORECASE)

        sql_query = sql_match.group(1).strip() if sql_match else None
        explanation = explanation_match.group(1).strip() if explanation_match else None

        # Save to database
        save_data = {
            'user_message': user_message,
            'ai_response': json.dumps({
                'response': ai_response,
                'sql': sql_query,
                'explanation': explanation
            }),
            'is_sql_query': sql_query is not None
        }
        
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """INSERT INTO chat_history 
                     (user_id, username, user_message, ai_response, is_sql_query, timestamp) 
                     VALUES (%s, %s, %s, %s, %s, NOW())"""
            cursor.execute(sql, (
                session['user_name'],
                session['user_name'],
                save_data['user_message'],
                save_data['ai_response'],
                save_data['is_sql_query']
            ))
        connection.commit()
        chat_id = cursor.lastrowid
        connection.close()

        return jsonify({
            'id': chat_id,
            'response': ai_response,
            'sql': sql_query,
            'explanation': explanation
        })

    except Exception as e:
        return jsonify({'response': f"An error occurred: {e}"})

@app.route('/logout')
def logout():
    session.clear()
    return redirect("http://localhost/hack/index.html")

@app.route('/check_access')
def check_access():
    if 'user_name' not in session:
        return jsonify({'error': 'Not logged in'}), 401

    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            # Get the most recent payment for the user
            sql = """SELECT status, access_expires_at, created_at 
                     FROM payments 
                     WHERE username = %s 
                     ORDER BY created_at DESC 
                     LIMIT 1"""
            cursor.execute(sql, (session['user_name'],))
            payment = cursor.fetchone()

            if payment:
                current_time = datetime.now()
                expires_at = payment['access_expires_at']
                
                # Check if payment is verified and not expired
                is_active = (payment['status'] == 'verified' and 
                            (expires_at is None or expires_at > current_time))
                
                return jsonify({
                    'hasAccess': is_active,
                    'pending': payment['status'] == 'pending',
                    'expiresAt': expires_at.strftime('%Y-%m-%d %H:%M:%S') if expires_at else None,
                    'status': payment['status'],
                    'message': 'Payment found'
                })
            
            return jsonify({
                'hasAccess': False,
                'pending': False,
                'message': 'No payment record found'
            })
            
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        if connection:
            connection.close()
            
@app.route('/submit_payment', methods=['POST'])
def submit_payment():
    if 'user_name' not in session:
        return jsonify({'success': False, 'message': 'User not logged in'}), 401

    try:
        # Get form data
        transaction_id = request.form.get('transaction_id')
        amount = request.form.get('amount', '1.00')
        upi_method = request.form.get('upi_method', 'Any UPI App')
        
        # Handle file upload
        if 'receipt' not in request.files:
            return jsonify({'success': False, 'message': 'No receipt file uploaded'}), 400
            
        file = request.files['receipt']
        if file.filename == '':
            return jsonify({'success': False, 'message': 'No selected file'}), 400

        # Validate file size (5MB max)
        file.seek(0, os.SEEK_END)
        file_size = file.tell()
        file.seek(0)
        if file_size > 5 * 1024 * 1024:  # 5MB
            return jsonify({'success': False, 'message': 'File too large (max 5MB)'}), 400

        # Create receipts directory if it doesn't exist
        upload_dir = os.path.join(app.root_path, 'receipts')
        if not os.path.exists(upload_dir):
            os.makedirs(upload_dir)

        # Generate unique filename
        file_ext = os.path.splitext(file.filename)[1]
        receipt_filename = f"{session['user_name']}_{datetime.now().strftime('%Y%m%d%H%M%S')}{file_ext}"
        upload_path = os.path.join(upload_dir, receipt_filename)

        # Save the file
        file.save(upload_path)

        # Calculate expiration date (1 month from now)
        payment_date = datetime.now()
        access_expires_at = payment_date + timedelta(days=30)

        # Insert into database
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """INSERT INTO payments (
                username, transaction_id, amount, upi_method, 
                receipt_filename, status, payment_date, 
                access_expires_at, plan
            ) VALUES (%s, %s, %s, %s, %s, 'pending', %s, %s, 'basic')"""
            
            cursor.execute(sql, (
                session['user_name'],
                transaction_id,
                amount,
                upi_method,
                receipt_filename,
                payment_date,
                access_expires_at
            ))
        connection.commit()

        return jsonify({
            'success': True,
            'message': 'Payment submitted for verification'
        })

    except Exception as e:
        # Delete the file if there was an error
        if 'upload_path' in locals() and os.path.exists(upload_path):
            os.remove(upload_path)
        return jsonify({'success': False, 'message': str(e)}), 500
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
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
    r"/logout": {"origins": "*"}
})

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
    return render_template('chatbot.html')

@app.route('/static/<path:filename>')
def static_files(filename):
    return send_from_directory('static', filename)

@app.route('/php_login', methods=['GET'])
def php_login():
    user_name = request.args.get('user_name')
    if not user_name:
        return jsonify({'error': 'Username is required'}), 400

    # Store user in session
    session['user_name'] = user_name
    session['user_id'] = user_name  # Using username as ID for simplicity

    # Redirect to chatbot.html
    return redirect(url_for('home'))

@app.route('/get_username')
def get_username():
    if 'user_name' in session:
        return jsonify({'username': session['user_name']})
    return jsonify({'error': 'Not logged in'}), 401

@app.route('/api/save_chat', methods=['POST'])
def save_chat(data):
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    user_id = session.get('user_id', session['user_name'])
    username = session['user_name']
    
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """INSERT INTO chat_history 
                         (user_id, username, user_message, ai_response, is_sql_query, timestamp) 
                         VALUES (%s, %s, %s, %s, %s, NOW())"""
            cursor.execute(sql, (
                user_id,
                username,
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
    
    user_id = session.get('user_id', session['user_name'])
    limit = request.args.get('limit', 20)
    
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """SELECT * FROM chat_history 
                         WHERE user_id = %s 
                         ORDER BY timestamp DESC 
                         LIMIT %s"""
            cursor.execute(sql, (user_id, int(limit)))
            result = cursor.fetchall()
        return jsonify({'history': result})
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
            return jsonify({'item': result})
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
    
    message = request.json['message']
    user_message = request.json['message'].strip()

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

        sql_query = sql_match.group(1).strip() if sql_match else ai_response
        sql_query = sql_query.replace("sql", "").replace("```", "").replace("'''", "").strip()
        
        explanation = explanation_match.group(1).strip() if explanation_match and "No explanation provided" not in explanation_match.group(1) else None

        # Save to chat history
        is_sql = sql_match is not None
        save_data = {
            'user_message': user_message,
            'ai_response': json.dumps({
                'response': ai_response,
                'sql': sql_query if is_sql else None,
                'explanation': explanation if explanation else None
            }),
            'is_sql_query': is_sql
        }
        
        # Save and get the inserted ID
        save_response = save_chat(save_data)
        save_data = json.loads(save_response.get_data(as_text=True))
        chat_id = save_data.get('id')

        return jsonify({
            'id': chat_id,
            'response': ai_response,
            'sql': sql_query if is_sql else None,
            'explanation': explanation if explanation else None
        })

    except Exception as e:
        return jsonify({'response': f"An error occurred: {e}"})

@app.route('/logout')
def logout():
    session.clear()
    return redirect("http://localhost/hack/index.html")

if __name__ == '__main__':
    # Set the required environment variables
    os.environ['FLASK_APP'] = 'app.py'
    os.environ['FLASK_ENV'] = 'development'

    # Run the application
    app.run(host='0.0.0.0', port=5000, debug=True)
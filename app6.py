from flask import Flask, session, jsonify, redirect, url_for, send_from_directory, request, render_template
from werkzeug.security import generate_password_hash, check_password_hash
import os
from datetime import timedelta
import pymysql
from flask_cors import CORS
import json

app = Flask(__name__)
app.secret_key = os.urandom(24)
app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(minutes=30)
app.config['SESSION_COOKIE_NAME'] = 'smartquery_session'

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'rajkumar@123@',
    'database': 'hackathon',
    'cursorclass': pymysql.cursors.DictCursor
}

def get_db_connection():
    return pymysql.connect(**db_config)

# Mock user database
users = {
    "testuser": {
        "password": generate_password_hash("testpassword"),
        "plan": "free"
    }
}

@app.route('/')
def home():
    if 'username' not in session:
        return redirect(url_for('login'))
    return render_template('chatbot.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        if username in users and check_password_hash(users[username]['password'], password):
            session.permanent = True
            session['username'] = username
            return redirect(url_for('home'))
        return render_template('login.html', error="Invalid credentials")
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    return jsonify({'success': True, 'message': 'Logged out successfully'})

@app.route('/check_session')
def check_session():
    if 'username' in session:
        return jsonify({
            'logged_in': True,
            'username': session['username'],
            'plan': users.get(session['username'], {}).get('plan', 'free')
        })
    return jsonify({'logged_in': False})

@app.route('/get_username')
def get_username():
    if 'username' in session:
        return jsonify({'username': session['username']})
    return jsonify({'error': 'Not logged in'}), 401

@app.route('/pricing')
def pricing():
    if 'username' not in session:
        return redirect(url_for('login'))
    return render_template('pricing.html')

@app.route('/payment')
def payment():
    if 'username' not in session:
        return redirect(url_for('login'))
    plan = request.args.get('plan', 'plus')
    return render_template('payment.html', plan=plan)

@app.route('/AdvanceSQB')
def advance_sqb():
    if 'username' not in session:
        return redirect(url_for('login'))
    return render_template('AdvanceSQB.html')

@app.route('/static/<path:filename>')
def static_files(filename):
    return send_from_directory('static', filename)

if __name__ == '__main__':
    app.run(port=6000, debug=True)
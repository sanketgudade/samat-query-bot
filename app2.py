from flask import Flask, render_template, request, jsonify
import google.generativeai as genai
import re
import os  
from flask_cors import CORS  

app = Flask(__name__)
CORS(app)  

genai.configure(api_key=os.environ.get('GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY')) 
model = genai.GenerativeModel('gemini-2.0-flash')

@app.route('/')
def index():
    return render_template('chatbot2.html')  # Loads chatbot2.html directly

@app.route('/api/chat', methods=['POST'])
def chat():
    message = request.json['message']
    user_message = message.strip()

    if not user_message:
        return jsonify({'response': "Please enter a query."})

    prompt = f"""
    - Generate optimized SQL queries based on user requests, including CREATE DATABASE and CREATE TABLE syntax.
    - If the user provides an existing SQL query, optimize it for efficiency.
    - If the request is unrelated to SQL, respond with: "I can only help with SQL queries."
    - If the user says hello, hi, good morning, good afternoon, good evening, or good night, respond with a friendly greeting.
    - If the user provides table names and column names, use those. Otherwise, make reasonable assumptions.
    - Allow all SQL queries, including CREATE DATABASE and CREATE TABLE.

    User: {user_message}
    """

    try:
        response = model.generate_content(prompt)
        ai_response = response.text.strip()

        # Extract SQL queries
        sql_match = re.search(r"(CREATE DATABASE|CREATE TABLE|ALTER TABLE|DROP TABLE|INSERT INTO|UPDATE|DELETE FROM|SELECT|GRANT|REVOKE|COMMIT|ROLLBACK|SAVEPOINT|MERGE|TRUNCATE TABLE|RENAME TABLE).+?(;|$)", ai_response, re.DOTALL | re.IGNORECASE)
        sql_query = sql_match.group(0).strip() if sql_match else ai_response

        return jsonify({'response': sql_query})

    except Exception as e:
        return jsonify({'response': f"An error occurred: {e}"})

if __name__ == '__main__':
    app.run(debug=True, port=5001)  # Running on a different port to avoid conflicts

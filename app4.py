from flask import Flask, request, jsonify, session
import pymysql
from flask_cors import CORS

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'  # Change this to a secure secret key
CORS(app)

# Database configuration - update with your credentials
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'rajkumar@123@',
    'database': 'hackathon',
    'cursorclass': pymysql.cursors.DictCursor
}

def get_db_connection():
    return pymysql.connect(**db_config)

@app.route('/api/get_chat_history_item')
def get_chat_history_item():
    if 'user_name' not in session:
        return jsonify({'error': 'User not logged in'}), 401
    
    item_id = request.args.get('id')
    if not item_id:
        return jsonify({'error': 'Item ID is required'}), 400
    
    user_id = session.get('user_id', session['user_name'])
    
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """SELECT * FROM chat_history 
                    WHERE id = %s AND user_id = %s"""
            cursor.execute(sql, (item_id, user_id))
            result = cursor.fetchone()
            
            if not result:
                return jsonify({'error': 'Chat item not found'}), 404
                
        return jsonify({'item': result})
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        if connection:
            connection.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=4000, debug=True)
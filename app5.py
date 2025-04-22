from flask import Flask, request, jsonify, render_template, send_from_directory
import google.generativeai as genai
import re
import os
from flask_cors import CORS

# Initialize Flask app with explicit template folder
app = Flask(__name__, 
            template_folder='C:/xampp/htdocs/Hack/templates')
CORS(app)

# Configure Gemini AI
genai.configure(api_key=os.environ.get('GOOGLE_API_KEY', 'YOUR_API_KEY_HERE'))
model = genai.GenerativeModel('gemini-2.0-flash')

def clean_sql(sql):
    """Remove comments, formatting, and clean up SQL"""
    # Remove markdown code blocks and triple quotes
    sql = re.sub(r'```sql?\s*|\s*```|[\'"]{3}', '', sql, flags=re.IGNORECASE)
    
    # Remove line comments
    sql = re.sub(r'--.*?$', '', sql, flags=re.MULTILINE)
    
    # Remove block comments
    sql = re.sub(r'/\*.*?\*/', '', sql, flags=re.DOTALL)
    
    # Remove empty lines and trim
    sql = re.sub(r'^\s*\n', '', sql, flags=re.MULTILINE)
    return sql.strip()

@app.route('/')
def serve_frontend():
    try:
        return render_template('AdvanceSQB.html')
    except Exception as e:
        # Fallback to direct file serving if template rendering fails
        return send_from_directory(app.template_folder, 'AdvanceSQB.html')

@app.route('/api/generate-sql', methods=['POST'])
def generate_sql():
    try:
        data = request.json
        user_query = data.get('query', '').strip()
        mode = data.get('mode', 'generate')
        db_type = data.get('db_type', 'PostgreSQL')

        if not user_query:
            return jsonify({'error': 'Query cannot be empty'}), 400

        if mode == 'generate':
            prompt = f"""Generate a {db_type} SQL query for: {user_query}
            - First provide the executable SQL query
            - Then provide a detailed explanation of the query
            - Format your response like this:
            
            SQL_QUERY: [the SQL query here]
            EXPLANATION: [the explanation here]"""
            
            response = model.generate_content(prompt)
            parts = re.split(r'EXPLANATION:', response.text, flags=re.IGNORECASE)
            
            if len(parts) >= 2:
                sql_part = parts[0].replace('SQL_QUERY:', '').strip()
                return jsonify({
                    'sql': clean_sql(sql_part),
                    'explanation': parts[1].strip(),
                    'mode': mode,
                    'db_type': db_type
                })
            else:
                # Fallback if the response doesn't follow our format
                return jsonify({
                    'sql': clean_sql(response.text),
                    'explanation': "This query retrieves the requested data from the database.",
                    'mode': mode,
                    'db_type': db_type
                })

        elif mode == 'optimize':
            prompt = f"""Optimize this {db_type} SQL query:
            {user_query}
            
            Return in EXACTLY this format:
            OPTIMIZED_SQL: [the optimized SQL without any comments]
            EXPLANATION: [detailed explanation of changes]"""
            
            response = model.generate_content(prompt)
            parts = re.split(r'EXPLANATION:', response.text, flags=re.IGNORECASE)
            
            if len(parts) >= 2:
                sql_part = parts[0].replace('OPTIMIZED_SQL:', '').strip()
                return jsonify({
                    'sql': clean_sql(sql_part),
                    'explanation': parts[1].strip(),
                    'mode': mode,
                    'db_type': db_type,
                    'original_sql': user_query
                })
            
        elif mode == 'explain':
            prompt = f"""Explain this {db_type} SQL query:
            {user_query}
            
            Return in EXACTLY this format:
            ORIGINAL_SQL: [the original SQL]
            EXPLANATION: [detailed explanation]"""
            
            response = model.generate_content(prompt)
            parts = re.split(r'EXPLANATION:', response.text, flags=re.IGNORECASE)
            
            if len(parts) >= 2:
                sql_part = parts[0].replace('ORIGINAL_SQL:', '').strip()
                return jsonify({
                    'sql': clean_sql(sql_part),
                    'explanation': parts[1].strip(),
                    'mode': mode,
                    'db_type': db_type
                })

        return jsonify({'error': 'Failed to process query'}), 500

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/debug')
def debug():
    """Debug endpoint to verify paths"""
    return {
        "template_directory": app.template_folder,
        "template_exists": os.path.exists(os.path.join(app.template_folder, 'AdvanceSQB.html')),
        "files_in_template": os.listdir(app.template_folder)
    }

if __name__ == '__main__':
    print(f"Template directory: {app.template_folder}")
    print(f"Files in template directory: {os.listdir(app.template_folder)}")
    app.run(debug=True, port=5006, host='0.0.0.0')
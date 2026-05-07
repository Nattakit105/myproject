from flask import Flask, request, jsonify
import subprocess
import os
import time # เพิ่มตัวจัดการเวลา

app = Flask(__name__)

# ตรวจสอบว่ามีโฟลเดอร์ uploads หรือยัง
if not os.path.exists('uploads'):
    os.makedirs('uploads')

@app.route('/scan', methods=['POST'])
def scan():
    if 'image' not in request.files:
        return jsonify({'success': False, 'message': 'No image uploaded'}), 400
    
    file = request.files['image']
    
    # 🛠️ จุดแก้ไข: ตั้งชื่อไฟล์ใหม่เป็นตัวเลข (Timestamp) เพื่อเลี่ยงภาษาไทย
    temp_filename = f"meter_{int(time.time())}.jpg"
    img_path = os.path.join('uploads', temp_filename)
    
    # บันทึกไฟล์ด้วยชื่อใหม่
    file.save(img_path)

    try:
        # เรียกใช้ไฟล์ AI ของคุณ
        # เพิ่ม stderr=subprocess.STDOUT เพื่อดึง Error จาก Python มาดูได้ถ้ามีปัญหา
        result = subprocess.check_output(['python', 'detecnum_new.py', img_path], stderr=subprocess.STDOUT)
        detected_text = result.decode('utf-8').strip()
        
        return jsonify({
            'success': True,
            'detected_number': detected_text
        })
    except subprocess.CalledProcessError as e:
        return jsonify({
            'success': False, 
            'message': 'AI Error',
            'debug': e.output.decode('utf-8')
        }), 500

if __name__ == '__main__':
    print("--- AI Server of Baan Praifa Resort is Online! ---")
    app.run(host='0.0.0.0', port=5000)
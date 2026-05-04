#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys, os, argparse, re
import cv2
import numpy as np
from PIL import Image
import pytesseract
from typing import Optional

# ==============================================================================
# 1. ฟังก์ชันค้นหาหน้าปัดมิเตอร์ (Smart ROI Detection)
# ==============================================================================
def find_meter_digits(img):
    h, w = img.shape[:2]
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # กำจัดจุดรบกวนแต่ยังรักษาขอบตัวเลขไว้ (Bilateral Filter เหมาะกับภาพที่มีแสงสะท้อน)
    blurred = cv2.bilateralFilter(gray, 9, 75, 75)
    
    # 🔥 ใช้ Morphological Closing แนวนอนเพื่อเชื่อมเลข 4-5 หลักให้เป็นแถบเดียวกัน
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (25, 5))
    thresh = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)[1]
    closed = cv2.morphologyEx(thresh, cv2.MORPH_CLOSE, kernel)

    cnts, _ = cv2.findContours(closed.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    best_roi = None
    max_score = 0

    for c in cnts:
        x, y, wb, hb = cv2.boundingRect(c)
        area = wb * hb
        aspect_ratio = wb / float(hb)

        # 🎯 กรองเฉพาะสี่เหลี่ยมแนวนอน (ช่องมิเตอร์)
        if 2.5 < aspect_ratio < 6.5 and area > (h * w * 0.01):
            # ให้คะแนนกล่องที่อยู่ช่วงกลาง-บนของภาพ (เลี่ยงโลโก้ด้านล่าง)
            pos_score = 1.0 - (abs(y - h*0.35) / h)
            total_score = area * pos_score
            
            if total_score > max_score:
                max_score = total_score
                best_roi = (x, y, wb, hb)

    if best_roi:
        x, y, wb, hb = best_roi
        # เฉือนขอบบนทิ้ง 25% เพื่อกำจัดเลขบอกหลัก (1000, 100) ที่มักจะอยู่ด้านบน
        padding = 5
        cut_top = int(hb * 0.25)
        roi = gray[y + cut_top : y + hb + padding, x - padding : x + wb + padding]
        return roi
    
    # Fallback: หากหาไม่เจอ ให้ใช้พิกัดกลางภาพเป็นแผนสำรอง
    return gray[int(h*0.25):int(h*0.45), int(w*0.25):int(w*0.75)]

# ==============================================================================
# 2. ฟังก์ชันประมวลผลตัวเลข (Character Cleaning)
# ==============================================================================
def clean_for_ocr(roi):
    if roi is None or roi.size == 0: return None
    # เร่ง Contrast ให้เลขตัดกับพื้นหลังชัดเจน
    enhanced = cv2.convertScaleAbs(roi, alpha=1.9, beta=10)
    # ทำขาว-ดำ (Otsu Threshold)
    thresh = cv2.threshold(enhanced, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)[1]
    
    # บังคับให้เป็น "ตัวเลขดำ บนพื้นขาว" เพื่อให้ Tesseract อ่านง่าย
    if (np.sum(thresh == 255) / thresh.size) < 0.5:
        thresh = cv2.bitwise_not(thresh)
        
    # ลบจุดรบกวนเล็กๆ (Noise)
    thresh = cv2.medianBlur(thresh, 3)
    return thresh

# ==============================================================================
# 3. ฟังก์ชันดึงข้อมูล (Universal Extraction)
# ==============================================================================
def extract_digits(image_path: str, tesseract_cmd: Optional[str] = None) -> str:
    if tesseract_cmd: pytesseract.pytesseract.tesseract_cmd = tesseract_cmd
    
    img = cv2.imread(image_path)
    if img is None: return "0000"
    
    roi = find_meter_digits(img)
    processed = clean_for_ocr(roi)
    
    if processed is None: return "0000"

    # บันทึกภาพลงดีบักเพื่อตรวจสอบ "ดวงตา AI"
    debug_dir = os.path.join(os.path.dirname(image_path), "..", "debug")
    os.makedirs(debug_dir, exist_ok=True)
    cv2.imwrite(os.path.join(debug_dir, "universal_eye.png"), processed)

    pil_img = Image.fromarray(processed)
    candidates = []

    # 🚀 รันหลายโหมด (6=บล็อก, 11=ตัวเลขกระจาย, 7=บรรทัดเดียว) เพื่อหาค่าที่ดีที่สุด
    for psm in [6, 11, 7, 8]:
        try:
            config = f"--psm {psm} -c tessedit_char_whitelist=0123456789"
            text = pytesseract.image_to_string(pil_img, config=config)
            
            # ลบทุกอย่างที่ไม่ใช่ตัวเลข (รวมถึงช่องว่างและเส้นแบ่งหลัก)
            num = re.sub(r'[^0-9]', '', text)
            
            # กรองเลขที่ไม่ใช่เป้าหมาย
            if len(num) >= 3 and num not in ["1000", "100", "10"]:
                candidates.append(num)
        except: continue

    if candidates:
        # เลือกค่าที่ยาวที่สุด (มักจะเป็นเลขมิเตอร์จริง)
        best_pick = max(candidates, key=len)
        
        # 🎯 ปรับแต่งผลลัพธ์ให้เป็น 4 หลัก:
        # 1. ถ้าได้ 5 หลัก (เช่น 34037) ให้ตัดเอาแค่ 4 หลักแรก (3403)
        if len(best_pick) >= 4:
            return best_pick[:4]
        
        # 2. ถ้าได้ 3 หลัก ให้เติม 0 ข้างหน้า (เช่น 276 -> 0276)
        return best_pick.zfill(4)
    
    return "0000"

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('image')
    parser.add_argument('--out')
    parser.add_argument('--tesseract')
    args = parser.parse_args()
    
    res = extract_digits(args.image, tesseract_cmd=args.tesseract)
    
    if args.out:
        with open(args.out, 'w', encoding='utf-8') as f:
            f.write(res)
    else:
        print(res)

if __name__ == '__main__':
    main()

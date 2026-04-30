# ไฟล์: reset_db.py
from database import engine
from models import Base
from sqlalchemy import MetaData

def reset_database_hard():
    print("🕵️ กำลังสแกนหาตารางทั้งหมดใน Database...")
    
    # 1. สร้างตัวอ่านข้อมูล (Metadata) เพื่อไปส่องดูตารางจริงใน DB
    meta = MetaData()
    meta.reflect(bind=engine) # สั่งให้ไปดูว่ามีตารางอะไรบ้าง (จะเจอ reviews ที่ซ่อนอยู่ด้วย)

    if meta.sorted_tables:
        print(f"   -> เจอ {len(meta.sorted_tables)} ตาราง: {[t.name for t in meta.sorted_tables]}")
    else:
        print("   -> ไม่เจอตารางใดๆ (Database ว่างเปล่า)")

    print("🗑️ กำลังล้างตารางทั้งหมดทิ้ง (รวมถึงตารางเก่าที่ตกค้าง)...")
    
    # 2. ลบทุกตารางที่เจอ (รวมถึง reviews ด้วย)
    # วิธีนี้จะลบแบบ Cascade ให้เองถ้ามีการเชื่อมโยงกัน
    meta.drop_all(bind=engine)
    
    print("🏗️ กำลังสร้างตารางใหม่ตามโค้ด models.py ล่าสุด...")
    # 3. สร้างตารางใหม่ตามโครงสร้างปัจจุบันที่เราต้องการ
    Base.metadata.create_all(bind=engine)
    
    print("✅ รีเซ็ต Database เสร็จสมบูรณ์! พร้อมใช้งาน")

if __name__ == "__main__":
    confirm = input("⚠️ ยืนยันการล้างข้อมูลทั้งหมด? (พิมพ์ y แล้วกด Enter): ")
    if confirm.lower() == 'y':
        reset_database_hard()
    else:
        print("❌ ยกเลิก")
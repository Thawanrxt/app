# ไฟล์: get_id.py
from database import SessionLocal
from models import PlantingPlan, Plot

db = SessionLocal()
# ดึงแผนการปลูกล่าสุดออกมา 1 อัน
plan = db.query(PlantingPlan).join(Plot).order_by(PlantingPlan.start_date.desc()).first()

if plan:
    print("\n" + "="*50)
    print(f"✅ พบแผนการปลูก: {plan.plot.plot_name}")
    print(f"🔑 PLAN ID (ก๊อปอันนี้ไปใส่): {plan.id}") 
    print("="*50 + "\n")
else:
    print("\n❌ ยังไม่พบข้อมูล (คุณรัน seed_data.py หรือยังครับ?)")

db.close()
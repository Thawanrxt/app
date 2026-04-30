from database import SessionLocal, engine
import models
from datetime import datetime, timedelta

# สร้างตารางถ้ายังไม่มี
models.Base.metadata.create_all(bind=engine)

def seed_data():
    db = SessionLocal()
    
    try:
        # 1. เพิ่มข้อมูลพันธุ์ข้าว (Master Data)
        jasmine_rice = models.RiceVariety(variety_name="ขาวดอกมะลิ 105", growth_duration=120)
        rice_berry = models.RiceVariety(variety_name="ไรซ์เบอร์รี่", growth_duration=130)
        db.add_all([jasmine_rice, rice_berry])
        db.commit()

        # 2. เพิ่มข้อมูลผู้ใช้ตัวอย่าง (User)
        farmer_somchai = models.User(
            username="somchai_farm", 
            password="password123", # ในระบบจริงต้องเข้ารหัส
            email="somchai@example.com",
            name="สมชาย มั่นคง",
            role="farmer"
        )
        db.add(farmer_somchai)
        db.commit()

        # 3. เพิ่มแปลงนา (Plot)
        plot_a = models.Plot(
            user_id=farmer_somchai.id, 
            plot_name="ทุ่งนาหนองใหญ่", 
            area_size=10.5,
            latitude=13.7563,
            longitude=100.5018
        )
        db.add(plot_a)
        db.commit()

        # 4. สร้างแผนการปลูก (Planting Plan)
        plan = models.PlantingPlan(
            plot_id=plot_a.id,
            variety_id=jasmine_rice.id,
            start_date=datetime.utcnow() - timedelta(days=30) # ปลูกมาแล้ว 30 วัน
        )
        db.add(plan)
        db.commit()

        # 5. บันทึกกิจกรรมทดสอบ (Activity & Details)
        # กิจกรรมใส่ปุ๋ย
        fert_event = models.ActivityEvent(plan_id=plan.id, activity_type_id=1, note="ใส่ปุ๋ยรอบแรก")
        db.add(fert_event)
        db.commit()
        
        fert_detail = models.FertilizationDetail(
            event_id=fert_event.id,
            formula="16-16-8",
            amount_kg=50.0
        )
        db.add(fert_detail)

        db.commit()
        print("✅ ข้อมูลตัวอย่างถูกบันทึกลงฐานข้อมูลเรียบร้อยแล้ว!")

    except Exception as e:
        print(f"❌ เกิดข้อผิดพลาด: {e}")
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    seed_data()
# ไฟล์: seed_data.py
# หน้าที่: สร้างข้อมูลตั้งต้น (ชาวนา, แปลง, แผน) หลังรีเซ็ตระบบ

from database import SessionLocal
from models import User, FarmerProfile, Plot, PlantingPlan, RiceVariety
from datetime import date

def seed_database():
    db = SessionLocal()
    try:
        print("🌱 กำลังสร้างข้อมูลตั้งต้น...")

        # 1. สร้าง User
        farmer = User(email="somchai@rice.com", role="FARMER")
        db.add(farmer)
        db.commit() # ได้ farmer.id

        # 2. สร้าง Profile (ตาม Model ใหม่ต้องมี)
        profile = FarmerProfile(
            user_id=farmer.id,
            full_name="ลุงสมชาย ใจดี",
            id_card_number="1234567890123",
            address="123 หมู่ 1 ต.ท่าข้าว อ.เมือง จ.ขอนแก่น"
        )
        db.add(profile)

        # 3. สร้างแปลงนา
        plot = Plot(
            user_id=farmer.id,
            code="P-001",
            plot_name="แปลงนาทุ่งทอง",
            area_rai=10,
            area_sqm=0
        )
        db.add(plot)

        # 4. สร้างพันธุ์ข้าว
        rice = RiceVariety(name="ขาวดอกมะลิ 105", grow_duration_days=120)
        db.add(rice)
        db.commit() # ได้ plot.id และ rice.id

        # 5. สร้างแผนการปลูก (สำคัญ! เอา ID นี้ไปใช้)
        plan = PlantingPlan(
            plot_id=plot.id,
            variety_id=rice.id,
            season_type="นาปี",
            start_date=date.today()
        )
        db.add(plan)
        db.commit()

        print("✅ สร้างข้อมูลสำเร็จ!")
        print("-" * 50)
        print(f"🔑 PLAN ID สำหรับทดสอบ (ก๊อปไปใช้ได้เลย):")
        print(f"{plan.id}")
        print("-" * 50)


    except Exception as e:
        print("❌ Error:", e)
    finally:
        db.close()

if __name__ == "__main__":
    seed_database()
    
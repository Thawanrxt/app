# ไฟล์: main.py (เวอร์ชันทดสอบบันทึกแบบครบเครื่อง)
from database import SessionLocal
from models import Base, User, Plot, PlantingPlan, ActivityEvent, ActivityType, FertilizationDetail, RiceVariety
from datetime import date

def test_full_system():
    db = SessionLocal()
    try:
        print("\n🚀 เริ่มทดสอบระบบแบบ Full Option...")

        # 1. สร้าง User (ชาวนา)
        farmer = User(
            email="somchai@rice.com", 
            full_name="นายสมชาย หมายปอง",
            role="FARMER"
        )
        db.add(farmer)
        db.commit() # commit เพื่อให้ได้ ID

        # 2. สร้างแปลงนา (Plot)
        plot = Plot(
            farmer_user_id=farmer.id,
            plot_name="แปลงนาทุ่งทอง",
            area_rai=15.0,
            area_square_wa=50.0  # <--- ฟิลด์ใหม่
        )
        db.add(plot)
        db.commit()

        # 3. สร้างพันธุ์ข้าว & แผนการปลูก
        rice = RiceVariety(name="กข43", growth_days_min=95)
        db.add(rice)
        db.commit()

        plan = PlantingPlan(
            plot_id=plot.id,
            variety_id=rice.id,
            season_type="นาปรัง",
            start_date=date.today()
        )
        db.add(plan)
        db.commit()

        # 4. สร้างประเภทกิจกรรม (ใส่ปุ๋ย) - ต้องมี name_th แล้วนะ
        act_type = ActivityType(code="FERTILIZE", name_th="หว่านปุ๋ย")
        db.add(act_type)
        db.commit()

        # 5. บันทึกกิจกรรม "ใส่ปุ๋ย"
        event = ActivityEvent(
            plan_id=plan.id,
            type_id=act_type.id,
            performed_at=date.today(),
            performed_by_name="นายสมชาย หมายปอง", # <--- ฟิลด์ใหม่
            issue_found="-"
        )
        db.add(event)
        db.flush() # flush เพื่อเอา event.id มาใช้ก่อน

        # 6. ใส่รายละเอียดปุ๋ย (ฟิลด์ใหม่เพียบ)
        detail = FertilizationDetail(
            activity_id=event.id,
            method="หว่านด้วยคน",           # <--- ตรงตาม UI
            fertilizer_formula="46-0-0",    # <--- ตรงตาม UI
            fertilizer_type="เคมี",
            qty_kg_per_rai=25.5
        )
        db.add(detail)
        db.commit()

        print(f"✅ บันทึกสำเร็จ! ใส่ปุ๋ยสูตร {detail.fertilizer_formula} โดย {event.performed_by_name}")

    except Exception as e:
        print("❌ Error:", e)
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    test_full_system()
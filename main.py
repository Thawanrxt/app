from database import SessionLocal
from models import User, Plot, PlantingPlan, ActivityEvent, ActivityType, FertilizationDetail, RiceVariety
from datetime import date
import uuid

def test_full_system():
    db = SessionLocal()
    try:
        print("\n🚀 เริ่มทดสอบระบบแบบ Full Option...")

        # 1. สร้าง User (ชาวนา) - อ้างอิงตาม Model User
        farmer = User(
            username="somchai_farmer", 
            password_hash="hashed_password",
            phone="0812345678",
            role="FARMER"
        )
        db.add(farmer)
        db.commit()

        # 2. สร้างแปลงนา (Plot) - อ้างอิงตาม Model Plot
        plot = Plot(
            user_id=farmer.id, # เปลี่ยนจาก farmer_user_id ให้ตรง Model
            farm_id=str(uuid.uuid4())[:8],
            plot_name="แปลงนาทุ่งทอง",
            area_rai=15,
            area_sq_wa=50 # ให้ตรงกับ area_sq_wa ใน Model
        )
        db.add(plot)
        db.commit()

        # 3. สร้างพันธุ์ข้าว & แผนการปลูก
        rice = RiceVariety(name="กข43", grow_duration_days=95)
        db.add(rice)
        db.commit()

        plan = PlantingPlan(
            plot_id=plot.id,
            rice_id=rice.id,
            season_type="นาปรัง",
            start_date=date.today()
        )
        db.add(plan)
        db.commit()

        # 4. สร้างประเภทกิจกรรม
        act_type = ActivityType(code="FERTILIZE", name_th="หว่านปุ๋ย")
        db.add(act_type)
        db.commit()

        # 5. บันทึกกิจกรรม "ใส่ปุ๋ย"
        event = ActivityEvent(
            plan_id=plan.id,
            type_id=act_type.id,
            performed_at=date.today(),
            performed_by_name="นายสมชาย หมายปอง", # ฟิลด์นี้มีใน Model แล้ว
            issue_found="-"
        )
        db.add(event)
        db.flush() 

        # 6. ใส่รายละเอียดปุ๋ย - ปรับให้ตรงกับ FertilizationDetail ใน models.py
        detail = FertilizationDetail(
            activity_id=event.id,
            fertilizer_kind="เคมี",       # เดิมคือ fertilizer_type
            fertilizer_formula="46-0-0", 
            qty_kg_per_rai=25.5
        )
        db.add(detail)
        db.commit()

        print(f"✅ บันทึกสำเร็จ! ใส่ปุ๋ยสูตร {detail.fertilizer_formula} ในแผน ID: {plan.id}")

    except Exception as e:
        print("❌ Error:", e)
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    test_full_system()
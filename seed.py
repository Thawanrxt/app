import uuid
from database import SessionLocal
import models

def seed_data():
    db = SessionLocal()
    try:
        print("🌱 กำลังหยอดข้อมูลพื้นฐานลงฐานข้อมูล...")

        # ------------------------------------------
        # 1. หยอดข้อมูลจังหวัด (Provinces)
        # ------------------------------------------
        provinces_data = [
            {"id": 54, "code": "67", "name_th": "เพชรบูรณ์", "name_en": "Phetchabun"},
            {"id": 1, "code": "10", "name_th": "กรุงเทพมหานคร", "name_en": "Bangkok"},
            {"id": 40, "code": "48", "name_th": "นครพนม", "name_en": "Nakhon Phanom"},
            {"id": 20, "code": "30", "name_th": "นครราชสีมา", "name_en": "Nakhon Ratchasima"}
        ]
        for p in provinces_data:
            if not db.query(models.Province).filter(models.Province.id == p["id"]).first():
                db.add(models.Province(**p))
        db.flush()

        # ------------------------------------------
        # 2. หยอดประเภทเกษตรกร (Farmer Types)
        # ------------------------------------------
        farmer_types = ["เกษตรกรรายย่อย", "กลุ่มเกษตรกร", "วิสาหกิจชุมชน", "เกษตรกรปราดเปรื่อง (Smart Farmer)"]
        for t_name in farmer_types:
            if not db.query(models.FarmerType).filter(models.FarmerType.name == t_name).first():
                db.add(models.FarmerType(name=t_name))
        db.flush()

        # ------------------------------------------
        # 3. หยอดประเภทกิจกรรม (Activity Types) 
        # ------------------------------------------
        activity_types = [
            {"id": 1, "code": "PREP", "name_th": "เตรียมดิน/ปลูก"},
            {"id": 2, "code": "WATER", "name_th": "จัดการน้ำ"},
            {"id": 3, "code": "FERT", "name_th": "ใส่ปุ๋ย"},
            {"id": 4, "code": "PEST", "name_th": "กำจัดศัตรูพืช"},
            {"id": 5, "code": "DIS", "name_th": "จัดการโรคพืช"},
            {"id": 6, "code": "HARV", "name_th": "เก็บเกี่ยว"},
            {"id": 7, "code": "SALE", "name_th": "จำหน่ายผลผลิต"}
        ]
        for act in activity_types:
            if not db.query(models.ActivityType).filter(models.ActivityType.id == act["id"]).first():
                db.add(models.ActivityType(**act))
        db.flush()

        # ------------------------------------------
        # 4. หยอดข้อมูลพันธุ์ข้าว (Rice Varieties)
        # ------------------------------------------
        rice_varieties = [
            {"name": "ขาวดอกมะลิ 105", "grow_duration_days": 120, "recommended_season": "นาปี"},
            {"name": "กข6", "grow_duration_days": 130, "recommended_season": "นาปี"},
            {"name": "กข43", "grow_duration_days": 95, "recommended_season": "นาปรัง/นาปี"},
            {"name": "ปทุมธานี 1", "grow_duration_days": 110, "recommended_season": "นาปรัง"}
        ]
        
        for rv in rice_varieties:
            existing_rv = db.query(models.RiceVariety).filter(models.RiceVariety.name == rv["name"]).first()
            if not existing_rv:
                new_rv = models.RiceVariety(
                    id=uuid.uuid4(),
                    name=rv["name"],
                    grow_duration_days=rv["grow_duration_days"],
                    recommended_season=rv["recommended_season"]
                )
                db.add(new_rv)
                db.flush() # เพื่อนำ id ไปใช้ทำ Template ต่อ
                
                # ------------------------------------------
                # 5. หยอดแม่แบบปฏิทินงาน (Activity Templates)
                # ------------------------------------------
                # สร้าง Template อัตโนมัติสำหรับพันธุ์ข้าวที่เพิ่งเพิ่ม
                templates = [
                    {"activity_type_id": 1, "day_after_start": 0, "description": "เริ่มปลูก/หว่านกล้า"},
                    {"activity_type_id": 3, "day_after_start": 20, "description": "ใส่ปุ๋ยครั้งที่ 1 (บำรุงต้น)"},
                    {"activity_type_id": 2, "day_after_start": 45, "description": "รักษาระดับน้ำช่วงข้าวแตกกอ"},
                    {"activity_type_id": 3, "day_after_start": 65, "description": "ใส่ปุ๋ยครั้งที่ 2 (รับท้อง)"},
                    {"activity_type_id": 6, "day_after_start": rv["grow_duration_days"], "description": "เก็บเกี่ยวผลผลิต"}
                ]
                
                for t in templates:
                    db.add(models.ActivityTemplate(
                        rice_id=new_rv.id,
                        activity_type_id=t["activity_type_id"],
                        day_after_start=t["day_after_start"],
                        description=t["description"]
                    ))

        db.commit()
        print("✅ ข้อมูลพื้นฐานถูกหยอดลงฐานข้อมูลเรียบร้อยแล้ว!")
        print("🚀 ระบบพร้อมสำหรับการสร้างแผนการปลูกอัตโนมัติ")

    except Exception as e:
        print(f"❌ เกิดข้อผิดพลาดขณะหยอดข้อมูล: {e}")
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    seed_data()
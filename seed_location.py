from database import SessionLocal
from models import Province, District, Subdistrict

def seed_locations_data():
    db = SessionLocal()
    try:
        print("🌍 กำลังสร้างข้อมูลจังหวัด/อำเภอ/ตำบล ตัวอย่าง...")

        if db.query(Province).count() > 0:
            print("⚠️ มีข้อมูลสถานที่อยู่แล้ว ข้ามการสร้าง")
            return

        # 1. จังหวัดขอนแก่น
        kk = Province(id=1, code="40", name_th="ขอนแก่น", name_en="Khon Kaen")
        db.add(kk)
        db.flush()
        
        # อำเภอเมืองขอนแก่น
        kk_muang = District(province_id=kk.id, code="4001", name_th="เมืองขอนแก่น", name_en="Mueang Khon Kaen")
        db.add(kk_muang)
        db.flush()
        
        # ตำบลในเมือง & ท่าพระ
        db.add(Subdistrict(district_id=kk_muang.id, code="400101", name_th="ในเมือง", zip_code="40000"))
        db.add(Subdistrict(district_id=kk_muang.id, code="400102", name_th="ท่าพระ", zip_code="40260"))

        # 2. กรุงเทพมหานคร
        bkk = Province(id=2, code="10", name_th="กรุงเทพมหานคร", name_en="Bangkok")
        db.add(bkk)
        db.flush()
        
        # เขตจตุจักร
        bkk_chatuchak = District(province_id=bkk.id, code="1030", name_th="จตุจักร", name_en="Chatuchak")
        db.add(bkk_chatuchak)
        db.flush()
        
        # แขวงลาดยาว
        db.add(Subdistrict(district_id=bkk_chatuchak.id, code="103001", name_th="ลาดยาว", zip_code="10900"))

        db.commit()
        print("✅ สร้างข้อมูลสถานที่ตัวอย่างเรียบร้อย!")

    except Exception as e:
        print("❌ Error:", e)
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    seed_locations_data()
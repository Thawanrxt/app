# ไฟล์: view_activities.py (ฉบับอัปเดต รองรับ ERD ใหม่)
from database import SessionLocal
from models import ActivityEvent, PlantingPlan, Plot, User

def print_header(title):
    print("\n" + "="*80)
    print(f" 📋 {title}")
    print("="*80)

def view_all_activities():
    db = SessionLocal()
    try:
        events = db.query(ActivityEvent).order_by(ActivityEvent.performed_at.desc()).all()

        if not events:
            print("\n❌ ยังไม่มีข้อมูลกิจกรรม (ลองยิง API เพิ่มข้อมูลก่อนนะครับ)")
            return

        print_header("รายงานสรุปกิจกรรมการทำนา (ERD Version)")

        for i, event in enumerate(events, 1):
            # เชื่อมโยงข้อมูล (ถ้ามี)
            plan = event.plan
            plot = plan.plot if plan else None
            plot_name = plot.plot_name if plot else "ไม่ระบุ"
            type_code = event.activity_type.code if event.activity_type else "UNKNOWN"
            type_name = event.activity_type.name_th if event.activity_type else type_code

            print(f"\n[{i}] 📅 วันที่: {event.performed_at} | 🕒 กิจกรรม: {type_name}")
            print(f"    🌾 แปลง: {plot_name} | 👤 ผู้บันทึก: {event.performed_by_name}")
            
            # --- แสดงรายละเอียดเจาะลึก ---
            
            # 1. เตรียมดิน
            if event.soil_detail:
                d = event.soil_detail
                print(f"    🚜 รายละเอียดเตรียมดิน:")
                print(f"       - เผาฟาง: {d.straw_burning} | ปรับระดับดิน: {'ใช่' if d.land_leveling else 'ไม่'}")
                print(f"       - ค่าดิน: pH={d.soil_ph or '-'}, N-P-K={d.soil_n or '-'}-{d.soil_p or '-'}-{d.soil_k or '-'}")

            # 2. จัดการน้ำ
            elif event.water_detail:
                d = event.water_detail
                print(f"    💧 รายละเอียดน้ำ:")
                print(f"       - วิธีการ: {d.method} | ระดับน้ำ: {d.water_level_cm} ซม. ({d.ref_point})")
                if d.note: print(f"       - หมายเหตุ: {d.note}")

            # 3. ใส่ปุ๋ย
            elif event.fertilizer_detail:
                d = event.fertilizer_detail
                print(f"    💊 รายละเอียดปุ๋ย:")
                print(f"       - สูตร: {d.fertilizer_formula} ({d.fertilizer_kind})")
                print(f"       - ปริมาณ: {d.qty_kg_per_rai} กก./ไร่")

            # 4. ศัตรูพืช
            elif event.pest_detail:
                d = event.pest_detail
                print(f"    🐛 ศัตรูพืช:")
                print(f"       - พบ: {d.pest_type}")
                print(f"       - สารเคมี: {d.chemical_common_name} (ใช้ {d.amount_used} ต่อน้ำ {d.water_liters} ลิตร)")

            # 5. โรคพืช
            elif event.disease_detail:
                d = event.disease_detail
                print(f"    fungi โรคพืช:")
                print(f"       - พบ: {d.disease_type}")
                print(f"       - ยาที่ใช้: {d.chemical_comm_name}")

            # 6. เก็บเกี่ยว
            elif event.harvest_detail:
                d = event.harvest_detail
                print(f"    🚜 เก็บเกี่ยว:")
                print(f"       - ช่วงเวลา: {d.harvest_start_date} ถึง {d.harvest_end_date}")
                print(f"       - ผลผลิตรวม: {d.total_yield_kg:,.2f} กก. | ความชื้น: {d.moisture_percent}%")

            # 7. ขายข้าว
            elif event.sale_detail:
                d = event.sale_detail
                print(f"    💰 การขายข้าว (ใบชั่ง: {d.ticket_no}):")
                print(f"       - โรงสี: {d.mill_name} | ทะเบียนรถ: {d.plate_no}")
                print(f"       - เวลา: {d.in_time} - {d.out_time}")
                print(f"       - น้ำหนักสุทธิ: {d.weight_net_kg:,.2f} กก. @ {d.price_per_kg} บาท")
                print(f"       - 💵 รายได้รวม: {d.total_income:,.2f} บาท")
            
            print("-" * 60)

    except Exception as e:
        print("❌ Error:", e)
    finally:
        db.close()

if __name__ == "__main__":
    view_all_activities()
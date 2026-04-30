import json
import os
from database import SessionLocal
from models import (
    Province, District, FarmerType, RiceVariety, 
    MasterSeason, Manual, ActivityType, ActivityTemplate
)

def seed_data():
    db = SessionLocal()
    print("🌱 เริ่มต้นการลงข้อมูล (โหมด Offline: อ่านไฟล์จากเครื่อง)...")

    # ==========================================
    # 1. จังหวัดและอำเภอ (อ่านจากไฟล์ JSON)
    # ==========================================
    print("   📂 1. กำลังอ่านไฟล์ provinces.json และ districts.json ...")
    
    try:
        # ตรวจสอบว่ามีไฟล์ไหม
        if not os.path.exists("provinces.json") or not os.path.exists("districts.json"):
            raise Exception("หาไฟล์ไม่เจอ! กรุณาโหลดไฟล์ JSON มาวางไว้ในโฟลเดอร์ก่อนนะครับ")

        # อ่านไฟล์
        with open("provinces.json", encoding="utf-8") as f:
            provinces = json.load(f)
        
        with open("districts.json", encoding="utf-8") as f:
            districts = json.load(f)
        
        # 1.1 ลงจังหวัด
        print(f"      - พบจังหวัดในไฟล์: {len(provinces)} แห่ง")
        for p in provinces:
            pid = p.get('id')
            if not db.query(Province).filter_by(id=pid).first():
                db.add(Province(
                    id=pid,
                    code=str(pid),
                    name_th=p.get('name_th'),
                    name_en=p.get('name_en')
                ))
        db.flush() 
        
        # 1.2 ลงอำเภอ
        print(f"      - พบอำเภอในไฟล์: {len(districts)} แห่ง")
        for d in districts:
            did = d.get('id')
            if not db.query(District).filter_by(id=did).first():
                db.add(District(
                    id=did,
                    province_id=d.get('province_id'),
                    code=str(did),
                    name_th=d.get('name_th'),
                    name_en=d.get('name_en')
                ))
        print("      ✅ นำเข้าข้อมูลภูมิศาสตร์สำเร็จ!")

    except Exception as e:
        print(f"      ❌ เกิดข้อผิดพลาด: {e}")
        print("      ⚠️ ระบบจะข้ามส่วนนี้ไป (คุณจะไม่มีข้อมูลจังหวัด)")

    # ==========================================
    # 2. ข้อมูลอื่นๆ (เหมือนเดิม)
    # ==========================================
    print("   ⏳ 2. ลงข้อมูลพื้นฐานอื่นๆ (ประเภท, ฤดูกาล, พันธุ์ข้าว)...")
    
    # ประเภทเกษตรกร
    for name in ["เจ้าของแปลง", "ผู้เช่า", "ทำฟรี/ที่ดินญาติ"]:
        if not db.query(FarmerType).filter_by(name=name).first():
            db.add(FarmerType(name=name))

    # ฤดูกาล
    seasons = [
        {"name": "นาปี", "desc": "ปลูกช่วงฤดูฝน (พ.ค. - ต.ค.)"},
        {"name": "นาปรัง 1", "desc": "ปลูกช่วงหน้าแล้งรอบที่ 1"},
        {"name": "นาปรัง 2", "desc": "ปลูกช่วงหน้าแล้งรอบที่ 2"},
        {"name": "นาปรัง 3", "desc": "ปลูกช่วงหน้าแล้งรอบที่ 3"}
    ]
    for s in seasons:
        if not db.query(MasterSeason).filter_by(name=s["name"]).first():
            db.add(MasterSeason(name=s["name"], description=s["desc"]))

    # พันธุ์ข้าว
    rice_varieties = [
        {"name": "ขาวดอกมะลิ 105", "days": 120, "season": "นาปี", "img": "https://img2.pic.in.th/pic/rice-jasmine.jpg"},
        {"name": "กข 43", "days": 95, "season": "นาปรัง/นาปี", "img": "https://img5.pic.in.th/file/secure-sv1/rice-rd43.jpg"},
        {"name": "ปทุมธานี 1", "days": 110, "season": "นาปรัง", "img": "https://img2.pic.in.th/pic/rice-pathum.jpg"},
        {"name": "กข 6 (ข้าวเหนียว)", "days": 115, "season": "นาปี", "img": "https://img5.pic.in.th/file/secure-sv1/rice-sticky.jpg"},
        {"name": "สันป่าตอง 1", "days": 130, "season": "นาปี", "img": "https://img2.pic.in.th/pic/rice-sanpatong.jpg"}
    ]
    for r in rice_varieties:
        if not db.query(RiceVariety).filter_by(name=r["name"]).first():
            db.add(RiceVariety(
                name=r["name"], grow_duration_days=r["days"], 
                recommended_season=r["season"], image_url=r["img"]
            ))

    # คู่มือ
    manuals = [
        {"name": "พันธุ์ข้าว", "icon": "https://cdn-icons-png.flaticon.com/512/2821/2821867.png", "pdf": "https://example.com/manuals/rice.pdf"},
        {"name": "การจัดการดิน", "icon": "https://cdn-icons-png.flaticon.com/512/3260/3260783.png", "pdf": "https://example.com/manuals/soil.pdf"},
        {"name": "การใช้น้ำ", "icon": "https://cdn-icons-png.flaticon.com/512/3105/3105807.png", "pdf": "https://example.com/manuals/water.pdf"},
        {"name": "ศัตรูพืช", "icon": "https://cdn-icons-png.flaticon.com/512/2675/2675868.png", "pdf": "https://example.com/manuals/pest.pdf"},
        {"name": "โรคข้าว", "icon": "https://cdn-icons-png.flaticon.com/512/2823/2823023.png", "pdf": "https://example.com/manuals/disease.pdf"},
        {"name": "เก็บเกี่ยว", "icon": "https://cdn-icons-png.flaticon.com/512/2270/2270276.png", "pdf": "https://example.com/manuals/harvest.pdf"}
    ]
    for m in manuals:
        if not db.query(Manual).filter_by(name=m["name"]).first():
            db.add(Manual(name=m["name"], icon_url=m["icon"], pdf_url=m["pdf"]))

    # กิจกรรม
    activities = [
        {"code": "SOIL", "name": "การเตรียมดิน"},
        {"code": "WATER", "name": "การจัดการน้ำ"},
        {"code": "FERT", "name": "การใส่ปุ๋ย"},
        {"code": "PEST", "name": "การจัดการศัตรูพืช"},
        {"code": "DISEASE", "name": "การจัดการโรคพืช"},
        {"code": "HARVEST", "name": "การเก็บเกี่ยว"},
        {"code": "SALE", "name": "การขายผลผลิต"}
    ]
    for act in activities:
        if not db.query(ActivityType).filter_by(code=act["code"]).first():
            db.add(ActivityType(code=act["code"], name_th=act["name"]))
    db.flush()

    # Template ปฏิทิน
    jasmine = db.query(RiceVariety).filter_by(name="ขาวดอกมะลิ 105").first()
    act_soil = db.query(ActivityType).filter_by(code="SOIL").first()
    act_fert = db.query(ActivityType).filter_by(code="FERT").first()
    act_harvest = db.query(ActivityType).filter_by(code="HARVEST").first()

    if jasmine and act_soil and act_fert:
        templates = [
            {"rice": jasmine, "act": act_soil, "day": 0, "desc": "ไถเตรียมดินและหมักฟาง"},
            {"rice": jasmine, "act": act_fert, "day": 20, "desc": "ใส่ปุ๋ยสูตร 46-0-0 (เร่งต้น)"},
            {"rice": jasmine, "act": act_fert, "day": 45, "desc": "ใส่ปุ๋ยสูตร 16-20-0 (รับรวง)"},
            {"rice": jasmine, "act": act_harvest, "day": 120, "desc": "เก็บเกี่ยวผลผลิต"}
        ]
        for t in templates:
            exists = db.query(ActivityTemplate).filter_by(
                rice_id=t["rice"].id, activity_type_id=t["act"].id, day_after_start=t["day"]
            ).first()
            if not exists:
                db.add(ActivityTemplate(
                    rice_id=t["rice"].id, activity_type_id=t["act"].id, day_after_start=t["day"], description=t["desc"]
                ))

    db.commit()
    db.close()
    print("🎉 Seed Complete! ลงข้อมูลสำเร็จแน่นอนครับ")

if __name__ == "__main__":
    seed_data()
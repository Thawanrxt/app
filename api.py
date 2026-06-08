
from fastapi import FastAPI, Depends, HTTPException, status, UploadFile, File, Form
from sqlalchemy.orm import Session
from typing import List, Optional
from fastapi.middleware.cors import CORSMiddleware
from datetime import date, timedelta, datetime
from pydantic import BaseModel, ConfigDict
import secrets
import hashlib
import bcrypt # เก็บไว้เผื่อใช้ในอนาคต
from sqlalchemy import Column, String, Boolean, Text, DateTime, ForeignKey, Integer, Float, JSON
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.sql import func
from database import Base
from sqlalchemy import or_
import uuid # สำหรับใช้ uuid.uuid4()
from uuid import UUID # ชื่อเล่นสำหรับ Pydantic
from sqlalchemy import UUID as SqlUUID # ชื่อเล่นสำหรับ SQLAlchemy Column
import os
import shutil
from fastapi import UploadFile, File
# นำเข้าโมเดลและฐานข้อมูล
import models
import schemas
from database import engine, get_db
from schemas import AppSettingsResponse, AppSettingsUpdate
from fastapi.staticfiles import StaticFiles
from pydantic import BaseModel
from datetime import date
from uuid import UUID
from typing import Optional

# 2. สร้างตัวแปร app (ต้องอยู่ก่อนการใช้ app.mount)
app = FastAPI(
    title="Smart Rice Farming API",
    version="1.0.3"
)

UPLOAD_DIR = "static/uploads"
if not os.path.exists(UPLOAD_DIR):
    os.makedirs(UPLOAD_DIR, exist_ok=True)

app.mount("/static", StaticFiles(directory="static"), name="static")

def verify_password(plain_password: str, hashed_in_db: str, plain_in_db: str = None):
    # 1. เช็คกับรหัสสั้น (Plain) ก่อน
    if plain_in_db and plain_password == plain_in_db:
        return True
    
    # 2. เช็คกรณีรหัสสั้นไปอยู่ในช่อง Hash
    if hashed_in_db and plain_password == hashed_in_db:
        return True

    # 3. เช็คด้วย Bcrypt (รหัสยึกยือ)
    try:
        if hashed_in_db and isinstance(hashed_in_db, str) and hashed_in_db.startswith('$'):
            return bcrypt.checkpw(
                plain_password.encode('utf-8'), 
                hashed_in_db.encode('utf-8')
            )
    except Exception as e:
        print(f"❌ Bcrypt Error: {e}")
        return False
            
def get_password_hash(password):
    """ฟังก์ชันสร้างรหัส (ถ้าจะใช้ Bcrypt ในอนาคต)"""
    pwd_bytes = password.encode('utf-8')
    salt = bcrypt.gensalt()
    return bcrypt.hashpw(pwd_bytes, salt).decode('utf-8')

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

models.Base.metadata.create_all(bind=engine)

# ==========================================
# --- 2. SCHEMAS (แม่พิมพ์ข้อมูล) ---
# ==========================================

class UserLogin(BaseModel):
    username: str
    password: str

class UserResponse(BaseModel):
    id: UUID
    username: str
    role: str
    model_config = ConfigDict(
        from_attributes=True,
        arbitrary_types_allowed=True
    )
# 🌟 เพิ่ม Schema สำหรับรับข้อมูล
class SoilPrepRequest(BaseModel):
    plan_id: UUID
    type_id: int = 1
    performed_by_name: str
    sequence_no: int
    performed_at: date
    straw_burning: Optional[str] = None
    soil_ph: Optional[float] = 0.0
    organic_matter: Optional[str] = None
    issue_found: Optional[str] = None
    model_config = ConfigDict(
        arbitrary_types_allowed=True
    )
# 🌟 เพิ่ม Schema สำหรับรับข้อมูลการจัดการน้ำ
class WaterManagementRequest(BaseModel):
    plan_id: UUID
    type_id: int = 2 # 🌟 กำหนด ID มาตรฐานสำหรับการจัดการน้ำ
    performed_by_name: str
    sequence_no: int
    performed_at: date
    water_level: Optional[str] = None
    water_detail: Optional[str] = None
    issue_found: Optional[str] = None

# 🌟 เพิ่มต่อจาก WaterManagementRequest
class FertilizerRequest(BaseModel):
    plan_id: UUID
    type_id: int = 3 # 🌟 กำหนด ID 3 สำหรับกิจกรรมหว่านปุ๋ย
    performed_by_name: str
    sequence_no: int
    performed_at: date
    fertilizer_type: Optional[str] = None
    fertilizer_amount: Optional[str] = None
    issue_found: Optional[str] = None

class PestControlRequest(BaseModel):
    plan_id: UUID
    type_id: int  
    performed_by_name: str
    sequence_no: int
    performed_at: date
    pest_type: Optional[str] = None
    chemical_name: Optional[str] = None
    chemical_amount: Optional[str] = None
    ratio_per_water: Optional[float] = None
    issue_found: Optional[str] = None

# วางไว้ประมาณบรรทัดที่ 50-100 ของไฟล์ api.py
class HarvestSchema(BaseModel):
    plan_id: UUID
    plot_id: UUID
    operator_name: Optional[str] = None      # ผู้ทำกิจกรรม
    activity_date: date                      # วันที่ทำกิจกรรม
    start_harvest_date: date                 # วันที่เริ่มการเก็บเกี่ยว
    end_harvest_date: date                   # วันที่สิ้นสุดการเก็บเกี่ยว
    harvest_amount: float                    # ผลการผลิต (กิโลกรัม)
    moisture_content: Optional[float] = None # ความชื้น (%)
    problems_found: Optional[str] = None     # ปัญหาที่พบ

    class Config:
        from_attributes = True

class SaleRequest(BaseModel):
    plan_id: UUID
    performed_by_name: str
    performed_at: date
    # 🌟 เพิ่มฟิลด์ใหม่ตามหน้า Frontend
    sale_time: Optional[str] = None  # สำหรับเก็บเวลา
    mill_name: Optional[str] = None
    product_name: Optional[str] = None
    car_details: Optional[str] = None # สำหรับเก็บ คันที่/เลขที่/ทะเบียน
    total_yield_kg: float             # ผลการผลิต
    net_weight_kg: float              # น้ำหนักสินค้าสุทธิ
    total_income: float
    price_per_kg: float
    issue_found: Optional[str] = None

class DashboardWorkItem(Base):
    __tablename__ = "dashboard_work_items"
    
    id = Column(SqlUUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(SqlUUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"), nullable=True)
    plot_id = Column(SqlUUID(as_uuid=True), ForeignKey("plots.id", ondelete="SET NULL"), nullable=True)
    activity_event_id = Column(SqlUUID(as_uuid=True), ForeignKey("activity_events.id", ondelete="SET NULL"), nullable=True)
    
    task_title = Column(String(255), nullable=False)
    issue_category = Column(String(100))
    status = Column(String(50), default="pending_review")
    priority = Column(String(20), default="medium")
    response_required = Column(Boolean, default=False)
    latest_note = Column(Text)
    meta = Column(JSONB)
    
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
    resolved_at = Column(DateTime(timezone=True))

class AdminActivityLog(Base):
    __tablename__ = "admin_activity_logs"
    
    id = Column(SqlUUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    admin_user_id = Column(SqlUUID(as_uuid=True), ForeignKey("users.id"))
    action = Column(String(100), nullable=False)
    target_type = Column(String(50))
    target_id = Column(SqlUUID(as_uuid=True))
    description = Column(Text)
    meta = Column(JSONB)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

# ==========================================
# --- 3. API ROUTES ---
# ==========================================

@app.post("/login", response_model=schemas.TokenResponse)
def login(data: schemas.LoginRequest, db: Session = Depends(get_db)):
    # ค้นหา User จากชื่อผู้ใช้ หรือ รหัสเกษตรกร (farm_id)
    user = db.query(models.User).join(models.Plot, isouter=True).filter(
        or_(
            models.User.username == data.username,
            models.Plot.farm_id == data.username
        )
    ).first()
    
    if not user:
        raise HTTPException(status_code=401, detail="ไม่พบชื่อผู้ใช้งานหรือรหัสเกษตรกร")

    # 🚩 ใช้ฟังก์ชัน verify_password ที่เราปรับปรุงใหม่
    # มันจะไปเช็คเองว่า user.password_hash ใน DB เป็นรหัสตรงๆ หรือรหัส Hash
    is_match = verify_password(
        data.password, 
        user.password_hash, 
        getattr(user, "password_plain", None) # ส่งตัวที่ 3 เข้าไป
    )

    if not is_match:
            print(f"❌ Password mismatch for user: {user.username}") # เอาไว้ดูใน terminal
            raise HTTPException(status_code=401, detail="รหัสผ่านไม่ถูกต้อง")

    # สร้าง Session Token
    plain_token = secrets.token_hex(40)
    token_hashed = hashlib.sha256(plain_token.encode()).hexdigest()
    expiration = datetime.utcnow() + timedelta(days=30)

    new_token = models.ApiAccessToken(
        user_id=user.id,
        name=getattr(data, "name", "Web Session"),
        device_id=getattr(data, "device_id", None),
        platform=getattr(data, "platform", "web"),
        token_hash=token_hashed,
        expires_at=expiration
    )
    
    db.add(new_token)
    db.commit()

    return {
        "access_token": plain_token,
        "token_type": "Bearer",
        "user_id": str(user.id), # ✅ เพิ่มบรรทัดนี้ (ต้องแปลงเป็น string ด้วย str())
        "username": user.username
    }
# 1. เพิ่ม Schema สำหรับรับข้อมูลสร้างแปลงและแผน (Integrated Request)
class CreatePlotAndPlanRequest(BaseModel):
    user_id: UUID
    plot_name: str
    season_type: str
    planting_type: str
    rice_id: UUID
    start_date: date
    expected_harvest_date: Optional[date] = None
    # 🚩 ปรับให้เป็น Optional หรือใส่ Default เป็น 0 เพื่อรับค่าจาก parseInt(..., 10) || 0
    area_rai: int = 0 
    area_ngan: int = 0
    area_sq_wa: int = 0
    area_sq_meter: int = 0
    latitude: Optional[float] = None
    longitude: Optional[float] = None

class CreatePlanForExistingPlotRequest(BaseModel):
    plot_id: UUID
    season_type: str
    planting_type: str
    rice_id: UUID
    start_date: date
    expected_harvest_date: Optional[date] = None

@app.post("/plans/for-existing-plot", status_code=status.HTTP_201_CREATED)
def create_plan_for_existing_plot(data: CreatePlanForExistingPlotRequest, db: Session = Depends(get_db)):
    plot = db.query(models.Plot).filter(models.Plot.id == data.plot_id).first()
    if not plot:
        raise HTTPException(status_code=404, detail="ไม่พบแปลงนา")

    variety = db.query(models.RiceVariety).filter(models.RiceVariety.id == data.rice_id).first()
    if not variety:
        raise HTTPException(status_code=404, detail="ไม่พบพันธุ์ข้าวที่เลือก")

    expected_harvest = data.expected_harvest_date
    if not expected_harvest:
        expected_harvest = data.start_date + timedelta(days=variety.grow_duration_days)

    new_plan = models.PlantingPlan(
        plot_id=data.plot_id,
        rice_id=data.rice_id,
        season_type=data.season_type,
        planting_type=data.planting_type,
        start_date=data.start_date,
        expected_harvest_date=expected_harvest,
        status="ACTIVE"
    )
    try:
        db.add(new_plan)
        db.commit()
        db.refresh(new_plan)
        return {"message": "สร้างแผนการปลูกสำเร็จ!", "plan_id": str(new_plan.id), "plot_id": str(data.plot_id)}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# 2. เพิ่ม API Route สำหรับสร้างข้อมูลแบบรวดเดียว
@app.post("/plans/integrated", status_code=status.HTTP_201_CREATED)
def create_plot_and_plan(data: CreatePlotAndPlanRequest, db: Session = Depends(get_db)):
    try:
        variety = db.query(models.RiceVariety).filter(models.RiceVariety.id == data.rice_id).first()
        if not variety:
            raise HTTPException(status_code=404, detail="ไม่พบข้อมูลพันธุ์ข้าวที่เลือก")

        new_plot = models.Plot(
            user_id=data.user_id,
            farm_id=f"FARM-{uuid.uuid4().hex[:6].upper()}", 
            plot_name=data.plot_name, 
            latitude=data.latitude,
            longitude=data.longitude,
            area_rai=data.area_rai,
            area_ngan=data.area_ngan,
            area_sq_wa=data.area_sq_wa,
            area_sq_meter=data.area_sq_meter,
            status="ACTIVE"
        )
        db.add(new_plot)
        db.flush() # ดึง ID มาให้ new_plan

        expected_harvest = data.expected_harvest_date
        if not expected_harvest:
            expected_harvest = data.start_date + timedelta(days=variety.grow_duration_days)

        new_plan = models.PlantingPlan(
            plot_id=new_plot.id,
            rice_id=data.rice_id,
            season_type=data.season_type,
            planting_type=data.planting_type, 
            start_date=data.start_date,
            expected_harvest_date=expected_harvest,
            status="ACTIVE"
        )
        db.add(new_plan)
        
        db.commit() # 🚩 ยืนยันการบันทึก
        db.refresh(new_plot) # 🚩 อัปเดตค่าจาก DB กลับมาที่ Python
        db.refresh(new_plan)

        print(f"--- บันทึกสำเร็จ: Plot ID {new_plot.id} ---") # เช็คใน Terminal

        return {
            "message": "สร้างแปลงนาและแผนการปลูกสำเร็จ!",
            "plot_id": str(new_plot.id),
            "plan_id": str(new_plan.id),
            "plot_name": new_plot.plot_name # ส่งกลับไปเช็คที่หน้าบ้านด้วย
        }

    except Exception as e:
        db.rollback() # 🚩 ถ้าพังให้ถอยกลับ ป้องกันข้อมูลค้าง
        print(f"เกิดข้อผิดพลาด: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# --- 🚀 บันทึกกิจกรรม (Activities) ---

@app.post("/activities/harvest", status_code=status.HTTP_201_CREATED)
def save_harvest_activity(data: HarvestSchema, db: Session = Depends(get_db)):
    """บันทึกการเก็บเกี่ยว (กำหนด ID เป็น 5)"""
    new_event = models.ActivityEvent(
        plan_id=data.plan_id, type_id=6, performed_by_name=data.operator_name,
        performed_at=data.performed_at, issue_found=data.issue_found, status="DONE"
    )
    db.add(new_event); db.commit()
    return {"message": "บันทึกการเก็บเกี่ยวสำเร็จ"}

# ==========================================
# 11. คำนวณความคืบหน้า (Progress Bar อัปเดตใหม่ 🌟)
# ==========================================
@app.get("/tracking/plan/{plan_id}/progress")
def get_plan_progress(plan_id: UUID, db: Session = Depends(get_db)):
    """คำนวณหลอดสีเขียวให้รองรับทุกกิจกรรมรวมถึงการขายข้าว"""
    events = db.query(models.ActivityEvent).filter(
        models.ActivityEvent.plan_id == plan_id, models.ActivityEvent.status == "DONE"
    ).all()
    
    done_ids = {event.type_id for event in events}
    
    steps = [
    {"id": 1, "label": "เลือกพันธุ์ข้าว", "status": "completed", "percent": 100, "badge": "เสร็จสิ้น"},
    {"id": 2, "label": "เตรียมดิน", "status": "completed" if 1 in done_ids else "pending", "percent": 100 if 1 in done_ids else 0, "badge": "เสร็จสิ้น" if 1 in done_ids else "รอดำเนินการ"},
    {"id": 3, "label": "การจัดการน้ำ", "status": "completed" if 2 in done_ids else "pending", "percent": 100 if 2 in done_ids else 0, "badge": "เสร็จสิ้น" if 2 in done_ids else "รอดำเนินการ"},
    {"id": 4, "label": "ใส่ปุ๋ย", "status": "completed" if 3 in done_ids else "pending", "percent": 100 if 3 in done_ids else 0, "badge": "เสร็จสิ้น" if 3 in done_ids else "รอดำเนินการ"},
    {"id": 5, "label": "กำจัดศัตรูพืช", "status": "completed" if 4 in done_ids else "pending", "percent": 100 if 4 in done_ids else 0, "badge": "เสร็จสิ้น" if 4 in done_ids else "รอดำเนินการ"},
    {"id": 6, "label": "โรคพืช", "status": "completed" if 5 in done_ids else "pending", "percent": 100 if 5 in done_ids else 0, "badge": "เสร็จสิ้น" if 5 in done_ids else "รอดำเนินการ"},
    {"id": 7, "label": "เก็บเกี่ยว", "status": "completed" if 6 in done_ids else "pending", "percent": 100 if 6 in done_ids else 0, "badge": "เสร็จสิ้น" if 6 in done_ids else "รอดำเนินการ"},
    {"id": 8, "label": "การขายผลผลิต", "status": "completed" if 7 in done_ids else "pending", "percent": 100 if 7 in done_ids else 0, "badge": "เสร็จสิ้น" if 7 in done_ids else "รอดำเนินการ"},
]
    
    for i in range(1, len(steps)):
        if steps[i]["status"] == "pending" and steps[i-1]["status"] == "completed":
            steps[i]["status"] = "in-progress"; steps[i]["percent"] = 40; steps[i]["badge"] = "อยู่ระหว่างการทำ"; break
            
    return steps

# ==========================================
# 13. ดึงประวัติกิจกรรม (Timeline)
# ==========================================
@app.get("/tracking/plan/{plan_id}/history")
def get_plan_history(plan_id: UUID, db: Session = Depends(get_db)):
    history = db.query(models.ActivityEvent, models.ActivityType.name_th).join(
        models.ActivityType, models.ActivityEvent.type_id == models.ActivityType.id
    ).filter(
        models.ActivityEvent.plan_id == plan_id, 
        models.ActivityEvent.status == "DONE"
    ).order_by(models.ActivityEvent.performed_at.desc()).all()

    return [{
        "id": str(e.id), 
        "type_id": e.type_id,  # 🚩 เพิ่มบรรทัดนี้เข้าไปครับ!
        "activity_name": n, 
        "performed_at": e.performed_at.strftime("%d %b %Y"), 
        "operator": e.performed_by_name or "ไม่ระบุ", 
        "issue": e.issue_found or "-"
    } for e, n in history]

@app.get("/tracking/active-plans/{user_id}")
def get_active_plans(user_id: UUID, db: Session = Depends(get_db)):
    # Query 1: plot ที่มี active plan
    with_plans = db.query(models.PlantingPlan, models.Plot).join(
        models.Plot, models.PlantingPlan.plot_id == models.Plot.id
    ).filter(
        models.Plot.user_id == user_id,
        models.PlantingPlan.status == "ACTIVE",
        models.Plot.status == "ACTIVE"
    ).all()

    result = []
    plan_plot_ids = set()

    for plan, plot in with_plans:
        plan_plot_ids.add(str(plot.id))
        result.append({
            "id": str(plan.id),
            "plot_id": str(plot.id),
            "plan_id": str(plan.id),
            "has_plan": True,
            "farm_id": plot.farm_id,
            "plot_name": plot.plot_name or "ไม่ระบุชื่อแปลง",
            "area_rai": plot.area_rai,
            "area_ngan": plot.area_ngan,
            "area_sq_wa": plot.area_sq_wa,
            "area_sq_meter": plot.area_sq_meter,
            "location": plot.address or "ประเทศไทย",
            "image": "/rice1.jpg"
        })

    # Query 2: plot ที่ admin สร้างแต่ยังไม่มีแผน
    all_plots = db.query(models.Plot).filter(
        models.Plot.user_id == user_id,
        models.Plot.status == "ACTIVE"
    ).all()

    for plot in all_plots:
        if str(plot.id) not in plan_plot_ids:
            result.append({
                "id": str(plot.id),
                "plot_id": str(plot.id),
                "plan_id": None,
                "has_plan": False,
                "farm_id": plot.farm_id,
                "plot_name": plot.plot_name or "ไม่ระบุชื่อแปลง",
                "area_rai": plot.area_rai,
                "area_ngan": plot.area_ngan,
                "area_sq_wa": plot.area_sq_wa,
                "area_sq_meter": plot.area_sq_meter,
                "location": plot.address or "ประเทศไทย",
                "image": "/rice1.jpg"
            })

    return result

@app.get("/plots/map/{user_id}")
def get_user_plots_for_map(user_id: UUID, db: Session = Depends(get_db)):
    """คืนข้อมูลแปลงทั้งหมดของ user พร้อมพิกัดและข้อมูลแผนปลูก (สำหรับหน้าแผนที่)"""
    plots = db.query(models.Plot).filter(
        models.Plot.user_id == user_id,
        models.Plot.status == "ACTIVE"
    ).all()

    result = []
    for plot in plots:
        lat = float(plot.latitude or plot.lat or 0)
        lng = float(plot.longitude or plot.lon or 0)

        # ดึงแผนปลูก ACTIVE ล่าสุด
        active_plan = db.query(models.PlantingPlan).filter(
            models.PlantingPlan.plot_id == plot.id,
            models.PlantingPlan.status == "ACTIVE"
        ).order_by(models.PlantingPlan.start_date.desc()).first()

        rice_name = None
        start_date = None
        harvest_date = None
        season_type = None

        if active_plan:
            rice = db.query(models.RiceVariety).filter(
                models.RiceVariety.id == active_plan.rice_id
            ).first()
            rice_name = rice.name if rice else None
            start_date = active_plan.start_date.strftime("%d/%m/%Y") if active_plan.start_date else None
            harvest_date = active_plan.expected_harvest_date.strftime("%d/%m/%Y") if active_plan.expected_harvest_date else None
            season_type = active_plan.season_type

        result.append({
            "plot_id": str(plot.id),
            "farm_id": plot.farm_id,
            "plot_name": plot.plot_name or "ไม่ระบุชื่อ",
            "lat": lat,
            "lng": lng,
            "has_location": lat != 0 and lng != 0,
            "area_rai": plot.area_rai or 0,
            "area_ngan": plot.area_ngan or 0,
            "area_sq_wa": plot.area_sq_wa or 0,
            "has_plan": active_plan is not None,
            "plan_id": str(active_plan.id) if active_plan else None,
            "rice_name": rice_name,
            "season_type": season_type,
            "start_date": start_date,
            "harvest_date": harvest_date,
        })

    return result

@app.get("/master/rice-varieties")
def get_rice_varieties(db: Session = Depends(get_db)):
    return db.query(models.RiceVariety).all()
# ==========================================
# 12. ลบแผนการปลูก (Soft Delete)
# ==========================================
@app.delete("/tracking/plan/{plan_id}")
def delete_plan(plan_id: UUID, db: Session = Depends(get_db)):
    """API สำหรับยกเลิก/ลบแปลงนา (เปลี่ยนสถานะเป็น DELETED)"""
    
    # 1. ค้นหาแผนการปลูกที่ต้องการลบ
    plan = db.query(models.PlantingPlan).filter(models.PlantingPlan.id == plan_id).first()
    if not plan:
        raise HTTPException(status_code=404, detail="ไม่พบแผนการปลูกนี้ในระบบ")
        
    # 2. เปลี่ยนสถานะแผนเป็น DELETED เพื่อซ่อนจากการแสดงผล
    plan.status = "DELETED"
    
    # 3. ตามไปเปลี่ยนสถานะแปลงนา (Plot) ที่ผูกกับแผนนี้ให้เป็น DELETED ด้วย
    plot = db.query(models.Plot).filter(models.Plot.id == plan.plot_id).first()
    if plot:
        plot.status = "DELETED"
        
    db.commit() # บันทึกการเปลี่ยนแปลงลงฐานข้อมูล
    return {"message": "ลบแปลงนาและแผนการปลูกสำเร็จเรียบร้อย"}
@app.get("/tracking/upcoming-activities/{user_id}")
def get_upcoming_activities(user_id: UUID, db: Session = Depends(get_db)):
    today = date.today()
    active_plans = db.query(models.PlantingPlan).join(models.Plot).filter(
        models.Plot.user_id == user_id,
        models.PlantingPlan.status == "ACTIVE"
    ).all()

    upcoming = []
    for plan in active_plans:
        standards = db.query(models.ActivityStandard).filter(
            models.ActivityStandard.rice_variety_id == plan.rice_id
        ).order_by(models.ActivityStandard.days_after_planting.asc()).all()

        for std in standards:
            target_date = plan.start_date + timedelta(days=std.days_after_planting)
            already_done = db.query(models.ActivityEvent).filter(
                models.ActivityEvent.plan_id == plan.id,
                models.ActivityEvent.type_id == std.activity_type_id,
                models.ActivityEvent.status == "DONE"
            ).first()

            if not already_done:
                upcoming.append({
                    "plan_id": str(plan.id),
                    "plot_name": plan.plot.plot_name,
                    "activity_name": std.description or "กิจกรรมตามกำหนดการ",
                    "activity_type_id": std.activity_type_id, 
                    "due_date": target_date.strftime("%d/%m/%Y"),
                    "days_left": (target_date - today).days
                })
                break
        upcoming.sort(key=lambda x: x['days_left'])
    return upcoming

# 📍 แก้ไขในฟังก์ชัน get_notifications (บรรทัดประมาณ 400+)
@app.get("/notifications/{user_id}")
def get_notifications(user_id: UUID, db: Session = Depends(get_db)):
    today = date.today()
    active_plans = db.query(models.PlantingPlan).join(models.Plot).filter(
        models.Plot.user_id == user_id,
        models.PlantingPlan.status == "ACTIVE"
    ).all()

    notifications = []

    for plan in active_plans:
        standards = db.query(models.ActivityStandard).filter(
            models.ActivityStandard.rice_variety_id == plan.rice_id
        ).order_by(models.ActivityStandard.days_after_planting.asc()).all()

        for std in standards:
            target_date = plan.start_date + timedelta(days=std.days_after_planting)

            already_done = db.query(models.ActivityEvent).filter(
                models.ActivityEvent.plan_id == plan.id,
                models.ActivityEvent.type_id == std.activity_type_id,
                models.ActivityEvent.status == "DONE"
            ).first()

            if already_done:
                continue

            days_diff = (target_date - today).days

            # ไม่แสดงกิจกรรมที่ยังเหลือเวลามากกว่า 7 วัน
            if days_diff > 7:
                break

            if days_diff <= -7:
                noti_type = "urgent"
                title = f"🚨 ลืมบันทึก! เลยกำหนดมา {abs(days_diff)} วันแล้ว"
            elif days_diff < 0:
                noti_type = "warning"
                title = f"⚠️ เลยกำหนด {abs(days_diff)} วัน — รีบบันทึกด้วยนะ"
            elif days_diff == 0:
                noti_type = "urgent"
                title = "🔔 ถึงกำหนดวันนี้ — อย่าลืมบันทึก!"
            elif days_diff <= 3:
                noti_type = "warning"
                title = f"📅 อีก {days_diff} วันถึงกำหนด — เตรียมตัวด้วย"
            else:
                noti_type = "info"
                title = f"📅 อีก {days_diff} วันถึงกำหนด"

            notifications.append({
                "id": str(uuid.uuid4()),
                "plot_id": str(plan.plot_id),
                "target_type": str(std.activity_type_id),
                "title": title,
                "message": f"{std.description} · แปลง {plan.plot.plot_name} · กำหนด {target_date.strftime('%d/%m/%Y')}",
                "type": noti_type,
                "due_date": target_date.strftime("%d/%m/%Y"),
                "days_left": days_diff,
                "is_read": False,
            })
            break

    # 🚩 ย้ายออกมาไว้ข้างนอกลูป (ให้ตรงกับระดับแนวตั้งของ 'for plan in active_plans')
    db_notifs = db.query(models.Notification).filter(
        models.Notification.user_id == user_id
    ).order_by(models.Notification.created_at.desc()).all()

    for n in db_notifs:
        notifications.append({
            "id": str(n.id),
            "title": n.title,
            "message": n.message,
            "type": "info",
            "is_read": n.is_read,
            "created_at": n.created_at.strftime("%d/%m/%Y %H:%M") if n.created_at else ""
        })

    # ดึงคำแนะนำจาก Admin (TrackingAdvice) ผ่าน activity_event_id ของ user
    user_activity_ids = [
        str(e.id) for e in db.query(models.ActivityEvent.id)
        .join(models.PlantingPlan, models.ActivityEvent.plan_id == models.PlantingPlan.id)
        .join(models.Plot, models.PlantingPlan.plot_id == models.Plot.id)
        .filter(models.Plot.user_id == user_id)
        .all()
    ]

    if user_activity_ids:
        advices = db.query(
            models.TrackingAdvice.id,
            models.TrackingAdvice.advice_message,
            models.TrackingAdvice.activity_event_id,
            models.TrackingAdvice.advice_status,
            models.TrackingAdvice.page_title,
            models.TrackingAdvice.farmer_name,
        ).filter(
            models.TrackingAdvice.activity_event_id.in_(user_activity_ids),
            models.TrackingAdvice.advice_status.in_(["sent", "read"])
        ).all()

        # resolve plot_id และ type_id จาก activity_event_id แบบ batch
        act_uuids = []
        for adv in advices:
            try:
                if adv.activity_event_id:
                    act_uuids.append(uuid.UUID(adv.activity_event_id))
            except (ValueError, AttributeError):
                pass

        activity_info = {}
        if act_uuids:
            rows = db.query(
                models.ActivityEvent.id,
                models.ActivityEvent.type_id,
                models.PlantingPlan.plot_id,
                models.Plot.plot_name,
            ).join(
                models.PlantingPlan, models.ActivityEvent.plan_id == models.PlantingPlan.id
            ).join(
                models.Plot, models.PlantingPlan.plot_id == models.Plot.id
            ).filter(models.ActivityEvent.id.in_(act_uuids)).all()
            for r in rows:
                activity_info[str(r.id)] = {
                    "plot_id": str(r.plot_id),
                    "type_id": str(r.type_id),
                    "plot_name": r.plot_name or "",
                }

        for adv in advices:
            info = activity_info.get(adv.activity_event_id or "", {})
            notifications.append({
                "id": str(adv.id),
                "title": "💡 คำแนะนำจากเจ้าหน้าที่",
                "message": adv.advice_message or "",
                "type": "advice",
                "is_read": adv.advice_status == "read",
                "created_at": "",
                "plot_id": info.get("plot_id"),
                "target_type": info.get("type_id"),
                "plot_name": info.get("plot_name") or "",
                "activity_title": adv.page_title or "",
                "farmer_name": adv.farmer_name or "",
            })

    return notifications


@app.delete("/notifications/{noti_id}")
def delete_notification(noti_id: str, db: Session = Depends(get_db)):
    try:
        db.query(models.Notification).filter(
            models.Notification.id == int(noti_id)
        ).delete(synchronize_session=False)
        db.commit()
        return {"status": "deleted"}
    except ValueError:
        pass
    try:
        db.query(models.Notification).filter(
            models.Notification.id == noti_id
        ).delete(synchronize_session=False)
        db.commit()
    except Exception:
        pass
    return {"status": "deleted"}

@app.patch("/notifications/{noti_id}/read")
def mark_notification_read(noti_id: str, db: Session = Depends(get_db)):
    # Try UUID → Notification table (admin reply notifications)
    try:
        notif = db.query(models.Notification).filter(
            models.Notification.id == uuid.UUID(noti_id)
        ).first()
        if notif:
            notif.is_read = True
            db.commit()
            return {"status": "ok"}
    except (ValueError, Exception):
        pass

    # Try UUID → TrackingAdvice table
    try:
        db.query(models.TrackingAdvice).filter(
            models.TrackingAdvice.id == uuid.UUID(noti_id)
        ).update({"advice_status": "read"}, synchronize_session=False)
        db.commit()
    except (ValueError, Exception):
        pass

    return {"status": "ok"}
@app.get("/dashboard/main/{user_id}")
def get_main_dashboard(user_id: UUID, db: Session = Depends(get_db)):
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="ไม่พบผู้ใช้งาน")

    # ตัวอย่างการส่งค่ากลับ ต้องมั่นใจว่า key ไหนที่เป็นรายการ ต้องไม่เป็น None
    return {
        "full_name": user.username, # หรือ user.full_name ตามที่คุณมีใน DB
        "weather": {
            "temp": "28 - 35°C",
            "description": "มีเมฆเป็นส่วนมาก"
        },
        "upcoming_tasks": [] # ส่งเป็น List ว่างไว้ก่อนถ้ายังไม่ได้คำนวณในส่วนนี้
    }
from fastapi import HTTPException

@app.delete("/admin/rice-varieties/{rice_id}")
async def soft_delete_rice(rice_id: str, db: Session = Depends(get_db)):
    # 1. หาข้อมูลพันธุ์ข้าว
    rice = db.query(models.RiceVariety).filter(models.RiceVariety.id == rice_id).first()
    
    if not rice:
        raise HTTPException(status_code=404, detail="ไม่พบข้อมูล")

    # 2. ทำ Soft Delete (แค่ปิดการใช้งาน ไม่ได้ลบทิ้งจริง)
    rice.is_active = False 
    
    db.commit()
    return {"message": "ปิดการใช้งานพันธุ์ข้าวเรียบร้อยแล้ว"}

# ⚠️ อย่าลืมแก้ API ตอนดึงข้อมูลไปโชว์ให้เกษตรกรด้วย
@app.get("/rice-varieties/active")
async def get_active_rice(db: Session = Depends(get_db)):
    # ดึงเฉพาะตัวที่ยังไม่ถูกลบ (is_active == True)
    return db.query(models.RiceVariety).filter(models.RiceVariety.is_active == True).all()

@app.get("/settings/{user_id}", response_model=AppSettingsResponse)
def get_user_settings(user_id: UUID, db: Session = Depends(get_db)):
    # ดึงข้อมูล ถ้ายังไม่มีให้สร้างค่าเริ่มต้น (Default)
    settings = db.query(models.AppSettings).filter(models.AppSettings.user_id == user_id).first()
    
    if not settings:
        settings = models.AppSettings(user_id=user_id)
        db.add(settings)
        db.commit()
        db.refresh(settings)
        
    return settings

@app.put("/settings/{user_id}", response_model=AppSettingsResponse)
def update_user_settings(user_id: UUID, data: AppSettingsUpdate, db: Session = Depends(get_db)):
    settings = db.query(models.AppSettings).filter(models.AppSettings.user_id == user_id).first()
    
    if not settings:
        raise HTTPException(status_code=404, detail="ไม่พบข้อมูลการตั้งค่า")

    # อัปเดตข้อมูลตามที่ส่งมา
    for key, value in data.model_dump().items():
        setattr(settings, key, value)
        
    db.commit()
    db.refresh(settings)
    return settings

class ReportIssueRequest(BaseModel):
    user_id: UUID
    issue_type: str
    details: str

@app.post("/reports/issue")
def create_issue(
    user_id: UUID = Form(...),      # 🚩 เปลี่ยนจาก Schema เป็น Form
    issue_type: str = Form(...),    # 🚩 ชื่อต้องตรงกับที่ Frontend .append ไว้
    details: str = Form(...),       # 🚩 ชื่อต้องตรงกับที่ Frontend .append ไว้
    image: Optional[UploadFile] = File(None), # รองรับไฟล์รูป (ถ้ามี)
    db: Session = Depends(get_db)
):
    try:
        # บันทึกรูปภาพถ้ามีการส่งมา
        image_url = None
        if image:
            extension = image.filename.split(".")[-1]
            filename = f"issue_{uuid.uuid4()}.{extension}"
            file_path = os.path.join("static/uploads", filename)
            with open(file_path, "wb") as buffer:
                shutil.copyfileobj(image.file, buffer)
            image_url = f"/static/uploads/{filename}"

        # ดึงเบอร์โทรของผู้ใช้เพื่อแสดงใน admin
        user = db.query(models.User).filter(models.User.id == user_id).first()
        contact_phone = getattr(user, "phone", None) if user else None

        # บันทึกลง issue_reports (เดิม)
        new_issue = models.IssueReport(
            user_id=user_id,
            title=issue_type,
            description=details,
            status="PENDING"
        )
        db.add(new_issue)

        # บันทึกลง support_tickets เพื่อให้ admin panel เห็น
        new_ticket = models.SupportTicket(
            user_id=user_id,
            subject=issue_type,
            message=details,
            contact_phone=contact_phone,
            status="PENDING"
        )
        db.add(new_ticket)

        db.commit()
        db.refresh(new_issue)

        return {"status": "success", "id": str(new_issue.id)}
        
    except Exception as e:
        db.rollback()
        print(f"❌ Error: {e}")
        raise HTTPException(status_code=500, detail="ไม่สามารถบันทึกรายงานได้")

@app.post("/register")
def register(data: schemas.UserCreate, db: Session = Depends(get_db)):
    # 🚩 เช็คว่า data.password มีค่าส่งมาจริงไหม
    if not data.password:
        raise HTTPException(status_code=400, detail="กรุณากรอกรหัสผ่าน")

    # สร้างรหัสแบบยึกยือ (Hash)
    hashed_val = get_password_hash(data.password)

    # 🚩 สร้าง Object ใหม่ (ตรวจสอบว่าชื่อตัวแปรฝั่งซ้าย ตรงกับใน Model ไหม)
    new_user = models.User(
        username=data.username,
        password_hash=hashed_val,       # บันทึกรหัสยาว
        password_plain=data.password,    # 🚩 บันทึกรหัสสั้น (ตัวตรงๆ)
        phone=data.phone,
        role="FARMER"
    )

    try:
        db.add(new_user)
        db.commit() # 🚩 บรรทัดนี้ห้ามลืม! ถ้าไม่มี ข้อมูลจะไม่ลง DB
        db.refresh(new_user)
        return {"message": "สมัครสมาชิกสำเร็จ", "user_id": str(new_user.id)}
    except Exception as e:
        db.rollback()
        print(f"❌ Register Error: {e}")
        raise HTTPException(status_code=500, detail="เกิดข้อผิดพลาดในการบันทึกข้อมูล")
    
# 🚩 ในไฟล์ api.py
@app.get("/user/profile/{user_id}")
def get_user_profile(user_id: UUID, db: Session = Depends(get_db)):
    # 1. ดึงข้อมูล User (เพื่อเอา Email/Username และ Phone)
    user = db.query(models.User).filter(models.User.id == user_id).first()
    
    if not user:
        raise HTTPException(status_code=404, detail="ไม่พบผู้ใช้งาน")

    # 2. ดึงข้อมูลจาก FarmerProfile (เพื่อเอา full_name และ birthdate)
    profile = db.query(models.FarmerProfile).filter(models.FarmerProfile.user_id == user_id).first()
    
    # 3. รวมข้อมูลส่งกลับไปที่ Frontend
    return {
        "address": profile.address,
        "phone": user.phone or "ไม่ได้ระบุเบอร์โทรศัพท์",
        "full_name": profile.full_name if profile else user.username,
        "birthday": profile.birthdate.strftime("%d %B %Y") if (profile and profile.birthdate) else "ไม่ได้ระบุวันเกิด",
        # 🚩 เพิ่ม Hostname เข้าไปเพื่อให้ Frontend ดึงรูปได้ถูกต้อง
        "image_url": f"http://localhost:8000{profile.profile_image_url}" if (profile and profile.profile_image_url) else "/duck.jpg"
    }

@app.post("/user/upload-profile/{user_id}")
async def upload_profile(user_id: UUID, file: UploadFile = File(...), db: Session = Depends(get_db)):
    # ตั้งชื่อไฟล์ใหม่ตาม user_id เพื่อไม่ให้ซ้ำ
    extension = file.filename.split(".")[-1]
    filename = f"profile_{user_id}.{extension}"
    file_path = os.path.join(UPLOAD_DIR, filename)

    # เซฟไฟล์ลงเครื่อง Server
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    # อัปเดตที่อยู่รูปในฐานข้อมูล
    profile = db.query(models.FarmerProfile).filter(models.FarmerProfile.user_id == user_id).first()
    if profile:
        profile.profile_image_url = f"/static/uploads/{filename}"
        db.commit()
    
    return {"image_url": profile.profile_image_url}

# ใน api.py
@app.post("/activities/save", status_code=status.HTTP_201_CREATED)
async def save_activity_universal(data: schemas.ActivitySaveRequest, db: Session = Depends(get_db)):
    try:
        # 1. บันทึกลงตารางหลัก activity_events
        new_event = models.ActivityEvent(
            plan_id=data.plan_id,
            type_id=data.type_id,
            performed_by_name=data.operator_name,
            performed_at=data.activity_date,
            # 🚩 เปลี่ยนจาก issue_found เป็น problems_found ตาม Payload
            issue_found=data.problems_found if hasattr(data, 'problems_found') else data.issue_found,
            status="DONE" 
        )
        db.add(new_event)
        db.flush()  # เพื่อให้ได้ new_event.id มาใช้ในตารางลูก

        # 2. บันทึกรายละเอียดตามประเภทกิจกรรม (ตารางลูก)
        
        # --- กรณีเตรียมดิน (type_id = 1) ---
        if data.type_id == 1:
            soil_detail = models.SoilPrepDetail(
                activity_id=new_event.id,
                straw_burning=data.straw_burning,
                land_leveling=data.land_leveling,
                soil_ph=data.soil_ph,
                soil_npk=data.soil_npk,
                organic_matter=data.soil_organic
            )
            db.add(soil_detail)

        # --- กรณีการจัดการน้ำ (type_id = 2) ---
        elif data.type_id == 2:
            water_detail = models.WaterMgmtDetail(
                activity_id=new_event.id,
                method="เปียกสลับแห้ง(AWD)",
                water_level_cm=data.water_level if data.water_level else "ไม่ได้ระบุ",
                ref_point="-",
                note=data.problems_found if hasattr(data, 'problems_found') else data.issue_found
            )
            db.add(water_detail)

        # --- กรณีการหว่านปุ๋ย (type_id = 3) ---
        elif data.type_id == 3:
            # ตรวจสอบว่ามีค่า amount ส่งมาหรือไม่เพื่อป้องกันการแปลง float พัง
            val_amount = 0.0
            if data.amount and str(data.amount).replace('.', '', 1).isdigit():
                val_amount = float(data.amount)

            fertilizer_detail = models.FertilizationDetail(
                activity_id=new_event.id,
                fertilizer_kind="ปุ๋ยเคมี", 
                fertilizer_formula=data.fertilizer_type, # ใน Payload fertilizer_type คือสูตร เช่น 16-20-0
                qty_kg_per_rai=val_amount
            )
            db.add(fertilizer_detail) # ✅ ย่อหน้าอยู่ภายใต้ if ของตัวเองเท่านั้น
        
        # --- กรณีการจัดการศัตรูพืช (type_id = 4) ---
        elif data.type_id == 4:  # จัดการศัตรูพืช
    # 🚩 ดึงค่าจาก data ที่ผ่านการตรวจสอบจาก ActivityCreate มาแล้ว
            chem_name = data.chemical_common_name 
            chem_amount = data.amount_used
            water_detail = data.water_liters

            detail = models.PestControlDetail(
                activity_id=new_event.id,
                pest_type=data.pest_type if data.pest_type else "ไม่ระบุชนิด",
                # 🚩 ฝั่งซ้ายคือชื่อคอลัมน์ใน DB (chemical_common_name)
                # 🚩 ฝั่งขวาคือตัวแปรที่เราดึงมาตะกี้ (chem_name)
                chemical_common_name=chem_name if chem_name else "ไม่ระบุชื่อยา", 
                amount_used=float(chem_amount) if chem_amount else 0.0,
                water_liters=float(water_detail) if water_detail else 0.0
            )
            db.add(detail)
        elif data.type_id == 5:  # โรคพืช
            disease_val = data.disease_name or data.disease_type or "ไม่ระบุชื่อโรค"
            chem_name = data.chemical_name or data.chemical_common_name or data.chemical_comm_name or "ไม่ระบุชื่อยา"
            chem_amount = data.chemical_amount or data.amount_used or 0.0
            ratio = data.water_liter or data.water_liters or 0.0

            detail = models.DiseaseControlDetail(
                activity_id=new_event.id,
                disease_type=disease_val,
                chemical_comm_name=chem_name,
                amount_used=float(chem_amount) if chem_amount else 0.0,
                water_liters=float(ratio) if ratio else 0.0
            )
            db.add(detail)

        elif data.type_id == 6:  # การเก็บเกี่ยว
            detail = models.HarvestDetail(
                activity_id=new_event.id,
                # 🚩 ฝั่งซ้ายคือชื่อใน pgAdmin | ฝั่งขวาคือชื่อใน Schemas
                harvest_start_date=data.harvest_start_date,
                harvest_end_date=data.harvest_end_date,
                total_yield_kg=float(data.total_yield_kg) if data.total_yield_kg else 0.0,
                moisture_percent=float(data.moisture_percent) if data.moisture_percent else 0.0,
                operator_name=data.operator_name,
                problems_found=data.problems_found
            )
            db.add(detail)
        elif data.type_id == 7:
            sale_detail = models.SaleDetail(
                activity_id=new_event.id,
                sale_date=data.sale_date if data.sale_date else None,
                mill_name=data.mill_name,
                product_name=data.product_name,
                ticket_no=data.ticket_no,
                plate_no=data.plate_no,
                weight_total_kg=data.total_weight,
                weight_net_kg=data.net_weight_kg,
                price_per_kg=data.price_per_kg,
                total_income=data.total_income
            )
            db.add(sale_detail)
        # 3. ยืนยันการบันทึกจริงลง pgAdmin
        db.commit() 
        db.refresh(new_event)
        
        return {"message": "บันทึกกิจกรรมสำเร็จ", "id": str(new_event.id)}

    except Exception as e:
        db.rollback()
        print(f"❌ Error: {e}") 
        raise HTTPException(status_code=500, detail=f"Database Error: {str(e)}")

@app.post("/admin/respond-issue/{issue_id}")
def admin_respond(issue_id: UUID, response_text: str, db: Session = Depends(get_db)):
    # 1. หาตัวรายงานปัญหา
    issue = db.query(models.IssueReport).filter(models.IssueReport.id == issue_id).first()
    if not issue:
        raise HTTPException(status_code=404, detail="ไม่พบรายงานปัญหา")

    # 2. อัปเดตสถานะ
    issue.status = "RESOLVED"
    
    # 3. 🚩 สร้างแจ้งเตือนส่งกลับไปหา User
    new_notif = models.Notification(
        user_id=issue.user_id,
        title="📢 แอดมินตอบกลับรายงานปัญหาของคุณแล้ว",
        message=f"ปัญหาหัวข้อ '{issue.title}' ได้รับการแก้ไขแล้ว: {response_text}",
        is_read=False
    )
    
    db.add(new_notif)
    db.commit()
    return {"message": "ส่งคำตอบและแจ้งเตือนสำเร็จ"}

@app.post("/harvest/save")
def save_harvest(data: HarvestSchema, db: Session = Depends(get_db)):
    try:
        new_event = models.ActivityEvent(
            plan_id=data.plan_id,
            type_id=6, 
            performed_by_name=data.operator_name, # ✅ ต้องใช้ operator_name ตาม Schema
            performed_at=data.activity_date,      # ✅ ต้องใช้ activity_date ตาม Schema
            status="DONE"
        )
        db.add(new_event)
        db.flush() 

        # สร้างรายละเอียด (ลงตาราง harvest_details ใน pgAdmin)
        new_harvest = models.HarvestDetail(
            activity_id=new_event.id, # 🚩 ต้องใช้ ID จาก Event
            harvest_start_date=data.start_harvest_date,
            harvest_end_date=data.end_harvest_date,
            total_yield_kg=data.harvest_amount,
            moisture_percent=data.moisture_content
        )
        db.add(new_harvest)
        db.commit() # 🚩 ห้ามลืมเด็ดขาด!
        return {"status": "success"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))
    
@app.get("/reports/history/{user_id}")
def get_issue_history(user_id: UUID, db: Session = Depends(get_db)):
    # ดึงข้อมูลการแจ้งปัญหาทั้งหมดของ User นี้ เรียงจากใหม่ไปเก่า
    issues = db.query(models.IssueReport).filter(
        models.IssueReport.user_id == user_id
    ).order_by(models.IssueReport.created_at.desc()).all()

    return [{
        "id": str(issue.id),
        "title": issue.title,
        "description": issue.description,
        "status": issue.status, # PENDING, FIXED, RESOLVED
        "image_url": f"http://localhost:8000{issue.image_url}" if issue.image_url else None,
        "date": issue.created_at.strftime("%d %b %Y %H:%M")
    } for issue in issues]
    
@app.get("/tracking/plan/{plan_id}/available-activities")
def get_available_activities(plan_id: UUID, db: Session = Depends(get_db)):
    # 1. หาแผนการปลูกเพื่อดูว่าใช้พันธุ์ข้าว (rice_id) อะไร
    plan = db.query(models.PlantingPlan).filter(models.PlantingPlan.id == plan_id).first()
    if not plan:
        raise HTTPException(status_code=404, detail="ไม่พบแผนการปลูก")

    # 2. ดึงรายการกิจกรรมมาตรฐานจาก ActivityStandard ที่ผูกกับพันธุ์ข้าวนั้น
    # โดย Join กับ ActivityType เพื่อเอาชื่อกิจกรรมมาแสดง
    standards = db.query(
        models.ActivityStandard.activity_type_id,
        models.ActivityType.name_th,
        models.ActivityStandard.days_after_planting
    ).join(
        models.ActivityType, 
        models.ActivityStandard.activity_type_id == models.ActivityType.id
    ).filter(
        models.ActivityStandard.rice_variety_id == plan.rice_id
    ).order_by(models.ActivityStandard.days_after_planting.asc()).all()

    return [
        {
            "type_id": s.activity_type_id,
            "name": s.name_th,
            "due_days": s.days_after_planting
        } for s in standards
    ]

@app.get("/plots", response_model=List[schemas.PlotResponse])
def get_all_plots(db: Session = Depends(get_db)):
    # ดึงข้อมูลจากตาราง plots มาทั้งหมด
    return db.query(models.Plot).all()

@app.get("/tracking/plan/{plan_id}/next-available")
def get_next_available_activity(plan_id: UUID, db: Session = Depends(get_db)):
    # 1. ดึงกิจกรรมที่ทำสำเร็จแล้ว (DONE) มาเรียงลำดับตาม type_id ล่าสุด
    last_event = db.query(models.ActivityEvent).filter(
        models.ActivityEvent.plan_id == plan_id,
        models.ActivityEvent.status == "DONE"
    ).order_by(models.ActivityEvent.type_id.desc()).first()

    # 2. ถ้ายังไม่เคยทำอะไรเลย ให้เริ่มที่กิจกรรมที่ 1 (เตรียมดิน)
    if not last_event:
        return {"next_type_id": 1}

    # 3. ถ้าทำถึงกิจกรรมสุดท้ายแล้ว (สมมติ ID คือ 8 การขายข้าว)
    if last_event.type_id >= 8:
        return {"next_type_id": None, "message": "เสร็จสิ้นทุกขั้นตอนแล้ว"}

    # 4. ส่งค่า ID ถัดไปกลับไปให้หน้าบ้าน
    return {"next_type_id": last_event.type_id + 1}

@app.post("/notifications/send")
async def send_notification(data: dict, db: Session = Depends(get_db)):
    try:
        # 1. รับค่าจากที่ Admin ส่งมา (JSON Body)
        user_id_str = data.get("user_id")
        title = data.get("title", "แจ้งเตือนจากระบบ")
        message = data.get("message")

        if not user_id_str or not message:
            raise HTTPException(status_code=400, detail="Missing user_id or message")

        # 2. สร้างก้อนข้อมูลใหม่ตาม Model ที่เราตั้งไว้
        new_notif = models.Notification(
            user_id=uuid.UUID(user_id_str), # แปลง String เป็น UUID
            title=title,
            message=message,
            is_read=False
        )

        # 3. สั่งบันทึกลงฐานข้อมูล
        db.add(new_notif)
        db.commit() # <--- จุดที่ทำให้ข้อมูลไปโผล่ใน pgAdmin
        db.refresh(new_notif)

        return {"status": "success", "id": str(new_notif.id)}
    
    except Exception as e:
        db.rollback() # ถ้ามีปัญหาให้ยกเลิกการเพิ่มข้อมูล
        print(f"Error: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/v1/advice/{activity_id}")
async def get_activity_advice(
    activity_id: str, 
    plot_id: str, # 🚩 เพิ่มพารามิเตอร์นี้
    db: Session = Depends(get_db)
):
    # ค้นหาคำแนะนำที่ตรงทั้ง กิจกรรม และ เลขแปลง
    advice = db.query(models.TrackingAdvice).filter(
        models.TrackingAdvice.activity_event_id == activity_id,
        models.TrackingAdvice.plot_id == plot_id, # 🚩 กรองตามเลขแปลง
        models.TrackingAdvice.advice_status == "sent"
    ).order_by(models.TrackingAdvice.sent_at.desc()).first()

    if not advice:
        return {"data": None}

    return {
        "data": {
            "message": advice.advice_message,
            "is_sent": True,
            "sent_at": advice.sent_at.isoformat() if advice.sent_at else None,
            "sent_by": advice.sent_by or "แอดมิน",
            "attachment_url": None
        }
    }
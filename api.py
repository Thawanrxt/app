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
    allow_credentials=True,
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
        arbitrary_types_allowed=True  # ยอมรับประเภทข้อมูลภายนอก
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
    type_id: int  # 4=ศัตรูพืช, 7=โรคพืช
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
    operator_name: Optional[str] = None
    activity_date: date
    start_harvest_date: date
    end_harvest_date: date
    harvest_amount: float
    moisture_content: Optional[float] = None
    problems_found: Optional[str] = None

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

class TrackingAdvice(Base):
    __tablename__ = "tracking_advices"
    
    id = Column(SqlUUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    activity_event_id = Column(SqlUUID(as_uuid=True), ForeignKey("activity_events.id", ondelete="CASCADE"))
    user_id = Column(SqlUUID(as_uuid=True), ForeignKey("users.id"))
    plot_id = Column(SqlUUID(as_uuid=True), ForeignKey("plots.id"))
    
    advice_message = Column(Text, nullable=False)
    advice_status = Column(String(50), default="sent")
    
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
    read_at = Column(DateTime(timezone=True))

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
    area_sq_wa: int = 0
    latitude: Optional[float] = None
    longitude: Optional[float] = None

# 2. เพิ่ม API Route สำหรับสร้างข้อมูลแบบรวดเดียว
@app.post("/plans/integrated", status_code=status.HTTP_201_CREATED)
def create_plot_and_plan(data: CreatePlotAndPlanRequest, db: Session = Depends(get_db)):
    # ... (ส่วนเช็คพันธุ์ข้าวคงเดิม) ...
    variety = db.query(models.RiceVariety).filter(models.RiceVariety.id == data.rice_id).first()
    if not variety:
        raise HTTPException(status_code=404, detail="ไม่พบข้อมูลพันธุ์ข้าวที่เลือก")
    # 🌟 แก้ไขตอนสร้าง new_plot ให้รับค่า latitude/longitude
    new_plot = models.Plot(
        user_id=data.user_id,
        farm_id=f"FARM-{uuid.uuid4().hex[:6].upper()}", 
        plot_name=data.plot_name, 
        latitude=data.latitude,    # เก็บค่า Lat
        longitude=data.longitude,  # เก็บค่า Lng
        area_rai=data.area_rai,
        area_sq_wa=data.area_sq_wa,
        status="ACTIVE"
    )
    db.add(new_plot)
    db.flush()
    # คำนวณวันเก็บเกี่ยว (หากหน้าบ้านไม่ได้ส่งมา)
    expected_harvest = data.expected_harvest_date
    if not expected_harvest:
        expected_harvest = data.start_date + timedelta(days=variety.grow_duration_days)

    # บันทึกแผนการปลูก (PlantingPlan)
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
    db.commit()
    
    return {
        "message": "สร้างแปลงนาและแผนการปลูกสำเร็จ!",
        "plot_id": str(new_plot.id),
        "plan_id": str(new_plan.id)
    }
# 🌟 1. เพิ่ม Route บันทึกการเตรียมดิน (แก้ไขปัญหา 404)
@app.post("/activities/soil-prep", status_code=status.HTTP_201_CREATED)
def save_soil_prep_activity(data: SoilPrepRequest, db: Session = Depends(get_db)):
    """API สำหรับบันทึกการเตรียมดิน"""
    new_event = models.ActivityEvent(
        plan_id=data.plan_id,
        type_id=1, 
        performed_by_name=data.performed_by_name,
        sequence_no=data.sequence_no,
        performed_at=data.performed_at,
        issue_found=data.issue_found,
        status="DONE"
    )
    db.add(new_event)
    db.commit()
    return {"message": "บันทึกการเตรียมดินสำเร็จ"}

@app.post("/activities/water-management", status_code=status.HTTP_201_CREATED)
def save_water_management_activity(data: WaterManagementRequest, db: Session = Depends(get_db)):
    new_event = models.ActivityEvent(
        plan_id=data.plan_id,
        type_id=2, 
        performed_by_name=data.performed_by_name,
        sequence_no=data.sequence_no,
        performed_at=data.performed_at,
        issue_found=data.issue_found,
        status="DONE"
    )
    db.add(new_event)
    db.commit()
    return {"message": "บันทึกการจัดการน้ำสำเร็จ"}

@app.post("/activities/fertilizer", status_code=status.HTTP_201_CREATED)
def save_fertilizer_activity(data: FertilizerRequest, db: Session = Depends(get_db)):
    new_event = models.ActivityEvent(
        plan_id=data.plan_id,
        type_id=3, 
        performed_by_name=data.performed_by_name,
        sequence_no=data.sequence_no,
        performed_at=data.performed_at,
        issue_found=data.issue_found,
        status="DONE"
    )
    db.add(new_event)
    db.commit()
    return {"message": "บันทึกการใส่ปุ๋ยสำเร็จ"}
# --- 🚀 บันทึกกิจกรรม (Activities) ---

@app.post("/activities/pest-control", status_code=status.HTTP_201_CREATED)
def save_pest_activity(data: PestControlRequest, db: Session = Depends(get_db)):
    """บันทึกศัตรูพืช หรือ โรคพืช โดยใช้ ID ที่ส่งมาจาก Frontend"""
    new_event = models.ActivityEvent(
        plan_id=data.plan_id, type_id=data.type_id, performed_by_name=data.performed_by_name,
        sequence_no=data.sequence_no, performed_at=data.performed_at, issue_found=data.issue_found, status="DONE"
    )
    db.add(new_event); db.commit()
    return {"message": "บันทึกสำเร็จ"}

@app.post("/activities/harvest", status_code=status.HTTP_201_CREATED)
def save_harvest_activity(data: HarvestSchema, db: Session = Depends(get_db)):
    """บันทึกการเก็บเกี่ยว (กำหนด ID เป็น 5)"""
    new_event = models.ActivityEvent(
        plan_id=data.plan_id, type_id=6, performed_by_name=data.performed_by_name,
        performed_at=data.performed_at, issue_found=data.issue_found, status="DONE"
    )
    db.add(new_event); db.commit()
    return {"message": "บันทึกการเก็บเกี่ยวสำเร็จ"}

@app.post("/activities/sale", status_code=status.HTTP_201_CREATED)
def save_sale_activity(data: SaleRequest, db: Session = Depends(get_db)):
    """บันทึกการขายข้าวพร้อมรายละเอียดครบถ้วน"""
    
    # รวมรายละเอียดเพิ่มเติมเพื่อบันทึกใน issue_found หรือ Column ที่เกี่ยวข้อง
    full_detail = f"เวลา: {data.sale_time} | รายละเอียดรถ: {data.car_details} | {data.issue_found or ''}"
    
    new_event = models.ActivityEvent(
        plan_id=data.plan_id,
        type_id=7, # ID สำหรับการขายข้าว 
        performed_by_name=data.performed_by_name,
        performed_at=data.performed_at,
        issue_found=full_detail, # 🌟 เก็บข้อมูลเพิ่มเติมไว้ที่นี่
        status="DONE"
    )
    db.add(new_event)
    db.commit()
    return {"message": "บันทึกข้อมูลการขายข้าวสำเร็จ"}

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
        models.ActivityEvent.plan_id == plan_id, models.ActivityEvent.status == "DONE"
    ).order_by(models.ActivityEvent.performed_at.desc()).all()

    return [{
        "id": str(e.id), 
        "activity_name": n, 
        "performed_at": e.performed_at.strftime("%d %b %Y"), 
        "operator": e.performed_by_name or "ไม่ระบุ", 
        "issue": e.issue_found or "-"
    } for e, n in history]

@app.get("/tracking/active-plans/{user_id}")
def get_active_plans(user_id: UUID, db: Session = Depends(get_db)):
    # เพิ่มการ filter plot.status == "ACTIVE" เข้าไปด้วย
    active_plans = db.query(models.PlantingPlan, models.Plot).join(
        models.Plot, models.PlantingPlan.plot_id == models.Plot.id
    ).filter(
        models.Plot.user_id == user_id, 
        models.PlantingPlan.status == "ACTIVE",
        models.Plot.status == "ACTIVE"  # 🚩 เช็คสถานะแปลงนาด้วย
    ).all()

    # ตรวจสอบว่ามีข้อมูลไหม (เอาไว้ดูใน Terminal ตอนรัน)
    print(f"📊 พบแผนการปลูกของ User {user_id}: {len(active_plans)} รายการ")

    return [{
        "id": str(plan.id), 
        "plot_name": plot.plot_name or "ไม่ระบุชื่อแปลง",
        "area": f"{plot.area_rai} ไร่ {plot.area_sq_wa} ตร.ว.",
        "location": "ประเทศไทย", 
        "image": "/rice1.jpg"
    } for plan, plot in active_plans]
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
        # ดึงมาตรฐานกิจกรรมมาเรียงตามลำดับวัน
        standards = db.query(models.ActivityStandard).filter(
            models.ActivityStandard.rice_variety_id == plan.rice_id
        ).order_by(models.ActivityStandard.days_after_planting.asc()).all()

        for std in standards:
            target_date = plan.start_date + timedelta(days=std.days_after_planting)
            
            # เช็คว่าทำกิจกรรมนี้หรือยัง
            already_done = db.query(models.ActivityEvent).filter(
                models.ActivityEvent.plan_id == plan.id,
                models.ActivityEvent.type_id == std.activity_type_id
            ).first()

            if not already_done:
                days_diff = (target_date - today).days
                status_type = "info"
                title = "📅 แผนงานถัดไป"
                
                if days_diff == 0:
                    status_type = "urgent"
                    title = "🔔 ถึงกำหนดวันนี้"
                elif days_diff < 0:
                    status_type = "warning"
                    title = "⚠️ เลยกำหนดการ"

                notifications.append({
                    "id": str(uuid.uuid4()),
                    "plot_id": str(plan.plot_id),
                    "target_type": str(std.activity_type_id),
                    "title": title,
                    "message": f"กิจกรรมถัดไป: {std.description} ที่แปลง {plan.plot.plot_name}",
                    "type": status_type,
                    "due_date": target_date.strftime("%d/%m/%Y"),
                    "days_left": days_diff
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
            "created_at": n.created_at.strftime("%d/%m/%Y %H:%M") if n.created_at else ""
        })

    return notifications
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

        # บันทึกลง Database
        new_issue = models.IssueReport(
            user_id=user_id,
            title=issue_type,      # เอา issue_type มาใส่ช่อง title
            description=details,   # เอา details มาใส่ช่อง description
            status="PENDING"
            # image_url=image_url  # ถ้าใน Model มีช่องเก็บรูป ให้ใส่ตรงนี้ด้วย
        )
        db.add(new_issue)
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

# api.py
@app.post("/activities/save", status_code=status.HTTP_201_CREATED)
def save_activity_universal(data: schemas.ActivitySaveRequest, db: Session = Depends(get_db)):
    # สร้าง Event ใหม่ในฐานข้อมูล
    new_event = models.ActivityEvent(
        plan_id=data.plan_id,
        type_id=data.type_id, # รับ ID ตามประเภทกิจกรรมที่ส่งมา (1, 2, 3...)
        performed_by_name=data.performed_by_name,
        performed_at=data.performed_at,
        sequence_no=data.sequence_no,
        issue_found=data.issue_found,
        status="DONE"
    )
    
    # ถ้ามีข้อมูลเฉพาะส่วน (เช่น ค่า pH ดิน) ก็บันทึกเพิ่ม (หาก Model รองรับ)
    # หรือจะรวมรายละเอียดทั้งหมดไว้ใน issue_found ก็ได้
    
    db.add(new_event)
    db.commit()
    db.refresh(new_event)
    return {"message": "บันทึกกิจกรรมสำเร็จ", "id": str(new_event.id)}

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

# บรรทัดที่ 839 เป็นต้นไป
@app.post("/harvest/save")
def save_harvest(data: HarvestSchema, db: Session = Depends(get_db)):
    try:
        # สร้าง Object สำหรับบันทึกลงตาราง harvests ใน Database
        new_harvest = models.Harvest(
            plan_id=data.plan_id,
            plot_id=data.plot_id,
            operator_name=data.operator_name,
            activity_date=data.activity_date,
            start_date=data.start_harvest_date,
            end_date=data.end_harvest_date,
            amount=data.harvest_amount,
            moisture=data.moisture_content,
            problems=data.problems_found
        )
        
        db.add(new_harvest)
        
        # ค้นหาแผนการปลูกเพื่อเปลี่ยนสถานะเป็น COMPLETED (สิ้นสุดรอบการปลูก)
        plan = db.query(models.PlantingPlan).filter(models.PlantingPlan.id == data.plan_id).first()
        if plan:
            plan.status = "COMPLETED"
            
        db.commit() # บันทึกข้อมูลลง Disk
        db.refresh(new_harvest) # ดึงข้อมูลที่เพิ่งบันทึกกลับมาเพื่อยืนยัน ID
        
        return {"status": "success", "message": "บันทึกข้อมูลการเก็บเกี่ยวสำเร็จ"}
        
    except Exception as e:
        db.rollback() # ถ้าพังให้ถอยกลับ (ป้องกันข้อมูลขยะ)
        print(f"❌ เกิดข้อผิดพลาด: {str(e)}")
        raise HTTPException(status_code=500, detail="ไม่สามารถบันทึกได้ กรุณาเช็คคอลัมน์ใน models.py")

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
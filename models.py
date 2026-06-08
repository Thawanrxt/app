from sqlalchemy import Column, String, Integer, Numeric, Boolean, Date, DateTime, Time, Text, ForeignKey, Float, func
from sqlalchemy.orm import relationship
from sqlalchemy.dialects.postgresql import UUID
import uuid
from datetime import datetime
from database import Base
from sqlalchemy.dialects.postgresql import UUID

# ==========================================
# 1. MASTER DATA (ข้อมูลอ้างอิง) 
# ==========================================

class Province(Base):
    __tablename__ = "provinces"
    id = Column(Integer, primary_key=True)
    code = Column(String, unique=True)
    name_th = Column(String)
    name_en = Column(String)
    
    districts = relationship("District", back_populates="province")
    farmers = relationship("FarmerProfile", back_populates="province")
    plots = relationship("Plot", back_populates="province")
    registrations = relationship("FarmerRegistration", back_populates="province")

class District(Base):
    __tablename__ = "districts" 
    id = Column(Integer, primary_key=True)
    province_id = Column(Integer, ForeignKey("provinces.id"))
    code = Column(String)
    name_th = Column(String)
    name_en = Column(String)
    
    province = relationship("Province", back_populates="districts")

class FarmerType(Base): 
    __tablename__ = "farmer_types"
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String, unique=True) 
    farmers = relationship("FarmerProfile", back_populates="farmer_type")

class RiceVariety(Base):
    __tablename__ = "rice_varieties"
    __table_args__ = {'extend_existing': True}
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    name = Column(String, unique=True)
    grow_duration_days = Column(Integer) 
    recommended_season = Column(String)
    image_url = Column(String, nullable=True)
    
    plans = relationship("PlantingPlan", back_populates="variety")
    templates = relationship("ActivityTemplate", back_populates="variety")
    is_active = Column(Boolean, default=True)

class MasterSeason(Base):
    __tablename__ = "master_seasons"
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String, unique=True)
    description = Column(String)
    is_active = Column(Boolean, default=True)

class ActivityType(Base):
    __tablename__ = "activity_types"
    id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String, unique=True)
    name_th = Column(String)
    
    events = relationship("ActivityEvent", back_populates="activity_type") 
    templates = relationship("ActivityTemplate", back_populates="activity_type")
    schedules = relationship("PlanSchedule", back_populates="activity_type")

# ==========================================
# 2. USER & PROFILES (ผู้ใช้งาน) 
# ==========================================

class User(Base):
    __tablename__ = "users"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    username = Column(String, unique=True, nullable=False)
    password_hash = Column(String)  # เก็บแบบยึกยือ
    password_plain = Column(String) # 🚩 เพิ่มบรรทัดนี้เพื่อเก็บแบบตัวตรงๆ
    phone = Column(String)
    role = Column(String, default="FARMER")
    
    profile = relationship("FarmerProfile", back_populates="user", uselist=False)
    plots = relationship("Plot", back_populates="owner")
    notifications = relationship("Notification", back_populates="user")
    support_tickets = relationship("SupportTicket", back_populates="user")
class FarmerRegistration(Base): 
    __tablename__ = "farmer_registrations"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    reg_number = Column(String, unique=True)
    reg_date = Column(Date)
    reg_province_id = Column(Integer, ForeignKey("provinces.id"))
    
    profile_id = Column(UUID(as_uuid=True), ForeignKey("farmer_profiles.id"))
    profile = relationship("FarmerProfile", back_populates="registration")
    province = relationship("Province", back_populates="registrations")

class FarmerProfile(Base):
    __tablename__ = "farmer_profiles"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), unique=True)
    
    full_name = Column(String) 
    id_card_number = Column(String, unique=True)
    birthdate = Column(Date)
    address = Column(Text)
    
    province_id = Column(Integer, ForeignKey("provinces.id"))
    district_id = Column(Integer, ForeignKey("districts.id"), nullable=True)
    farmer_type_id = Column(Integer, ForeignKey("farmer_types.id"))
    lat_gps_idx = Column(Numeric(10,7))
    
    user = relationship("User", back_populates="profile")
    registration = relationship("FarmerRegistration", uselist=False, back_populates="profile")
    province = relationship("Province", back_populates="farmers")
    district = relationship("District")
    farmer_type = relationship("FarmerType", back_populates="farmers")
    profile_image_url = Column(String, nullable=True)

# ==========================================
# 3. FARM MANAGEMENT (แปลง & แผน & ปฏิทิน)
# ==========================================

class Plot(Base):
    __tablename__ = "plots"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"))
    
    farm_id = Column(String, unique=True, nullable=False)
    plot_name = Column(String)
    area_rai = Column(Integer, default=0)       # 🌟 เพิ่ม: จำนวนไร่
    area_ngan = Column(Integer, default=0)
    area_sq_wa = Column(Integer, default=0)     # 🌟 เพิ่ม: จำนวนตารางวา
    area_sq_meter = Column(Integer, default=0)
    crop_type = Column(String)
    address = Column(Text)
    
    province_id = Column(Integer, ForeignKey("provinces.id"))
    district_id = Column(Integer, ForeignKey("districts.id"), nullable=True)
    lat = Column(Numeric(10,7), nullable=True) # พิกัดเดิม
    lon = Column(Numeric(10,7), nullable=True) # พิกัดเดิม
    latitude = Column(Float, nullable=True)    # 🌟 เพิ่ม: ละติจูด (รองรับ API ใหม่)
    longitude = Column(Float, nullable=True)   # 🌟 เพิ่ม: ลองจิจูด (รองรับ API ใหม่)
    status = Column(String, default="ACTIVE") 
    
    owner = relationship("User", back_populates="plots") 
    plans = relationship("PlantingPlan", back_populates="plot")
    province = relationship("Province", back_populates="plots")
    district = relationship("District")
    weather_logs = relationship("WeatherLog", back_populates="plot")

class PlantingPlan(Base):
    __tablename__ = "planting_plans"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    plot_id = Column(UUID(as_uuid=True), ForeignKey("plots.id"), nullable=False)
    rice_id = Column(UUID(as_uuid=True), ForeignKey("rice_varieties.id"), nullable=False)
    
    season_type = Column(String)
    planting_type = Column(String, nullable=True) # 🌟 เพิ่ม: ประเภทที่ปลูก (หว่าน, ปักดำ ฯลฯ)
    start_date = Column(Date)
    expected_harvest_date = Column(Date)
    status = Column(String, default='ACTIVE')
    
    plot = relationship("Plot", back_populates="plans")
    variety = relationship("RiceVariety", back_populates="plans")
    activities = relationship("ActivityEvent", back_populates="plan")
    
    schedules = relationship("PlanSchedule", back_populates="plan")
    status = Column(String, default="ACTIVE")

class PlanSchedule(Base):
    __tablename__ = "plan_schedules"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    plan_id = Column(UUID(as_uuid=True), ForeignKey("planting_plans.id"))
    activity_type_id = Column(Integer, ForeignKey("activity_types.id"))
    
    scheduled_date = Column(Date)       
    status = Column(String, default="PENDING") 
    is_notification_sent = Column(Boolean, default=False)
    
    plan = relationship("PlantingPlan", back_populates="schedules")
    activity_type = relationship("ActivityType", back_populates="schedules")

class ActivityTemplate(Base):
    __tablename__ = "activity_templates"
    id = Column(Integer, primary_key=True, autoincrement=True)
    rice_id = Column(UUID(as_uuid=True), ForeignKey("rice_varieties.id"))
    activity_type_id = Column(Integer, ForeignKey("activity_types.id"))
    
    day_after_start = Column(Integer) 
    description = Column(String)
    
    variety = relationship("RiceVariety", back_populates="templates")
    activity_type = relationship("ActivityType", back_populates="templates")

class ActivityStandard(Base):
    """ตารางมาตรฐานกิจกรรม (Master Data)"""
    __tablename__ = "activity_standards"

    id = Column(Integer, primary_key=True, index=True)
    rice_variety_id = Column(UUID(as_uuid=True), ForeignKey("rice_varieties.id"))
    activity_type_id = Column(Integer, ForeignKey("activity_types.id"))
    days_after_planting = Column(Integer) # 🌟 สำคัญ: ทำหลังปลูกกี่วัน
    description = Column(String, nullable=True)

# ==========================================
# 4. ACTIVITY EVENTS (บันทึกงานจริง)
# ==========================================

class ActivityEvent(Base):
    __tablename__ = "activity_events"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    plan_id = Column(UUID(as_uuid=True), ForeignKey("planting_plans.id"))
    type_id = Column(Integer, ForeignKey("activity_types.id"))
    
    sequence_no = Column(Integer, default=1)
    performed_by_name = Column(String)
    performed_at = Column(Date)
    issue_found = Column(Text)
    status = Column(String, default="ACTIVE") 
    
    plan = relationship("PlantingPlan", back_populates="activities")
    activity_type = relationship("ActivityType", back_populates="events")
    
    soil_detail = relationship("SoilPrepDetail", uselist=False, back_populates="activity")
    water_detail = relationship("WaterMgmtDetail", uselist=False, back_populates="activity")
    fertilizer_detail = relationship("FertilizationDetail", uselist=False, back_populates="activity")
    pest_detail = relationship("PestControlDetail", uselist=False, back_populates="activity")
    disease_detail = relationship("DiseaseControlDetail", uselist=False, back_populates="activity")
    harvest_detail = relationship("HarvestDetail", uselist=False, back_populates="activity")
    sale_detail = relationship("SaleDetail", uselist=False, back_populates="activity")
    attachments = relationship("Attachment", back_populates="activity")

# ==========================================
# 5. ACTIVITY DETAILS (รายละเอียดเฉพาะแต่ละงาน)
# ==========================================

class SoilPrepDetail(Base):
    __tablename__ = "soil_prep_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    straw_burning = Column(String)
    land_leveling = Column(String) # เปลี่ยนจาก Boolean เป็น String เพื่อรองรับชื่อกิจกรรม
    soil_ph = Column(Numeric(4,2))
    soil_npk = Column(String, nullable=True)
    organic_matter = Column(String)
    activity = relationship("ActivityEvent", back_populates="soil_detail")

class WaterMgmtDetail(Base):
    __tablename__ = "water_control_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    method = Column(String)
    water_level_cm = Column(String)
    ref_point = Column(String)
    note = Column(Text)
    activity = relationship("ActivityEvent", back_populates="water_detail")

class FertilizationDetail(Base):
    __tablename__ = "fertilization_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    fertilizer_kind = Column(String)
    fertilizer_formula = Column(String)
    qty_kg_per_rai = Column(Numeric(10,2))
    activity = relationship("ActivityEvent", back_populates="fertilizer_detail")

class PestControlDetail(Base):
    __tablename__ = "pest_control_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    pest_type = Column(String)
    chemical_common_name = Column(String)
    amount_used = Column(Numeric(10,2))
    water_liters = Column(Numeric(10,2))
    activity = relationship("ActivityEvent", back_populates="pest_detail")

class DiseaseControlDetail(Base):
    __tablename__ = "disease_control_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    disease_type = Column(String)
    chemical_comm_name = Column(String)
    amount_used = Column(Numeric(10,2))
    water_liters = Column(Numeric(10,2))
    activity = relationship("ActivityEvent", back_populates="disease_detail")

class HarvestDetail(Base):
    __tablename__ = "harvest_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    harvest_start_date = Column(Date)
    harvest_end_date = Column(Date)
    total_yield_kg = Column(Numeric(14,2))
    moisture_percent = Column(Numeric(5,2))
    operator_name = Column(String)  # สำหรับชื่อผู้ทำกิจกรรม
    problems_found = Column(Text)    # สำหรับระบุปัญหาที่พบ
    activity = relationship("ActivityEvent", back_populates="harvest_detail")

class SaleDetail(Base):
    __tablename__ = "sale_details"
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"), primary_key=True)
    mill_name = Column(String)
    product_name = Column(String)
    ticket_no = Column(String)
    plate_no = Column(String)
    sale_date = Column(Date, nullable=True)
    in_time = Column(Time)
    out_time = Column(Time)
    weight_total_kg = Column(Numeric(14,2))
    weight_net_kg = Column(Numeric(14,2))
    price_per_kg = Column(Numeric(14,2))
    total_income = Column(Numeric(14,2))
    activity = relationship("ActivityEvent", back_populates="sale_detail")

class Attachment(Base):
    __tablename__ = "attachments"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    activity_id = Column(UUID(as_uuid=True), ForeignKey("activity_events.id"))
    storage_key = Column(String)
    filename = Column(String)
    mime_type = Column(String)
    file_size = Column(Integer)
    uploaded_at = Column(DateTime, default=datetime.utcnow)
    activity = relationship("ActivityEvent", back_populates="attachments")

# ==========================================
# 6. EXTRAS (คู่มือ, แจ้งเตือน, อากาศ)
# ==========================================

class Manual(Base):
    __tablename__ = "manuals"
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String, unique=True)    
    icon_url = Column(String)             
    pdf_url = Column(String)              

class WeatherLog(Base):
    __tablename__ = "weather_logs"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    plot_id = Column(UUID(as_uuid=True), ForeignKey("plots.id"))
    temp = Column(Numeric(4,2))
    humidity = Column(Numeric(5,2))
    rain_mm = Column(Numeric(6,2))
    wind_speed = Column(Numeric(5,2))
    recorded_at = Column(DateTime, default=datetime.utcnow)
    plot = relationship("Plot", back_populates="weather_logs")

# ==========================================
# 7. SUPPORT & SECURITY
# ==========================================

class SupportTicket(Base):
    __tablename__ = "support_tickets"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=True)
    subject = Column(String)
    message = Column(Text)
    contact_email = Column(String)
    contact_phone = Column(String)
    status = Column(String, default="OPEN")
    created_at = Column(DateTime, default=datetime.utcnow)
    user = relationship("User", back_populates="support_tickets")

class PasswordResetToken(Base):
    __tablename__ = "password_reset_tokens"
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"))
    token = Column(String, unique=True)
    expires_at = Column(DateTime)
    is_used = Column(Boolean, default=False)

class AppSettings(Base):
    __tablename__ = "app_settings"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="CASCADE"), unique=True)
    
    theme = Column(String(20), default="light")
    font_family = Column(String(50), default="Kanit")
    font_size = Column(String(10), default="medium")
    language = Column(String(10), default="th")
    timezone = Column(String(50), default="Asia/Bangkok")
    date_format = Column(String(20), default="DD/MM/YYYY")
    area_unit = Column(String(20), default="rai")

class ApiAccessToken(Base):
    __tablename__ = "api_access_tokens"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    name = Column(String(255))
    device_id = Column(String(255))
    platform = Column(String(50))
    token_hash = Column(String(255), unique=True, nullable=False, index=True)
    last_used_at = Column(DateTime, nullable=True)
    expires_at = Column(DateTime, nullable=True)
    revoked_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())

# เพิ่มไว้ในส่วนที่ 7. SUPPORT & SECURITY ของไฟล์ models.py
class IssueReport(Base):
    __tablename__ = "issue_reports"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    
    # เก็บประเภทปัญหา (เช่น 'แอปค้าง', 'ข้อมูลผิด')
    title = Column(String(255), nullable=False)        
    # เก็บรายละเอียดที่เกษตรกรพิมพ์มา
    description = Column(Text, nullable=False)          
    # 🚩 สำคัญ: เก็บที่อยู่รูปภาพประกอบปัญหา
    image_url = Column(String(255), nullable=True)     
    # สถานะการจัดการ
    status = Column(String(50), default="PENDING")      
    
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # ความสัมพันธ์กับ User
    user = relationship("User")

class Notification(Base):
    __tablename__ = "notifications"
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), index=True)
    title = Column(String(255))
    message = Column(Text)
    is_read = Column(Boolean, default=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    user = relationship("User", back_populates="notifications")

class TrackingAdvice(Base):
    __tablename__ = "tracking_advices"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    page_key = Column(String)
    page_title = Column(String)
    farmer_name = Column(String)
    plot_id = Column(UUID(as_uuid=True))
    advice_message = Column(Text)
    sent_at = Column(DateTime, default=datetime.now)
    sent_by = Column(String, default="แอดมิน")
    advice_status = Column(String)
    activity_event_id = Column(String)
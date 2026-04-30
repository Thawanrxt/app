from sqlalchemy import Column, Integer, String, Float, ForeignKey, DateTime, Boolean, Text
from sqlalchemy.orm import relationship
from database import Base
import datetime

# --- 1. ระบบจัดการพื้นที่และข้อมูลพื้นฐาน (Master Data & Locations) ---

class Province(Base):
    __tablename__ = 'provinces'
    id = Column(Integer, primary_key=True)
    name_th = Column(String(100), nullable=False)
    districts = relationship("District", back_populates="province")

class District(Base):
    __tablename__ = 'districts'
    id = Column(Integer, primary_key=True)
    province_id = Column(Integer, ForeignKey('provinces.id'))
    name_th = Column(String(100), nullable=False)
    province = relationship("Province", back_populates="districts")

class RiceVariety(Base):
    __tablename__ = 'rice_varieties'
    id = Column(Integer, primary_key=True)
    variety_name = Column(String(100), nullable=False)
    growth_duration = Column(Integer)  # จำนวนวันโดยประมาณ
    plans = relationship("PlantingPlan", back_populates="variety")

# --- 2. ระบบจัดการผู้ใช้และเกษตรกร (User & Farmer) ---

class FarmerType(Base):
    __tablename__ = 'farmer_types'
    id = Column(Integer, primary_key=True)
    type_name = Column(String(50))  # เช่น เกษตรกรรายย่อย, วิสาหกิจ

class User(Base):
    __tablename__ = 'users'
    id = Column(Integer, primary_key=True)
    username = Column(String(50), unique=True, nullable=False)
    password = Column(String(255), nullable=False)
    email = Column(String(100), unique=True)
    role = Column(String(20))  # admin, farmer, staff
    name = Column(String(100))
    phone = Column(String(20))
    address = Column(Text)
    avatar = Column(String(255))
    farmer_type_id = Column(Integer, ForeignKey('farmer_types.id'))
    
    plots = relationship("Plot", back_populates="owner")
    registrations = relationship("FarmerRegistration", back_populates="user")

class FarmerRegistration(Base):
    __tablename__ = 'farmer_registrations'
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey('users.id'))
    registration_no = Column(String(50))
    issue_date = Column(DateTime)
    user = relationship("User", back_populates="registrations")

# --- 3. ระบบจัดการแปลงนาและแผนการปลูก (Plots & Planning) ---

class Plot(Base):
    __tablename__ = 'plots'
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey('users.id'))
    plot_name = Column(String(100))
    area_size = Column(Float)  # หน่วยไร่
    latitude = Column(Float)
    longitude = Column(Float)
    status = Column(String(20))  # Active, Inactive
    
    owner = relationship("User", back_populates="plots")
    plans = relationship("PlantingPlan", back_populates="plot")

class PlantingPlan(Base):
    __tablename__ = 'planting_plans'
    id = Column(Integer, primary_key=True)
    plot_id = Column(Integer, ForeignKey('plots.id'))
    variety_id = Column(Integer, ForeignKey('rice_varieties.id'))
    season_id = Column(Integer)
    start_date = Column(DateTime)
    status = Column(String(20)) # Ongoing, Harvested
    
    plot = relationship("Plot", back_populates="plans")
    variety = relationship("RiceVariety", back_populates="plans")
    activities = relationship("ActivityEvent", back_populates="plan")
    schedules = relationship("PlanSchedule", back_populates="plan")

class PlanSchedule(Base):
    __tablename__ = 'plan_schedules'
    id = Column(Integer, primary_key=True)
    plan_id = Column(Integer, ForeignKey('planting_plans.id'))
    task_name = Column(String(100))
    expected_date = Column(DateTime)
    is_completed = Column(Boolean, default=False)
    plan = relationship("PlantingPlan", back_populates="schedules")

# --- 4. บันทึกกิจกรรมภาคสนาม (Activity Events & Details) ---

class ActivityEvent(Base):
    __tablename__ = 'activity_events'
    id = Column(Integer, primary_key=True)
    plan_id = Column(Integer, ForeignKey('planting_plans.id'))
    activity_type_id = Column(Integer)
    note = Column(Text)
    created_at = Column(DateTime, default=datetime.datetime.utcnow)
    
    plan = relationship("PlantingPlan", back_populates="activities")
    attachments = relationship("Attachment", back_populates="activity")
    # รายละเอียดกิจกรรมเฉพาะด้าน (One-to-One / One-to-Many)
    soil_preps = relationship("SoilPrepDetail", back_populates="event")
    fertilizations = relationship("FertilizationDetail", back_populates="event")

class SoilPrepDetail(Base):
    __tablename__ = 'soil_prep_details'
    id = Column(Integer, primary_key=True)
    event_id = Column(Integer, ForeignKey('activity_events.id'))
    method = Column(String(100)) # ไถคราด, ไถพรวน
    event = relationship("ActivityEvent", back_populates="soil_preps")

class FertilizationDetail(Base):
    __tablename__ = 'fertilization_details'
    id = Column(Integer, primary_key=True)
    event_id = Column(Integer, ForeignKey('activity_events.id'))
    fertilizer_formula = Column(String(50))
    amount_kg = Column(Float)
    event = relationship("ActivityEvent", back_populates="fertilizations")

class PestControlDetail(Base):
    __tablename__ = 'pest_control_details'
    id = Column(Integer, primary_key=True)
    event_id = Column(Integer, ForeignKey('activity_events.id'))
    pest_type = Column(String(100))
    chemical_used = Column(String(100))

class WaterControlDetail(Base):
    __tablename__ = 'water_control_details'
    id = Column(Integer, primary_key=True)
    event_id = Column(Integer, ForeignKey('activity_events.id'))
    water_level_cm = Column(Float)

class Attachment(Base):
    __tablename__ = 'attachments'
    id = Column(Integer, primary_key=True)
    activity_id = Column(Integer, ForeignKey('activity_events.id'))
    file_path = Column(String(255))
    activity = relationship("ActivityEvent", back_populates="attachments")

# --- 5. ผลลัพธ์และการวิเคราะห์ (Harvest, Sales & Weather) ---

class HarvestDetail(Base):
    __tablename__ = 'harvest_details'
    id = Column(Integer, primary_key=True)
    plan_id = Column(Integer, ForeignKey('planting_plans.id'))
    harvest_date = Column(DateTime)
    total_weight_kg = Column(Float)
    moisture_percent = Column(Float)

class SaleDetail(Base):
    __tablename__ = 'sale_details'
    id = Column(Integer, primary_key=True)
    harvest_id = Column(Integer, ForeignKey('harvest_details.id'))
    price_per_kg = Column(Float)
    total_amount = Column(Float)
    buyer_name = Column(String(200))

class WeatherLog(Base):
    __tablename__ = 'weather_logs'
    id = Column(Integer, primary_key=True)
    temp = Column(Float)
    humidity = Column(Float)
    rainfall = Column(Float)
    recorded_at = Column(DateTime, default=datetime.datetime.utcnow)

# --- 6. ระบบสนับสนุน (Support & Others) ---

class SupportTicket(Base):
    __tablename__ = 'support_tickets'
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey('users.id'))
    subject = Column(String(200))
    message = Column(Text)
    status = Column(String(20)) # Open, Closed

class Manual(Base):
    __tablename__ = 'manuals'
    id = Column(Integer, primary_key=True)
    title = Column(String(200))
    content = Column(Text)
    category = Column(String(50))
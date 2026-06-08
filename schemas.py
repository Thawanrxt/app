from fastapi import FastAPI, Depends, HTTPException, status
from sqlalchemy.orm import Session
from uuid import UUID
from typing import List
from pydantic import BaseModel
from datetime import datetime
from typing import Optional
from datetime import date

class AppSettingsBase(BaseModel):
    theme: str = "light"
    font_family: str = "Kanit"
    font_size: str = "medium"
    language: str = "th"
    timezone: str = "Asia/Bangkok"
    date_format: str = "DD/MM/YYYY"
    area_unit: str = "rai"

class AppSettingsUpdate(AppSettingsBase):
    pass

class AppSettingsResponse(AppSettingsBase):
    class Config:
        from_attributes = True

class LoginRequest(BaseModel):
    username: str
    password: str
    name: Optional[str] = "Unknown Device"
    device_id: Optional[str] = None
    platform: Optional[str] = "web" # ios, android, web

class TokenResponse(BaseModel):
    access_token: str
    token_type: str 
    user_id: str  # 🚩 เพิ่มบรรทัดนี้ เพื่อให้ส่งค่า ID ออกไปได้
    username: Optional[str] = None

class UserCreate(BaseModel):
    username: str
    password: str
    phone: Optional[str] = None
    # คุณสามารถเพิ่มฟิลด์อื่นที่ต้องการได้ เช่น email: str

class IssueCreate(BaseModel):
    user_id: UUID
    title: str
    description: str

    class Config:
        from_attributes = True

# schemas.py
class ActivitySaveRequest(BaseModel):
    # --- ข้อมูลพื้นฐาน ---
    plan_id: UUID
    type_id: int
    operator_name: Optional[str] = None
    activity_date: Optional[date] = None
    plot_id: Optional[UUID] = None
    sequence_no: Optional[int] = 1
    description: Optional[str] = None
    issue_found: Optional[str] = None
    problems_found: Optional[str] = None

    # --- เตรียมดิน (type 1) ---
    straw_burning: Optional[str] = None
    land_leveling: Optional[str] = None
    soil_ph: Optional[float] = None
    soil_npk: Optional[str] = None
    soil_organic: Optional[str] = None

    # --- การจัดการน้ำ (type 2) ---
    water_level: Optional[str] = None

    # --- หว่านปุ๋ย (type 3) ---
    amount: Optional[str] = None
    fertilizer_type: Optional[str] = None
    fertilizer_formula: Optional[str] = None
    fertilizer_amount: Optional[str] = None

    # --- ศัตรูพืช (type 4) ---
    pest_type: Optional[str] = None
    chemical_common_name: Optional[str] = None
    chemical_comm_name: Optional[str] = None
    amount_used: Optional[float] = 0.0
    water_liters: Optional[float] = 0.0
    chemical_amount: Optional[str] = None
    ratio_per_water: Optional[float] = 0.0

    # --- โรคพืช (type 5) ---
    disease_type: Optional[str] = None
    disease_name: Optional[str] = None
    chemical_name: Optional[str] = None
    water_liter: Optional[float] = 0.0

    # --- การเก็บเกี่ยว (type 6) ---
    harvest_start_date: Optional[str] = None
    harvest_end_date: Optional[str] = None
    total_yield_kg: Optional[float] = 0.0
    moisture_percent: Optional[float] = 0.0

    # --- การขายข้าว (type 7) ---
    sale_date: Optional[str] = None
    mill_name: Optional[str] = None
    product_name: Optional[str] = None
    total_weight: Optional[float] = None
    net_weight_kg: Optional[float] = None
    price_per_kg: Optional[float] = None
    total_income: Optional[float] = None
    car_details: Optional[str] = None
    ticket_no: Optional[str] = None
    plate_no: Optional[str] = None

class PlotResponse(BaseModel):
    id: UUID
    farm_id: str
    plot_name: str
    # 🚩 ต้องมี 2 ฟิลด์นี้เพื่อให้หน้าหลักดึงไปโชว์ได้
    area_rai: int = 0
    area_ngan: int = 0
    area_sq_wa: int = 0
    area_sq_meter: int = 0
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    status: str

    class Config:
        from_attributes = True

class ActivityCreate(BaseModel):
    plan_id: UUID
    type_id: int
    # 🚩 ตรวจสอบชื่อให้ตรงกับที่หน้าบ้านส่งมา
    chemical_name: Optional[str] = None
    chemical_amount: Optional[float] = 0.0  # 🚩 หน้าบ้านส่ง chemical_amount
    water_liter: Optional[float] = 0.0      # 🚩 หน้าบ้านส่ง water_liter (ไม่มี s)
    pest_type: Optional[str] = None
    disease_name: Optional[str] = None     # 🚩 หน้าบ้านส่ง disease_name
    harvest_start_date: Optional[str] = None
    harvest_end_date: Optional[str] = None
    total_yield_kg: Optional[float] = 0.0
    moisture_percent: Optional[float] = 0.0
    operator_name: Optional[str] = None
    problems_found: Optional[str] = None
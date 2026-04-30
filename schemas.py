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
    plan_id: UUID
    type_id: int
    performed_by_name: str
    performed_at: date
    sequence_no: int = 1
    # รวมฟิลด์จากทุกกิจกรรมไว้ที่นี่ และกำหนดให้เป็น Optional ทั้งหมด
    straw_burning: Optional[str] = None
    soil_ph: Optional[float] = None
    water_level: Optional[str] = None
    fertilizer_type: Optional[str] = None
    issue_found: Optional[str] = None
    
class Config:
        from_attributes = True

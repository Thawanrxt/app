from pydantic import BaseModel, EmailStr
from typing import List, Optional
from datetime import datetime

class UserCreate(BaseModel):
    username: str
    password: str
    email: EmailStr
    name: str

class PlotCreate(BaseModel):
    user_id: int
    plot_name: str
    area_size: float
    latitude: float
    longitude: float

class ActivityCreate(BaseModel):
    plan_id: int
    activity_type_id: int
    note: Optional[str] = None

class FertilizationCreate(BaseModel):
    formula: str
    amount_kg: float
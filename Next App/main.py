from uuid import UUID
from datetime import date, timedelta
from fastapi import FastAPI, Depends, UploadFile, File, HTTPException
from fastapi.staticfiles import StaticFiles
from sqlalchemy.orm import Session
import os, uuid, shutil
import models, schemas, crud
from database import engine, get_db

# สร้างโฟลเดอร์เก็บไฟล์
UPLOAD_DIR = "static/attachments"
os.makedirs(UPLOAD_DIR, exist_ok=True)

# สร้าง Table ใน DB
models.Base.metadata.create_all(bind=engine)

app = FastAPI(title="Smart Farm Full API")
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.post("/users/", response_model=schemas.UserCreate)
def register_user(user: schemas.UserCreate, db: Session = Depends(get_db)):
    db_user = models.User(**user.dict())
    db.add(db_user)
    db.commit()
    return db_user

@app.post("/upload-photo/{event_id}")
async def upload_photo(event_id: int, file: UploadFile = File(...), db: Session = Depends(get_db)):
    file_ext = file.filename.split(".")[-1]
    file_name = f"{uuid.uuid4()}.{file_ext}"
    path = os.path.join(UPLOAD_DIR, file_name)
    
    with open(path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    
    new_att = models.Attachment(event_id=event_id, file_path=path)
    db.add(new_att)
    db.commit()
    return {"info": f"File saved at {path}"}

@app.get("/dashboard/summary/{plan_id}")
def get_summary(plan_id: int, db: Session = Depends(get_db)):
    return crud.get_total_cost(db, plan_id)

@app.get("/dashboard/main/{user_id}")
def get_main_dashboard(user_id: UUID, db: Session = Depends(get_db)):
    # ดึงข้อมูล User
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        # ถ้าหา User ไม่เจอจริงๆ จะส่ง 404 พร้อมบอกเหตุผล
        raise HTTPException(status_code=404, detail="User not found in Database")

    return {
        "full_name": user.full_name,
        "weather": {"temp": "28 - 35°C", "description": "มีเมฆเป็นส่วนมาก"}
    }

@app.get("/tracking/upcoming-activities/{user_id}")
def get_upcoming_activities(user_id: UUID, db: Session = Depends(get_db)):
    today = date.today()
    # ดึงแผนการปลูกของ User คนนี้
    plans = db.query(models.PlantingPlan).join(models.Plot).filter(
        models.Plot.user_id == user_id,
        models.PlantingPlan.status == "ACTIVE"
    ).all()

    upcoming = []
    for plan in plans:
        # สมมติกิจกรรม: ใส่ปุ๋ย (หลังปลูก 20 วัน)
        target_date = plan.start_date + timedelta(days=20)
        if today <= target_date <= (today + timedelta(days=14)):
            upcoming.append({
                "activity_name": "ใส่ปุ๋ยรอบที่ 1",
                "plot_name": plan.plot.plot_name,
                "due_date": target_date.strftime("%d %b %Y"),
                "days_left": (target_date - today).days
            })
    return upcoming
from sqlalchemy.orm import Session
from sqlalchemy import func
import models, schemas

def get_user(db: Session, user_id: int):
    return db.query(models.User).filter(models.User.id == user_id).first()

def create_plot(db: Session, plot: schemas.PlotCreate):
    db_plot = models.Plot(**plot.dict())
    db.add(db_plot)
    db.commit()
    db.refresh(db_plot)
    return db_plot

def get_total_cost(db: Session, plan_id: int):
    # คำนวณปริมาณปุ๋ยรวมในแผนนี้
    total_fert = db.query(func.sum(models.FertilizationDetail.amount_kg))\
        .join(models.ActivityEvent)\
        .filter(models.ActivityEvent.plan_id == plan_id).scalar()
    return {"total_fertilizer_used": total_fert or 0}
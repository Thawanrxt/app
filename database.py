from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker

# ⚠️ สำคัญ: ตรวจสอบ Username, Password และชื่อ Database ให้ตรงกับเครื่องของคุณ
# รูปแบบ: postgresql://username:password@localhost/database_name
SQLALCHEMY_DATABASE_URL = "postgresql://postgres:admin123@localhost:5432/postgres"

# สร้างการเชื่อมต่อ
engine = create_engine(SQLALCHEMY_DATABASE_URL)

# สร้าง Session สำหรับเรียกใช้ใน API
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# *** ตัวแปรนี้แหละครับที่ Error ถามหา (ห้ามลบ!) ***
Base = declarative_base()

# ฟังก์ชันสำหรับเปิด-ปิด Connection กับ Database อัตโนมัติ (api.py จะเรียกใช้ตัวนี้)
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
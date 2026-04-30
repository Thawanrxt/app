import bcrypt

# 1. กำหนดรหัสผ่านที่คุณต้องการใช้ (เช่น 12345678)
raw_password = "Mmeaw080646" 

# 2. ทำการ Hash ด้วย Library ที่เราใช้ใน api.py
byte_pwd = raw_password.encode('utf-8')
salt = bcrypt.gensalt()
hashed_pwd = bcrypt.hashpw(byte_pwd, salt)

# 3. ดูผลลัพธ์
print("--- ก๊อปปี้ค่าด้านล่างนี้ไปแปะใน DB ---")
print(hashed_pwd.decode('utf-8'))
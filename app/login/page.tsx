"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

export default function LoginPage() {
  const [showPermission, setShowPermission] = useState(false);
  const router = useRouter();

  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [errorMsg, setErrorMsg] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  // 🌟 ฟังก์ชัน Login
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");
    setIsLoading(true);

    try {
      /**
       * 🚩 จุดที่ต้องระวัง: 
       * 1. หากรันในเครื่องเดียวกัน ให้ใช้ "http://127.0.0.1:8000"
       * 2. หากรันผ่านมือถือ ให้ใช้ URL ngrok ล่าสุดที่แสดงใน Terminal ของคุณ
       */
      const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

      const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: { 
          "Content-Type": "application/json",
          "ngrok-skip-browser-warning": "true" // 🌟 เพิ่มบรรทัดนี้เพื่อข้ามหน้า Warning ของ ngrok
        },
        body: JSON.stringify({ username, password })
      });

      const result = await response.json();

      if (response.ok) {
  // ✅ ตรวจสอบว่า result.user_id มีค่าจริงๆ 
  if (result.user_id) {
    localStorage.setItem("user_id", result.user_id); 
    localStorage.setItem("username", result.username || username);
    
    alert("เข้าสู่ระบบสำเร็จ!");
    const policyAccepted = localStorage.getItem("policyAccepted");
    router.push(policyAccepted ? "/home" : "/policy");
  } else {
    // กรณี Login ผ่านแต่ Backend ไม่ได้ส่ง ID มา (ป้องกัน error ตัว u)
    setErrorMsg("ระบบปลายทางส่งข้อมูลไม่ครบ (Missing User ID)");
  }
}
    } catch (error) {
      // 🚩 หากเข้าส่วนนี้ แสดงว่าต่อ API ไม่ติด (Server ปิดอยู่ หรือ URL ผิด)
      setErrorMsg("ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาเปิด Backend หรือเช็ค URL");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    const timer = setTimeout(() => setShowPermission(true), 1000);
    return () => clearTimeout(timer);
  }, []);

  const requestLocation = () => {
    if (!navigator.geolocation) {
      setShowPermission(false);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      () => setShowPermission(false),
      () => setShowPermission(false)
    );
  };

  return (
    <div className="app-wrapper">
      <div className="phone-frame">
        <div className="login-page">
          <div className="login-hero" />
          <div className="login-card">
            <h1>เข้าสู่ระบบ</h1>

            <form onSubmit={handleLogin} style={{ width: '100%' }}>
              <div className="form-group">
                <label>ชื่อผู้ใช้</label>
                <input 
                  type="text" 
                  placeholder="xxxxxx" 
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  required
                />
              </div>

              <div className="form-group">
                <label>รหัสผ่าน</label>
                <input 
                  type="password" 
                  placeholder="••••••••" 
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>

              <p className="forgot" onClick={() => router.push("/contact")} style={{ cursor: "pointer" }}>
                ลืมรหัสผ่าน / ติดต่อเจ้าหน้าที่
              </p>

              {errorMsg && (
                <div style={{ 
                  backgroundColor: "#ffebee", 
                  color: "#c62828", 
                  padding: "10px", 
                  borderRadius: "8px", 
                  fontSize: "12px", 
                  textAlign: "center", 
                  marginBottom: "15px",
                  border: "1px solid #ffcdd2"
                }}>
                  ⚠️ {errorMsg}
                </div>
              )}

              <button type="submit" className="login-btn" disabled={isLoading}>
                {isLoading ? "กำลังตรวจสอบข้อมูล..." : "เข้าสู่ระบบ"}
              </button>
            </form>
          </div>

          {showPermission && (
            <div className="permission-overlay">
              <div className="permission-modal">
                <img src="/location-icon.svg" className="permission-icon" alt="location" />
                <h4>ขอเข้าถึงตำแหน่ง</h4>
                <p>อนุญาตให้แอปเข้าถึงตำแหน่งขณะใช้งาน เพื่อระบุพิกัดแปลงนาของคุณ</p>
                <div className="permission-buttons">
                  <button className="allow-btn" onClick={requestLocation}>อนุญาตตลอด</button>
                  <button className="allow-outline-btn" onClick={requestLocation}>อนุญาตขณะใช้งาน</button>
                  <button className="skip-btn" onClick={() => setShowPermission(false)}>ภายหลัง</button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
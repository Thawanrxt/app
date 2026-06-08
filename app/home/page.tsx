"use client";

import Image from "next/image";
import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { Bell } from 'lucide-react';
import "./home.css";
import BottomNav from "../components/BottomNav";


const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";


export default function HomePage() {
  const router = useRouter();
  const WEATHER_API_KEY = process.env.NEXT_PUBLIC_WEATHER_API;
  
  const [dashboardData, setDashboardData] = useState<any>(null);
  const [activePlots, setActivePlots] = useState<any[]>([]); 
  const [upcomingActivities, setUpcomingActivities] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [weather, setWeather] = useState<any>(null);
  const [unreadCount, setUnreadCount] = useState(0);

  const [profileImg, setProfileImg] = useState<string>("/duck.jpg");
  useEffect(() => {
  const savedImg = localStorage.getItem('user_profile_img');
  if (savedImg) {
    setProfileImg(savedImg);
  }
}, []);

  // --- 2. ฟังก์ชันดึงข้อมูล (ดึงทุกอย่างในฟังก์ชันเดียว) ---
  const fetchData = useCallback(async () => {
    setIsLoading(true);
    const userId = localStorage.getItem("user_id");

    if (!userId) {
      router.push("/login");
      return;
    }

    try {
      // 🚩 ดึงข้อมูลชื่อผู้ใช้
      const dashRes = await fetch(`${API_URL}/dashboard/main/${userId}`, {
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      if (dashRes.ok) setDashboardData(await dashRes.json());

      // 🚩 ดึงรายการแปลงนา (แผนการปลูก)
      const plotsRes = await fetch(`${API_URL}/tracking/active-plans/${userId}`, {
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      if (plotsRes.ok) {
        const plotsData = await plotsRes.json();
        console.log("🌾 แผนที่ดึงมาได้:", plotsData);
        setActivePlots(Array.isArray(plotsData) ? plotsData : []);
      }

      // 🚩 ดึงกิจกรรมที่ต้องทำถัดไป
      const activitiesRes = await fetch(`${API_URL}/tracking/upcoming-activities/${userId}`, {
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      if (activitiesRes.ok) {
        const activitiesData = await activitiesRes.json();
        setUpcomingActivities(Array.isArray(activitiesData) ? activitiesData : []);
      }

      const notiRes = await fetch(`${API_URL}/notifications/${userId}`, {
          headers: { "ngrok-skip-browser-warning": "true" }
        });
        if (notiRes.ok) {
          const notiData = await notiRes.json();
          const unread = notiData.filter((n: any) => !n.is_read).length;
          setUnreadCount(unread);
        }

      } catch (error) {
        console.error("❌ เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
      } finally {
        setIsLoading(false);
      }
    }, [router]);

  // --- 3. ดึงสภาพอากาศ (แยก Logic ออกมาเพื่อให้โค้ดสะอาด) ---
  useEffect(() => {
    if (navigator.geolocation && WEATHER_API_KEY) {
      navigator.geolocation.getCurrentPosition(async (position) => {
        const { latitude, longitude } = position.coords;
        try {
          const res = await fetch(
            `https://api.openweathermap.org/data/2.5/weather?lat=${latitude}&lon=${longitude}&appid=${WEATHER_API_KEY}&units=metric&lang=th`
          );
          if (res.ok) {
            const data = await res.json();
            setWeather({
              temp: Math.round(data.main.temp),
              description: data.weather[0].description,
              icon: data.weather[0].icon,
            });
          }
        } catch (err) { console.error("Weather error:", err); }
      });
    }
  }, [WEATHER_API_KEY]);

  // --- 4. สั่งให้ fetchData ทำงานเมื่อเปิดหน้าจอ ---
  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // --- 5. ฟังก์ชันลบแปลงนา ---
  const handleDeletePlan = async (planId: string, plotName: string) => {
    const isConfirm = window.confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบแปลง "${plotName}"?`);
    if (!isConfirm) return;

    try {
      const response = await fetch(`${API_URL}/tracking/plan/${planId}`, {
        method: "DELETE",
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      if (response.ok) {
        alert("🗑️ ลบแปลงนาสำเร็จ!");
        fetchData(); // ดึงข้อมูลใหม่ทันทีหลังลบ
      }
    } catch (error) {
      console.error("Delete error:", error);
    }
  };

  const userName = dashboardData?.full_name || "นาย สมหมาย หมายปอง";

  const manuals = [
    { title: "พันธุ์ข้าว", img: "/rice1.jpg", link: "/manual/rice" },
    { title: "การจัดการดิน", img: "/soil.jpg", link: "/manual/soil" },
    { title: "การใช้น้ำ", img: "/water.jpg", link: "/manual/water" },
    { title: "การจัดการธาตุอาหาร", img: "/fertilizer.jpg", link: "/manual/fertilizer" },
    { title: "การจัดการศัตรูพืช", img: "/bug.jpg", link: "/manual/bug" },
    { title: "โรคข้าว", img: "/weed.jpg", link: "/manual/disease" },
    { title: "การเก็บเกี่ยว", img: "/harvest.jpg", link: "/manual/harvest" },
  ];

  return (
    <>
      <div className="content">

         {/* Header - แก้ไขใหม่ให้เชื่อมโยงกับหน้าโปรไฟล์สวยๆ */}
          <div className="header">
  <div className="profile" onClick={() => router.push("/profile")} style={{ cursor: 'pointer' }}>
    <div className="avatar">
      <img 
        src={profileImg} 
        alt="User Profile" 
        style={{ width: '100%', height: '100%', borderRadius: '50%', objectFit: 'cover' }} 
      />
    </div>
    <div className="user-info" style={{ display: 'flex', flexDirection: 'column', marginLeft: '10px' }}>
      <span className="username" style={{ fontSize: '16px', fontWeight: 'bold' }}>{userName}</span>
      <span style={{ fontSize: '12px', color: '#666' }}>เกษตรกรอัจฉริยะ</span>
    </div>
  </div>

  {/* 🚩 แก้ไขจุดนี้: กระดิ่งแจ้งเตือนพร้อมจุดแดง */}
  <div 
    className="notify" 
    onClick={() => router.push("/notifications")} 
    style={{ position: 'relative', cursor: 'pointer', fontSize: '24px' }}
  >
    <Bell size={24} />
    {unreadCount > 0 && (
      <span style={{
        position: 'absolute',
        top: '-5px',
        right: '-5px',
        backgroundColor: '#e53935',
        color: 'white',
        borderRadius: '50%',
        width: '18px',
        height: '18px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '11px',
        fontWeight: 'bold',
        border: '2px solid #fff'
      }}>
        {unreadCount}
      </span>
    )}
  </div>
</div>

<div className="weather-card" onClick={() => router.push("/weather")} style={{ cursor: "pointer" }}>
  <div className="weather-info">
    {/* 🚩 แก้ไขบรรทัดนี้: เช็คว่า weather มีค่าจริงไหม */}
    <span className="temp">{weather !== null ? `${weather.temp}°C` : "กำลังโหลด..."}</span>
    <span className="desc">{weather !== null ? weather.description : "กรุณาเปิด GPS"}</span>
  </div>
  <div className="weather-icon">
    {weather?.icon ? (
      <img src={`https://openweathermap.org/img/wn/${weather.icon}@2x.png`} alt="weather" width={40} />
    ) : "⛅"}
  </div>
</div>

          {/* Section: แผนการปลูก */}
          <div className="section">
            <div className="section-header">
              <h3>แผนการปลูก</h3>
              <span style={{ cursor: "pointer", color: "#4CAF50" }} onClick={() => router.push("/plant-tracking")}>
                ติดตามการปลูกข้าว ➔
              </span>
            </div>

            {isLoading ? (
              <div className="empty-box">กำลังโหลดข้อมูล...</div>
            ) : (activePlots && activePlots.length > 0) ? (
              <>
                {activePlots.map((plot) => (
  <div key={plot.id} style={{ backgroundColor: "#f9fff9", border: "1px solid #4CAF50", borderRadius: "10px", padding: "15px", marginBottom: "12px", position: "relative" }}>
    
    {plot.plan_id && (
      <button
        onClick={() => handleDeletePlan(plot.plan_id, plot.plot_name)}
        style={{ position: "absolute", top: "12px", right: "12px", background: "none", border: "none", color: "#e53935", fontSize: "16px", cursor: "pointer" }}
      >🗑️</button>
    )}

    <div style={{ marginBottom: "15px" }}>
      <h4 style={{ margin: "0 0 5px 0", color: "#2e7d32", fontSize: "18px", display: "flex", alignItems: "center", gap: "8px" }}>
        🌾 แปลง: {plot.plot_name}
      </h4>
      <p style={{ fontSize: '11px', color: '#2e7d32', margin: '2px 0 8px 28px', fontWeight: 'bold' }}>
        {plot.farm_id || "FARM-" + plot.plot_id?.substring(0, 8).toUpperCase()}
      </p>
      <p style={{ margin: 0, fontSize: "14px", color: "#666", marginLeft: "28px" }}>
        ขนาดพื้นที่: {plot.area_rai || 0} ไร่ {plot.area_ngan || 0} งาน {plot.area_sq_wa || 0} ตร.ว.
      </p>
      {!plot.has_plan && (
        <p style={{ margin: "8px 0 0 28px", fontSize: "12px", color: "#f57c00" }}>
          ⚠️ ยังไม่มีแผนการปลูก — กรุณาสร้างแผนเพื่อเริ่มบันทึกกิจกรรม
        </p>
      )}
    </div>

    {plot.has_plan ? (
      <button
        className="primary-btn"
        onClick={() => router.push(`/plant-tracking/${plot.plan_id}`)}
        style={{ width: "100%", padding: "12px", borderRadius: "8px", fontWeight: "bold" }}
      >
        📝 บันทึกกิจกรรมแปลงนี้
      </button>
    ) : (
      <button
        onClick={() => router.push(`/create-plan?plot_id=${plot.plot_id}&plot_name=${encodeURIComponent(plot.plot_name)}`)}
        style={{ width: "100%", padding: "12px", borderRadius: "8px", fontWeight: "bold", backgroundColor: "#f57c00", color: "#fff", border: "none", cursor: "pointer" }}
      >
        🌱 สร้างแผนการปลูก
      </button>
    )}
  </div>
))}
                <button onClick={() => router.push("/create-plan")} className="add-plot-btn-dashed" style={{ width: "100%", padding: "12px", border: "2px dashed #4CAF50", color: "#4CAF50", borderRadius: "8px", background: "none", fontWeight: "bold", cursor: "pointer" }}>
                  + เพิ่มแปลงใหม่
                </button>
              </>
            ) : (
              <div className="banner" onClick={() => router.push("/create-plan")} style={{ cursor: "pointer", position: "relative", height: "120px", borderRadius: "10px", overflow: "hidden" }}>
                <Image src="/rice-banner.jpg" alt="plant" fill style={{ objectFit: "cover" }} />
                <div className="banner-overlay" style={{ position: "absolute", bottom: 0, width: "100%", padding: "10px", background: "rgba(0,0,0,0.5)", textAlign: "center", color: "#fff" }}>
                   + เริ่มแผนการปลูกแรกของคุณ
                </div>
              </div>
            )}
          </div>

          {/* 🌟 Section: กิจกรรมที่ต้องทำถัดไป 🌟 */}
          <div className="section">
            <h3>กิจกรรมถัดไปที่ต้องทำ</h3>
            
            {isLoading ? (
              <div className="empty-box">กำลังโหลด...</div>
            ) : (upcomingActivities && upcomingActivities.length > 0) ? (
              <div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
                {upcomingActivities.map((task, index) => (
                  <div 
                    key={index}
                    className="activity-card" 
                    style={{ display: "flex", justifyContent: "space-between", padding: "12px", backgroundColor: "#fff", border: "1px solid #eee", borderRadius: "8px", cursor: "pointer" }}
                    onClick={() =>router.push(`/plant-tracking/${task.plan_id}?type=${task.activity_type_id}`)}
                  >

                    <div>
                      <div style={{ fontWeight: "600", color: "#333" }}>{task.activity_name}</div>
                      <div style={{ fontSize: "12px", color: "#666" }}>🌾 แปลง: {task.plot_name}</div>
                    </div>
                    <div style={{ textAlign: "right" }}>
                      <div style={{ color: task.days_left <= 0 ? "#d32f2f" : "#2e7d32", fontWeight: "bold", fontSize: "14px" }}>
                        {task.days_left === 0 ? "วันนี้" : task.days_left < 0 ? "เลยกำหนด" : `อีก ${task.days_left} วัน`}
                      </div>
                      <div style={{ fontSize: "11px", color: "#999" }}>{task.due_date}</div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="empty-box">ไม่พบข้อมูลกิจกรรมที่ต้องทำในช่วงนี้</div>
            )}
          </div>

          {/* Manuals */}
          <div className="section">
            <h3>คู่มือเกษตร</h3>

            <div className="manual-list">
              {manuals.map((manual, index) => (
                <div
                  key={index}
                  className="manual-card"
                  onClick={() => router.push(manual.link)}
                  style={{ cursor: "pointer" }}
                >
                  <Image
                    src={manual.img}
                    alt={manual.title}
                    fill
                    style={{ objectFit: "cover" }}
                  />

                  <div
                    className={`manual-overlay ${
                      index % 2 === 0 ? "left" : "right"
                    }`}
                  >
                    <div className="manual-label">
                      {manual.title}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

        </div>

        <BottomNav activePath="/home" />

    </>
  );
}
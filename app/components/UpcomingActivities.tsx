"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

// 1. กำหนดรูปแบบข้อมูลให้ตรงกับที่ API ส่งมา
interface UpcomingActivity {
  plan_id?: string;
  id?: string;
  plot_name: string;
  activity_name: string;
  due_date: string;
  days_left: number;
}

export default function UpcomingActivities() {
  const [activities, setActivities] = useState<UpcomingActivity[]>([]);
  const [loading, setLoading] = useState(true);

  // 2. ใช้ useEffect ดึงข้อมูลเมื่อหน้าจอถูกโหลด
  useEffect(() => {
    const userId = localStorage.getItem("user_id"); // ดึง id ผู้ใช้ที่ login ไว้
    
    if (userId) {
      // 🌟 เพิ่ม cache: "no-store" เพื่อให้ดึงข้อมูลใหม่เสมอ
      fetch(`http://127.0.0.1:8000/tracking/upcoming-activities/${userId}`, { cache: "no-store" })
        .then((res) => res.json())
        .then((data) => {
          // 🌟 ป้องกัน Error ถ้า API ส่งข้อมูลมาไม่ใช่ Array
          setActivities(Array.isArray(data) ? data : (data?.data || []));
          setLoading(false);
        })
        .catch((err) => {
          console.error("Error fetching activities:", err);
          setLoading(false);
        });
    }
  }, []);

  if (loading) return <p>กำลังโหลดกิจกรรม...</p>;
  if (activities.length === 0) return <p>ไม่มีกิจกรรมที่กำลังจะมาถึง</p>;

  return (
    <div style={{ padding: "20px" }}>
      <h2 style={{ marginBottom: "15px", fontWeight: "bold" }}>กิจกรรมที่กำลังจะมาถึง</h2>
      <div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
        {activities.map((item, index) => (
          <Link key={index} 
            href={(item.plan_id || item.id) ? `/plant-tracking/${encodeURIComponent(item.plan_id || item.id || "")}` : "/plant-tracking"}
            style={{
            border: "1px solid #e0e0e0",
            borderRadius: "12px",
            padding: "15px",
            background: item.days_left <= 1 ? "#fff3e0" : "#fff", // ไฮไลท์สีถ้าใกล้ถึงวัน
            textDecoration: "none",
            color: "inherit",
            display: "block"
          }}>
            <div style={{ display: "flex", justifyContent: "space-between" }}>
              <strong style={{ fontSize: "16px" }}>{item.plot_name}</strong>
              <span style={{ color: "#2e7d32", fontWeight: "bold" }}>
                {item.days_left === 0 ? "วันนี้" : `อีก ${item.days_left} วัน`}
              </span>
            </div>
            <p style={{ margin: "5px 0 0", color: "#666" }}>{item.activity_name}</p>
            <small style={{ color: "#999" }}>กำหนดวันที่: {item.due_date}</small>
          </Link>
        ))}
      </div>
    </div>
  );
}
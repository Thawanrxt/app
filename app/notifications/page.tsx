"use client";

import { useEffect, useState, Suspense } from "react"; // เพิ่ม Suspense ครอบเพื่อให้ใช้งาน useSearchParams ได้ไม่มีปัญหา
import { useRouter, useSearchParams } from "next/navigation";
import { UserRound, BellRing, AlertTriangle, Lightbulb } from "lucide-react";
import styles from "./notifications.module.css";
import BackButton from "../components/BackButton";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

function NotificationsContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const from = searchParams.get("from");

  const [notifications, setNotifications] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedAdvice, setSelectedAdvice] = useState<any | null>(null);

  // 🚩 ฟังก์ชันจัดการปุ่มย้อนกลับ
  const handleBack = () => {
    if (from === "settings") {
      router.push("/settings"); // หรือ path หน้าตั้งค่า/โปรไฟล์ของคุณ
    } else {
      router.push("/home");
    }
  };

  const handleNotiClick = async (item: any) => {
    try {
      await fetch(`${API_URL}/notifications/${item.id}/read`, {
        method: "PATCH",
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      setNotifications(prev =>
        prev.map(n => n.id === item.id ? { ...n, is_read: true } : n)
      );
    } catch (err) {
      console.error("Update error:", err);
    }

    if (item.type === "advice") {
      setSelectedAdvice(item);
      return;
    }

    if (item.plot_id && item.target_type) {
      router.push(`/plant-tracking/${item.plot_id}?type=${item.target_type}`);
    }
  };

  useEffect(() => {
    const fetchNotifs = async () => {
      const userId = localStorage.getItem("user_id");
      if (!userId) {
        router.push("/login");
        return;
      }

      try {
        const res = await fetch(`${API_URL}/notifications/${userId}`, {
          headers: { "ngrok-skip-browser-warning": "true" }
        });
        if (res.ok) {
          const data = await res.json();
          setNotifications(data);
        }
      } catch (err) {
        console.error("Fetch error:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchNotifs();
  }, [router]);

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        {/* 🚩 แก้ไข: เปลี่ยน onClick ให้เรียกใช้ handleBack */}
        <BackButton onClick={handleBack} className={styles.backBtn} />
        <h1>แจ้งเตือน</h1>
      </header>

      <main className={styles.content}>
        {loading ? (
          <div className={styles.emptyText}>กำลังดึงข้อมูล...</div>
        ) : notifications.length > 0 ? (
          notifications.map((item) => (
            <article
              key={item.id}
              className={`${styles.card} ${item.type === 'urgent' ? styles.urgent : ''}`}
              onClick={() => handleNotiClick(item)}
              style={{
                cursor: "pointer",
                borderLeft: item.type === "advice" ? "4px solid #1976d2" : undefined,
                opacity: item.is_read ? 0.65 : 1,
              }}
            >
              <div className={styles.avatarWrap}>
                {item.type === "urgent" ? (
                  <BellRing size={32} color="#ff9800" />
                ) : item.type === "warning" ? (
                  <AlertTriangle size={32} color="#f44336" />
                ) : item.type === "advice" ? (
                  <Lightbulb size={32} color="#1976d2" />
                ) : (
                  <UserRound size={32} color="#2e7d32" />
                )}
              </div>

              <div className={styles.messageBody}>
                <h2 style={{ color: item.type === "advice" ? "#1565c0" : undefined }}>
                  {item.title}
                </h2>
                <div className={styles.metaRow}>
                  <p>{item.message}</p>
                  <span>{item.created_at || item.due_date}</span>
                </div>
              </div>
              {!item.is_read && <div className={styles.unreadDot} />}
            </article>
          ))
        ) : (
          <div className={styles.emptyText}>📭 ไม่มีแจ้งเตือนใหม่</div>
        )}
      </main>

      {selectedAdvice && (
        <div
          onClick={() => setSelectedAdvice(null)}
          style={{
            position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.5)",
            display: "flex", alignItems: "center", justifyContent: "center",
            zIndex: 1000, padding: "24px",
          }}
        >
          <div
            onClick={e => e.stopPropagation()}
            style={{
              backgroundColor: "#fff", borderRadius: "16px",
              padding: "24px", width: "100%", maxWidth: "380px",
              maxHeight: "80vh", overflowY: "auto",
              boxShadow: "0 8px 32px rgba(0,0,0,0.2)",
            }}
          >
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px" }}>
              <h2 style={{ fontSize: "16px", fontWeight: "bold", color: "#1565c0", margin: 0 }}>
                💡 คำแนะนำจากเจ้าหน้าที่
              </h2>
              <button
                onClick={() => setSelectedAdvice(null)}
                style={{ background: "none", border: "none", fontSize: "20px", cursor: "pointer", color: "#666" }}
              >✕</button>
            </div>

            <div style={{ backgroundColor: "#f0f4ff", borderRadius: "8px", padding: "10px 14px", marginBottom: "16px" }}>
              <p style={{ margin: "0 0 4px 0", fontSize: "13px", color: "#555" }}>
                🌾 <strong>แปลง:</strong> {selectedAdvice.plot_name || "ไม่ระบุแปลง"}
              </p>
              <p style={{ margin: "0 0 4px 0", fontSize: "13px", color: "#555" }}>
                📋 <strong>หัวข้อ:</strong> {selectedAdvice.activity_title || "ไม่ระบุหัวข้อ"}
              </p>
              {selectedAdvice.farmer_name && (
                <p style={{ margin: 0, fontSize: "13px", color: "#555" }}>
                  👤 <strong>ถึง:</strong> {selectedAdvice.farmer_name}
                </p>
              )}
            </div>

            <p style={{ fontSize: "15px", lineHeight: "1.7", color: "#333", whiteSpace: "pre-wrap" }}>
              {selectedAdvice.message}
            </p>
            {selectedAdvice.plot_id && selectedAdvice.target_type && (
              <button
                onClick={() => {
                  setSelectedAdvice(null);
                  router.push(`/plant-tracking/${selectedAdvice.plot_id}?type=${selectedAdvice.target_type}`);
                }}
                style={{
                  marginTop: "20px", width: "100%", padding: "12px",
                  backgroundColor: "#1976d2", color: "#fff", border: "none",
                  borderRadius: "8px", fontSize: "15px", fontWeight: "bold", cursor: "pointer",
                }}
              >
                ไปที่หน้ากิจกรรม →
              </button>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

// 🚩 ต้องครอบด้วย Suspense เพราะมีการใช้ useSearchParams
export default function NotificationsPage() {
  return (
    <Suspense fallback={<div>กำลังโหลด...</div>}>
      <NotificationsContent />
    </Suspense>
  );
}
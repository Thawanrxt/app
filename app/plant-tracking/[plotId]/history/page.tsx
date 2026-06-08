"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { Clock, History, User, AlertCircle } from "lucide-react";
import styles from "../plot-detail.module.css";
import BackButton from "../../../components/BackButton";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

export default function ActivityHistoryPage() {
  const router = useRouter();
  const { plotId } = useParams();
  const [history, setHistory] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchHistory = async () => {
      try {
        const res = await fetch(`${API_URL}/tracking/plan/${plotId}/history`, {
          headers: { "ngrok-skip-browser-warning": "true" }
        });
        if (res.ok) {
          setHistory(await res.json());
        }
      } catch (e) {
        console.error("Fetch history error:", e);
      } finally {
        setIsLoading(false);
      }
    };
    fetchHistory();
  }, [plotId]);

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.back()} className={styles.backBtn} />
        <h1>ประวัติการบันทึก</h1>
      </header>

      <main className={styles.content} style={{ padding: "15px" }}>
        {isLoading ? (
          <p style={{ textAlign: "center", marginTop: "20px" }}>กำลังโหลดประวัติ...</p>
        ) : history.length === 0 ? (
          <div style={{ textAlign: "center", marginTop: "50px", color: "#999" }}>
            <History size={48} style={{ marginBottom: "10px", opacity: 0.5 }} />
            <p>ยังไม่มีรายการที่บันทึกไว้</p>
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "15px" }}>
            {history.map((item) => (
              <div key={item.id} style={{ 
                backgroundColor: "white", 
                padding: "15px", 
                borderRadius: "12px", 
                boxShadow: "0 2px 6px rgba(0,0,0,0.05)",
                borderLeft: "5px solid #2e7d32" 
              }}>
                <div style={{ display: "flex", justifyContent: "space-between", marginBottom: "8px" }}>
                  <strong style={{ fontSize: "17px", color: "#2e7d32" }}>{item.activity_name}</strong>
                  <span style={{ fontSize: "13px", color: "#666", display: "flex", alignItems: "center", gap: "4px" }}>
                    <Clock size={14} /> {item.performed_at}
                  </span>
                </div>
                
                <div style={{ fontSize: "14px", color: "#444", marginBottom: "5px", display: "flex", alignItems: "center", gap: "6px" }}>
                  <User size={14} /> ผู้ทำ: {item.operator}
                </div>

                {item.issue !== "-" && (
                  <div style={{ 
                    fontSize: "13px", 
                    color: "#d32f2f", 
                    backgroundColor: "#fff5f5", 
                    padding: "8px", 
                    borderRadius: "6px",
                    marginTop: "8px",
                    display: "flex",
                    gap: "6px"
                  }}>
                    <AlertCircle size={14} style={{ flexShrink: 0, marginTop: "2px" }} />
                    <span>ปัญหา: {item.issue}</span>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
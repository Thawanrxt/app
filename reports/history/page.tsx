"use client";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import styles from "../reports.module.css";

export default function IssueHistoryPage() {
    // 🚩 กำหนดค่าเริ่มต้นเป็น Array ว่างอย่างชัดเจน
    const [issues, setIssues] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const router = useRouter();

    useEffect(() => {
        const userId = localStorage.getItem("user_id");
        if (userId) {
            fetch(`http://localhost:8000/reports/history/${userId}`)
                .then(res => res.json())
                .then(data => {
                    // 🚩 ตรวจสอบว่า data ที่ได้รับเป็น Array หรือไม่ก่อน set state
                    if (Array.isArray(data)) {
                        setIssues(data);
                    } else {
                        console.error("API returned non-array data:", data);
                        setIssues([]); // ถ้าไม่ใช่ Array ให้เซ็ตเป็นค่าว่างเพื่อไม่ให้ map พัง
                    }
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    setIssues([]);
                })
                .finally(() => setIsLoading(false));
        } else {
            setIsLoading(false);
        }
    }, []);

    return (
        <div className={styles.page}>
            <header className={styles.header} style={{ display: 'flex', alignItems: 'center', padding: '10px 15px' }}>
                <button 
                    onClick={() => router.back()} 
                    style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer' }}
                >
                    ←
                </button>
                <h1 style={{ fontSize: '18px', marginLeft: '10px' }}>ประวัติการแจ้งปัญหา</h1>
            </header>

            <main className={styles.content} style={{ padding: '15px' }}>
                {isLoading ? (
                    <div style={{ textAlign: 'center', marginTop: '50px' }}>กำลังโหลดข้อมูล...</div>
                ) : !Array.isArray(issues) || issues.length === 0 ? (
                    <div style={{ textAlign: 'center', marginTop: '50px', color: '#999' }}>
                        ยังไม่มีประวัติการแจ้งปัญหา
                    </div>
                ) : (
                    // 🚩 ใช้ Optional Chaining และเช็ค Array ซ้ำอีกครั้งเพื่อความปลอดภัย
                    issues.map((item: any) => (
                        <div key={item.id} className={styles.issueCard} style={{ 
                            padding: '15px', 
                            backgroundColor: '#fff', 
                            borderRadius: '10px', 
                            marginBottom: '15px',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
                            borderLeft: item.status === 'PENDING' ? '5px solid #ffa500' : '5px solid #4CAF50'
                        }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                <strong style={{ color: '#333' }}>{item.title || "ไม่ระบุหัวข้อ"}</strong>
                                <span style={{ fontSize: '12px', color: '#888' }}>{item.date}</span>
                            </div>
                            <p style={{ fontSize: '14px', color: '#666', marginTop: '8px' }}>{item.description}</p>
                            
                            <div style={{ marginTop: '12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ 
                                    padding: '3px 10px', 
                                    borderRadius: '15px', 
                                    fontSize: '11px',
                                    fontWeight: '600',
                                    backgroundColor: item.status === 'PENDING' ? '#fff3e0' : '#e8f5e9',
                                    color: item.status === 'PENDING' ? '#ef6c00' : '#2e7d32'
                                }}>
                                    {item.status === 'PENDING' ? '● กำลังดำเนินการ' : '✔ แก้ไขแล้ว'}
                                </span>
                                {item.image_url && (
                                    <span style={{ fontSize: '12px', color: '#4CAF50', display: 'flex', alignItems: 'center', gap: '4px' }}>
                                        📷 รูปประกอบ
                                    </span>
                                )}
                            </div>
                        </div>
                    ))
                )}
            </main>
        </div>
    );
}
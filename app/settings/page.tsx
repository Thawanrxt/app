"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  Bell,
  Lock,
  CircleHelp,
  LogOut,
  ChevronRight,
  UserPen
} from "lucide-react";
import styles from "./settings.module.css";
import BottomNav from "../components/BottomNav";
import BackButton from "../components/BackButton";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

export default function SettingsPage() {
  const router = useRouter();
  const [fullName, setFullName] = useState("กำลังโหลด...");

  useEffect(() => {
    const fetchUserData = async () => {
      const userId = localStorage.getItem("user_id");
      if (!userId) {
        router.push("/login");
        return;
      }

      try {
        const res = await fetch(`${API_URL}/dashboard/main/${userId}`, {
          headers: { "ngrok-skip-browser-warning": "true" }
        });

        if (res.ok) {
          const data = await res.json();
          setFullName(data.full_name || "ไม่ระบุชื่อ");
        }
      } catch (error) {
        console.error("Error fetching profile:", error);
        setFullName("ไม่สามารถโหลดชื่อได้");
      }
    };

    fetchUserData();
  }, [router]);

  const handleLogout = () => {
    if (window.confirm("คุณต้องการออกจากระบบใช่หรือไม่?")) {
      localStorage.clear();
      router.push("/login");
    }
  };

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.back()} className={styles.backBtn} />
        <h1>ตั้งค่า</h1>
      </header>

      <main className={styles.content}>
        <section className={styles.profileSection} onClick={() => router.push("/profile")}>
          <div className={styles.avatar}>👤</div>
          <div className={styles.profileInfo}>
            <h3>{fullName}</h3> 
            <p>แก้ไขข้อมูลส่วนตัว</p>
          </div>
          <ChevronRight size={20} color="#ccc" />
        </section>

        <div className={styles.groupLabel}>บัญชีและการใช้งาน</div>
        <div className={styles.menuGroup}>
          <div className={styles.menuItem} onClick={() => router.push("/profile")}>
            <div className={`${styles.iconWrap} ${styles.blue}`}>
              <UserPen size={20} />
            </div>
            <span>ข้อมูลบัญชี</span>
            <ChevronRight size={18} color="#ccc" />
          </div>
          
          {/* 🚩 แก้ไข: ส่งพารามิเตอร์บอกว่ามาจากหน้า settings */}
          <div className={styles.menuItem} onClick={() => router.push('/notifications?from=settings')}>
            <div className={`${styles.iconWrap} ${styles.orange}`}>
              <Bell size={20} />
            </div>
            <span>การแจ้งเตือน</span>
            <ChevronRight size={18} color="#ccc" />
          </div>

          <div className={styles.menuItem} onClick={() => router.push("/settings/privacy")}>
            <div className={`${styles.iconWrap} ${styles.green}`}>
              <Lock size={20} />
            </div>
            <span>ความเป็นส่วนตัว</span>
            <ChevronRight size={18} color="#ccc" />
          </div>
        </div>

        <div className={styles.groupLabel}>อื่นๆ</div>
        <div className={styles.menuGroup}>
          {/* 🚩 แก้ไข: ลิงก์ LINE สำหรับความช่วยเหลือ */}
          <div className={styles.menuItem} onClick={() => window.location.href = 'https://line.me/ti/p/~ID_LINE_YOUR'}>
            <div className={`${styles.iconWrap} ${styles.gray}`}>
              <CircleHelp size={20} />
            </div>
            <span>ความช่วยเหลือ</span>
            <ChevronRight size={18} color="#ccc" />
          </div>
        </div>

        <button className={styles.logoutBtn} onClick={handleLogout}>
          <LogOut size={20} />
          <span>ออกจากระบบ</span>
        </button>
      
        <div className={styles.version}>เวอร์ชัน 1.0.2</div>
      </main>

      <BottomNav activePath="/settings" />
    </div>
  );
}
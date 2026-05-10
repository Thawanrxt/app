"use client";

import Image from "next/image";
import { useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { Camera, FolderOpen, ImageIcon, Plus } from "lucide-react";
import styles from "./reports.module.css";

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: false, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: true, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function ReportsPage() {
  const router = useRouter();
  const [issueType, setIssueType] = useState("");
  const [issueDetail, setIssueDetail] = useState("");
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [isSending, setIsSending] = useState(false);

  const handleSubmit = async () => {
    if (!issueType || !issueDetail) {
      alert("กรุณากรอกประเภทและรายละเอียดปัญหา");
      return;
    }

    setIsSending(true);
    const userId = localStorage.getItem("user_id") || "guest";
    const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

    const formData = new FormData();
    formData.append("user_id", userId);
    formData.append("issue_type", issueType);
    formData.append("details", issueDetail);

    if (selectedFile) {
      formData.append("image", selectedFile);
    }
    try {
      const res = await fetch(`${API_URL}/reports/issue`, {
        method: "POST",
        body: formData,
      });

      if (res.ok) {
        alert("✅ ส่งรายงานสำเร็จ! ขอบคุณสำหรับข้อมูลครับ");
        router.push("/home");
      }
    } catch (err) {
      console.error(err);
      alert("❌ เกิดข้อผิดพลาดในการส่งข้อมูล");
    } finally {
      setIsSending(false);
    }
  };

  const galleryRef = useRef<HTMLInputElement>(null);
  const cameraRef = useRef<HTMLInputElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  const applyImage = (file?: File | null) => {
    if (!file) return;
    setSelectedFile(file);
    setImageUrl(URL.createObjectURL(file));
    setPickerOpen(false);
  };

  return (
    <div className={styles.page}>
      
      {/* 🚩 ก๊อปปี้ส่วนนี้ไปวางแทนที่ Header เดิมได้เลย */}
      <header style={{
        display: 'flex',
        alignItems: 'center',
        padding: '0 20px',
        backgroundColor: '#2e7d32', 
        height: '110px',            // เพิ่มความสูงให้ดูโปร่งแบบในรูป
        borderBottomLeftRadius: '30px',
        borderBottomRightRadius: '30px',
        position: 'relative',
        boxShadow: '0 4px 10px rgba(0,0,0,0.1)'
      }}>
        
        {/* ปุ่มย้อนกลับทรงวงกลมโปร่งแสง */}
        <button 
          onClick={() => router.push("/home")}
          style={{
            width: '45px',
            height: '45px',
            borderRadius: '50%',
            backgroundColor: 'rgba(255, 255, 255, 0.2)', 
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            border: 'none',
            cursor: 'pointer',
            color: 'white',
            fontSize: '24px',
            flexShrink: 0 // กันปุ่มเบี้ยว
          }}
        >
          ‹
        </button>

        {/* ข้อความหัวข้อหน้า */}
        <h1 style={{
          color: 'white',
          fontSize: '20px',
          fontWeight: 'bold',
          margin: '0 0 0 15px', // เว้นระยะจากปุ่มซ้าย 15px
          flexGrow: 1
        }}>
          แจ้งปัญหาแอปฯ
        </h1>

        {/* ปุ่มประวัติการแจ้ง (ถ้ายังอยากให้มีในหน้านี้) */}
        <button 
          onClick={() => router.push("/reports/history")}
          style={{
            backgroundColor: 'white',
            color: '#2e7d32',
            border: 'none',
            padding: '6px 14px',
            borderRadius: '20px',
            fontSize: '12px',
            fontWeight: 'bold',
            cursor: 'pointer'
          }}
        >
          ประวัติ
        </button>
      </header>

      <main className={styles.content}>
        <section className={styles.formSection}>
          <h2>ประเภทปัญหา</h2>
          <input value={issueType} onChange={(e) => setIssueType(e.target.value)} placeholder="เช่น แอปค้าง, ข้อมูลไม่ถูกต้อง" />

          <h2>รายละเอียดปัญหา</h2>
          <textarea
            value={issueDetail}
            onChange={(e) => setIssueDetail(e.target.value)}
            placeholder="อธิบายรายละเอียดที่พบ..."
          />
        </section>

        <section className={styles.uploadSection}>
          <button type="button" className={styles.imageUpload} onClick={() => setPickerOpen((v) => !v)}>
            {imageUrl ? <Image src={imageUrl} alt="ภาพประกอบปัญหา" fill className={styles.uploadedImage} /> : <Plus size={60} color="#8f8f8f" />}
          </button>

          {pickerOpen && (
            <section className={styles.imageMenu}>
              <button type="button" onClick={() => galleryRef.current?.click()}>
                <ImageIcon size={22} /> คลังรูปภาพ
              </button>
              <button type="button" onClick={() => cameraRef.current?.click()}>
                <Camera size={22} /> ถ่ายภาพ
              </button>
              <button type="button" onClick={() => fileRef.current?.click()}>
                <FolderOpen size={22} /> เลือกไฟล์
              </button>
            </section>
          )}

          <input ref={galleryRef} type="file" accept="image/*" className={styles.hiddenInput} onChange={(e) => applyImage(e.target.files?.[0])} />
          <input ref={cameraRef} type="file" accept="image/*" capture="environment" className={styles.hiddenInput} onChange={(e) => applyImage(e.target.files?.[0])} />
          <input ref={fileRef} type="file" className={styles.hiddenInput} onChange={(e) => applyImage(e.target.files?.[0])} />
        </section>

        <button 
          className={styles.submitBtn} 
          type="button" 
          onClick={handleSubmit} 
          disabled={isSending}
        >
          {isSending ? "กำลังส่ง..." : "ส่งรายงาน"}
        </button>
      </main>

      <nav className={styles.bottomNav}>
        {navItems.map((item) => (
          <button
            key={item.path}
            className={`${styles.navItem} ${item.active ? styles.active : ""} ${
              item.tone === "orange" ? styles.orange : styles.green
            }`}
            onClick={() => router.push(item.path)}
          >
            <span className={styles.icon}>{item.icon}</span>
            <span>{item.label}</span>
          </button>
        ))}
      </nav>
    </div>
  );
}
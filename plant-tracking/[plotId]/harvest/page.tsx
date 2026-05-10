"use client";

import Image from "next/image";
import { useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { Camera, FolderOpen, ImageIcon, Plus } from "lucide-react";
import styles from "./harvest-activity.module.css";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

type PickerTarget = "left" | "right" | null;

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: false, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function HarvestActivityPage() {
  const [activityDate, setActivityDate] = useState(new Date().toISOString().split("T")[0]); // 🌟 เพิ่มวันที่ทำกิจกรรม
  const router = useRouter();
  const params = useParams<{ plotId: string }>();
  const plotId = decodeURIComponent(params.plotId || "");

  const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]);
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [yieldKg, setYieldKg] = useState("");
  const [moisture, setMoisture] = useState("");
  const [issueText, setIssueText] = useState("");
  const [operator, setOperator] = useState(""); // เพิ่ม State สำหรับผู้ทำกิจกรรม
  const [imagePickerOpen, setImagePickerOpen] = useState<PickerTarget>(null);
  const [leftImageUrl, setLeftImageUrl] = useState<string | null>(null);
  const [rightImageUrl, setRightImageUrl] = useState<string | null>(null);

  const [isSaving, setIsSaving] = useState(false);

  const leftGalleryRef = useRef<HTMLInputElement>(null);
  const leftCameraRef = useRef<HTMLInputElement>(null);
  const leftFileRef = useRef<HTMLInputElement>(null);
  const rightGalleryRef = useRef<HTMLInputElement>(null);
  const rightCameraRef = useRef<HTMLInputElement>(null);
  const rightFileRef = useRef<HTMLInputElement>(null);

  const applyImage = (target: Exclude<PickerTarget, null>, file?: File | null) => {
    if (!file) return;
    const url = URL.createObjectURL(file);
    if (target === "left") setLeftImageUrl(url);
    if (target === "right") setRightImageUrl(url);
    setImagePickerOpen(null);
  };

  const currentPicker = imagePickerOpen === "left"
    ? { gallery: leftGalleryRef, camera: leftCameraRef, file: leftFileRef, target: "left" as const }
    : { gallery: rightGalleryRef, camera: rightCameraRef, file: rightFileRef, target: "right" as const };

  // 🌟 ฟังก์ชันส่งข้อมูลการเก็บเกี่ยว
  const handleSaveHarvest = async () => {
    if (!yieldKg) {
      alert("กรุณากรอกน้ำหนักผลผลิต");
      return;
    }

    setIsSaving(true);

    try {
      // 🚩 ตรวจสอบ URL: ต้องเป็น /harvest/save เพื่อให้บันทึกลงตารางลูกด้วย
      const response = await fetch(`${API_URL}/harvest/save`, { 
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          plan_id: plotId,
          plot_id: plotId,
          operator_name: operator || "ไม่ระบุ", // 🚩 ชื่อต้องตรงกับ HarvestSchema
          activity_date: activityDate,         // 🚩 ชื่อต้องตรงกับ HarvestSchema
          start_harvest_date: startDate,       // 🚩 ชื่อต้องตรงกับ HarvestSchema
          end_harvest_date: endDate,           // 🚩 ชื่อต้องตรงกับ HarvestSchema
          harvest_amount: parseFloat(yieldKg) || 0,
          moisture_content: parseFloat(moisture) || 0,
          problems_found: issueText
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || "บันทึกไม่สำเร็จ");
      }

      alert("✅ บันทึกข้อมูลการเก็บเกี่ยวเรียบร้อยแล้ว!");
      router.push("/plant-tracking");

    } catch (error: any) {
      console.error("Save harvest error:", error);
      alert(`❌ เกิดข้อผิดพลาด: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };
    const dateInputStyle = {
        appearance: 'auto' as any, 
        display: 'block', 
        width: '100%', 
        backgroundColor: 'white',
        color: 'black',
        padding: '8px'
      };
  return (
      <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push(`/plant-tracking/${encodeURIComponent(plotId)}`)}>
          ←
        </button>
        <h1>การเก็บเกี่ยว</h1>
      </header>
      <main className={styles.content}>
        <section className={styles.formSection}>
          
          {/* 🌟 ส่วนที่ 1: ข้อมูลทั่วไป (ตามรูปภาพ) */}
          <div className={styles.formColumn}>
      <label>วันที่ทำกิจกรรม</label>
      <input 
        type="date" 
        style={dateInputStyle} // 🚩 บังคับ UI
        value={activityDate} 
        onChange={(e) => setActivityDate(e.target.value)} 
      />
    </div>

          <div className={styles.formColumn}>
            <label>ผู้ทำกิจกรรม (ถ้ามี)</label>
            <input 
              value={operator} 
              onChange={(e) => setOperator(e.target.value)} 
              placeholder="ชื่อผู้จ้างหรือผู้ดำเนินการ"
            />
          </div>

          <hr className="my-4 border-gray-200" />

          {/* 🌟 ส่วนที่ 2: ข้อมูลการเก็บเกี่ยว */}
          <h2>ข้อมูลการเก็บเกี่ยว</h2>

          <div className={styles.formColumn}>
  <label>วันที่เริ่มการเก็บเกี่ยว (ทดสอบ)</label>
  <input 
    type="date" 
    style={{ appearance: 'auto', display: 'block', width: '100%', backgroundColor: 'white' }} // 🚩 ใส่ style ตรงๆ เพื่อบังคับให้เปลี่ยน
    value={startDate} 
    onChange={(e) => setStartDate(e.target.value)} 
  />
</div>

          <div className={styles.formColumn}>
            <label>วันที่สิ้นสุดการเก็บเกี่ยว</label>
            <input 
              type="date" 
              value={endDate} 
              onChange={(e) => setEndDate(e.target.value)} 
            />
          </div>

          <h2>สรุปผลการเก็บเกี่ยว</h2>

          <div className={styles.formRow}>
            <label>ผลผลิตรวม</label>
            <div className={styles.amountRow}>
              <input 
                type="number"
                value={yieldKg} 
                onChange={(e) => setYieldKg(e.target.value)} 
                placeholder="0.00"
              />
              <span>กิโลกรัม</span>
            </div>
          </div>

          <div className={styles.formColumn}>
            <label>ความชื้นของข้าวเปลือกสด (%)</label>
            <input 
              type="number"
              value={moisture} 
              onChange={(e) => setMoisture(e.target.value)} 
              placeholder="0.00"
            />
          </div>

          <div className={styles.formColumn}>
            <label>ปัญหาที่พบระหว่างการเก็บเกี่ยว</label>
            <textarea value={issueText} onChange={(e) => setIssueText(e.target.value)} placeholder="เช่น เครื่องจักรขัดข้อง, ฝนตกตกหนัก" />
          </div>

          <h2>อัปโหลดรูปภาพ</h2>

          <section className={styles.imagesRow}>
            <button type="button" className={styles.imageUpload} onClick={() => setImagePickerOpen("left")}>
              {leftImageUrl ? <Image src={leftImageUrl} alt="ภาพเก็บเกี่ยว 1" fill className={styles.uploadedImage} /> : <Plus size={58} color="#8f8f8f" />}
            </button>
            <button type="button" className={styles.imageUpload} onClick={() => setImagePickerOpen("right")}>
              {rightImageUrl ? <Image src={rightImageUrl} alt="ภาพเก็บเกี่ยว 2" fill className={styles.uploadedImage} /> : <Plus size={58} color="#8f8f8f" />}
            </button>
          </section>

          {imagePickerOpen && (
            <section className={styles.imageMenu}>
              <button type="button" onClick={() => currentPicker.gallery.current?.click()}><ImageIcon size={22} /> คลังรูปภาพ</button>
              <button type="button" onClick={() => currentPicker.camera.current?.click()}><Camera size={22} /> ถ่ายภาพ</button>
              <button type="button" onClick={() => currentPicker.file.current?.click()}><FolderOpen size={22} /> เลือกไฟล์</button>
            </section>
          )}

          <div style={{ display: "none" }}>
            <input ref={leftGalleryRef} type="file" accept="image/*" onChange={(e) => applyImage("left", e.target.files?.[0])} />
            <input ref={leftCameraRef} type="file" accept="image/*" capture="environment" onChange={(e) => applyImage("left", e.target.files?.[0])} />
            <input ref={leftFileRef} type="file" onChange={(e) => applyImage("left", e.target.files?.[0])} />
            <input ref={rightGalleryRef} type="file" accept="image/*" onChange={(e) => applyImage("right", e.target.files?.[0])} />
            <input ref={rightCameraRef} type="file" accept="image/*" capture="environment" onChange={(e) => applyImage("right", e.target.files?.[0])} />
            <input ref={rightFileRef} type="file" onChange={(e) => applyImage("right", e.target.files?.[0])} />
          </div>

          <div className={styles.actionRow}>
            <button 
              className={styles.saveBtn} 
              type="button" 
              onClick={handleSaveHarvest}
              disabled={isSaving}
            >
              {isSaving ? "กำลังบันทึก..." : "บันทึก"}
            </button>
            <button className={styles.editBtn} type="button">
              แก้ไข
            </button>
          </div>
        </section>
      </main>

      <nav className={styles.bottomNav}>
        {navItems.map((item) => (
          <button
            key={item.path}
            className={`${styles.navItem} ${item.active ? styles.active : ""} ${item.tone === "orange" ? styles.orange : styles.green}`}
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
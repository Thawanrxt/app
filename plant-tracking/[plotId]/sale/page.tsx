"use client";

import Image from "next/image";
import { useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { Camera, FolderOpen, ImageIcon, Plus } from "lucide-react";
import styles from "./sale-activity.module.css";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

type PickerTarget = "left" | "right" | null;

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: false, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function SaleActivityPage() {
  const router = useRouter();
  const params = useParams<{ plotId: string }>();
  const plotId = decodeURIComponent(params.plotId || "");

  const [mill, setMill] = useState("");
  const [productName, setProductName] = useState("");
  const [date, setDate] = useState("");
  const [queueNo, setQueueNo] = useState("");
  const [ticketNo, setTicketNo] = useState("");
  const [licensePlate, setLicensePlate] = useState("");
  const [itemName, setItemName] = useState("");
  const [time, setTime] = useState("");
  const [yieldKg, setYieldKg] = useState("");
  const [weightKg, setWeightKg] = useState("");
  const [netWeightKg, setNetWeightKg] = useState("");
  const [totalIncome, setTotalIncome] = useState("");
  const [pricePerKg, setPricePerKg] = useState("");
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

  const currentPicker =
    imagePickerOpen === "left"
      ? { gallery: leftGalleryRef, camera: leftCameraRef, file: leftFileRef }
      : { gallery: rightGalleryRef, camera: rightCameraRef, file: rightFileRef };

  // 🌟 ฟังก์ชันส่งข้อมูลการขายข้าวไปยัง Backend 🌟
  const handleSaveSale = async () => {
    if (!totalIncome || !pricePerKg) {
      alert("กรุณากรอกข้อมูลรายได้รวมและราคาต่อกิโลกรัม");
      return;
    }

    setIsSaving(true);

    try {
      const response = await fetch(`${API_URL}/activities/sale`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "ngrok-skip-browser-warning": "true"
        },
        body: JSON.stringify({
    plan_id: plotId,
    type_id: 7, // 💰 แก้จาก 6 เป็น 7 (ให้ตรงกับ ID การขายข้าวที่คุณตั้งไว้ใน Backend)
    
    // 🚩 เปลี่ยนชื่อ Key ให้ตรงกับที่ Backend รอรับ (อ้างอิงจาก Error ก่อนหน้า)
    performed_by_name: "ระบบบันทึกการขาย", 
    performed_at: date || new Date().toISOString().split("T")[0],
    
    // ข้อมูลการขาย (ต้องสะกดให้ตรงกับใน schemas.py/api.py)
    mill_name: mill,            // แก้จาก buyer_name เป็น mill_name
    product_name: productName,  // เพิ่มตัวนี้เข้าไป
    car_details: ticketNo,      // หรือถ้าใน Backend ใช้ ticket_no ก็แก้ให้ตรงกัน
    total_yield_kg: parseFloat(yieldKg) || 0,
    net_weight_kg: parseFloat(netWeightKg) || 0,
    price_per_kg: parseFloat(pricePerKg) || 0,
    total_income: parseFloat(totalIncome) || 0,
    
    // หมายเหตุ (ถ้ามี)
    problems_found: `ทะเบียนรถ: ${licensePlate}, คันที่: ${queueNo}, เวลา: ${time}`
})
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || "บันทึกการขายไม่สำเร็จ");
      }

      alert("✅ บันทึกข้อมูลการขายสำเร็จ!");
      router.push("/plant-tracking");

    } catch (error: any) {
      console.error("Save sale error:", error);
      alert(`❌ เกิดข้อผิดพลาด: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push(`/plant-tracking/${encodeURIComponent(plotId)}`)}>
          ←
        </button>
        <h1>การขายข้าว</h1>
      </header>

      <main className={styles.content}>
        
        <section className={styles.formSection}>
          <h2>ข้อมูลหลังขายเข้าโรงสี</h2>

          <div className={styles.formRow}>
            <label>โรงสี</label>
            <input value={mill} onChange={(e) => setMill(e.target.value)} placeholder="ระบุชื่อโรงสีหรือจุดรับซื้อ" />
          </div>
          <div className={styles.formRow}>
            <label>ชื่อสินค้า</label>
            <input value={productName} onChange={(e) => setProductName(e.target.value)} placeholder="เช่น ข้าวหอมมะลิ" />
          </div>
          <div className={styles.formRow}>
            <label>วันที่</label>
            <input type="date" value={date} onChange={(e) => setDate(e.target.value)} />
          </div>
          <div className={styles.formRow}>
            <label>คันที่</label>
            <input value={queueNo} onChange={(e) => setQueueNo(e.target.value)} />
          </div>
          <div className={styles.formRow}>
            <label>เลขที่</label>
            <input value={ticketNo} onChange={(e) => setTicketNo(e.target.value)} />
          </div>
          <div className={styles.formRow}>
            <label>ทะเบียน</label>
            <input value={licensePlate} onChange={(e) => setLicensePlate(e.target.value)} />
          </div>
          <div className={styles.formRow}>
            <label>รายการ</label>
            <input value={itemName} onChange={(e) => setItemName(e.target.value)} />
          </div>
          <div className={styles.formRow}>
            <label>เวลา</label>
            <input type="time" value={time} onChange={(e) => setTime(e.target.value)} />
          </div>

          <div className={styles.formRow}>
            <label>ผลการผลิต</label>
            <div className={styles.amountRow}>
              <input type="number" value={yieldKg} onChange={(e) => setYieldKg(e.target.value)} />
              <span>กิโลกรัม</span>
            </div>
          </div>
          <div className={styles.formRow}>
            <label>น้ำหนัก</label>
            <div className={styles.amountRow}>
              <input type="number" value={weightKg} onChange={(e) => setWeightKg(e.target.value)} />
              <span>กิโลกรัม</span>
            </div>
          </div>
          <div className={styles.formRow}>
            <label>น้ำหนักสินค้าสุทธิ</label>
            <div className={styles.amountRow}>
              <input type="number" value={netWeightKg} onChange={(e) => setNetWeightKg(e.target.value)} />
              <span>กิโลกรัม</span>
            </div>
          </div>
          <div className={styles.formRow}>
            <label>รวมรายได้</label>
            <div className={styles.amountRow}>
              <input type="number" value={totalIncome} onChange={(e) => setTotalIncome(e.target.value)} placeholder="0.00" />
              <span>บาท</span>
            </div>
          </div>
          <div className={styles.formRow}>
            <label>ราคาต่อกก.</label>
            <div className={styles.amountRow}>
              <input type="number" value={pricePerKg} onChange={(e) => setPricePerKg(e.target.value)} placeholder="0.00" />
              <span>บาท</span>
            </div>
          </div>

          <h2>อัปโหลดใบเสร็จขายข้าว</h2>
          <section className={styles.imagesRow}>
            <button type="button" className={styles.imageUpload} onClick={() => setImagePickerOpen("left")}>
              {leftImageUrl ? <Image src={leftImageUrl} alt="ใบเสร็จ 1" fill className={styles.uploadedImage} /> : <Plus size={58} color="#8f8f8f" />}
            </button>
            <button type="button" className={styles.imageUpload} onClick={() => setImagePickerOpen("right")}>
              {rightImageUrl ? <Image src={rightImageUrl} alt="ใบเสร็จ 2" fill className={styles.uploadedImage} /> : <Plus size={58} color="#8f8f8f" />}
            </button>
          </section>

          {imagePickerOpen && (
            <section className={styles.imageMenu}>
              <button type="button" onClick={() => currentPicker.gallery.current?.click()}><ImageIcon size={22} /> คลังรูปภาพ</button>
              <button type="button" onClick={() => currentPicker.camera.current?.click()}><Camera size={22} /> ถ่ายภาพ</button>
              <button type="button" onClick={() => currentPicker.file.current?.click()}><FolderOpen size={22} /> เลือกไฟล์</button>
            </section>
          )}

          {/* ซ่อน Input รับไฟล์ */}
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
              onClick={handleSaveSale}
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
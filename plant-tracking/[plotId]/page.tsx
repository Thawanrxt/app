"use client";

import Image from "next/image";
import { useRef, useState, useEffect, Suspense } from "react"; // ✨ เพิ่ม Suspense ตรงนี้
import { useParams, useRouter, useSearchParams } from "next/navigation"; // ✨ เพิ่ม useSearchParams ตรงนี้
import { Camera, ChevronDown, FolderOpen, ImageIcon, Plus } from "lucide-react";
import styles from "./plot-detail.module.css";


const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

type PickerTarget = "left" | "right" | null;

type ActivityEntry =
  | { type: "group"; label: string }
  | { type: "option"; label: string; value: string };

const activityOptions: ActivityEntry[] = [
  { type: "group", label: "เตรียมดิน" },
  { type: "option", label: "ปรับ land leveling", value: "เตรียมดิน - ปรับ land leveling" },
  { type: "option", label: "ไม่ปรับ land leveling", value: "เตรียมดิน - ไม่ปรับ land leveling" },
  { type: "option", label: "การจัดการน้ำ", value: "การจัดการน้ำ" },
  { type: "option", label: "หว่านปุ๋ย", value: "หว่านปุ๋ย" },
  { type: "option", label: "จัดการศัตรูพืช", value: "จัดการศัตรูพืช" },
  { type: "option", label: "โรคพืช", value: "โรคพืช" },
  { type: "option", label: "การเก็บเกี่ยว", value: "การเก็บเกี่ยว" },
  { type: "option", label: "การขายข้าว", value: "การขายข้าว" },
];

const defaultMethodOptions = ["ไถดะ", "ไถพรวน", "ปรับเทือก"];
const waterMethodOptions = ["เปียกสลับแห้ง(AWD)"];
const fertilizerMethodOptions = ["หว่านด้วยคน", "หว่านปุ๋ยด้วยโดรน"];

function PlotTrackingContent() {
  const router = useRouter();
  const params = useParams<{ plotId: string }>();
  const planIdFromUrl = decodeURIComponent(params.plotId || "");
  const searchParams = useSearchParams();
  const targetType = searchParams.get("type");
  console.log("เช็คค่า Type จาก URL:", targetType);
  // --- Common States ---
  const [activityOpen, setActivityOpen] = useState(false);
  const [methodOpen, setMethodOpen] = useState(false);
  const [activity, setActivity] = useState("");
  const [method, setMethod] = useState("");
  const [operator, setOperator] = useState("");
  const [activityDate, setActivityDate] = useState(new Date().toISOString().split("T")[0]);
  const [thaiActivityDate, setThaiActivityDate] = useState(""); // 🚩 เพิ่มตัวนี้
  const [thaiHarvestStart, setThaiHarvestStart] = useState("");
  const [thaiHarvestEnd, setThaiHarvestEnd] = useState("");
  const [thaiSaleDate, setThaiSaleDate] = useState("");
  const [saleDate, setSaleDate] = useState(""); // สำหรับเก็บ ค.ศ. ของวันขาย
  const [finishedTypeIds, setFinishedTypeIds] = useState<number[]>([]);

    // --- Image States ---
  const [leftImageUrl, setLeftImageUrl] = useState<string | null>(null);
  const leftFileRef = useRef<HTMLInputElement>(null);
    
  // 🌟 ฟังก์ชันแปลงวันที่ไทยเป็นสากล (ค.ศ.)
const handleThaiDateChange = (value: string, setterISO: (v: string) => void, setterThai: (v: string) => void) => {
  setterThai(value);
  const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
  const match = value.match(datePattern);

  if (match) {
    const day = match[1];
    const month = match[2];
    const yearInter = parseInt(match[3]) - 543; // แปลง พ.ศ. -> ค.ศ.
    setterISO(`${yearInter}-${month}-${day}`);
  }
};
useEffect(() => {
  const today = new Date();
  const d = String(today.getDate()).padStart(2, '0');
  const m = String(today.getMonth() + 1).padStart(2, '0');
  const yInter = today.getFullYear(); // ค.ศ. 2026
  const yThai = yInter + 543;         // พ.ศ. 2569

  const isoDate = `${yInter}-${m}-${d}`;      // สำหรับส่ง API
  const thaiDate = `${d}/${m}/${yThai}`;      // สำหรับโชว์หน้าจอ

  // ตั้งค่าให้ทุกช่องวันที่เป็น "วันนี้" อัตโนมัติ
  setActivityDate(isoDate);
  setThaiActivityDate(thaiDate);

  setHarvestStartDate(isoDate);
  setThaiHarvestStart(thaiDate);

  setHarvestEndDate(isoDate);
  setThaiHarvestEnd(thaiDate);

  setSaleDate(isoDate);
  setThaiSaleDate(thaiDate);
}, []);
  const [issueText, setIssueText] = useState("");
  const [roundNo, setRoundNo] = useState("1");
  const [isSaving, setIsSaving] = useState(false);
  const [advice, setAdvice] = useState<any>(null);

  // --- 🚩 ส่วนที่ 1: ดึงคำแนะนำ (Advice) ---
  useEffect(() => {
    const fetchAdvice = async () => {
      const typeMapping: Record<string, number> = {
        "เตรียมดิน - ปรับ land leveling": 1,
        "เตรียมดิน - ไม่ปรับ land leveling": 1,
        "การจัดการน้ำ": 2,
        "หว่านปุ๋ย": 3,
        "จัดการศัตรูพืช": 4,
        "โรคพืช": 5,
        "การเก็บเกี่ยว": 6,
        "การขายข้าว": 7
      };
      
      const currentActivityId = typeMapping[activity] || targetType;

      if (currentActivityId) {
        try {
          const res = await fetch(
        `${API_URL}/api/v1/advice/${currentActivityId}?plot_id=${planIdFromUrl}`, 
        { headers: { "ngrok-skip-browser-warning": "true" } }
      );
      
      if (res.ok) {
        const result = await res.json();
        setAdvice(result.data);
      }
    } catch (err) {
      console.error("Advice fetch error:", err);
        }
      }
    };
    fetchAdvice();
  }, [activity, targetType]);

  // --- 🚩 ส่วนที่ 2: จัดการ Auto-select กิจกรรมจาก URL ---
  useEffect(() => {
    if (targetType) {
      const typeMapping: Record<string, string> = {
        "1": "เตรียมดิน - ปรับ land leveling",
        "2": "การจัดการน้ำ",
        "3": "หว่านปุ๋ย",
        "4": "จัดการศัตรูพืช",
        "5": "โรคพืช",
        "6": "การเก็บเกี่ยว",
        "7": "การขายข้าว"
      };
      const selectedName = typeMapping[targetType];
      if (selectedName) {
        setActivity(selectedName);
      }
    }
  }, [targetType]);
  useEffect(() => {
  const fetchOwnerName = async () => {
    const userId = localStorage.getItem("user_id");
    if (!userId) return;

    try {
      const res = await fetch(`${API_URL}/dashboard/main/${userId}`, {
        headers: { "ngrok-skip-browser-warning": "true" }
      });
      if (res.ok) {
        const data = await res.json();
        // ถ้ามีชื่อใน DB ให้เซตใส่ operator ทันที
        if (data.full_name) setOperator(data.full_name);
      }
    } catch (error) {
      console.error("Error fetching owner name:", error);
    }
  };
  fetchOwnerName();
}, []);
useEffect(() => {
  const fetchFinishedActivities = async () => {
    try {
      const res = await fetch(`${API_URL}/tracking/plan/${planIdFromUrl}/history`, {
  headers: { "ngrok-skip-browser-warning": "true" }
});
      if (res.ok) {
        const data = await res.json();
        
        // 🚩 สำคัญ: ต้องสะกด item.type_id (ตัวเล็กหมด มีขีดล่าง)
        const doneIds = data.map((item: any) => item.type_id);
        
        console.log("ตรวจสอบ ID ที่เสร็จแล้ว:", doneIds); // <--- เปิด Console (F12) ดูเลขนี้
        setFinishedTypeIds(doneIds);
      }
    } catch (error) {
      console.error("Error fetching history:", error);
    }
  };

  if (planIdFromUrl) fetchFinishedActivities();
}, [planIdFromUrl]);
  // --- Specific States ---
  // เตรียมดิน
  const [strawBurning, setStrawBurning] = useState<"burn" | "no-burn" | "">("");
  const [soilPh, setSoilPh] = useState("");
  const [soilNpk, setSoilNpk] = useState("");
  const [soilOrganicMatter, setSoilOrganicMatter] = useState("");
  // การจัดการน้ำ
  const [waterLevel, setWaterLevel] = useState<"above-5" | "below-10" | "below-15" | "">("");
  // หว่านปุ๋ย
  const [fertilizerType, setFertilizerType] = useState("");
  const [fertilizerAmount, setFertilizerAmount] = useState("");
  // ศัตรูพืช / โรคพืช
  const [pestType, setPestType] = useState("");
  const [chemicalName, setChemicalName] = useState("");
  const [chemicalAmount, setChemicalAmount] = useState("");
  const [chemicalUnit, setChemicalUnit] = useState("มล.");
  const [ratioPerWater, setRatioPerWater] = useState("");
  // การเก็บเกี่ยว (อ้างอิง image_4b1266.png)
  const [harvestStartDate, setHarvestStartDate] = useState("");
  const [harvestEndDate, setHarvestEndDate] = useState("");
  const [totalYield, setTotalYield] = useState("");
  const [moisture, setMoisture] = useState("");
  // การขายข้าว (อ้างอิง image_4b1248.png / image_49a362.png)
  const [millName, setMillName] = useState("");
  const [netWeight, setNetWeight] = useState("");
  const [totalIncome, setTotalIncome] = useState("");
  const [pricePerKg, setPricePerKg] = useState("");
  const [carNo, setCarNo] = useState("");      // สำหรับคันที่ (ถ้าจะเก็บ)
  const [plateNo, setPlateNo] = useState("");  // สำหรับทะเบียน (ถ้าจะเก็บ)
  const [productName, setProductName] = useState("");   // เก็บชื่อสินค้า เช่น ข้าวหอมมะลิ
  const [netWeightKg, setNetWeightKg] = useState("");     // เก็บน้ำหนักสุทธิ
  const [ticketNo, setTicketNo] = useState("");           // เก็บเลขที่ตั๋ว/ใบชั่ง
  const [yieldKg, setYieldKg] = useState("");
  const [saleTime, setSaleTime] = useState("");
  const [totalWeight, setTotalWeight] = useState("");   // น้ำหนักรวม (ก่อนหัก)
  // --- UI & Image States ---
  const [imagePickerOpen, setImagePickerOpen] = useState<PickerTarget>(null);
  const [rightImageUrl, setRightImageUrl] = useState<string | null>(null);
  const leftGalleryRef = useRef<HTMLInputElement>(null);
  const leftCameraRef = useRef<HTMLInputElement>(null);
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

  const isWaterManagementActivity = activity.includes("การจัดการน้ำ");
  const isFertilizerActivity = activity.includes("หว่านปุ๋ย");
  const isPestActivity = activity.includes("จัดการศัตรูพืช");
  const isDiseaseActivity = activity.includes("โรคพืช");
  const isLandLevelingActivity = activity.includes("เตรียมดิน");
  const isHarvestActivity = activity === "การเก็บเกี่ยว";
  const isSaleActivity = activity === "การขายข้าว";

  const methodOptions = isWaterManagementActivity
    ? waterMethodOptions
    : isFertilizerActivity
    ? fertilizerMethodOptions
    : defaultMethodOptions;

  const handleSaveActivity = async () => {
  // 1. สร้าง Array สำหรับเก็บรายการข้อผิดพลาด
  const errors: string[] = [];

  // 2. ตรวจสอบข้อมูลพื้นฐาน
  if (!activity) errors.push("- กรุณาเลือกกิจกรรม");
  if (!operator) errors.push("- กรุณาระบุผู้ทำกิจกรรม");
  if (!activityDate) errors.push("- กรุณาระบุวันที่ทำกิจกรรม");

  // 3. ตรวจสอบรูปภาพ
  if (!leftImageUrl && !rightImageUrl) {
    errors.push("- กรุณาอัปโหลดรูปภาพกิจกรรมอย่างน้อย 1 รูป");
  }

  // 4. ตรวจสอบข้อมูลตามประเภทกิจกรรม
  if (isLandLevelingActivity) {
    if (!strawBurning) errors.push("- กรุณาเลือกข้อมูลการเผาฟาง");
    if (!soilPh) errors.push("- กรุณาเลือกค่า pH");
    if (!soilNpk || soilNpk.length < 8) errors.push("- กรุณาระบุค่า N-P-K ให้ครบ (เช่น 15-15-15)");
    if (!soilOrganicMatter) errors.push("- กรุณาระบุข้อมูลอินทรียวัตถุ");
  }

  if (isWaterManagementActivity && !waterLevel) {
    errors.push("- กรุณาเลือกข้อมูลระดับการขังน้ำ");
  }

  if (isFertilizerActivity) {
    if (!fertilizerType) errors.push("- กรุณาเลือกประเภทปุ๋ย");
    if (!fertilizerAmount) errors.push("- กรุณาระบุปริมาณปุ๋ยที่ใช้");
  }

  if (isPestActivity || isDiseaseActivity) {
    const typeLabel = isPestActivity ? "ศัตรูพืช" : "โรคพืช";
    if (!pestType) errors.push(`- กรุณาระบุประเภท${typeLabel}`);
    if (!chemicalName) errors.push("- กรุณาระบุชื่อสารเคมี");
    if (!chemicalAmount) errors.push("- กรุณาระบุปริมาณที่ใช้");
  }

  if (isHarvestActivity) {
    if (!harvestStartDate || !harvestEndDate) errors.push("- กรุณาระบุวันที่เริ่มและสิ้นสุดการเก็บเกี่ยว");
    if (!totalYield) errors.push("- กรุณาระบุผลการผลิต");
    if (!moisture) errors.push("- กรุณาระบุความชื้น");
  }

  if (isSaleActivity) {
    if (!millName) errors.push("- กรุณาระบุชื่อโรงสี");
    if (!totalIncome) errors.push("- กรุณาระบุรวมรายได้");
  }

  // 5. ถ้ามีข้อผิดพลาด ให้แสดงผลทั้งหมดทีเดียวแล้วหยุดการทำงาน
  if (errors.length > 0) {
    alert("⚠️ กรุณากรอกข้อมูลให้ครบถ้วน:\n\n" + errors.join("\n"));
    return;
  }
  setIsSaving(true);
    try {
        let payload: any = {
            plan_id: planIdFromUrl,
            plot_id: planIdFromUrl, 
            operator_name: operator || "ไม่ระบุ",
            activity_date: activityDate,
            problems_found: issueText, 
        };
          let endpoint = `${API_URL}/activities/save`;

          if (isLandLevelingActivity) {
            payload = { 
        ...payload, 
        type_id: 1, 
        straw_burning: strawBurning || "no-burn",
        soil_ph: soilPh ? parseInt(soilPh) : 0, 
        soil_npk: soilNpk || "00-00-00", 
        soil_organic: soilOrganicMatter || "" 
    };
        } else if (isWaterManagementActivity) {
            payload = { ...payload, type_id: 2, water_level: waterLevel };
        } else if (isFertilizerActivity) {
            payload = { ...payload, type_id: 3, fertilizer_type: fertilizerType, amount: fertilizerAmount };
       } else if (isPestActivity) {
    payload = { 
        ...payload, 
        type_id: 4, 
        pest_type: pestType,
        // 🚩 เพิ่ม/แก้ไข 3 บรรทัดนี้ให้ชื่อตรงกับ Database
        chemical_common_name: chemicalName || "ไม่ระบุชื่อยา", 
        amount_used: parseFloat(chemicalAmount) || 0,
        water_liters: parseFloat(ratioPerWater) || 0
    };
        } else if (isDiseaseActivity) {
            payload = {
    ...payload,
    type_id: 5,
    disease_name: pestType, // ชื่อโรค
    chemical_name: chemicalName,
    chemical_amount: chemicalAmount,  
    water_liter: ratioPerWater

};
        } else if (isHarvestActivity) {
            payload = {
                ...payload,
                type_id: 6,
                // 🚩 แก้จาก harvestAmount เป็น totalYield
                total_yield_kg: totalYield, 
                // 🚩 แก้จาก moisture หรืออันอื่น เป็น moisture ให้ตรงตามรูป
                moisture_percent: moisture, 
                // 🚩 วันที่เริ่มและสิ้นสุด (ใช้ชื่อตามในรูปที่คุณแคปมา)
                harvest_start_date: harvestStartDate,
                harvest_end_date: harvestEndDate,
                // ส่วน operator กับ notes ถ้ามีก็ใส่เพิ่มได้ครับ
                operator_name: operator, 
                problems_found: issueText
    };
        } else if (isSaleActivity) {
    payload = {
        ...payload,
        type_id: 7, 
        mill_name: millName,
        product_name: productName,
        sale_date: activityDate, // ใช้วันที่เลือกจากปฏิทินหลัก
        
        // 🚩 แยกตัวแปรน้ำหนักชัดเจน
        total_weight: parseFloat(totalWeight) || 0, // น้ำหนักรวม
        net_weight_kg: parseFloat(netWeightKg) || 0, // น้ำหนักสุทธิ
        
        price_per_kg: parseFloat(pricePerKg) || 0,
        total_income: parseFloat(totalIncome) || 0,
        car_details: `ทะเบียน: ${plateNo}, ตั๋ว: ${ticketNo}`, 
    };
}
      const response = await fetch(endpoint, { 
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

      if (!response.ok) throw new Error("บันทึกไม่สำเร็จ");
      alert("✅ บันทึกกิจกรรมเรียบร้อยแล้ว!");
      router.push("/plant-tracking");
    } catch (error: any) {
      alert(`❌ เกิดข้อผิดพลาด: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push("/plant-tracking")}>
    ‹
  </button>
        <h1>ติดตามการปลูกข้าว</h1>
      </header>

<main className={styles.content}>
        {/* 1. แถบกิจกรรมกับครั้งที่ (บนสุด) */}
        <section className={styles.selectorSection}>
          <div className={styles.selectorCard}>
            <h2>กิจกรรม</h2>
            <button className={styles.selectorButton} onClick={() => { setActivityOpen(!activityOpen); setMethodOpen(false); }}>
              <span>{activity || "เลือกกิจกรรม"}</span>
              <ChevronDown size={22} className={activityOpen ? styles.chevronOpen : ""} />
            </button>
            {activityOpen && (
              <div className={styles.dropdown}>
                {activityOptions.map((entry, idx) => {
  if (entry.type === "group") {
    return <div key={idx} className={styles.groupTitle}>{entry.label}</div>;
  }

  // 1. กำหนด ID ของแต่ละกิจกรรม
  const typeMapping: Record<string, number> = {
  "เตรียมดิน - ปรับ land leveling": 1,
  "เตรียมดิน - ไม่ปรับ land leveling": 1,
  "การจัดการน้ำ": 2,
  "หว่านปุ๋ย": 3,
  "จัดการศัตรูพืช": 4,
  "โรคพืช": 5,
  "การเก็บเกี่ยว": 6,
  "การขายข้าว": 7
};
  
  const currentId = typeMapping[entry.value] || 0;
const isFinished = finishedTypeIds.includes(currentId);
  
  // 2. Logic การล็อคปุ่ม
  const isLocked = isFinished || (currentId > 1 && !finishedTypeIds.includes(currentId - 1));

  return (
    <button 
      key={idx} 
      disabled={isLocked}
      onClick={() => { setActivity(entry.value); setActivityOpen(false); }}
      style={{
        opacity: isLocked ? 0.5 : 1,
        cursor: isLocked ? "not-allowed" : "pointer",
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        width: "100%",
        padding: "12px",
        backgroundColor: isFinished ? "#f0f0f0" : "transparent",
        border: "none",
        borderBottom: "1px solid #eee",
        color: isFinished ? "#888" : "#333",
        textAlign: "left"
      }}
    >
      <span>{entry.label}</span>
      {isFinished && <span style={{ color: "#2e7d32", fontSize: "12px", fontWeight: "bold" }}></span>}
    </button>
  );
})}
              </div>
            )}
          </div>

          <div className={styles.selectorCard}>
            <h2>{isPestActivity || isDiseaseActivity || isHarvestActivity || isSaleActivity ? "ครั้งที่" : "วิธีการ"}</h2>
            {isPestActivity || isDiseaseActivity || isHarvestActivity || isSaleActivity ? (
              <div style={{ backgroundColor: "#2e7d32", color: "#fff", padding: "10px", borderRadius: "8px", textAlign: "center", fontWeight: "bold", fontSize: "18px", marginTop: "5px" }}>
                {roundNo}
              </div>
            ) : (
              <>
                <button className={styles.selectorButton} onClick={() => { setMethodOpen(!methodOpen); setActivityOpen(false); }}>
                  <span>{method || "เลือกวิธีการ"}</span>
                  <ChevronDown size={22} className={methodOpen ? styles.chevronOpen : ""} />
                </button>
                {methodOpen && (
                  <div className={styles.dropdown}>
                    {methodOptions.map((o, idx) => <button key={idx} onClick={() => { setMethod(o); setMethodOpen(false); }}>{o}</button>)}
                  </div>
                )}
              </>
            )}
          </div>
        </section>
        {(isHarvestActivity || isSaleActivity || isPestActivity || isDiseaseActivity || isLandLevelingActivity || isWaterManagementActivity || isFertilizerActivity) && (
        <h3 style={{ marginTop: "20px", marginBottom: "10px", fontWeight: "bold" }}>
        {isHarvestActivity ? "อัปโหลดรูปภาพการเก็บเกี่ยว" : 
        isSaleActivity ? "อัปโหลดใบเสร็จขายข้าว" : 
        isPestActivity ? "อัปโหลดรูปภาพการจัดการศัตรูพืช" : 
        isDiseaseActivity ? "อัปโหลดรูปภาพโรคพืช" : 
        isLandLevelingActivity ? "อัปโหลดรูปภาพการเตรียมดิน" : 
        isWaterManagementActivity ? "อัปโหลดรูปภาพการจัดการน้ำ" : 
        isFertilizerActivity ? "อัปโหลดรูปภาพการหว่านปุ๋ย" : 
        "อัปโหลดรูปภาพกิจกรรม"}
      </h3>
        )}

        {imagePickerOpen && (
          <section className={styles.imageMenu}>
            <button onClick={() => currentPicker.gallery.current?.click()}><ImageIcon size={20} /> คลังรูปภาพ</button>
            <button onClick={() => currentPicker.camera.current?.click()}><Camera size={20} /> ถ่ายภาพ</button>
            <button onClick={() => currentPicker.file.current?.click()}><FolderOpen size={20} /> เลือกไฟล์</button>
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

        <section className={styles.imagesRow}>
          <button className={styles.imageUpload} onClick={() => setImagePickerOpen("left")}>
            {leftImageUrl ? <Image src={leftImageUrl} alt="1" fill style={{objectFit:"cover"}} /> : <Plus size={60} color="#8f8f8f" />}
          </button>
          <button className={styles.imageUpload} onClick={() => setImagePickerOpen("right")}>
            {rightImageUrl ? <Image src={rightImageUrl} alt="2" fill style={{objectFit:"cover"}} /> : <Plus size={60} color="#8f8f8f" />}
          </button>
        </section>

        <div style={{ display: "none" }}>
          <input ref={leftGalleryRef} type="file" accept="image/*" onChange={(e) => applyImage("left", e.target.files?.[0])} />
          <input ref={leftCameraRef} type="file" accept="image/*" capture="environment" onChange={(e) => applyImage("left", e.target.files?.[0])} />
          <input ref={leftFileRef} type="file" onChange={(e) => applyImage("left", e.target.files?.[0])} />
          <input ref={rightGalleryRef} type="file" accept="image/*" onChange={(e) => applyImage("right", e.target.files?.[0])} />
          <input ref={rightCameraRef} type="file" accept="image/*" capture="environment" onChange={(e) => applyImage("right", e.target.files?.[0])} />
          <input ref={rightFileRef} type="file" onChange={(e) => applyImage("right", e.target.files?.[0])} />
        </div>
        {advice && (
          <div style={{ 
            margin: "0 20px 20px 20px", 
            backgroundColor: "#fff9c4", 
            borderLeft: "6px solid #fbc02d", 
            padding: "15px", 
            borderRadius: "12px",
            boxShadow: "0 2px 4px rgba(0,0,0,0.05)"
          }}>
            <div style={{ display: "flex", alignItems: "center", gap: "8px", marginBottom: "5px" }}>
              <span style={{ fontSize: "18px" }}>💡</span>
              <h4 style={{ margin: 0, color: "#f57f17", fontSize: "16px", fontWeight: "bold" }}>คำแนะนำจากแอดมิน</h4>
            </div>
            <p style={{ margin: 0, fontSize: "14px", color: "#444", lineHeight: "1.5" }}>
              {advice.message}
            </p>
          </div>
        )}

        <section className={styles.formSection}>
  <h3 style={{ marginBottom: "15px" }}>รายละเอียด</h3>

  <div className={styles.formRow} style={{ marginBottom: "15px" }}>
    <label>ผู้ทำกิจกรรม</label>
    <input 
      value={operator} 
      onChange={(e) => setOperator(e.target.value)} 
      placeholder="ระบุชื่อผู้ทำกิจกรรม"
      style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} 
    />
  </div>

  <div className={styles.formRow} style={{ marginBottom: "15px" }}>
    <label>วันที่ทำกิจกรรม</label>
    <div style={{ position: 'relative', width: '100%' }}>
      {/* ช่องโชว์วันที่ไทย พ.ศ. */}
      <input 
        type="text" 
        value={thaiActivityDate} 
        readOnly 
        onClick={() => document.getElementById('activityPicker').showPicker()} 
        style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc", backgroundColor: "#fff", cursor: "pointer" }} 
      />
      <div 
        onClick={() => document.getElementById('activityPicker').showPicker()}
        style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', cursor: 'pointer' }}
      >
        📅
      </div>
      {/* ปฏิทินจริงที่ซ่อนไว้ */}
      <input 
        id="activityPicker"
        type="date" 
        value={activityDate} 
        onChange={(e) => {
          const newDate = e.target.value; // YYYY-MM-DD
          setActivityDate(newDate);
          const [y, m, d] = newDate.split('-');
          setThaiActivityDate(`${d}/${m}/${parseInt(y) + 543}`);
        }} 
        style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', opacity: 0, pointerEvents: 'none' }} 
      />
    </div>
  </div>
          

          {isLandLevelingActivity && (
  <div style={{ background: "#f9f9f9", padding: "15px", borderRadius: "12px", marginBottom: "20px", border: "1px solid #e0e0e0" }}>
    <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>การเผาฟาง</label>
    <div style={{ display: "flex", gap: "30px", marginBottom: "15px" }}>
      <label style={{ display: "flex", alignItems: "center", gap: "8px" }}>
        <input type="radio" name="straw" value="burn" checked={strawBurning === "burn"} onChange={() => setStrawBurning("burn")} /> เผา
      </label>
      <label style={{ display: "flex", alignItems: "center", gap: "8px" }}>
        <input type="radio" name="straw" value="no-burn" checked={strawBurning === "no-burn"} onChange={() => setStrawBurning("no-burn")} /> ไม่เผา
      </label>
    </div>

    <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>ผลการตรวจดิน</label>
    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: "10px" }}>
      
      {/* 1. ค่า pH Dropdown 0-14 */}
      <select 
        value={soilPh} 
        onChange={(e) => setSoilPh(e.target.value)} 
        style={{ width: "100%", padding: "8px", borderRadius: "8px", border: "1px solid #ccc", backgroundColor: "#fff" }}
      >
        <option value="">ค่า pH</option>
        {[...Array(15)].map((_, i) => (
          <option key={i} value={i}>{i}</option>
        ))}
      </select>

      {/* 2. N-P-K กำหนดรูปแบบเป็น xx-xx-xx (ใช้ Text) */}
      <input 
  type="text"
  placeholder="N - P - K" 
  value={soilNpk} 
  onChange={(e) => {
    // 1. รับเฉพาะตัวเลข
    let value = e.target.value.replace(/\D/g, ""); 
    
    // 2. จำกัดความยาวไม่เกิน 6 ตัวเลข (เพื่อรูปแบบ xx-xx-xx)
    if (value.length > 6) value = value.slice(0, 6);
    
    // 3. ใส่เครื่องหมาย - เมื่อพิมพ์ถึงตำแหน่งที่กำหนด
    let maskedValue = "";
    if (value.length > 0) {
      maskedValue = value.substring(0, 2);
      if (value.length > 2) {
        maskedValue += "-" + value.substring(2, 4);
      }
      if (value.length > 4) {
        maskedValue += "-" + value.substring(4, 6);
      }
    }
    setSoilNpk(maskedValue);
  }} 
  style={{ 
    width: "100%", 
    padding: "8px", 
    borderRadius: "8px", 
    border: "1px solid #ccc",
    textAlign: "center" // จัดกลางให้อ่านง่ายขึ้นเหมือนในรูป
  }} 
/>

      {/* 3. อินทรียวัตถุ รับเฉพาะตัวอักษร (กรองตัวเลขออก) */}
      <input 
        type="text"
        placeholder="อินทรียวัตถุ" 
        value={soilOrganicMatter} 
        onChange={(e) => {
          const value = e.target.value;
          // กรองเอาเฉพาะตัวที่ไม่ใช่ตัวเลข
          const filtered = value.split('').filter(char => isNaN(parseInt(char))).join('');
          setSoilOrganicMatter(filtered);
        }} 
        style={{ width: "100%", padding: "8px", borderRadius: "8px", border: "1px solid #ccc" }} 
      />
    </div>
  </div>
)} 


          {isWaterManagementActivity && (
            <div style={{ background: "#f9f9f9", padding: "15px", borderRadius: "12px", marginBottom: "20px", border: "1px solid #e0e0e0" }}>
              <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>ขังน้ำในแปลง</label>
              <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
                <label style={{ display: "flex", alignItems: "center", gap: "10px" }}><input type="radio" name="water" value="above-5" checked={waterLevel === "above-5"} onChange={() => setWaterLevel("above-5")} /> ขังน้ำเหนือพื้นดิน 5 ชม.</label>
                <label style={{ display: "flex", alignItems: "center", gap: "10px" }}><input type="radio" name="water" value="below-10" checked={waterLevel === "below-10"} onChange={() => setWaterLevel("below-10")} /> ต่ำกว่าพื้นดิน 10 ชม.</label>
                <label style={{ display: "flex", alignItems: "center", gap: "10px" }}><input type="radio" name="water" value="below-15" checked={waterLevel === "below-15"} onChange={() => setWaterLevel("below-15")} /> ต่ำกว่าพื้นดิน 15 ชม.</label>
              </div>
            </div>
          )}

          {isFertilizerActivity && (
            <div style={{ background: "#f9f9f9", padding: "15px", borderRadius: "12px", marginBottom: "20px", border: "1px solid #e0e0e0" }}>
              <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>ประเภทปุ๋ย</label>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "15px", marginBottom: "15px" }}>
                {["46-0-0", "0-0-60", "16-20-0", "ปุ๋ยอินทรีย์"].map((type) => (
                  <label key={type} style={{ display: "flex", alignItems: "center", gap: "8px" }}><input type="radio" name="fert" value={type} checked={fertilizerType === type} onChange={() => setFertilizerType(type)} /> {type}</label>
                ))}
              </div>
              <label style={{ fontWeight: "bold", display: "block", marginBottom: "5px" }}>ปริมาณที่ใช้</label>
              <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                <input type="number" value={fertilizerAmount} onChange={(e) => setFertilizerAmount(e.target.value)} placeholder="ระบุจำนวน" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
                <span style={{ fontWeight: "bold" }}>กิโลกรัม/ไร่</span>
              </div>
            </div>
          )}

          {(isPestActivity || isDiseaseActivity) && (
            <>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>ประเภท{isPestActivity ? "ศัตรูพืช" : "โรคพืช"}</label>
                <input value={pestType} onChange={(e) => setPestType(e.target.value)} placeholder={isPestActivity ? "เช่น เพลี้ยกระโดด" : "เช่น โรคไหม้"} style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
              </div>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>ชื่อสามัญสารเคมี</label>
                <input  value={chemicalName} onChange={(e) => setChemicalName(e.target.value)} placeholder="ชื่อยาที่ใช้" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
              </div>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>ปริมาณที่ใช้</label>
                <div style={{ display: "flex", gap: "10px" }}>
                  <input type="number" value={chemicalAmount} onChange={(e) => setChemicalAmount(e.target.value)} placeholder="ปริมาณ" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
                  <select value={chemicalUnit} onChange={(e) => setChemicalUnit(e.target.value)} style={{ width: "100px", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }}>
                    <option value="มล.">มล.</option><option value="ซีซี">ซีซี</option><option value="กรัม">กรัม</option>
                  </select>
                </div>
              </div>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>อัตราส่วนต่อน้ำ (ลิตร)</label>
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <input type="number" value={ratioPerWater} onChange={(e) => setRatioPerWater(e.target.value)} style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
                  <span style={{ fontWeight: "bold" }}>ลิตร</span>
                </div>
              </div>
            </>
          )}

          {isHarvestActivity && (
  <>
    <h3 style={{ marginTop: "10px", marginBottom: "15px" }}>ข้อมูลการเก็บเกี่ยว</h3>
    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>วันที่เริ่มการเก็บเกี่ยว</label>
      <input 
        type="text" 
        placeholder="เช่น 20/05/2569"
        value={thaiHarvestStart} // 🚩 อย่าลืมเพิ่ม State นี้ไว้ด้านบนด้วยนะ
        onChange={(e) => handleThaiDateChange(e.target.value, setHarvestStartDate, setThaiHarvestStart)} 
        style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} 
      />
    </div>
    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>วันที่สิ้นสุดการเก็บเกี่ยว</label>
      <input 
        type="text" 
        placeholder="เช่น 23/05/2569"
        value={thaiHarvestEnd} 
        onChange={(e) => handleThaiDateChange(e.target.value, setHarvestEndDate, setThaiHarvestEnd)} 
        style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} 
      />
    </div>
              <h3 style={{ marginTop: "10px", marginBottom: "15px" }}>สรุปผลการเก็บเกี่ยว</h3>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>ผลการผลิต (กิโลกรัม)</label>
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <input type="number" value={totalYield} onChange={(e) => setTotalYield(e.target.value)} placeholder="ปริมาณ" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
                  <span style={{ fontWeight: "bold" }}>กิโลกรัม</span>
                </div>
              </div>
              <div className={styles.formRow} style={{ marginBottom: "15px" }}>
                <label>ความชื้นของข้าวเปลือกสด (%)</label>
                <input type="number" value={moisture} onChange={(e) => setMoisture(e.target.value)} placeholder="ระบุ % ความชื้น" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
              </div>
            </>
          )}

          {/* 💰 6. การขายข้าว (ปรับปรุงตาม image_3d63e8.png) */}
{isSaleActivity && (
  <>
  
    <h3 style={{ marginTop: "10px", marginBottom: "15px" }}>ข้อมูลหลังขายเข้าโรงสี</h3>
    
    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>โรงสี</label>
      <input value={millName} onChange={(e) => setMillName(e.target.value)} placeholder="ระบุชื่อโรงสี" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>ชื่อสินค้า</label>
      <input value={productName} onChange={(e) => setProductName(e.target.value)} placeholder="ระบุชื่อสินค้า" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>วันที่ขาย (วว/ดด/พ.ศ.)</label>
      <input 
        type="text" 
        placeholder="เช่น 25/05/2569"
        value={thaiSaleDate} 
        onChange={(e) => handleThaiDateChange(e.target.value, setSaleDate, setThaiSaleDate)} 
        style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} 
      />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>คันที่</label>
      <input value={carNo} onChange={(e) => setCarNo(e.target.value)} placeholder="ระบุรายละเอียดเพิ่มเติม" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>เลขที่ตั๋ว</label>
      <input value={ticketNo} onChange={(e) => setTicketNo(e.target.value)} placeholder="ระบุรายละเอียดเพิ่มเติม" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>ทะเบียน </label>
      <input value={plateNo} onChange={(e) => setPlateNo(e.target.value)} placeholder="ระบุทะเบียน" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>รายการ</label>
      <input value={productName} onChange={(e) => setProductName(e.target.value)} placeholder="ระบุรายการ" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
    </div>

{/* 🌟 แสดงเฉพาะเวลาเท่านั้น */}
    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>เวลาที่ขาย</label>
      <input 
       type="time" 
        value={saleTime} onChange={(e) => setSaleTime(e.target.value)} style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} 
      />
    </div>


    {/* แถวข้อมูลตัวเลขแบบมีหน่วยด้านหลังตามรูปภาพ */}
    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>ผลการผลิต</label>
      <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
        <input type="number" value={yieldKg} onChange={(e) => setYieldKg(e.target.value)}placeholder="0.00" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
        <span style={{ fontWeight: "bold", minWidth: "65px" }}>กิโลกรัม</span>
      </div>
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>น้ำหนัก</label>
      <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
        <input type="number" value={totalWeight} onChange={(e) => setTotalWeight(e.target.value)}placeholder="0.00" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
        <span style={{ fontWeight: "bold", minWidth: "65px" }}>กิโลกรัม</span>
      </div>
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>น้ำหนักสินค้าสุทธิ</label>
      <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
        <input type="number" value={netWeightKg} onChange={(e) => setNetWeightKg(e.target.value)} placeholder="0.00" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
        <span style={{ fontWeight: "bold", minWidth: "65px" }}>กิโลกรัม</span>
      </div>
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>รวมรายได้</label>
      <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
        <input type="number" value={totalIncome} onChange={(e) => setTotalIncome(e.target.value)} placeholder="0.00" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc", fontWeight: "bold", color: "#2e7d32" }} />
        <span style={{ fontWeight: "bold", minWidth: "65px" }}>บาท</span>
      </div>
    </div>

    <div className={styles.formRow} style={{ marginBottom: "15px" }}>
      <label>ราคาต่อ กก. </label>
      <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
        <input type="number" value={pricePerKg} onChange={(e) => setPricePerKg(e.target.value)} placeholder="0.00" style={{ flex: 1, padding: "10px", borderRadius: "8px", border: "1px solid #ccc" }} />
        <span style={{ fontWeight: "bold", minWidth: "65px" }}>บาท</span>
      </div>
    </div>

  </>
)}

          <div className={styles.formColumn} style={{ marginBottom: "20px", marginTop: "15px" }}>
            <label>{isHarvestActivity ? "ปัญหาที่พบระหว่างการเก็บเกี่ยว" : "ปัญหาที่เจอ / หมายเหตุ"}</label>
            <textarea value={issueText} onChange={(e) => setIssueText(e.target.value)} placeholder="ระบุปัญหาที่พบ" style={{ width: "100%", padding: "10px", borderRadius: "8px", border: "1px solid #ccc", minHeight: "80px" }} />
          </div>

          <div className={styles.actionRow} style={{ display: "flex", gap: "10px", marginTop: "20px" }}>
            <button className={styles.saveBtn} onClick={handleSaveActivity} disabled={isSaving} style={{ flex: 1, backgroundColor: "#2e7d32", color: "white", padding: "12px", borderRadius: "8px", border: "none", fontWeight: "bold", fontSize: "16px" }}>
              {isSaving ? "กำลังบันทึก..." : "บันทึก"}
            </button>
            <button className={styles.editBtn} style={{ flex: 1, backgroundColor: "#e0e0e0", color: "#333", padding: "12px", borderRadius: "8px", border: "none", fontWeight: "bold", fontSize: "16px" }}>
              แก้ไข
            </button>
          </div>
        </section>
      </main>
    </div>
    
  );
}
export default function PlotTrackingDetailPage() {
  return (
    <Suspense fallback={
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        กำลังโหลดข้อมูล...
      </div>
    }>
      <PlotTrackingContent />
    </Suspense>
  );
}
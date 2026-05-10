"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import dynamic from "next/dynamic";
import { MapPin } from "lucide-react";
import "./create-plan.css";

const MapPicker = dynamic(() => import("@/components/MapPicker"), { ssr: false });
const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

// --- Types & Helpers (คงเดิม) ---
type RiceVariety = { id: string; name: string; grow_duration_days?: number; };
type LatLngPoint = { lat: number; lng: number; };
type LocationSearchResult = { id: string; name: string; lat: number; lng: number; };

const FALLBACK_RICE_VARIETIES: RiceVariety[] = [
  { id: "mock-rd6", name: "กข6", grow_duration_days: 130 },
  { id: "mock-rd15", name: "กข15", grow_duration_days: 120 },
  { id: "mock-pathum1", name: "ปทุมธานี 1", grow_duration_days: 110 },
];

const asRecord = (value: unknown): Record<string, unknown> | null => {
  if (typeof value !== "object" || value === null) return null;
  return value as Record<string, unknown>;
};

const pickFirst = (obj: Record<string, unknown>, keys: string[]): unknown => {
  for (const key of keys) { if (key in obj) return obj[key]; }
  return undefined;
};

const normalizeRiceVarieties = (raw: unknown): RiceVariety[] => {
  const rawObj = asRecord(raw);
  const sourceCandidate = Array.isArray(raw) ? raw : rawObj?.data || rawObj?.items || rawObj?.results || [];
  if (!Array.isArray(sourceCandidate)) return [];
  return sourceCandidate.map((item) => {
    const obj = asRecord(item);
    if (!obj) return null;
    const id = pickFirst(obj, ["id", "rice_id", "uuid", "_id"]);
    const name = pickFirst(obj, ["name", "rice_name", "variety_name"]);
    const durationRaw = pickFirst(obj, ["grow_duration_days", "growDays", "duration_days"]);
    const grow_duration_days = durationRaw === undefined || durationRaw === null || durationRaw === "" ? undefined : Number(durationRaw);
    if (!id || !name) return null;
    return { id: String(id), name: String(name), grow_duration_days: Number.isNaN(grow_duration_days) ? undefined : grow_duration_days };
  }).filter(Boolean) as RiceVariety[];
};

// --- Main Component ---
export default function CreatePlanPage() {
  const router = useRouter();

  const [season, setSeason] = useState("");
  const [landName, setLandName] = useState("");
  const [plantingType, setPlantingType] = useState("ข้าว");
  const [riceType, setRiceType] = useState("");
  
  // 🌟 ส่วนวันที่: startDate เก็บ ค.ศ. (ส่ง API), thaiDateInput เก็บ พ.ศ. (แสดงผล)
  const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]); // ตั้งค่าเริ่มต้นเป็นวันนี้
  const [thaiDateInput, setThaiDateInput] = useState(""); 
  const [harvestDate, setHarvestDate] = useState(""); 
  
  const [rai, setRai] = useState("");
  const [ngan, setNgan] = useState("");
  const [wah, setWah] = useState("");
  const [meter, setMeter] = useState("");
  const [riceVarieties, setRiceVarieties] = useState<RiceVariety[]>([]);

  const [selectedPosition, setSelectedPosition] = useState<LatLngPoint | null>({ lat: 13.7563, lng: 100.5018 });
  const [isGettingLocation, setIsGettingLocation] = useState(false);
  const [locationKeyword, setLocationKeyword] = useState("");
  const [locationResults, setLocationResults] = useState<LocationSearchResult[]>([]);
  const [isSearchingLocation, setIsSearchingLocation] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  

  // 1. ดึงข้อมูลพันธุ์ข้าว
  useEffect(() => {
    const fetchRiceVarieties = async () => {
      try {
        const response = await fetch(`${API_URL}/master/rice-varieties`, {
          headers: { "ngrok-skip-browser-warning": "true" },
        });
        if (response.ok) {
          const data = await response.json();
          const normalized = normalizeRiceVarieties(data);
          setRiceVarieties(normalized.length > 0 ? normalized : FALLBACK_RICE_VARIETIES);
        } else { setRiceVarieties(FALLBACK_RICE_VARIETIES); }
      } catch { setRiceVarieties(FALLBACK_RICE_VARIETIES); }
    };
    fetchRiceVarieties();
  }, []);

  // 2. 🌟 ฟังก์ชันจัดการวันที่ไทย (พ.ศ.)
  const handleThaiDateChange = (value: string) => {
    setThaiDateInput(value);
    // ตรวจสอบรูปแบบ วว/ดด/พ.ศ. (เช่น 24/03/2569)
    const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = value.match(datePattern);

    if (match) {
      const day = match[1];
      const month = match[2];
      const yearInter = parseInt(match[3]) - 543; // แปลง พ.ศ. เป็น ค.ศ.
      setStartDate(`${yearInter}-${month}-${day}`);
    }
  };

  // 3. 🌟 คำนวณวันเก็บเกี่ยว
  useEffect(() => {
  if (!startDate || !riceType || riceVarieties.length === 0) { 
    setHarvestDate(""); 
    return; 
  }
  
  
  const selectedRice = riceVarieties.find((rice) => String(rice.id) === String(riceType));
  const duration = selectedRice?.grow_duration_days || 120; 
  
  const dateObj = new Date(startDate);
  dateObj.setDate(dateObj.getDate() + duration);
  setHarvestDate(dateObj.toISOString().split('T')[0]);
}, [startDate, riceType, riceVarieties]);

  
  const formatToThaiDisplay = (isoDate: string) => {
    if (!isoDate) return "";
    const [y, m, d] = isoDate.split("-");
    return `${d}/${m}/${parseInt(y) + 543}`;
  };

  // 🌟 ฟังก์ชันจัดการพิกัด
  const handleManualCoordChange = (type: 'lat' | 'lng', value: string) => {
    const numValue = parseFloat(value);
    const currentPos = selectedPosition || { lat: 13.7563, lng: 100.5018 };
    setSelectedPosition(type === 'lat' ? { ...currentPos, lat: numValue } : { ...currentPos, lng: numValue });
  };

  const handleUseCurrentLocation = () => {
    if (typeof window === "undefined" || !navigator.geolocation) return alert("ไม่รองรับ GPS");
    setIsGettingLocation(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setSelectedPosition({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        setIsGettingLocation(false);
      },
      () => { alert("ดึงตำแหน่งไม่สำเร็จ"); setIsGettingLocation(false); }
    );
  };

  const handleSubmit = async () => {
    const currentUserId = localStorage.getItem("user_id");
    
    // 1. ตรวจสอบ Login
    if (!currentUserId) {
      alert("ไม่พบข้อมูลผู้ใช้งาน กรุณาล็อกอินใหม่");
      return router.push("/login");
    }

    // 2. ตรวจสอบข้อมูลที่จำเป็น (Validation)
    if (!landName.trim()) return alert("กรุณาระบุชื่อแปลง");
    if (!riceType) return alert("กรุณาเลือกพันธุ์ข้าว");
    if (!startDate) return alert("กรุณาระบุวันที่เริ่มปลูกให้ถูกต้อง (วว/ดด/พ.ศ.)");
    
    const calculatedSqWa = Math.round(
    (parseInt(ngan || "0") * 100) + 
    parseInt(wah || "0") + 
    (parseFloat(meter || "0") / 4)
  );

    // 3. เตรียมข้อมูลให้ตรงกับที่ Backend (FastAPI) ต้องการ
    const payload = {
    user_id: currentUserId,
    plot_name: landName.trim(),
    season_type: season || "นาปี",
    planting_type: plantingType,
    rice_id: riceType,
    start_date: startDate, 
    expected_harvest_date: harvestDate,
    latitude: selectedPosition?.lat || null,
    longitude: selectedPosition?.lng || null,
    area_rai: parseInt(rai, 10) || 0,
    area_sq_wa: calculatedSqWa, // 🚩 ค่าที่ส่งตอนนี้จะเป็นจำนวนเต็มแน่นอน
  };
    console.log("🚀 กำลังส่งข้อมูล:", payload); // เอาไว้ดูใน Console ของ Browser

    try {
      const response = await fetch(`${API_URL}/plans/integrated`, {
        method: "POST",
        headers: { 
          "Content-Type": "application/json",
          "ngrok-skip-browser-warning": "true" 
        },
        body: JSON.stringify(payload),
      });

      if (response.ok) {
        alert("✅ บันทึกแผนการปลูกสำเร็จ!");
        router.push("/home");
      } else {
        const errorDetail = await response.json();
        console.error("❌ Error 422/500:", errorDetail);
        alert(`เกิดข้อผิดพลาด: ${errorDetail.detail?.[0]?.msg || "ตรวจสอบข้อมูลอีกครั้ง"}`);
      }
    } catch (error) {
      console.error("❌ Network Error:", error);
      alert("ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาเช็คอินเทอร์เน็ตหรือสถานะ Backend");
    } finally {
      setIsSubmitting(false);
    }
  };
  return (
    <div className="page-container">
      <div className="top-bar">
        <button className="back-btn-modern" onClick={() => router.back()}>‹</button>
        <h3>สร้างแผนปลูกข้าว</h3>
      </div>

      <div className="form-container">
        <label>ชื่อแปลง</label>
        <input type="text" value={landName} onChange={(e) => setLandName(e.target.value)} placeholder="เช่น นาสมศรี" />

        <label>ฤดูกาลปลูก</label>
        <div className="radio-group">
          {["นาปี", "นาปรัง 1", "นาปรัง 2", "นาปรัง 3"].map((item) => (
            <label key={item} className="radio-item">
              <input type="radio" name="season" value={item} checked={season === item} onChange={(e) => setSeason(e.target.value)} /> {item}
            </label>
          ))}
        </div>
          <label>เลือกประเภทที่ปลูก</label>
        <select value={plantingType} onChange={(e) => setPlantingType(e.target.value)}>
          <option value="ข้าว">ข้าว</option>
          <option value="ข้าวโพด">ข้าวโพด</option>
          <option value="มันสำปะหลัง">มันสำปะหลัง</option>
        </select>

        <label>พันธุ์ข้าวที่ปลูก</label>
        <select value={riceType} onChange={(e) => setRiceType(e.target.value)}>
          <option value="">-- เลือกพันธุ์ข้าว --</option>
          {riceVarieties.map((rice) => (
            <option key={rice.id} value={rice.id}>{rice.name} ({rice.grow_duration_days ?? "-"} วัน)</option>
          ))}
        </select>

        {/* 🌟 ส่วนกรอกวันที่ไทย */}
  <label style={{ marginTop: "20px" }}>
  วันที่เริ่มปลูก (พ.ศ. {parseInt(startDate.split('-')[0]) + 543})
</label>
<div style={{ position: 'relative', width: '100%' }}>
  {/* ช่องแสดงผลวันที่แบบไทย (พ.ศ.) */}
  <input 
    type="text" 
    value={formatToThaiDisplay(startDate)} 
    readOnly 
    onClick={() => document.getElementById('hiddenDatePicker').showPicker()} 
    style={{ 
      width: "100%", 
      padding: "10px", 
      paddingRight: "40px", 
      borderRadius: "8px", 
      border: "1px solid #ccc", 
      backgroundColor: "#ffffff",
      cursor: "pointer"
    }} 
  />
  {/* ไอคอนปฏิทินที่กดแล้วจะเปิดที่เลือกวันที่ */}
  <div 
    onClick={() => document.getElementById('hiddenDatePicker').showPicker()}
    style={{ 
      position: 'absolute', 
      right: '12px', 
      top: '50%', 
      transform: 'translateY(-50%)', 
      cursor: 'pointer'
    }}
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
      <line x1="16" y1="2" x2="16" y2="6"></line>
      <line x1="8" y1="2" x2="8" y2="6"></line>
      <line x1="3" y1="10" x2="21" y2="10"></line>
    </svg>
  </div>
  {/* Input วันที่จริงๆ ที่ซ่อนไว้เพื่อใช้เรียกปฏิทิน */}
  <input 
    id="hiddenDatePicker"
    type="date" 
    value={startDate} 
    onChange={(e) => setStartDate(e.target.value)} 
    style={{ 
      position: 'absolute',
      top: 0,
      left: 0,
      width: '100%',
      height: '100%',
      opacity: 0,
      pointerEvents: 'none'
    }} 
  />
</div>

<label style={{ marginTop: "15px" }}>วันที่คาดว่าจะเก็บเกี่ยว (พ.ศ.)</label>
<div style={{ position: 'relative', width: '100%' }}>
  <input 
    type="text" 
    value={harvestDate ? formatToThaiDisplay(harvestDate) : "รอเลือกวันที่  ..."} 
    readOnly 
    style={{ 
      width: "100%", 
      padding: "10px", 
      paddingRight: "40px", // เว้นที่ด้านขวาให้ไอคอน
      borderRadius: "8px", 
      border: "1px solid #ccc", 
      backgroundColor: "#ffffff", // ใช้สีขาวให้เหมือนช่องบน
      color: "#000000", 
      fontWeight: "500",
      fontSize: "14px",
      outline: "none"
    }} 
  />
  {/* ไอคอนปฏิทินทางขวาให้ตำแหน่งตรงกับช่องบนเป๊ะๆ */}
  <div style={{ 
    position: 'absolute', 
    right: '12px', 
    top: '50%', 
    transform: 'translateY(-50%)', 
    display: 'flex',
    alignItems: 'center',
    pointerEvents: 'none'
  }}>
    <svg 
      width="16" 
      height="16" 
      viewBox="0 0 24 24" 
      fill="none" 
      stroke="currentColor" 
      strokeWidth="2" 
      strokeLinecap="round" 
      strokeLinejoin="round" 
      style={{ color: "#333" }}
    >
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
      <line x1="16" y1="2" x2="16" y2="6"></line>
      <line x1="8" y1="2" x2="8" y2="6"></line>
      <line x1="3" y1="10" x2="21" y2="10"></line>
    </svg>
  </div>
</div>

        {/* 🌟 ส่วนพิกัดกรอกเอง */}
        <label>พิกัดแปลงนา (กำหนดเองหรือ GPS)</label>
        <div style={{ display: "flex", gap: "10px", marginBottom: "10px" }}>
          <input type="number" step="any" value={selectedPosition?.lat || ""} onChange={(e) => handleManualCoordChange('lat', e.target.value)} placeholder="Lat" />
          <input type="number" step="any" value={selectedPosition?.lng || ""} onChange={(e) => handleManualCoordChange('lng', e.target.value)} placeholder="Lng" />
        </div>

        <button type="button" className="gps-btn" onClick={handleUseCurrentLocation} disabled={isGettingLocation}>
          <MapPin size={16} /> {isGettingLocation ? "กำลังดึง..." : "ใช้ตำแหน่งปัจจุบันของคุณ"}
        </button>

        <div style={{ height: "300px", marginTop: "10px", borderRadius: "12px", overflow: "hidden", border: "1px solid #ddd" }}>
          <MapPicker onSelect={(pos) => setSelectedPosition(pos)} selectedPosition={selectedPosition} />
        </div>

        <label style={{ marginTop: "20px" }}>พื้นที่ปลูก</label>
<div className="area-group" style={{ 
  display: 'flex', 
  flexWrap: 'wrap', 
  gap: '10px', 
  alignItems: 'center' 
}}>
  {/* ไร่ */}
  <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
    <input 
      type="number" 
      value={rai} 
      onChange={(e) => setRai(e.target.value)} 
      placeholder="0"
      style={{ width: '60px', padding: '8px', borderRadius: '8px', border: '1px solid #ccc' }}
    />
    <span>ไร่</span>
  </div>

  {/* งาน */}
  <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
    <input 
      type="number" 
      value={ngan} 
      onChange={(e) => setNgan(e.target.value)} 
      placeholder="0"
      style={{ width: '60px', padding: '8px', borderRadius: '8px', border: '1px solid #ccc' }}
    />
    <span>งาน</span>
  </div>

  {/* ตารางวา */}
  <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
    <input 
      type="number" 
      value={wah} 
      onChange={(e) => setWah(e.target.value)} 
      placeholder="0"
      style={{ width: '60px', padding: '8px', borderRadius: '8px', border: '1px solid #ccc' }}
    />
    <span>ตร.ว.</span>
  </div>

  {/* ตารางเมตร (เพิ่มส่วนนี้) */}
  <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
    <input 
      type="number" 
      value={meter} 
      onChange={(e) => setMeter(e.target.value)} 
      placeholder="0"
      style={{ width: '60px', padding: '8px', borderRadius: '8px', border: '1px solid #ccc' }}
    />
    <span>ตร.ม.</span>
  </div>
</div>

        <button className="save-btn" onClick={handleSubmit} disabled={isSubmitting}>
          {isSubmitting ? "กำลังบันทึก..." : "ยืนยันการสร้างแผน"}
        </button>
      </div>
            <div className="bottom-nav">
        <div className="nav-item" onClick={() => router.push("/home")}>🏠<span>หน้าหลัก</span></div>
        <div className="nav-item" onClick={() => router.push("/weather")}>☀️<span>อากาศ</span></div>
        <div className="nav-item" onClick={() => router.push("/map")}>🗺️<span>แผนที่</span></div>
        <div className="nav-item" onClick={() => router.push("/reports")}>👥<span>รายงาน</span></div>
        <div className="nav-item" onClick={() => router.push("/settings")}>⚙️<span>ตั้งค่า</span></div>
      </div>
    </div>
  );
}
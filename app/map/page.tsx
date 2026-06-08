"use client";

import { useEffect, useState, useRef } from "react";
import { useRouter } from "next/navigation";
import dynamic from "next/dynamic";
import styles from "./map.module.css";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

type PlotMapData = {
  plot_id: string;
  farm_id: string;
  plot_name: string;
  lat: number;
  lng: number;
  has_location: boolean;
  area_rai: number;
  area_ngan: number;
  area_sq_wa: number;
  has_plan: boolean;
  plan_id: string | null;
  rice_name: string | null;
  season_type: string | null;
  start_date: string | null;
  harvest_date: string | null;
};

// โหลด MapView แบบ dynamic เพื่อป้องกัน SSR error
const MapView = dynamic(() => import("./MapView"), { ssr: false, loading: () => <div className={styles.mapLoading}>🗺️ กำลังโหลดแผนที่...</div> });

export default function MapPage() {
  const router = useRouter();
  const [plots, setPlots] = useState<PlotMapData[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedPlot, setSelectedPlot] = useState<PlotMapData | null>(null);
  const [noLocationPlots, setNoLocationPlots] = useState<PlotMapData[]>([]);

  useEffect(() => {
    const userId = localStorage.getItem("user_id");
    if (!userId) { router.push("/login"); return; }

    fetch(`${API_URL}/plots/map/${userId}`, {
      headers: { "ngrok-skip-browser-warning": "true" },
    })
      .then((r) => r.json())
      .then((data: PlotMapData[]) => {
        setPlots(data.filter((p) => p.has_location));
        setNoLocationPlots(data.filter((p) => !p.has_location));
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [router]);

  return (
    <div className={styles.page}>
      {/* Header */}
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push("/home")}>‹</button>
        <h1>แผนที่แปลงนา</h1>
        <span className={styles.plotCount}>{plots.length} แปลง</span>
      </header>

      {/* Map */}
      <div className={styles.mapWrap}>
        {loading ? (
          <div className={styles.mapLoading}>⏳ กำลังดึงข้อมูล...</div>
        ) : (
          <MapView
            plots={plots}
            selectedPlot={selectedPlot}
            onSelectPlot={setSelectedPlot}
          />
        )}
      </div>

      {/* Popup ข้อมูลแปลงที่เลือก */}
      {selectedPlot && (
        <div className={styles.popup}>
          <button className={styles.popupClose} onClick={() => setSelectedPlot(null)}>✕</button>
          <div className={styles.popupTitle}>{selectedPlot.plot_name}</div>
          <div className={styles.popupMeta}>
            <span className={`${styles.badge} ${selectedPlot.has_plan ? styles.badgeGreen : styles.badgeGray}`}>
              {selectedPlot.has_plan ? "มีแผนปลูก" : "ยังไม่มีแผน"}
            </span>
            <span className={styles.farmId}>{selectedPlot.farm_id}</span>
          </div>

          {selectedPlot.has_plan && (
            <div className={styles.popupInfo}>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>พันธุ์ข้าว</span>
                <span>{selectedPlot.rice_name || "-"}</span>
              </div>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>ฤดูกาล</span>
                <span>{selectedPlot.season_type || "-"}</span>
              </div>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>วันปลูก</span>
                <span>{selectedPlot.start_date || "-"}</span>
              </div>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>เก็บเกี่ยว</span>
                <span>{selectedPlot.harvest_date || "-"}</span>
              </div>
            </div>
          )}

          <div className={styles.popupInfo}>
            <div className={styles.infoRow}>
              <span className={styles.infoLabel}>พื้นที่</span>
              <span>
                {[
                  selectedPlot.area_rai ? `${selectedPlot.area_rai} ไร่` : "",
                  selectedPlot.area_ngan ? `${selectedPlot.area_ngan} งาน` : "",
                  selectedPlot.area_sq_wa ? `${selectedPlot.area_sq_wa} ตร.ว.` : "",
                ].filter(Boolean).join(" ") || "-"}
              </span>
            </div>
            <div className={styles.infoRow}>
              <span className={styles.infoLabel}>พิกัด</span>
              <span>{selectedPlot.lat.toFixed(5)}, {selectedPlot.lng.toFixed(5)}</span>
            </div>
          </div>

          {selectedPlot.plan_id && (
            <button
              className={styles.popupBtn}
              onClick={() => router.push(`/plant-tracking/${selectedPlot.plot_id}`)}
            >
              📋 ดูรายละเอียดแปลง
            </button>
          )}
        </div>
      )}

      {/* แปลงที่ไม่มีพิกัด */}
      {noLocationPlots.length > 0 && (
        <div className={styles.noLocSection}>
          <div className={styles.noLocTitle}>⚠️ แปลงที่ยังไม่มีพิกัด ({noLocationPlots.length})</div>
          <div className={styles.noLocList}>
            {noLocationPlots.map((p) => (
              <div key={p.plot_id} className={styles.noLocItem}>
                <span>{p.plot_name}</span>
                <span className={styles.noLocFarm}>{p.farm_id}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Bottom Nav */}
      <nav className={styles.bottomNav}>
        <button onClick={() => router.push("/home")}>🏠<span>หน้าหลัก</span></button>
        <button onClick={() => router.push("/weather")}>☀️<span>อากาศ</span></button>
        <button className={styles.navActive}>🗺️<span>แผนที่</span></button>
        <button onClick={() => router.push("/reports")}>👥<span>รายงาน</span></button>
        <button onClick={() => router.push("/settings")}>⚙️<span>ตั้งค่า</span></button>
      </nav>
    </div>
  );
}

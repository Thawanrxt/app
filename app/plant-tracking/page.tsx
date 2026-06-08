  "use client";

  import Image from "next/image";
  import { useMemo, useState, useEffect } from "react";
  import { useRouter } from "next/navigation";
  import { ChevronDown, ChevronUp, Search, CheckSquare, Clock, History as HistoryIcon, User, AlertCircle } from "lucide-react";
  import styles from "./plant-tracking.module.css";
  import BottomNav from "../components/BottomNav";
  import BackButton from "../components/BackButton";

  const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

  type Plot = {
    id: string;
    plot_name: string;
    area: string;
    location: string;
    image: string;
  };

  export default function PlantTrackingListPage() {
    const router = useRouter();
    const [keyword, setKeyword] = useState("");
    const [plots, setPlots] = useState<Plot[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    // 🌟 State สำหรับ Accordion กางดูความคืบหน้า
    const [expandedPlotId, setExpandedPlotId] = useState<string | null>(null);
    const [plotProgresses, setPlotProgresses] = useState<Record<string, any[]>>({});

    // 🌟 State สำหรับการดึงและแสดงประวัติย่อย (History) ภายในตาราง
    const [historyData, setHistoryData] = useState<Record<string, any[]>>({});
    const [showHistoryForStep, setShowHistoryForStep] = useState<{plotId: string, label: string} | null>(null);

    useEffect(() => {
      const fetchPlots = async () => {
        const userId = localStorage.getItem("user_id") || "00000000-0000-0000-0000-000000000001"; 
        try {
          const response = await fetch(`${API_URL}/tracking/active-plans/${userId}`, {
            headers: { "ngrok-skip-browser-warning": "true" }
          });
          if (response.ok) {
            const data = await response.json();
            setPlots(data);
          }
        } catch (error) {
          console.error("โหลดข้อมูลแปลงนาไม่สำเร็จ:", error);
        } finally {
          setIsLoading(false);
        }
      };
      fetchPlots();
    }, []);

    // 🌟 ฟังก์ชันจัดการตอนกดลูกศรกางออก
    const handleExpand = async (e: React.MouseEvent, plotId: string) => {
      e.stopPropagation(); 
      if (expandedPlotId === plotId) {
        setExpandedPlotId(null);
        return;
      }
      setExpandedPlotId(plotId);
      
      if (!plotProgresses[plotId]) {
        try {
          const response = await fetch(`${API_URL}/tracking/plan/${plotId}/progress`, {
            headers: { "ngrok-skip-browser-warning": "true" }
          });
          if (response.ok) {
            const progressData = await response.json(); 
            setPlotProgresses(prev => ({ ...prev, [plotId]: progressData }));
          }
        } catch (error) {
          console.error("ดึงข้อมูลความคืบหน้าไม่สำเร็จ:", error);
        }
      }
    };

    // 🌟 ฟังก์ชันดึงประวัติย่อยในแต่ละขั้นตอน (ปรับปรุง Filter ให้แม่นยำขึ้น)
    const fetchHistoryForStep = async (e: React.MouseEvent, plotId: string, label: string) => {
      e.stopPropagation();
      
      if (showHistoryForStep?.label === label && showHistoryForStep?.plotId === plotId) {
        setShowHistoryForStep(null);
        return;
      }

      try {
        const response = await fetch(`${API_URL}/tracking/plan/${plotId}/history`, {
          headers: { "ngrok-skip-browser-warning": "true" }
        });
        if (response.ok) {
          const data = await response.json();
          
          // 🌟 ปรับการ Filter ให้ฉลาดขึ้น รองรับทั้งคำว่า "จัดการ" และ "กำจัด"
          const filtered = data.filter((h: any) => {
            const dbName = h.activity_name.toLowerCase();
            const uiLabel = label.toLowerCase();
            
            return dbName.includes(uiLabel) || 
                  uiLabel.includes(dbName) ||
                  (uiLabel.includes("ศัตรูพืช") && dbName.includes("ศัตรูพืช")) ||
                  (uiLabel.includes("โรคพืช") && dbName.includes("โรคพืช"));
          });

          setHistoryData(prev => ({ ...prev, [`${plotId}-${label}`]: filtered }));
          setShowHistoryForStep({ plotId, label });
        }
      } catch (e) {
        console.error("Fetch history error:", e);
      }
    };

    const filteredPlots = useMemo(() => {
      const key = keyword.trim().toLowerCase();
      if (!key) return plots;
      return plots.filter((plot) =>
        `${plot.plot_name} ${plot.area} ${plot.location}`.toLowerCase().includes(key)
      );
    }, [keyword, plots]);

    return (
      <div className={styles.page}>
        <header className={styles.header}>
          <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
          <h1>ติดตามการปลูกข้าว</h1>
        </header>

        <main className={styles.content}>
          <div className={styles.searchRow}>
            <label className={styles.searchBox}>
              <Search size={24} />
              <input
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
                placeholder="ค้นหาแปลงของฉัน"
              />
            </label>
            <button className={styles.sortBtn} type="button"><span>↓↑</span></button>
          </div>

          <div className={styles.summaryRow}>
            <h2>แปลงของฉันทั้งหมด</h2>
            <p>({filteredPlots.length}) แปลง</p>
          </div>

          <section className={styles.plotList}>
            {isLoading ? (
              <p style={{ textAlign: "center", marginTop: "20px" }}>กำลังโหลดข้อมูล...</p>
            ) : filteredPlots.map((plot) => {
              const isExpanded = expandedPlotId === plot.id; 
              const currentProgress = plotProgresses[plot.id] || [];

              return (
                <div key={plot.id} className={styles.plotBlock} style={{ marginBottom: "16px", backgroundColor: "#fff", borderRadius: "12px", overflow: "hidden", boxShadow: "0 2px 8px rgba(0,0,0,0.05)" }}>
                  
                  <article
                    className={styles.plotCard}
                    onClick={() => router.push(`/plant-tracking/${encodeURIComponent(plot.id)}`)}
                    style={{ cursor: "pointer", display: "flex", padding: "12px", alignItems: "center", borderBottom: isExpanded ? "1px solid #f0f0f0" : "none" }}
                  >
                    <div className={styles.plotImage} style={{ position: "relative", width: "80px", height: "80px", borderRadius: "8px", overflow: "hidden", marginRight: "16px", flexShrink: 0 }}>
                      <Image src={plot.image} alt={plot.plot_name} fill style={{ objectFit: "cover" }} />
                    </div>

                    <div className={styles.plotInfo} style={{ flexGrow: 1 }}>
                      <h3 style={{ margin: "0 0 4px 0", fontSize: "18px", color: "#333" }}>{plot.plot_name}</h3>
                      <p style={{ margin: "0 0 2px 0", fontSize: "14px", color: "#666" }}>{plot.area}</p>
                      <p style={{ margin: "0", fontSize: "14px", color: "#666" }}>{plot.location}</p>
                    </div>

                    <button
                      className={styles.expandBtn}
                      type="button"
                      style={{ background: "none", border: "none", cursor: "pointer", padding: "8px" }}
                      onClick={(e) => handleExpand(e, plot.id)}
                    >
                      {isExpanded ? <ChevronUp size={28} color="#666" /> : <ChevronDown size={28} color="#666" />}
                    </button>
                  </article>

                  {isExpanded && (
                    <div style={{ padding: "16px", backgroundColor: "#fafafa" }}>
                      {currentProgress.length === 0 ? (
                        <p style={{ textAlign: "center", color: "#999", fontSize: "14px" }}>กำลังดึงข้อมูล...</p>
                      ) : (
                        currentProgress.map((step, idx) => {
                          const isCompleted = step.status === "completed";
                          const isInProgress = step.status === "in-progress";
                          const isHistoryOpen = showHistoryForStep?.plotId === plot.id && showHistoryForStep?.label === step.label;
                          const histories = historyData[`${plot.id}-${step.label}`] || [];

                          const iconColor = isCompleted || isInProgress ? "#2e7d32" : "#9e9e9e";
                          const badgeBg = isCompleted ? "#c8e6c9" : isInProgress ? "#ffe0b2" : "#e0e0e0";
                          const badgeColor = isCompleted ? "#2e7d32" : isInProgress ? "#e65100" : "#757575";

                          return (
                            <div key={idx} style={{ marginBottom: "15px" }}>
                              <div style={{ display: "flex", alignItems: "center", gap: "12px", marginBottom: "6px" }}>
                                {isCompleted ? <CheckSquare size={22} color="#2e7d32" /> : <Clock size={22} color="#999" />}
                                <span style={{ flexGrow: 1, fontSize: "16px", fontWeight: isCompleted ? "bold" : "normal" }}>{step.label}</span>
                                
                                <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                                  {isCompleted && (
                                    <button 
                                      onClick={(e) => fetchHistoryForStep(e, plot.id, step.label)}
                                      style={{ background: "#e1f5fe", border: "none", color: "#0288d1", cursor: "pointer", padding: "4px 10px", borderRadius: "8px", fontSize: "12px", fontWeight: "bold", display: "flex", alignItems: "center", gap: "4px" }}
                                    >
                                      <HistoryIcon size={14} /> ประวัติ
                                    </button>
                                  )}
                                  <span style={{ fontSize: "11px", padding: "3px 8px", borderRadius: "10px", backgroundColor: isCompleted ? "#e8f5e9" : "#eee", color: isCompleted ? "#2e7d32" : "#666", fontWeight: "bold" }}>
                                    {step.badge}
                                  </span>
                                </div>
                              </div>
                              
                              <div style={{ width: "100%", height: "6px", backgroundColor: "#eee", borderRadius: "3px", overflow: "hidden" }}>
                                <div style={{ width: `${step.percent}%`, height: "100%", backgroundColor: "#4caf50", borderRadius: "3px", transition: "width 0.4s ease" }} />
                              </div>

                              {isHistoryOpen && (
                                <div style={{ marginTop: "12px", marginLeft: "34px", padding: "12px", backgroundColor: "#fff", borderRadius: "10px", border: "1px dashed #0288d1", boxShadow: "inset 0 1px 4px rgba(0,0,0,0.02)" }}>
                                  {histories.length > 0 ? histories.map((h: any, hIdx: number) => (
                                    <div key={hIdx} style={{ fontSize: "13px", color: "#444", borderBottom: hIdx === histories.length - 1 ? "none" : "1px solid #f0f0f0", paddingBottom: "8px", marginBottom: "8px" }}>
                                      <div style={{ display: "flex", justifyContent: "space-between", fontWeight: "bold", color: "#333" }}>
                                        <span>📅 วันที่: {h.performed_at}</span>
                                        <span style={{ color: "#0288d1" }}>👤 {h.operator}</span>
                                      </div>
                                      {h.issue && h.issue !== "-" && (
                                        <div style={{ color: "#d32f2f", marginTop: "4px", display: "flex", alignItems: "center", gap: "4px" }}>
                                          <AlertCircle size={14} /> ปัญหา: {h.issue}
                                        </div>
                                      )}
                                    </div>
                                  )) : <p style={{ fontSize: "12px", color: "#999", textAlign: "center", margin: 0 }}>ไม่มีรายละเอียดประวัติ</p>}
                                </div>
                              )}
                            </div>
                          );
                        })
                      )}
                    </div>
                  )}
                </div>
              );
            })}
          </section>
        </main>

        <BottomNav activePath="" />
      </div>
    );
  }
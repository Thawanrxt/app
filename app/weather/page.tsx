"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Wind, Droplets, MapPin } from "lucide-react";
import styles from "./weather.module.css";
import BottomNav from "../components/BottomNav";
import BackButton from "../components/BackButton";

// --- Types ---
type WeatherViewData = {
  location: string;
  temp: number;
  description: string;
  humidity: number;
  windKmh: number;
  icon: string;
  hourly: Array<{ label: string; temp: number; icon: string }>;
  daily: Array<{ day: string; subtitle: string; min: number; max: number; icon: string }>;
};

export default function WeatherPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [data, setData] = useState<WeatherViewData | null>(null);

  const fetchWeatherData = async (lat: number, lon: number) => {
    const apiKey = process.env.NEXT_PUBLIC_WEATHER_API;
    if (!apiKey) {
      setError("กรุณาตั้งค่า API Key ในระบบ");
      return;
    }

    try {
      // 1. ดึงข้อมูลปัจจุบัน
      const currentRes = await fetch(
        `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&lang=th&appid=${apiKey}`
      );
      if (!currentRes.ok) throw new Error("ดึงข้อมูลสภาพอากาศปัจจุบันล้มเหลว");
      const current = await currentRes.json();

      // 2. ดึงข้อมูลพยากรณ์ (ใช้ 5-day/3-hour forecast เป็นตัวหลักเพราะฟรีและชัวร์)
      const forecastRes = await fetch(
        `https://api.openweathermap.org/data/2.5/forecast?lat=${lat}&lon=${lon}&units=metric&lang=th&appid=${apiKey}`
      );
      if (!forecastRes.ok) throw new Error("ดึงข้อมูลพยากรณ์ล้มเหลว");
      const forecast = await forecastRes.json();

      // จัดการข้อมูลรายชั่วโมง (เอา 4 ช่วงถัดไป)
      const hourlyData = forecast.list.slice(0, 4).map((item: any, i: number) => ({
        label: i === 0 ? "ตอนนี้" : new Date(item.dt * 1000).toLocaleTimeString("th-TH", { hour: "2-digit", minute: "2-digit" }),
        temp: Math.round(item.main.temp),
        icon: item.weather[0].icon
      }));

      // จัดการข้อมูลรายวัน (กรองเอาเฉพาะวันละ 1 ค่า)
      const dailyMap = new Map();
      forecast.list.forEach((item: any) => {
        const date = new Date(item.dt * 1000).toLocaleDateString("en-US", { weekday: "short" });
        if (!dailyMap.has(date)) {
          dailyMap.set(date, {
            day: date,
            subtitle: item.weather[0].description,
            min: Math.round(item.main.temp_min),
            max: Math.round(item.main.temp_max),
            icon: item.weather[0].icon
          });
        }
      });

      setData({
        location: `${current.name}, TH`,
        temp: Math.round(current.main.temp),
        description: current.weather[0].description,
        humidity: current.main.humidity,
        windKmh: Math.round(current.wind.speed * 3.6),
        icon: current.weather[0].icon,
        hourly: hourlyData,
        daily: Array.from(dailyMap.values()).slice(0, 5)
      });
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        (pos) => fetchWeatherData(pos.coords.latitude, pos.coords.longitude),
        () => {
          // ถ้าปิด GPS ให้ใช้ค่า Default (เช่น กรุงเทพฯ)
          fetchWeatherData(13.7563, 100.5018);
        }
      );
    }
  }, []);

  const humidityWidth = useMemo(() => `${data?.humidity || 0}%`, [data]);

  return (
    <div className={styles.page}>
      {/* Header */}
      <header className={styles.header}>
        <BackButton onClick={() => router.back()} className={styles.backBtn} variant="dark" />
        <h1>สภาพอากาศ</h1>
      </header>

      <main className={styles.content}>
        {loading ? (
          <div className={styles.status}>กำลังค้นหาตำแหน่งและดึงข้อมูลอากาศ...</div>
        ) : error ? (
          <div className={styles.status} style={{ color: "red" }}>{error}</div>
        ) : data && (
          <>
            {/* Hero Card */}
            <section className={styles.heroCard}>
              <div className={styles.heroHeader}>
                <MapPin size={16} />
                <span>{data.location}</span>
              </div>
              <div className={styles.mainInfo}>
                <img src={`https://openweathermap.org/img/wn/${data.icon}@4x.png`} alt="weather" />
                <div className={styles.tempGroup}>
                  <span className={styles.mainTemp}>{data.temp}°</span>
                  <p className={styles.description}>{data.description}</p>
                </div>
              </div>

              {/* Hourly Mini Row */}
              <div className={styles.hourlyRow}>
                {data.hourly.map((h, i) => (
                  <div key={i} className={styles.hourCell}>
                    <p>{h.label}</p>
                    <img src={`https://openweathermap.org/img/wn/${h.icon}.png`} alt="h-icon" />
                    <strong>{h.temp}°</strong>
                  </div>
                ))}
              </div>
            </section>

            {/* Metrics */}
            <section className={styles.metrics}>
              <div className={styles.metricCard}>
                <div className={styles.metricTitle}>
                  <Droplets size={18} color="#3498db" />
                  <h4>ความชื้น</h4>
                </div>
                <div className={styles.progressTrack}>
                  <div className={styles.progressValue} style={{ width: humidityWidth }} />
                </div>
                <p>{data.humidity}%</p>
              </div>

              <div className={styles.metricCard}>
                <div className={styles.metricTitle}>
                  <Wind size={18} color="#2ecc71" />
                  <h4>ความเร็วลม</h4>
                </div>
                <div className={styles.windValue}>
                  <strong>{data.windKmh}</strong> <span>กม./ชม.</span>
                </div>
              </div>
            </section>

            {/* 5-Day Forecast */}
            <h3 className={styles.sectionTitle}>พยากรณ์อากาศ 5 วัน</h3>
            <section className={styles.dailyList}>
              {data.daily.map((d, i) => (
                <div key={i} className={styles.dailyRow}>
                  <div className={styles.dailyLeft}>
                    <img src={`https://openweathermap.org/img/wn/${d.icon}.png`} alt="d-icon" />
                    <div>
                      <strong>{i === 0 ? "วันนี้" : d.day}</strong>
                      <p>{d.subtitle}</p>
                    </div>
                  </div>
                  <div className={styles.dailyTemp}>
                    {d.min}° / {d.max}°
                  </div>
                </div>
              ))}
            </section>
          </>
        )}
      </main>

      <BottomNav activePath="/weather" />
    </div>
  );
}
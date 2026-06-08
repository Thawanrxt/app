"use client";

import { useEffect, useState } from "react";

export default function WeatherBox() {
  const API_KEY = process.env.NEXT_PUBLIC_WEATHER_API;

  const [current, setCurrent] = useState<any>(null);
  const [forecast, setForecast] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const city = "Nonthaburi";

  useEffect(() => {
    const fetchWeather = async () => {
      try {
        // 🌤️ สภาพอากาศปัจจุบัน
        const currentRes = await fetch(
          `https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${API_KEY}&units=metric`
        );
        const currentData = await currentRes.json();

        // 📅 พยากรณ์ 5 วัน
        const forecastRes = await fetch(
          `https://api.openweathermap.org/data/2.5/forecast?q=${city}&appid=${API_KEY}&units=metric`
        );
        const forecastData = await forecastRes.json();

        setCurrent(currentData);

        // เอาเฉพาะวันละ 1 ค่า (ทุก 8 รายการ = 1 วัน)
        const daily = forecastData.list.filter(
          (_: any, index: number) => index % 8 === 0
        );

        setForecast(daily);
      } catch (error) {
        console.error("Error fetching weather:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchWeather();
  }, []);

  if (loading) return <p>กำลังโหลดข้อมูล...</p>;
  if (!current) return <p>ไม่สามารถโหลดข้อมูลได้</p>;

  return (
    <div style={{ padding: "20px" }}>
      {/* กล่องอากาศปัจจุบัน */}
      <div
        style={{
          background: "#2f9e44",
          color: "white",
          padding: "20px",
          borderRadius: "16px",
          marginBottom: "20px",
        }}
      >
        <h2>{current.name}, Thailand</h2>
        <h1 style={{ fontSize: "48px", margin: "10px 0" }}>
          {Math.round(current.main.temp)}°C
        </h1>
        <p>{current.weather[0].description}</p>
      </div>

      {/* ความชื้น / ลม */}
      <div
        style={{
          background: "#f1f3f5",
          padding: "15px",
          borderRadius: "12px",
          marginBottom: "20px",
        }}
      >
        <p>ความชื้น: {current.main.humidity}%</p>
        <p>ความเร็วลม: {current.wind.speed} km/h</p>
      </div>

      {/* พยากรณ์ล่วงหน้า */}
      <div>
        <h3>พยากรณ์ 5 วัน</h3>

        {forecast.map((day, index) => (
          <div
            key={index}
            style={{
              background: "#f8f9fa",
              padding: "10px",
              borderRadius: "10px",
              marginBottom: "10px",
              display: "flex",
              justifyContent: "space-between",
            }}
          >
            <span>
              {new Date(day.dt * 1000).toLocaleDateString("th-TH", {
                weekday: "short",
                day: "numeric",
                month: "short",
              })}
            </span>
            <span>{Math.round(day.main.temp)}°C</span>
            <span>{day.weather[0].description}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
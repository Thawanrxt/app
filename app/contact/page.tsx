"use client";

import { useRouter } from "next/navigation";
import { Home, CloudSun, Map, FileText, Settings } from "lucide-react";
import BackButton from "../components/BackButton";

export default function ContactPage() {
  const router = useRouter();

  return (
    <div className="app-wrapper">
      <div className="phone-frame">
        <div className="contact-page">

          {/* ===== Header ===== */}
          <div className="contact-header">
            <BackButton onClick={() => router.back()} />
            
            <h2>ติดต่อเจ้าหน้าที่</h2>
          </div>

          {/* ===== Content ===== */}
          <div className="contact-content">
            <div
              className="contact-card"
              onClick={() => window.open("https://line.me", "_blank")}
            >
              <img src="/line_icon.svg" alt="line" />
              <p>แอดไลน์เพื่อติดต่อเจ้าหน้าที่</p>
            </div>
          </div>

          {/* ===== Bottom Navigation ===== */}
          <div className="bottom-nav">
            <button onClick={() => router.push("/")}>
              <Home size={22} />
            </button>

            <button onClick={() => router.push("/weather")}>
              <CloudSun size={22} />
            </button>

            <button onClick={() => router.push("/map")}>
              <Map size={22} />
            </button>

            <button onClick={() => router.push("/report")}>
              <FileText size={22} />
            </button>

            <button onClick={() => router.push("/settings")}>
              <Settings size={22} />
            </button>
          </div>

        </div>
      </div>
    </div>
  );
}
